@extends('layouts.help')

@section('title', 'Timesheets — A6-ERP User Guide')
@section('breadcrumb', 'Timesheets')

@section('content')

<section id="timesheet">
    <h2>Timesheet</h2>
    <p class="section-intro">Two views for tracking time — a filterable log list and a weekly grid.</p>

    <h3>Log List — <code>/time-logs</code></h3>
    <p>A table of all time entries. Available filters: User, Team, Date, Project, Task.</p>
    <p>Each row shows: Date · User (if visible) · Context (Task / Project / Other) · Description · Time Spent formatted as <code>Xh Ym</code>.</p>

    <img class="guide-img" src="/guide/images/07-1-timelog-list.png">

    <h3>Logging Time</h3>
    <p>Click <strong>Log Time</strong> on the time-log index, project detail, or task detail pages.</p>

    <img class="guide-img" src="/guide/images/07-3-timelog-edit.png">

    <div class="table-wrap">
        <table>
            <thead><tr><th>Field</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><strong>Date</strong></td><td>Date worked (defaults to today)</td></tr>
                <tr><td><strong>Project</strong></td><td>Optional — link to a project</td></tr>
                <tr><td><strong>Task</strong></td><td>Optional — filtered to the selected project's tasks</td></tr>
                <tr><td><strong>Description</strong></td><td>What you worked on</td></tr>
                <tr><td><strong>Time Spent</strong></td><td>Decimal hours (e.g. <code>1.5</code> = 1h 30m). Min: 0.25 · Max: 24</td></tr>
            </tbody>
        </table>
    </div>
    <div class="callout tip">
        <span class="callout-icon">⚡</span>
        <div><strong>Quick-time buttons</strong> (30m, 1h, 2h, 4h, 8h) fill the time field instantly — no need to type.</div>
    </div>

    <h3>Weekly View — <code>/timesheets/weekly</code></h3>
    <p>A grid showing the current week. Days are columns; tasks/projects/other are rows.</p>
    <ul>
        <li>Use <strong>Prev / Next</strong> to navigate weeks, or click <strong>This Week</strong> to return.</li>
        <li><strong>Hover</strong> over any time cell to see the log description in a tooltip.</li>
        <li>The <strong>Today</strong> column is highlighted.</li>
        <li><strong>Day totals</strong> appear in the bottom row; the <strong>week total</strong> in the bottom-right.</li>
        <li>Click a cell to view that log entry (or a filtered list if multiple entries exist that day).</li>
    </ul>

    <img class="guide-img" src="/guide/images/07-2-timelog-weekly.png">

    <div class="callout info">
        <span class="callout-icon">ℹ️</span>
        <div>With team/all timesheet permissions, use the <strong>User</strong> or <strong>Team</strong> filter to view other users' weekly logs.</div>
    </div>
</section>

@endsection