@extends('layouts.help')

@section('title', 'Tasks — A6-ERP User Guide')
@section('breadcrumb', 'Tasks')

@section('content')

<section id="tasks">
    <h2>Tasks</h2>
    <p class="section-intro">Units of work that can be standalone or linked to a project. Each task can have multiple assignees and a progress percentage.</p>

    <h3>Task ID Format</h3>
    <p>Every task has a unique identifier: <code>TK-{number}</code> (e.g. <code>TK-7</code>).</p>

    <img class="guide-img" src="/guide/images/06-1-task-list.png">

    <h3>Index Page Columns</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Column</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><strong>ID</strong></td><td>Clickable <code>TK-{n}</code> link</td></tr>
                <tr><td><strong>Name</strong></td><td>Task name</td></tr>
                <tr><td><strong>Status</strong></td><td>Badge: Not Started / In Progress / Done</td></tr>
                <tr><td><strong>Project</strong></td><td>Linked project's <code>PJ-{n}</code> (if any)</td></tr>
                <tr><td><strong>Assignees</strong></td><td>Users assigned to the task</td></tr>
                <tr><td><strong>Progress</strong></td><td>Visual progress bar (0–100%)</td></tr>
                <tr><td><strong>Dates</strong></td><td>Start and expected end date</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Task Detail Page</h3>

    <img class="guide-img" src="/guide/images/06-2-task-show.png">

    <ul>
        <li>Task ID badge, name, status badge</li>
        <li>Linked project (if any)</li>
        <li>Description, progress bar</li>
        <li>Start, expected end, and actual end dates</li>
        <li><strong>Assignees</strong> — each assignee is shown with their avatar and current attendance status; click their name to go to their profile</li>
        <li><strong>Activity Log</strong> — all changes</li>
        <li><strong>Edit</strong> and <strong>Log Time</strong> buttons <em>(permission-gated)</em></li>
    </ul>

    <h3>Statuses</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
            <tbody>
                <tr><td><span class="badge badge-gray">Not Started</span></td><td>Work has not begun</td></tr>
                <tr><td><span class="badge badge-blue">In Progress</span></td><td>Currently being worked on</td></tr>
                <tr><td><span class="badge badge-green">Done</span></td><td>Completed</td></tr>
            </tbody>
        </table>
    </div>        
</section>

@endsection