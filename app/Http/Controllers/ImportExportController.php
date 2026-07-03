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
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportExportController extends Controller
{
    private const TYPES = ['users', 'teams', 'leave-requests', 'ot-requests'];

    public function index()
    {
        if (!auth()->user()->can('module import_export')) abort(403);
        return view('import-export.index');
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
                ['name', 'email', 'password', 'position', 'grade', 'leave_balance', 'roles'],
                ['Nguyen Van A', 'vana@company.com', 'password123', 'Developer', 'Junior', '8', 'Staff|Manager'],
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

        $results = [
            'created' => $import->created,
            'updated' => $import->updated ?? 0,
            'skipped' => $import->skipped,
            'errors'  => $import->errors,
        ];

        return back()->with('import_results', $results)->with('import_type', $type);
    }
}
