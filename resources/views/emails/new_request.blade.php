@extends('emails.layout')

@section('title', __('New Request'))
@section('header-title', config('app.name'))
@section('header-subtitle')
    {!! __('New :type request pending approval', ['type' => '<strong style="color:#fff;">' . match ($type) { 'leave' => __('Leave'), 'wfh' => __('WFH'), default => __('Overtime') } . '</strong>']) !!}
@endsection

@section('body')
    <p>{{ __('Hello') }},</p>
    <p>
        <strong>{{ $requester->name }}</strong>
        @if($requester->position)
            ({{ $requester->position }})
        @endif
        {{ __('has just submitted a') }}
        @if($type === 'leave')
            <span class="badge badge-leave">{{ __('Leave') }}</span>
        @elseif($type === 'wfh')
            <span class="badge badge-leave">{{ __('WFH') }}</span>
        @else
            <span class="badge badge-ot">{{ __('Overtime') }}</span>
        @endif
        {{ __('request that needs approval.') }}
    </p>

    <div class="detail-box">
        <table>
            <tr>
                <td>{{ __('Requester') }}:</td>
                <td>{{ $requester->name }}{{ $requester->position ? ' — ' . $requester->position : '' }}{{ $requester->grade ? ' — ' . $requester->grade : '' }}</td>
            </tr>
            <tr>
                <td>{{ __('Type') }}:</td>
                <td>{{ $type === 'wfh' ? __('WFH') : $request->type }}</td>
            </tr>
            <tr>
                <td>{{ __('From') }}:</td>
                <td>{{ $request->start_at->format('D, d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>{{ __('To') }}:</td>
                <td>{{ $request->end_at->format('D, d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>{{ __('Hours') }}:</td>
                <td>{{ $request->hours }}h</td>
            </tr>
            @if($request->description)
            <tr>
                <td>{{ __('Notes') }}:</td>
                <td class="desc">{{ $request->description }}</td>
            </tr>
            @endif
            @if(in_array($type, ['ot', 'wfh']) && $request->relationLoaded('project') && $request->project)
            <tr>
                <td>{{ __('Project') }}:</td>
                <td>{{ $request->project->name }}</td>
            </tr>
            @endif
            @if(in_array($type, ['ot', 'wfh']) && $request->relationLoaded('task') && $request->task)
            <tr>
                <td>{{ __('Task') }}:</td>
                <td>{{ $request->task->name }}</td>
            </tr>
            @endif
        </table>
    </div>

    <p>{{ __('Please log in to review and approve the request:') }}</p>

    @php
        $actionUrl = route('requests.index', ['type' => $type, 'status' => 'pending']);
        $actionLabel = match ($type) {
            'leave' => __('View leave request'),
            'wfh'   => __('View WFH request'),
            default => __('View OT request'),
        };
    @endphp

    <a href="{{ $actionUrl }}" class="btn">{{ $actionLabel }}</a>

    <p class="url-fallback">
        {{ __('If the button above does not work, copy and paste the following link into your browser:') }}<br>
        <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
    </p>
@endsection
