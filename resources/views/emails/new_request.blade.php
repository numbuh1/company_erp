<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu mới</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #4f46e5; padding: 32px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .header p  { color: #c7d2fe; margin: 6px 0 0; font-size: 14px; }
        .body { padding: 32px 40px; color: #374151; font-size: 15px; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .detail-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .detail-box table { border-collapse: collapse; width: 100%; }
        .detail-box td { padding: 5px 0; font-size: 14px; vertical-align: top; }
        .detail-box td:first-child { color: #6b7280; width: 140px; }
        .detail-box td:last-child { font-weight: 600; color: #111827; }
        .desc { font-weight: normal !important; color: #374151 !important; white-space: pre-wrap; }
        .btn { display: inline-block; margin: 8px 0 20px; padding: 12px 28px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .footer { padding: 20px 40px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-leave { background: #dbeafe; color: #1d4ed8; }
        .badge-ot    { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>
                @if($type === 'leave')
                    Yêu cầu <strong style="color:#fff;">Nghỉ phép</strong> mới cần phê duyệt
                @else
                    Yêu cầu <strong style="color:#fff;">Tăng ca</strong> mới cần phê duyệt
                @endif
            </p>
        </div>

        <div class="body">
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
                        <td>{{ $requester->name }}{{ $requester->position ? ' — ' . $requester->position : '' }}</td>
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

            @if($type === 'leave')
                <a href="{{ route('requests.index', ['type' => 'leave', 'status' => 'pending']) }}" class="btn">Xem yêu cầu nghỉ phép</a>
            @else
                <a href="{{ route('requests.index', ['type' => 'ot', 'status' => 'pending']) }}" class="btn">Xem yêu cầu tăng ca</a>
            @endif

            <p style="color:#6b7280;font-size:13px;">
                Nếu nút trên không hoạt động, hãy copy đường dẫn sau vào trình duyệt:<br>
                @if($type === 'leave')
                    <a href="{{ route('requests.index', ['type' => 'leave', 'status' => 'pending']) }}" style="color:#4f46e5;">
                        {{ route('requests.index', ['type' => 'leave', 'status' => 'pending']) }}
                    </a>
                @else
                    <a href="{{ route('requests.index', ['type' => 'ot', 'status' => 'pending']) }}" style="color:#4f46e5;">
                        {{ route('requests.index', ['type' => 'ot', 'status' => 'pending']) }}
                    </a>
                @endif
            </p>
        </div>

        <div class="footer">
            Email này được gửi tự động từ {{ config('app.name') }}. Vui lòng không trả lời email này.
        </div>
    </div>
</body>
</html>
