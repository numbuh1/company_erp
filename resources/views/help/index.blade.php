@extends('layouts.help')

@section('title', 'A6 ERP - User Guide')
@section('breadcrumb', 'Index')

@section('content')
    <!-- Hero -->
    <div class="hero">
        <h1>A6-ERP User Guide</h1>
        <p>Everything you need to know to use the platform — from logging time to managing leave requests.</p>
    </div>

    <!-- ── Module Overview ── -->
    <div class="feature-grid">
        <div class="feature-card">
            <div class="fc-icon">📊</div>
            <strong>Dashboard</strong>
            <span>Your daily overview</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">📢</div>
            <strong>Announcements</strong>
            <span>Company-wide posts</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">📁</div>
            <strong>Projects</strong>
            <span>Manage work & files</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">✅</div>
            <strong>Tasks</strong>
            <span>Track individual work</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">⏱️</div>
            <strong>Timesheet</strong>
            <span>Log & review hours</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">👥</div>
            <strong>Teams</strong>
            <span>Organise people</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">👤</div>
            <strong>Users</strong>
            <span>Accounts & profiles</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">🏖️</div>
            <strong>Leave</strong>
            <span>Request time off</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">🕐</div>
            <strong>Overtime</strong>
            <span>Log extra hours</span>
        </div>
        <div class="feature-card">
            <div class="fc-icon">🔑</div>
            <strong>Roles</strong>
            <span>Access control</span>
        </div>
        <div class="feature-card">
            <span class="badge-new">New</span>
            <div class="fc-icon">🏢</div>
            <strong>Attendance</strong>
            <span>Work Check In</span>
        </div>
        <div class="feature-card">
            <span class="badge-new">New</span>
            <div class="fc-icon">📅</div>
            <strong>Calendar</strong>
            <span>Meeting events</span>
        </div>
        <div class="feature-card">
            <span class="badge-new">New</span>
            <div class="fc-icon">📚</div>
            <strong>Skills</strong>
            <span>User & Applicant's Skill</span>
        </div>
        <div class="feature-card">
            <span class="badge-new">New</span>
            <div class="fc-icon">🤝</div>
            <strong>Recruitment</strong>
            <span>HR Recruiment System</span>
        </div>
    </div>
@endsection