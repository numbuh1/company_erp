@extends('layouts.help')

@section('title', 'Roles — A6-ERP User Guide')
@section('breadcrumb', 'Roles')

@section('content')

<section id="roles">
    <h2>Roles &amp; Permissions</h2>
    <p class="section-intro">Roles bundle permissions together and are assigned to users to control what they can access.</p>
    <div class="callout info"><span class="callout-icon">🔑</span><div>Requires <code>Roles</code> module enabled to access this section.</div></div>

    <img class="guide-img" src="/guide/images/12-1-role-list.png">

    <h3>Managing Roles</h3>
    <div class="callout info"><span class="callout-icon">🔑</span><div>Requires <code>Create/Edit Roles</code> to create or edit.</div></div>
    <ol class="steps">
        <li>Go to <strong>Roles</strong> in the sidebar (Admin group).</li>
        <li>Click <strong>Create Role</strong>.</li>
        <li>Give the role a <strong>Name</strong>.</li>
        <li>Check the <strong>permissions</strong> to grant — grouped by module.</li>
        <li>Click <strong>Save</strong>.</li>
    </ol>

    <img class="guide-img" src="/guide/images/12-2-role-edit.png">

    <h3>Assigning Roles to Users</h3>
    <p>Roles are assigned from the <strong>User edit form</strong> — look for the Roles section when editing a user.</p>

    <img class="guide-img" src="/guide/images/09-2-user-edit.png">

    <h3>Deleting Roles</h3>
    <div class="callout warning">
        <span class="callout-icon">⚠️</span>
        <div>Requires <code>Delete Roles</code>. Deleting a role removes it from <strong>all users</strong> currently assigned to it.</div>
    </div>
</section>

@endsection