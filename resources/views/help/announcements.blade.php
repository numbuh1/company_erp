@extends('layouts.help')

@section('title', 'Announcement — A6-ERP User Guide')
@section('breadcrumb', 'Announcement')

@section('content')

<section id="announcements">
    <h2>Announcements</h2>
    <p class="section-intro">Company-wide posts with rich text formatting — bold, italic, lists, headings, and embedded images.</p>

    <img class="guide-img" src="/guide/images/04-1-announcement-list.png">

    <h3>Viewing Announcements</h3>
    <p>The index page lists all announcements as cards. Click a title to read the full content on its own page.</p>

    <img class="guide-img" src="/guide/images/04-3-announcement-show.png">

    <h3>Creating an Announcement</h3>
    <div class="callout info"><span class="callout-icon">🔑</span><div>Requires the <code>Create/Edit Announcements</code> permission.</div></div>
    <ol class="steps">
        <li>Click <strong>New Announcement</strong> on the index page.</li>
        <li>Enter a <strong>Title</strong>.</li>
        <li>Write your content using the rich text editor. Use the toolbar for formatting.</li>
        <li>To insert an image, click the image icon in the toolbar — it uploads automatically.</li>
        <li>Click <strong>Save</strong>.</li>
    </ol>

    <h3>Editing &amp; Deleting</h3>
    <ul>
        <li><strong>Edit</strong> — available on the index card and the show page with <code>Create/Edit Announcements</code> permission.</li>
        <li><strong>Delete</strong> — requires <code>Delete Announcements</code> permission.</li>
    </ul>

    <img class="guide-img" src="/guide/images/04-2-announcement-edit.png">
</section>

@endsection