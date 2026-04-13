@extends('layouts.help')

@section('title', 'Notifications — A6-ERP User Guide')
@section('breadcrumb', 'Notifications')

@section('content')

<section id="notifications">
    <h2>Notifications</h2>

    <h3>Bell Dropdown</h3>
    <p>Click the <strong>🔔 bell icon</strong> in the top bar to see your 5 most recent unread notifications. Each shows the sender's avatar, a title, a short description, and a relative timestamp. Click any notification to jump to the relevant page.</p>
    <p>Click <strong>View All</strong> to open the full notifications page.</p>

    <img class="guide-img" src="/guide/images/10-4-leave-reject-noti.png">

    <h3>Notifications Page — <code>/notifications</code></h3>
    <p>All notifications are listed here. Unread entries are highlighted with an indigo background. Opening this page marks all unread notifications as read.</p>

    <img class="guide-img" src="/guide/images/10-5-noti.png">

    <h3>When Are Notifications Sent?</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Event</th><th>Who is Notified</th></tr></thead>
            <tbody>
                <tr><td>Leave request <strong>approved</strong></td><td>The requester</td></tr>
                <tr><td>Leave request <strong>rejected</strong></td><td>The requester</td></tr>
                <tr><td>OT request <strong>approved</strong></td><td>The requester</td></tr>
                <tr><td>OT request <strong>rejected</strong></td><td>The requester</td></tr>
            </tbody>
        </table>
    </div>
</section>

@endsection