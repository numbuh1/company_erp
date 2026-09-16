@extends('emails.layout')

@section('title', 'Yêu cầu mới')
@section('header-title', config('app.name'))
@section('header-subtitle')
    @if($type === 'leave')
        Yêu cầu <strong style="color:#fff;">Nghỉ phép</strong> mới cần phê duyệt
    @else
        Yêu cầu <strong style="color:#fff;">Tăng ca</strong> mới cần phê duyệt
    @endif
@endsection

@section('body')
    <p>Xin chào,</p>
    <p>
        <strong>{{ $requester->name }}</strong>
        @if($requester->position)
            ({{ $requester->position }})
        @endif
        vừa gửi một yêu cầu
        @if($type === 'leave')
            <span class="badge badge-leave">Nghỉ phép</span>
        @else
            <span class="badge badge-ot">Tăng ca</span>
        @endif
        cần được phê duyệt.
    </p>

    <div class="detail-box">
        <table>
            <tr>
                <td>Người yêu cầu:</td>
                <td>{{ $requester->name }}{{ $requester->position ? ' — ' . $requester->position : '' }}{{ $requester->grade ? ' — ' . $requester->grade : '' }}</td>
            </tr>
            <tr>
                <td>Loại:</td>
                <td>{{ $request->type }}</td>
            </tr>
            <tr>
                <td>Từ:</td>
                <td>{{ $request->start_at->format('D, d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Đến:</td>
                <td>{{ $request->end_at->format('D, d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Số giờ:</td>
                <td>{{ $request->hours }}h</td>
            </tr>
            @if($request->description)
            <tr>
                <td>Ghi chú:</td>
                <td class="desc">{{ $request->description }}</td>
            </tr>
            @endif
            @if($type === 'ot' && $request->relationLoaded('project') && $request->project)
            <tr>
                <td>Dự án:</td>
                <td>{{ $request->project->name }}</td>
            </tr>
            @endif
            @if($type === 'ot' && $request->relationLoaded('task') && $request->task)
            <tr>
                <td>Công việc:</td>
                <td>{{ $request->task->name }}</td>
            </tr>
            @endif
        </table>
    </div>

    <p>Vui lòng đăng nhập để xem và phê duyệt yêu cầu:</p>

    @php
        $actionUrl = $type === 'leave'
            ? route('requests.index', ['type' => 'leave', 'status' => 'pending'])
            : route('requests.index', ['type' => 'ot', 'status' => 'pending']);
        $actionLabel = $type === 'leave' ? 'Xem yêu cầu nghỉ phép' : 'Xem yêu cầu tăng ca';
    @endphp

    <a href="{{ $actionUrl }}" class="btn">{{ $actionLabel }}</a>

    <p class="url-fallback">
        Nếu nút trên không hoạt động, hãy copy đường dẫn sau vào trình duyệt:<br>
        <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
    </p>
@endsection
