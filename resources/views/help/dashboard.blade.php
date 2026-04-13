@extends('layouts.help')

@section('title', 'Dashboard — A6-ERP User Guide')
@section('breadcrumb', 'Dashboard')

@section('content')

<section id="dashboard">
    <h2>Dashboard</h2>
    <p class="section-intro">A quick overview of what matters most — your time, leave, upcoming deadlines, and pending actions.</p>

    <img class="guide-img" src="/guide/images/03-dashboard.png">

    <h3>Stats Bar</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Card</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><strong>Leave Balance</strong></td><td>Your remaining leave hours</td></tr>
                <tr><td><strong>This Week</strong></td><td>Total hours you have logged this week</td></tr>
                <tr><td><strong>This Month</strong></td><td>Total hours you have logged this month</td></tr>
                <tr><td><strong>OT This Month</strong></td><td>Your approved overtime hours this month</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Announcements Panel <em>(left)</em></h3>
    <ul>
        <li>Shows the <strong>latest announcement</strong> with full rendered content.</li>
        <li>Below it, a list of the <strong>5 previous announcements</strong> with links to read each one.</li>
    </ul>

    <h3>Notifications Panel <em>(right)</em></h3>
    <p>Three sections stacked vertically:</p>

    <p><strong>Pending Approvals</strong> — visible to approvers only. Lists pending Leave and OT requests waiting for your review, with direct links to action them.</p>

    <p><strong>Upcoming Approved Leaves</strong> — approved leaves happening in the next <strong>14 days</strong>. Shows your own leaves, or your team's/all users' if you have broader access.</p>

    <p><strong>Tasks Nearing Deadline</strong> — In Progress tasks with an expected end date <strong>within 5 days</strong>. Click a task name to view its details.</p>
</section>

@endsection