@extends('layouts.help')

@section('title', 'Skills — A6-ERP User Guide')
@section('breadcrumb', 'Skills')

@section('content')

<section id="skills">
    <h2>Skills</h2>
    <p class="section-intro">Skill library configuration for users and recruitment applicants.</p>

    <h3>Index Page</h3>
    <p>Lists all skills and their categories.</p>

    <img class="guide-img" src="/guide/images/17-skills-list.png">

    <h3>Creating / Editing a Skill</h3>
    <div class="callout info"><span class="callout-icon">🔑</span><div>Requires <code>Create/Edit Skills</code> permission.</div></div>
    <ol class="steps">
        <li>Click <strong>New Skill</strong> or the Edit button.</li>
        <li>Enter the <strong>Skill Name</strong> and <strong>Category</strong>.</li>
        <li>Click <strong>Save</strong>.</li>
    </ol>

    <img class="guide-img" src="/guide/images/17-skills-edit.png">

    <div class="callout tip">
        <span class="callout-icon">💡</span>
        <div>The Skills will show up on <a href="{{ route('help.ot-requests') }}"><span class="icon">👤</span> User</a> data and <a href="{{ route('help.ot-requests') }}"><span class="icon">🤝</span> Recruitment</a> Applicant data</div>
    </div>
</section>

@endsection