@extends('layouts.help')

@section('title', 'Teams — A6-ERP User Guide')
@section('breadcrumb', 'Teams')

@section('content')

<section id="teams">
    <h2>Teams</h2>
    <p class="section-intro">Teams group users together. They are used for scoping leave/OT approvals, timesheet visibility, and project assignment.</p>

    <h3>Index Page</h3>
    <p>Lists all teams <em>(or only your own teams, depending on your permission)</em> with member count, leader names, and action buttons.</p>

    <img class="guide-img" src="/guide/images/08-1-team-list.png">

    <h3>Team Detail Page</h3>
    <p>Shows two panels side by side:</p>
    <ul>
        <li><strong>Leaders</strong> — users who lead the team</li>
        <li><strong>Members</strong> — regular members</li>
    </ul>

    <img class="guide-img" src="/guide/images/08-2-team-view.png">

    <h3>Creating / Editing a Team</h3>
    <div class="callout info"><span class="callout-icon">🔑</span><div>Requires <code>Create/Edit Teams</code>.</div></div>
    <ol class="steps">
        <li>Click <strong>Create Team</strong> or the Edit button.</li>
        <li>Enter the <strong>Team Name</strong>.</li>
        <li>Check the users you want as <strong>members</strong>.</li>
        <li>Among the checked users, tick the <strong>Leader</strong> checkbox for those who should lead.</li>
        <li>Click <strong>Save</strong>.</li>
    </ol>

    <img class="guide-img" src="/guide/images/08-3-team-edit.png">

    <div class="callout tip">
        <span class="callout-icon">💡</span>
        <div>Leaders can approve leave and OT requests from their team members, and can view their team's timesheets.</div>
    </div>
</section>

@endsection