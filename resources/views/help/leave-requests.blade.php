@extends('layouts.help')

@section('title', 'Leave Requests — A6-ERP User Guide')
@section('breadcrumb', 'Leave Requests')

@section('content')

<section id="leave-requests">
    <h2>Leave Requests</h2>
    <p class="section-intro">Request time off. Leave is measured in <strong>hours</strong> and deducted from your leave balance upon approval.</p>

    <img class="guide-img" src="/guide/images/10-1-leave-list.png">

    <h3>Submitting a Request</h3>
    <ol class="steps">
        <li>Click <strong>New Request</strong>.</li>
        <li>Select the <strong>Type</strong> (e.g. Annual, Sick, Emergency).</li>
        <li>Set the <strong>Start</strong> and <strong>End</strong> date/time.</li>
        <li>Enter the <strong>Hours</strong> being consumed.</li>
        <li>Write a <strong>Description</strong> (reason for the leave).</li>
        <li>Click <strong>Submit</strong>.</li>
    </ol>

    <img class="guide-img" src="/guide/images/10-2-leave-edit.png">

    <h3>Request Statuses</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
            <tbody>
                <tr><td><span class="badge badge-amber">Pending</span></td><td>Awaiting approval</td></tr>
                <tr><td><span class="badge badge-green">Approved</span></td><td>Approved — leave balance deducted</td></tr>
                <tr><td><span class="badge badge-red">Rejected</span></td><td>Not approved — a rejection reason is provided</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Approving / Rejecting <em>(approvers)</em></h3>
    <p>Users with <code>Approve Team Leaves</code> or <code>Approve All Leaves</code> see <strong>Approve</strong> and <strong>Reject</strong> buttons on pending requests.</p>
    <ul>
        <li><strong>Approve</strong> — sets status to Approved and notifies the requester.</li>
        <li><strong>Reject</strong> — prompts for a rejection reason, then notifies the requester.</li>
    </ul>

    <img class="guide-img" src="/guide/images/10-3-leave-reject.png">

    <div class="callout tip">
        <span class="callout-icon">🔔</span>
        <div>The requester receives a <strong>bell notification</strong> the moment their request is approved or rejected.</div>
    </div>

    <img class="guide-img" src="/guide/images/10-4-leave-reject-noti.png">
</section>

@endsection