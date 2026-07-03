<?php

namespace App\Http\Controllers;

use App\Exports\ImportTemplateExport;
use App\Exports\LeaveRequestsExport;
use App\Exports\OvertimeRequestsExport;
use App\Exports\TeamsExport;
use App\Exports\UsersExport;
use App\Imports\LeaveRequestsImport;
use App\Imports\OvertimeRequestsImport;
use App\Imports\TeamsImport;
use App\Imports\UsersImport;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    private const TYPES = ['users', 'teams', 'leave-requests', 'ot-requests'];

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
                ['name', 'leaders', 'members'],
                ['Dev Team', '1:Nguyen Van A', '1:Nguyen Van A (Leader)|2:Tran Van B'],
            ],
            'leave-requests' => [
                ['user', 'type', 'start_at', 'end_at', 'hours', 'description', 'status', 'approved_by', 'reject_reason'],
                ['Nguyen Van A', 'annual', '01/06/2026 09:00', '01/06/2026 17:00', '8', 'Personal leave', 'pending', '', ''],
            ],
            'ot-requests' => [
                ['user', 'type', 'start_at', 'end_at', 'hours', 'project', 'task', 'description', 'status', 'approved_by', 'reject_reason'],
                ['Nguyen Van A', 'OT x1.5', '01/06/2026 18:00', '01/06/2026 20:00', '2', 'Project Alpha', '', 'Extra work', 'pending', '', ''],
            ],
            default => abort(404),
        };

        $filename = 'template_import_' . $type . '.xlsx';
        return Excel::download(new ImportTemplateExport($headers, $sample), $filename);
    }

    public function import(Request $request, string $type)
    {
        if (!auth()->user()->can('module import_export')) abort(403);

        if (!in_array($type, self::TYPES, true)) abort(404);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = match($type) {
            'users'          => new UsersImport,
            'teams'          => new TeamsImport,
            'leave-requests' => new LeaveRequestsImport,
            'ot-requests'    => new OvertimeRequestsImport,
        };

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        // Persist the log
        $log = ImportLog::create([
            'user_id'       => auth()->id(),
            'type'          => $type,
            'filename'      => $request->file('file')->getClientOriginalName(),
            'created_count' => $import->created,
            'updated_count' => $import->updated ?? 0,
            'skipped_count' => $import->skipped,
            'rows'          => $import->rows,
        ]);

        return redirect()
            ->route('import-export.log.show', $log)
            ->with('import_just_done', true);
    }

    public function logShow(ImportLog $log)
    {
        if (!auth()->user()->can('module import_export')) abort(403);
        $log->load('user');
        return view('import-export.log-show', compact('log'));
    }
}
