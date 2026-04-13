@extends('layouts.help')

@section('title', 'Getting Started — A6-ERP User Guide')
@section('breadcrumb', 'Getting Started')

@section('content')

<section id="getting-started">
    <h2>Getting Started</h2>

    <img class="guide-img" src="/guide/images/01-login.png">

    <h3>Logging In</h3>
    <p>Navigate to the application URL and sign in with your <strong>email and password</strong>. All features require authentication — you will be redirected to the login page if you are not signed in.</p>

    <h3>Access Control</h3>
    <p>What you can see and do depends on the <strong>permissions assigned to your role</strong>. If you try to access a page you do not have permission for, you will see a <code>403 Forbidden</code> error.</p>

    <div class="callout info">
        <span class="callout-icon">ℹ️</span>
        <div>Contact your administrator to request additional access. Permissions are managed through the <strong>Roles</strong> module.</div>
    </div>
</section>

@endsection