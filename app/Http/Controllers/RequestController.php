<?php

namespace App\Http\Controllers;

use App\Exports\RequestsExport;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->canAny(['module leaves', 'module ot'])) {
            abort(403);
        }

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo   = $request->input('date_to',   now()->endOfMonth()->format('Y-m-d'));
        $status   = $request->input('status', 'all');
        $type     = $request->input('type',   'all');

        $rows = collect();

        // ── Leave ──────────────────────────────────────────────────
        if ($user->can('module leaves') && in_array($type, ['all', 'leave'])) {
            $q = LeaveRequest::with('user', 'approver');
            $this->applyScope($q, 'leave', $user);
            $this->applyDateFilter($q, $dateFrom, $dateTo);
            $this->applyStatusFilter($q, $status);
            $rows = $rows->concat($q->get()->map(fn($r) => ['_type' => 'leave', 'record' => $r]));
        }

        // ── OT ─────────────────────────────────────────────────────
        if ($user->can('module ot') && in_array($type, ['all', 'ot'])) {
            $q = OvertimeRequest::with('user', 'approver', 'project', 'task');
            $this->applyScope($q, 'ot', $user);
            $this->applyDateFilter($q, $dateFrom, $dateTo);
            $this->applyStatusFilter($q, $status);
            $rows = $rows->concat($q->get()->map(fn($r) => ['_type' => 'ot', 'record' => $r]));
        }

        // ── Sort + manual paginate ─────────────────────────────────
        $sorted  = $rows->sortByDesc(fn($r) => $r['record']->start_at)->values();
        $perPage = 20;
        $page    = (int) $request->input('page', 1);
        $items   = new LengthAwarePaginator(
            $sorted->slice(($page - 1) * $perPage, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('requests.index', compact('items', 'dateFrom', 'dateTo', 'status', 'type'));
    }

    public function exportPage(Request $request)
    {
        return view('requests.export', [
            'dateFrom' => $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
            'dateTo'   => $request->input('date_to',   now()->endOfMonth()->format('Y-m-d')),
            'status'   => $request->input('status', 'all'),
            'type'     => $request->input('type',   'all'),
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
            'status'    => 'nullable|string',
            'type'      => 'nullable|string|in:all,leave,ot',
        ]);

        $filename = 'requests_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new RequestsExport(
            type:     $request->input('type', 'all'),
            dateFrom: $request->input('date_from'),
            dateTo:   $request->input('date_to'),
            status:   $request->input('status', 'all'),
            user:     auth()->user(),
        ), $filename);
    }

    private function applyScope($query, string $module, $user): void
    {
        $allPerm  = $module === 'leave' ? 'view all leaves' : 'view all ot';
        $teamPerm = $module === 'leave' ? 'view team leaves' : 'view team ot';
        if ($user->can($allPerm)) {
            // no scope
        } elseif ($user->can($teamPerm)) {
            $query->whereIn('user_id', $user->teamMembers()->pluck('id'));
        } else {
            $query->where('user_id', $user->id);
        }
    }

    private function applyDateFilter($query, ?string $from, ?string $to): void
    {
        if ($from) $query->whereDate('start_at', '>=', $from);
        if ($to)   $query->whereDate('start_at', '<=', $to);
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status && $status !== 'all') $query->where('status', $status);
    }
}