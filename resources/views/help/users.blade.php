@extends('layouts.help')

@section('title', 'Users — A6-ERP User Guide')
@section('breadcrumb', 'Users')

@section('content')

<section id="users">
    <h2>Users</h2>
    <p class="section-intro">Manage user accounts, roles, and leave balances.</p>

    <img class="guide-img" src="/guide/images/09-1-user-list.png">

    <h3>User Profile</h3>
    <p>Every user has a profile page showing:</p>
    <ul>
        <li>Profile picture (or initial avatar), name, position, email</li>
        <li><strong>Roles</strong> assigned as badges</li>
        <li><strong>Teams</strong> — with Leader / Member label</li>            
        <li><strong>Leave Balance</strong> in hours, with a link to the full change history <strong>(private info)</strong></li>
        <li><strong>Leave Requests</strong> — a summary table of recent requests <strong>(private info)</strong></li>
    </ul>
    <div class="callout info">
        <span class="callout-icon">ℹ️</span>
        <div>User can see their own company private info. Other user without <code>Edit All User Profile</code> permission can't.</div>
    </div>

    <img class="guide-img" src="/guide/images/09-3-user-show.png">

    <h3>Editing Your Own Profile</h3>
    <p>Click <strong>Profile</strong> in the top-right profile menu to edit your own name, email, password, position, and profile picture.</p>
    <div class="callout info">
        <span class="callout-icon">ℹ️</span>
        <div>Only users with <code>Edit All User Profile</code> can edit other user's company private info.</div>
    </div>

    <img class="guide-img" src="/guide/images/09-2-user-edit.png">

    <h3>Managing Users <em>(admin)</em></h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Action</th><th>Required Permission</th></tr></thead>
            <tbody>
                <tr><td>Create a new user account</td><td><code>Create All User Profile</code></td></tr>
                <tr><td>Edit any user's details or roles</td><td><code>Edit All User Profile</code></td></tr>
                <tr><td>Reset another user's password</td><td><code>Edit All User Profile</code></td></tr>
                <tr><td>Adjust a user's leave balance</td><td><code>Edit All Leaves Balance</code></td></tr>
                <tr><td>Delete a user account</td><td><code>Delete All User Profile</code></td></tr>
            </tbody>
        </table>
    </div>        

    <h3>Leave Balance History</h3>
    <p>Click the <strong>Leave Balance History</strong> link on a user's profile to see a paginated log of all adjustments — change amount, balance after, reason, and who made the change.</p>

    <img class="guide-img" src="/guide/images/09-4-user-leave-balance.png">
</section>

@endsection