@extends('emails.layout', ['brandColor' => '#7c3aed'])

@section('title', __('Approval Reminder'))
@section('header-title', '⏰ ' . __('Approval Reminder'))
@section('header-subtitle', now()->translatedFormat('l, d/m/Y'))

@section('body')
    <p>{{ __('Hello') }} <strong>{{ $recipient->name }}</strong>,</p>

    <div class="summary-box">
        {{ __('Your team currently has') }}
        <strong>{{ __(':count leave requests', ['count' => $pendingLeaves->count()]) }}</strong>
        {{ __('and') }}
        <strong>{{ __(':count OT requests', ['count' => $pendingOts->count()]) }}</strong>
        {{ __('pending approval.') }}
    </div>

    {{-- Pending Leaves --}}
    @if($pendingLeaves->isNotEmpty())
    <h2 class="section">📋 {{ __('Pending leave requests') }} ({{ $pendingLeaves->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('From date') }}</th>
                <th>{{ __('To date') }}</th>
                <th>{{ __('Hours') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingLeaves as $leave)
            <tr>
                <td>
                    <strong>{{ $leave->user->name }}</strong>
                    @if($leave->user->position)
                        <br><span style="color:#9ca3af;font-size:11px;">{{ $leave->user->position }}</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-type">
                        {{ ['annual' => __('Annual Leave'), 'sick' => __('Sick Leave'), 'unpaid' => __('Unpaid Leave')][$leave->type] ?? $leave->type }}
                    </span>
                </td>
                <td>{{ $leave->start_at->format('d/m/Y H:i') }}</td>
                <td>{{ $leave->end_at->format('d/m/Y H:i') }}</td>
                <td><strong>{{ $leave->hours }}h</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Pending OTs --}}
    @if($pendingOts->isNotEmpty())
    <h2 class="section">⏱ {{ __('Pending OT requests') }} ({{ $pendingOts->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Time') }}</th>
                <th>{{ __('Hours') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingOts as $ot)
            <tr>
                <td>
                    <strong>{{ $ot->user->name }}</strong>
                    @if($ot->user->position)
                        <br><span style="color:#9ca3af;font-size:11px;">{{ $ot->user->position }}</span>
                    @endif
                </td>
                <td><span class="badge badge-ot">{{ $ot->type }}</span></td>
                <td>{{ $ot->start_at->format('d/m/Y') }}</td>
                <td>{{ $ot->start_at->format('H:i') }} – {{ $ot->end_at->format('H:i') }}</td>
                <td><strong>{{ $ot->hours }}h</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="cta">
        <a href="{{ route('requests.index', ['status' => 'pending']) }}">
            {{ __('View & Approve requests') }} →
        </a>
    </div>
@endsection

@section('footer')
    {{ __('This email is sent automatically every weekend from') }} {{ config('app.name') }}.
@endsection
