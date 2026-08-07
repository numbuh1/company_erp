<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc nhở phê duyệt yêu cầu</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; color: #1f2937; }
        .wrapper { max-width: 640px; margin: 32px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header  { background: #7c3aed; padding: 28px 32px; }
        .header h1 { color: #fff; font-size: 20px; margin: 0; }
        .header p  { color: #ddd6fe; font-size: 13px; margin: 4px 0 0; }
        .body    { padding: 28px 32px; }
        .greeting { font-size: 15px; margin-bottom: 16px; }
        .summary  { background: #f5f3ff; border-left: 4px solid #7c3aed; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 14px; color: #4c1d95; }
        h2 { font-size: 15px; font-weight: 600; color: #374151; margin: 24px 0 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f9fafb; text-align: left; padding: 8px 10px; color: #6b7280; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-leave { background: #dbeafe; color: #1d4ed8; }
        .badge-ot    { background: #fed7aa; color: #c2410c; }
        .badge-type  { background: #f3f4f6; color: #374151; }
        .cta { text-align: center; margin: 28px 0 8px; }
        .cta a { background: #7c3aed; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-block; }
        .cta a:hover { background: #6d28d9; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <h1>⏰ Nhắc nhở phê duyệt yêu cầu</h1>
        <p>{{ now()->translatedFormat('l, d/m/Y') }}</p>
    </div>

    {{-- Body --}}
    <div class="body">

        <p class="greeting">Xin chào <strong>{{ $recipient->name }}</strong>,</p>

        <div class="summary">
            Nhóm của bạn hiện có
            <strong>{{ $pendingLeaves->count() }} yêu cầu nghỉ phép</strong>
            và
            <strong>{{ $pendingOts->count() }} yêu cầu tăng ca</strong>
            đang chờ phê duyệt.
        </div>

        {{-- Pending Leaves --}}
        @if($pendingLeaves->isNotEmpty())
        <h2>📋 Yêu cầu nghỉ phép đang chờ ({{ $pendingLeaves->count() }})</h2>
        <table>
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
                    <td><strong>{{ $leave->user->name }}</strong>
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
        <h2>⏱ Yêu cầu tăng ca đang chờ ({{ $pendingOts->count() }})</h2>
        <table>
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
                    <td><strong>{{ $ot->user->name }}</strong>
                        @if($ot->user->position)
                            <br><span style="color:#9ca3af;font-size:11px;">{{ $ot->user->position }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-ot">{{ $ot->type }}</span>
                    </td>
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

    </div>

    {{-- Footer --}}
    <div class="footer">
        Email này được gửi tự động mỗi cuối tuần từ hệ thống {{ config('app.name') }}.
        Vui lòng không trả lời email này.
    </div>

</div>
</body>
</html>
