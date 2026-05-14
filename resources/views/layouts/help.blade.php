<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'A6-ERP User Guide')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-width: 260px;
            --topbar-height: 56px;
            --color-bg: #f8f9fb;
            --color-surface: #ffffff;
            --color-sidebar-bg: #1e2130;
            --color-sidebar-text: #c9cfe4;
            --color-sidebar-active: #ffffff;
            --color-sidebar-active-bg: #4f6ef7;
            --color-sidebar-hover-bg: rgba(255,255,255,0.07);
            --color-sidebar-group: #6b7491;
            --color-primary: #4f6ef7;
            --color-text: #1a1d2e;
            --color-muted: #6b7280;
            --color-border: #e5e7eb;
            --color-code-bg: #f1f3f9;
            --color-info-bg: #eff6ff;
            --color-info-border: #bfdbfe;
            --color-info-text: #1e40af;
            --color-tip-bg: #f0fdf4;
            --color-tip-border: #bbf7d0;
            --color-tip-text: #166534;
            --color-perm-bg: #faf5ff;
            --color-perm-border: #e9d5ff;
            --color-perm-text: #6b21a8;
            --color-warn-bg: #fffbeb;
            --color-warn-border: #fde68a;
            --color-warn-text: #92400e;
            --radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--color-sidebar-bg);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            text-decoration: none;
        }

        .sidebar-logo-icon {
            width: 34px; height: 34px;
            background: var(--color-primary);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-logo-text {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
        }

        .sidebar-logo-sub {
            font-size: 11px;
            color: var(--color-sidebar-group);
            font-weight: 400;
        }

        .sidebar-nav {
            padding: 12px 0;
            flex: 1;
        }

        .sidebar-group-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-sidebar-group);
            padding: 14px 20px 4px;
        }

        .sidebar-text {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            font-size: 13.5px;
            color: var(--color-sidebar-text);
            text-decoration: none;
            border-radius: 0;
            transition: background 0.15s;
            margin-top: 10px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            font-size: 13.5px;
            color: var(--color-sidebar-text);
            text-decoration: none;
            border-radius: 0;
            transition: background 0.15s;
        }

        .sidebar-link:hover {
            background: var(--color-sidebar-hover-bg);
            color: #fff;
        }

        .sidebar-link.active {
            background: var(--color-sidebar-active-bg);
            color: var(--color-sidebar-active);
            font-weight: 600;
        }

        .sidebar-link .icon { font-size: 15px; width: 20px; text-align: center; }

        /* ── Main ── */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            position: sticky; top: 0;
            height: var(--topbar-height);
            background: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            display: flex; align-items: center;
            padding: 0 32px;
            gap: 12px;
            z-index: 50;
            box-shadow: var(--shadow);
        }

        .topbar-breadcrumb {
            font-size: 13px;
            color: var(--color-muted);
            display: flex; align-items: center; gap: 6px;
        }

        .topbar-breadcrumb a { color: var(--color-muted); text-decoration: none; }
        .topbar-breadcrumb a:hover { color: var(--color-primary); }
        .topbar-breadcrumb .sep { color: var(--color-border); }
        .topbar-breadcrumb .current { color: var(--color-text); font-weight: 500; }

        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }

        .topbar-badge {
            background: var(--color-primary);
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .content {
            flex: 1;
            padding: 40px 48px;
            max-width: 900px;
        }

        @yield('content-styles')
    </style>

    <style>
        /* ── Page header ── */
        .page-header {
            margin-bottom: 36px;
        }

        .page-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px;
            background: var(--color-primary);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            border-radius: 50%;
            margin-bottom: 14px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--color-text);
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .page-header p.lead {
            font-size: 15px;
            color: var(--color-muted);
        }

        .divider {
            border: none;
            border-top: 1px solid var(--color-border);
            margin: 32px 0;
        }

        /* ── Screenshot ── */
        .screenshot {
            width: 100%;
            border-radius: var(--radius);
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            margin: 20px 0 28px;
            display: block;
        }

        /* ── Section ── */
        .section { margin-bottom: 40px; }

        .section h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section h2::before {
            content: '';
            display: inline-block;
            width: 4px; height: 20px;
            background: var(--color-primary);
            border-radius: 2px;
        }

        .section p {
            font-size: 14.5px;
            color: #374151;
            line-height: 1.75;
        }

        /* ── Callout ── */
        .callout {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius);
            font-size: 13.5px;
            line-height: 1.65;
            margin-top: 20px;
        }

        .callout-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .callout.info {
            background: var(--color-info-bg);
            border: 1px solid var(--color-info-border);
            color: var(--color-info-text);
        }

        .callout.tip {
            background: var(--color-tip-bg);
            border: 1px solid var(--color-tip-border);
            color: var(--color-tip-text);
        }

        .callout.perm {
            background: var(--color-perm-bg);
            border: 1px solid var(--color-perm-border);
            color: var(--color-perm-text);
        }

        .callout strong { font-weight: 600; }

        /* ── Inline code ── */
        code {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 12.5px;
            background: var(--color-code-bg);
            color: #be185d;
            padding: 1px 5px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }

        /* ── Step list ── */
        .step-list {
            list-style: none;
            counter-reset: steps;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 14px;
        }

        .step-list li {
            counter-increment: steps;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
        }

        .step-list li::before {
            content: counter(steps);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px; height: 24px;
            background: var(--color-primary);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* ── Cards ── */
        .card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 20px;
        }

        .card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: var(--shadow);
        }

        .card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
        }

        .card-body {
            font-size: 13px;
            color: var(--color-muted);
            line-height: 1.55;
        }

        .card-icon { font-size: 22px; margin-bottom: 10px; }
    </style>
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 260px;
            --accent:    #4f46e5;
            --accent-lt: #eef2ff;
            --text:      #1e293b;
            --muted:     #64748b;
            --border:    #e2e8f0;
            --bg:        #f8fafc;
            --white:     #ffffff;
            --green:     #16a34a;
            --green-lt:  #dcfce7;
            --blue:      #1d4ed8;
            --blue-lt:   #dbeafe;
            --amber:     #b45309;
            --amber-lt:  #fef3c7;
            --red:       #dc2626;
            --red-lt:    #fee2e2;
            --radius:    8px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 15px;
            line-height: 1.7;
            color: var(--text);
            background: var(--bg);
            display: flex;
        }

        /* ── Sidebar ── */
        #toc {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--white);
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 24px 0 40px;
            z-index: 100;
        }

        #toc .toc-brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 12px;
        }

        #toc .toc-brand span {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            margin-bottom: 4px;
        }

        #toc .toc-brand strong {
            font-size: 16px;
            color: var(--text);
        }

        #toc ul { list-style: none; }

        #toc > ul > li > a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            transition: color .15s, background .15s;
            border-left: 3px solid transparent;
        }

        #toc > ul > li > a:hover,
        #toc > ul > li > a.active {
            color: var(--accent);
            background: var(--accent-lt);
            border-left-color: var(--accent);
        }

        #toc > ul > li > a .icon {
            width: 18px;
            text-align: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        /* ── Main Content ── */
        #content {
            margin-left: var(--sidebar-w);
            flex: 1;
            min-width: 0;
            padding: 48px 56px 80px;
            max-width: 900px;
        }

        /* ── Typography ── */
        h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .subtitle {
            color: var(--muted);
            font-size: 15px;
            margin-bottom: 40px;
        }

        h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            margin: 56px 0 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            scroll-margin-top: 24px;
        }

        h2 .num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: var(--accent);
            color: #fff;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            margin-right: 10px;
            flex-shrink: 0;
        }

        h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin: 28px 0 10px;
            scroll-margin-top: 24px;
        }

        p { margin-bottom: 12px; }

        a { color: var(--accent); }

        strong { font-weight: 600; }

        ul, ol {
            padding-left: 22px;
            margin-bottom: 14px;
        }

        li { margin-bottom: 4px; }

        /* ── Tables ── */
        .table-wrap { overflow-x: auto; margin: 16px 0 24px; border-radius: var(--radius); border: 1px solid var(--border); }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }

        thead { background: #f1f5f9; }

        th {
            padding: 10px 14px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        tr:last-child td { border-bottom: none; }

        tr:hover td { background: #f8fafc; }

        td code {
            background: #f1f5f9;
            color: var(--accent);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            white-space: nowrap;
        }

        /* ── Code / Badges ── */
        code {
            background: #f1f5f9;
            color: var(--accent);
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 13px;
            font-family: 'SF Mono', 'Fira Code', monospace;
        }

        /* ── Callout Boxes ── */
        .callout {
            display: flex;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius);
            margin: 16px 0;
            font-size: 14px;
            line-height: 1.6;
        }

        .callout-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .callout.info    { background: var(--blue-lt);  border-left: 4px solid var(--blue); }
        .callout.tip     { background: var(--green-lt); border-left: 4px solid var(--green); }
        .callout.warning { background: var(--amber-lt); border-left: 4px solid var(--amber); }
        .callout.danger  { background: var(--red-lt);   border-left: 4px solid var(--red); }

        /* ── Status Badges ── */
        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-gray   { background: #f1f5f9; color: #475569; }
        .badge-blue   { background: var(--blue-lt);  color: var(--blue); }
        .badge-green  { background: var(--green-lt); color: var(--green); }
        .badge-amber  { background: var(--amber-lt); color: var(--amber); }
        .badge-red    { background: var(--red-lt);   color: var(--red); }
        .badge-indigo { background: var(--accent-lt); color: var(--accent); }

        /* ── Steps ── */
        .steps { counter-reset: step; list-style: none; padding: 0; margin: 12px 0 20px; }

        .steps li {
            counter-increment: step;
            display: flex;
            gap: 14px;
            margin-bottom: 10px;
            align-items: flex-start;
        }

        .steps li::before {
            content: counter(step);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            min-width: 26px;
            background: var(--accent);
            color: #fff;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 700;
            margin-top: 1px;
        }

        /* ── Section divider ── */
        .section-intro {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--muted);
        }

        /* ── Keyboard Shortcut ── */
        kbd {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 1px 7px;
            font-size: 12px;
            font-family: monospace;
        }

        /* ── Header hero ── */
        .hero {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 12px;
            padding: 36px 40px;
            margin-bottom: 48px;
            color: white;
        }

        .hero h1 { color: white; font-size: 28px; margin-bottom: 6px; }
        .hero p  { color: rgba(255,255,255,.8); margin: 0; font-size: 14px; }

        /* ── Feature Grid ── */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin: 16px 0 24px;
        }

        .feature-card {
            position: relative;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 16px;
            font-size: 13px;
        }

        .feature-card .fc-icon { font-size: 20px; margin-bottom: 6px; }
        .feature-card strong   { display: block; margin-bottom: 2px; font-size: 14px; }
        .feature-card span     { color: var(--muted); font-size: 12px; }

        /* badge */
        .badge-new {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #e53935;
            color: #fff !important;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* ── Scroll-spy highlight ── */
        section { scroll-margin-top: 20px; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            #toc { display: none; }
            #content { margin-left: 0; padding: 24px 20px 60px; }
        }

        @media print {
            #toc { display: none; }
            #content { margin-left: 0; }
        }

        .guide-img {
            width: 100%;
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- ── Sidebar ── --}}
<aside class="sidebar">
    <a href="{{ route('help.index') }}" class="sidebar-logo">
        <div class="sidebar-logo-icon">📘</div>
        <div>
            <div class="sidebar-logo-text">A6-ERP</div>
            <div class="sidebar-logo-sub">User Guide</div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <span class="sidebar-text">Overview</span>
        <a href="{{ route('help.index') }}" class="sidebar-link {{ request()->routeIs('help.index') ? 'active' : '' }}"><span class="icon">🏠</span> Home</a>
        <a href="{{ route('help.getting-started') }}" class="sidebar-link {{ request()->routeIs('help.getting-started') ? 'active' : '' }}"><span class="icon">🚀</span> Getting Started</a>
        <a href="{{ route('help.navigation') }}" class="sidebar-link {{ request()->routeIs('help.navigation') ? 'active' : '' }}"><span class="icon">🧭</span> Navigation</a>
        <span class="sidebar-text">Work</span>
        <a href="{{ route('help.dashboard') }}" class="sidebar-link {{ request()->routeIs('help.dashboard') ? 'active' : '' }}"><span class="icon">📊</span> Dashboard</a>
        <a href="{{ route('help.announcements') }}" class="sidebar-link {{ request()->routeIs('help.announcements') ? 'active' : '' }}"><span class="icon">📢</span> Announcements</a>
        <a href="{{ route('help.attendance') }}" class="sidebar-link {{ request()->routeIs('help.attendance') ? 'active' : '' }}"><span class="icon">🏢</span> Attendance</a>
        <a href="{{ route('help.projects') }}" class="sidebar-link {{ request()->routeIs('help.projects') ? 'active' : '' }}"><span class="icon">📁</span> Projects</a>
        <a href="{{ route('help.tasks') }}" class="sidebar-link {{ request()->routeIs('help.tasks') ? 'active' : '' }}"><span class="icon">✅</span> Tasks</a>
        <a href="{{ route('help.timesheet') }}" class="sidebar-link {{ request()->routeIs('help.timesheet') ? 'active' : '' }}"><span class="icon">⏱️</span> Timesheet</a>
        <a href="{{ route('help.leave-requests') }}" class="sidebar-link {{ request()->routeIs('help.leave-requests') ? 'active' : '' }}"><span class="icon">🏖️</span> Leave Requests</a>
        <a href="{{ route('help.ot-requests') }}" class="sidebar-link {{ request()->routeIs('help.ot-requests') ? 'active' : '' }}"><span class="icon">🕐</span> OT Requests</a>
        <a href="{{ route('help.calendar') }}" class="sidebar-link {{ request()->routeIs('help.calendar') ? 'active' : '' }}"><span class="icon">📅</span> Calendar</a>
        <span class="sidebar-text">Users</span>
        <a href="{{ route('help.teams') }}" class="sidebar-link {{ request()->routeIs('help.teams') ? 'active' : '' }}"><span class="icon">👥</span> Teams</a>
        <a href="{{ route('help.users') }}" class="sidebar-link {{ request()->routeIs('help.users') ? 'active' : '' }}"><span class="icon">👤</span> Users</a>
        <a href="{{ route('help.roles') }}" class="sidebar-link {{ request()->routeIs('help.roles') ? 'active' : '' }}"><span class="icon">🔑</span> Roles &amp; Permissions</a>
        <a href="{{ route('help.notifications') }}" class="sidebar-link {{ request()->routeIs('help.notifications') ? 'active' : '' }}"><span class="icon">🔔</span> Notifications</a>
        <a href="{{ route('help.skills') }}" class="sidebar-link {{ request()->routeIs('help.skills') ? 'active' : '' }}"><span class="icon">📚</span> Skills</a>
        <a href="{{ route('help.recruitment') }}" class="sidebar-link {{ request()->routeIs('help.recruitment') ? 'active' : '' }}"><span class="icon">🤝</span> Recruitment</a>
    </nav>
</aside>

{{-- ── Main ── --}}
<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-breadcrumb">
            <a href="#">Guide</a>
            <span class="sep">›</span>
            <span class="current">@yield('breadcrumb', 'Getting Started')</span>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge">v1.0</span>
        </div>
    </header>

    <main class="content">
        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>