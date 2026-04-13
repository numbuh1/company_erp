@extends('layouts.help')

@section('title', 'Navigation — A6-ERP User Guide')
@section('breadcrumb', 'Navigation')

@section('content')

<section id="navigation">
    <h2>Navigation</h2>

    <img class="guide-img" src="/guide/images/02-layout.png">

    <h3>Top Bar</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Element</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><strong>Logo / App Name</strong></td><td>Click to go to the Dashboard</td></tr>
                <tr><td><strong>Dark Mode Toggle</strong></td><td>Sun/Moon icon — switches between light and dark theme (saved automatically)</td></tr>
                <tr><td><strong>Bell Icon</strong></td><td>Notifications with unread count badge; click to preview the latest 5 unread notifications</td></tr>
                <tr><td><strong>Profile Menu</strong></td><td>Your name — dropdown with Profile settings and Log Out</td></tr>
            </tbody>
        </table>
    </div>

    <h3>Left Sidebar</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Group</th><th>Links</th></tr></thead>
            <tbody>
                <tr><td>—</td><td>Dashboard</td></tr>
                <tr><td><strong>Work</strong></td><td>Announcements, Projects, Tasks, Timesheet</td></tr>
                <tr><td><strong>People</strong></td><td>Teams, Users</td></tr>
                <tr><td><strong>Requests</strong></td><td>Leave Requests, OT Requests</td></tr>
                <tr><td><strong>Admin</strong></td><td>Roles</td></tr>
            </tbody>
        </table>
    </div>

    <div class="callout info">
        <span class="callout-icon">ℹ️</span>
        <div>All links on the left sidebar can be shown/hidden based on user's roles, which is set up in the Roles module.</div>
    </div>
</section>

@endsection