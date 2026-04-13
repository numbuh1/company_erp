@extends('layouts.help')

@section('title', 'Attendance — A6-ERP User Guide')
@section('breadcrumb', 'Attendance')

@section('content')

<section id="attendance">
    <h2>Attendance</h2>
    <p class="section-intro">Check attendance for everyone in your team.</p>

    <img class="guide-img" src="/guide/images/14-attendance-list.png">

    <h3>Viewing Attendance</h3>
    <p>The index page lists all attendance for today.</p>
    <p>The list includes user's teammates, supervisors and subordinates.</p>
    <div class="callout info"><span class="callout-icon">🔑</span><div>User with <code>View All Attendance</code> permission can view all users in the system.</div></div>

    <br>

    <img class="guide-img" src="/guide/images/14-attendance-checkin.png">

    <h3>Check in</h3>
    <p>User can checkin as:</p>
    <ul>
        <li><strong>On Site</strong></li>
        <li><strong>Work From Home</strong> - User will be asked to provide reason. This will notify user's supervisor to approve or reject the request.</li>
    </ul>

    <img class="guide-img" src="/guide/images/14-attendance-wfh.png">
</section>

@endsection