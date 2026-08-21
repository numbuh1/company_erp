<?php

namespace App\Http\Controllers;

use App\Exports\ImportTemplateExport;
use App\Exports\LeaveRequestsExport;
use App\Exports\OvertimeRequestsExport;
use App\Exports\TeamsExport;
use App\Exports\UsersExport;
use App\Imports\LeaveBalanceImport;
use App\Imports\LeaveRequestsImport;
use App\Imports\OvertimeRequestsImport;
use App\Imports\RequestsImport;
use App\Imports\TeamsImport;
use App\Imports\UsersImport;
use App\Jobs\ProcessImport;
use App\Models\ImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    private const TYPES = ['users', 'teams', 'leave-requests', 'ot-requests', 'leave-balance', 'requests'];

    public function index()
    {
        if (!auth()->user()->can('module import_export')) abort(403);

        $logs = ImportLog::with('user')
            ->latest()
            ->paginate(20);

        return view('import-export.index', compact('logs'));
    }

    public function export(string $type)
    {
        if (!auth()->user()->can('module import_export')) abort(403);

        $filename = now()->format('Ymd_His') . '_' . $type . '.xlsx';

        return match($type) {
            'users'          => Excel::download(new UsersExport,            $filename),
            'teams'          => Excel::download(new TeamsExport,            $filename),
            'leave-requests' => Excel::download(new LeaveRequestsExport,    $filename),
            'ot-requests'    => Excel::download(new OvertimeRequestsExport, $filename),
            default          => abort(404),
        };
    }

    public function template(string $type)
    {
        if (!auth()->user()->can('module import_export')) abort(403);

        [$headers, $sample] = match($type) {
            'users' => [
                [
                    'name', 'email', 'password',
                    'full_name', 'contact_email', 'position', 'grade',
                    'phone_number', 'citizen_id', 'tax_code', 'social_insurance_id',
                    'home_address', 'birthday', 'contract_expiry',
                    'probation_start_date', 'probation_end_date',
                    'employment_status', 'is_active', 'wfh_without_approval',
                    'leave_balance', 'salary', 'salary_type',
                    'roles',
                ],
                [
                    'Nguyen Van A', 'vana@company.com', 'password123',
                    'Nguyễn Văn A', '', 'Developer', 'Junior',
                    '0901234567', '', '', '',
                    '', '01/01/1995', '31/12/2027',
                    '01/03/2026', '31/05/2026',
                    'active', '1', '0',
                    '8', '', '',
                    'Staff',
                ],
            ],
            'teams' => [
                ['user_email', 'team_name', 'is_leader'],
                ['vana@company.com', 'Dev Team', '1'],
            ],
            'leave-requests' => [
                ['user', 'type', 'start_at', 'end_at', 'hours', 'description', 'status', 'approved_by', 'reject_reason'],
                ['Nguyen Van A', 'annual', '01/06/2026 09:00', '01/06/2026 17:00', '8', 'Personal leave', 'pending', '', ''],
            ],
            'ot-requests' => [
                ['user', 'type', 'start_at', 'end_at', 'hours', 'project', 'task', 'description', 'status', 'approved_by', 'reject_reason'],
                ['Nguyen Van A', 'OT x1.5', '01/06/2026 18:00', '01/06/2026 20:00', '2', 'Project Alpha', '', 'Extra work', 'pending', '', ''],
            ],
            'leave-balance' => [
                ['user', 'action', 'hours', 'reason'],
                ['Nguyen Van A', 'set', '112', 'Annual reset'],
            ],
            'requests' => [
                ['category', 'user', 'type', 'start_at', 'end_at', 'hours', 'project', 'task', 'description', 'status', 'approved_by', 'reject_reason'],
                ['leave', 'Nguyen Van A', 'annual', '01/06/2026 09:00', '01/06/2026 17:00', '8', '', '', 'Personal leave', 'pending', '', ''],
            ],
            default => abort(404),
        };

        $filename = 'template_import_' . $type . '.xlsx';
        return Excel::download(new ImportTemplateExport($headers, $sample), $filename);
    }

    public function preview(Request $request, string $type): JsonResponse
    {
        if (!auth()->user()->can('module import_export')) abort(403);
        if (!in_array($type, self::TYPES, true)) abort(404);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $ext      = $request->file('file')->getClientOriginalExtension();
        $tempName = Str::uuid() . '.' . strtolower($ext);
        $tempPath = $request->file('file')->storeAs('import_temp', $tempName);

        $import = match($type) {
            'users'          => new UsersImport(dryRun: true),
            'teams'          => new TeamsImport(dryRun: true),
            'leave-requests' => new LeaveRequestsImport(dryRun: true),
            'ot-requests'    => new OvertimeRequestsImport(dryRun: true),
            'leave-balance'  => new LeaveBalanceImport(dryRun: true),
            'requests'       => new RequestsImport(dryRun: true),
        };

        try {
            Excel::import($import, Storage::path($tempPath));
        } catch (\Throwable $e) {
            Storage::delete($tempPath);
            return response()->json(['error' => 'Không thể đọc file: ' . $e->getMessage()], 422);
        }

        return response()->json([
            'temp_path'     => $tempPath,
            'filename'      => $request->file('file')->getClientOriginalName(),
            'created_count' => $import->created,
            'updated_count' => $import->updated ?? 0,
            'skipped_count' => $import->skipped,
            'rows'          => $import->rows,
        ]);
    }

    public function import(Request $request, string $type)
    {
        if (!auth()->user()->can('module import_export')) abort(403);
        if (!in_array($type, self::TYPES, true)) abort(404);

        $useTempFile = $request->filled('temp_path');

        if ($useTempFile) {
            $tempPath = $request->input('temp_path');
            if (!preg_match('/^import_temp\/[a-f0-9\-]+\.(xlsx|xls|csv)$/i', $tempPath)) {
                abort(422, 'Invalid temp_path.');
            }
            if (!Storage::exists($tempPath)) {
                return back()->with('import_error', 'File tạm không tìm thấy. Vui lòng tải lại file và thử lại.');
            }
            $originalName = $request->input('filename', basename($tempPath));
        } else {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);
            $ext          = $request->file('file')->getClientOriginalExtension();
            $tempName     = Str::uuid() . '.' . strtolower($ext);
            $tempPath     = $request->file('file')->storeAs('import_temp', $tempName);
            $originalName = $request->file('file')->getClientOriginalName();
        }

        $log = ImportLog::create([
            'user_id'       => auth()->id(),
            'type'          => $type,
            'filename'      => $originalName,
            'status'        => 'in_progress',
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'rows'          => [],
        ]);

        ProcessImport::dispatch($log->id, $type, $tempPath);

        return redirect()->route('import-export.progress', $log);
    }

    public function progressPage(ImportLog $log)
    {
        if (!auth()->user()->can('module import_export')) abort(403);
        $log->load('user');
        return view('import-export.progress', compact('log'));
    }

    public function progressData(ImportLog $log): JsonResponse
    {
        if (!auth()->user()->can('module import_export')) abort(403);

        $log->refresh();
        $total     = $log->total_rows     ?? 0;
        $processed = $log->processed_rows ?? 0;

        return response()->json([
            'status'         => $log->status,
            'total_rows'     => $total,
            'processed_rows' => $processed,
            'percentage'     => $total > 0
                ? min(100, (int) ($processed / $total * 100))
                : ($log->status === 'done' ? 100 : 0),
            'created_count'  => $log->created_count,
            'updated_count'  => $log->updated_count,
            'skipped_count'  => $log->skipped_count,
            'error_message'  => $log->error_message,
        ]);
    }

    public function logShow(ImportLog $log)
    {
        if (!auth()->user()->can('module import_export')) abort(403);
        $log->load('user');
        return view('import-export.log-show', compact('log'));
    }
}
