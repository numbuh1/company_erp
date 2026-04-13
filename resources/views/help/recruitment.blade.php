@extends('layouts.help')

@section('title', 'Recruitment — A6-ERP User Guide')
@section('breadcrumb', 'Recruitment')

@section('content')

<section id="recruitement">
    <h2>Recruitment</h2>

    <h3>Viewing Recruitment Positions</h3>
    <p>The index page lists all recruitment positions and their status</p>

    <img class="guide-img" src="/guide/images/16-1-recruitment-list.png">

    <h3>Editing Position</h3>
    <p>User can create/edit position, upload or edit the Job Details, and assign user to manage the recruiting / interview.</p>

    <img class="guide-img" src="/guide/images/16-1-recruitment-edit.png">

    <br>

    <p>User can edit the desired skills for the position.</p>
    <div class="callout info">User can add / edit skills in <a href="{{ route('help.ot-requests') }}"><span class="icon">📚</span> Skill</a> module</div>

    <img class="guide-img" src="/guide/images/16-1-recruitment-edit-skill.png">

    <h3>Viewing Applicant List</h3>
    <p>User can check the list of Applicants by going to Position details page.</p>

    <img class="guide-img" src="/guide/images/16-2-applicant-list.png">

    <p>User can switch to Kanban view to quick change the Applicants' status.</p>

    <img class="guide-img" src="/guide/images/16-2-applicant-kanban.png">

    <h3>Creating an Applicant</h3>
    <p>User can create a new Applicant for the position, upload their CV and provide their details.</p>

    <img class="guide-img" src="/guide/images/16-2-applicant-edit.png">

    <h3>Viewing an Applicant</h3>
    <p>User can view an Applicant's info, download their CV.</p>

    <img class="guide-img" src="/guide/images/16-2-applicant-view.png">

    <p>User can also quick book an Interview Meeting Event. The info and attendees (current user + assigned users) will be auto-filled.</p>
    <div class="callout info">The events will show up in <a href="{{ route('help.ot-requests') }}"><span class="icon">📅</span> Calendar</a> module</div>

    <img class="guide-img" src="/guide/images/16-2-applicant-view-book.png">

    <h3>Plan for future</h3>
    <ul>
        <li>Applicant Import from Hiring Websites</li>
        <li>Email Templates based on Applicant Data</li>
    </ul>
</section>

@endsection