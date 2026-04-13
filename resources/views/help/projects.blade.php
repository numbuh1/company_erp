@extends('layouts.help')

@section('title', 'Projects — A6-ERP User Guide')
@section('breadcrumb', 'Projects')

@section('content')

<section id="projects">
    <h2>Projects</h2>
    <p class="section-intro">Track large bodies of work with assigned teams, members, files, and tasks.</p>

    <h3>Project ID Format</h3>
    <p>Every project has a unique identifier: <code>PJ-{number}</code> (e.g. <code>PJ-12</code>). This appears in list views and inside task/time-log references.</p>

    <img class="guide-img" src="/guide/images/05-1-project-list.png">

    <h3>Index Page Columns</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Column</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><strong>ID</strong></td><td>Clickable <code>PJ-{n}</code> link to the detail page</td></tr>
                <tr><td><strong>Name</strong></td><td>Project name</td></tr>
                <tr><td><strong>Status</strong></td><td><span class="badge badge-gray">Not Started</span> <span class="badge badge-blue">In Progress</span> <span class="badge badge-green">Done</span></td></tr>
                <tr><td><strong>Teams</strong></td><td>Assigned teams</td></tr>
                <tr><td><strong>Members</strong></td><td>Count of directly assigned users</td></tr>
                <tr><td><strong>Dates</strong></td><td>Start and expected end date</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Project Detail Page</h3>

    <img class="guide-img" src="/guide/images/05-2-project-show.png">

    <p><strong>Left Panel — Details</strong></p>
    <ul>
        <li>Status, description, start and end dates</li>
        <li>Assigned teams and members</li>
        <li><strong>Activity Log</strong> — full change history (who changed what and when)</li>
    </ul>

    <p><strong>Right Panel — Work</strong></p>
    <ul>
        <li><strong>File Explorer</strong> — manage files and folders</li>
        <li><strong>Tasks</strong> — all tasks in this project with status and progress</li>
        <li><strong>Log Time</strong> button — quick link to log time against this project</li>
    </ul>

    <h3>File Explorer</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Action</th><th>How</th></tr></thead>
            <tbody>
                <tr><td><strong>Navigate</strong></td><td>Click a folder name; use the breadcrumb to go back up</td></tr>
                <tr><td><strong>Upload</strong></td><td>Click Upload or drag a file into the explorer</td></tr>
                <tr><td><strong>Create Folder</strong></td><td>Click the New Folder button</td></tr>
                <tr><td><strong>Rename</strong></td><td>Click the rename icon next to any file or folder</td></tr>
                <tr><td><strong>Download</strong></td><td>Click the download icon to save locally</td></tr>
                <tr><td><strong>Delete</strong></td><td>Click the delete icon — this cannot be undone</td></tr>
            </tbody>
        </table>
    </div>

    <div class="callout info">
        <span class="callout-icon">🔑</span>
        <div>Users with  <code>Upload/Edit Own Files</code> permission can only upload and modify their own files and folders.<br>
        Users with  <code>Upload/Edit All Files & Folders</code> permission can modify everything regardless of who create them.</div>
    </div>

    <h3>Editing &amp; Deleting</h3>
    <ul>
        <li><strong>Create</strong> — available on the index card with <code>Create/Edit Projects</code> permission.</li>
        <li><strong>Edit</strong> — available on the index card and the show page with <code>Create/Edit Projects</code> permission.</li>
        <li><strong>Delete</strong> — requires <code>Delete Projects</code> permission.</li>
    </ul>

    <img class="guide-img" src="/guide/images/05-3-project-edit.png">
</section>

@endsection