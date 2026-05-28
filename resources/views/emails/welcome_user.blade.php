<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chào mừng bạn</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #DB2777; padding: 32px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px 40px; color: #374151; font-size: 15px; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .creds { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .creds table { border-collapse: collapse; width: 100%; }
        .creds td { padding: 5px 0; font-size: 14px; }
        .creds td:first-child { color: #6b7280; width: 130px; }
        .creds td:last-child { font-weight: 600; color: #111827; word-break: break-all; }
        .btn { display: inline-block; margin: 8px 0 20px; padding: 12px 28px; background: #DB2777; color: #fff; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .footer { padding: 20px 40px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
            <p>Tài khoản của bạn đã được tạo. Dưới đây là thông tin đăng nhập:</p>

            <div class="creds">
                <table>
                    <tr>
                        <td>Email:</td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td>Mật khẩu:</td>
                        <td>{{ $plainPassword }}</td>
                    </tr>
                </table>
            </div>

            <p>Vui lòng đổi mật khẩu sau khi đăng nhập lần đầu.</p>

            <a href="{{ $loginUrl }}" class="btn">Đăng nhập ngay</a>

            <p style="color:#6b7280;font-size:13px;">Nếu nút trên không hoạt động, hãy copy đường dẫn sau vào trình duyệt:<br>
                <a href="{{ $loginUrl }}" style="color:#DB2777;">{{ $loginUrl }}</a>
            </p>
        </div>
        <div class="footer">
            Email này được gửi tự động từ {{ config('app.name') }}. Vui lòng không trả lời email này.
        </div>
    </div>
</body>
</html>
