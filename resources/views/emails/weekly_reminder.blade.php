@extends('emails.layout', ['brandColor' => '#7c3aed'])

@section('title', 'Nhắc nhở phê duyệt yêu cầu')
@section('header-title', '⏰ Nhắc nhở phê duyệt yêu cầu')
@section('header-subtitle', now()->translatedFormat('l, d/m/Y'))

@section('body')
    <p>Xin chào <strong>{{ $recipient->name }}</strong>,</p>

    <div class="summary-box">
        Nhóm của bạn hiện có
        <strong>{{ $pendingLeaves->count() }} yêu cầu nghỉ phép</strong>
        và
        <strong>{{ $pendingOts->count() }} yêu cầu tăng ca</strong>
        đang chờ phê duyệt.
    </div>

    {{-- Pending Leaves --}}
    @if($pendingLeaves->isNotEmpty())
    <h2 class="section">📋 Yêu cầu nghỉ phép đang chờ ({{ $pendingLeaves->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Loại</th>
                <th>Từ ngày</th>
                <th>Đến ngày</th>
                <th>Số giờ</th>
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
                        {{ ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Không lương'][$leave->type] ?? $leave->type }}
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
    <h2 class="section">⏱ Yêu cầu tăng ca đang chờ ({{ $pendingOts->count() }})</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Loại</th>
                <th>Ngày</th>
                <th>Giờ</th>
                <th>Số giờ</th>
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
            Xem & Phê duyệt yêu cầu →
        </a>
    </div>
@endsection

@section('footer')
    Email này được gửi tự động mỗi cuối tuần từ hệ thống {{ config('app.name') }}.
    Vui lòng không trả lời email này.
@endsection
