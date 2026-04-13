@extends('layouts.help')

@section('title', 'OT Requests — A6-ERP User Guide')
@section('breadcrumb', 'OT Requests')

@section('content')

<section id="ot-requests">
    <h2>Overtime (OT) Requests</h2>
    <p class="section-intro">Log overtime hours worked outside regular hours. The approval flow mirrors Leave Requests.</p>

    <img class="guide-img" src="/guide/images/11-1-ot-list.png">

    <h3>Submitting an OT Request</h3>
    <ol class="steps">
        <li>Click <strong>New OT Request</strong>.</li>
        <li>Set the start time, end time, hours, type, and description.</li>
        <li>Click <strong>Submit</strong>.</li>
    </ol>

    <img class="guide-img" src="/guide/images/11-2-ot-edit.png">

    <h3>Approval Flow</h3>
    <p>Approvers with <code>Approve Team OT Requests</code> or <code>Approve All OT Requests</code> can approve or reject. The requester receives a notification either way.</p>

    <div class="callout info">
        <span class="callout-icon">ℹ️</span>
        <div><code>Approve Team OT Requests</code> only covers requests from team members in teams you <strong>lead</strong>. You will not see requests from teams you belong to but do not lead.</div>
    </div>
</section>

@endsection