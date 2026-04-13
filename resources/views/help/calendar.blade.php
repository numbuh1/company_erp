@extends('layouts.help')

@section('title', 'Calendar — A6-ERP User Guide')
@section('breadcrumb', 'Calendar')

@section('content')

<section id="attendance">
    <h2>Calendar</h2>
    <p class="section-intro">Check events / meetings in monthly, weekly or daily view.</p>

    <h3>Viewing Calendar</h3>
    <p>The index page lists all events in the calendar.</p>
    <p>User can filter the events based on event type or location.</p>
    <p>The calendar includes monthly, weekly and daily view.</p>

    <img class="guide-img" src="/guide/images/15-calendar-monthly.png">

    <br>

    <img class="guide-img" src="/guide/images/15-calendar-weekly.png">

    <br>

    <img class="guide-img" src="/guide/images/15-calendar-daily.png">

    <br>

    <h3>Creating Events</h3>
    <p>User can create new event. The attendees will be notified of the event.</p>

    <img class="guide-img" src="/guide/images/15-calendar-event-edit.png">

    <br>

    <h3>Events Reminder</h3>
    <p>Events of the week will be also featured in Dashboard.</p>

    <img class="guide-img" src="/guide/images/03-dashboard.png">
</section>

@endsection