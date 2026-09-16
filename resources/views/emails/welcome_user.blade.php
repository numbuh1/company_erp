@extends('emails.layout')

@section('title', 'Chào mừng bạn')
@section('header-title', config('app.name'))

@section('body')
    <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
    <p>Tài khoản của bạn đã được tạo mới.</p>

    <div class="detail-box">
        <table>
            <tr>
                <td>Email:</td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td>Mật khẩu:</td>
                <td style="word-break:break-all;">{{ $plainPassword }}</td>
            </tr>
        </table>
    </div>

    <p>Vui lòng đổi mật khẩu sau khi đăng nhập lần đầu.</p>

    <a href="{{ $loginUrl }}" class="btn">Đăng nhập ngay</a>

    <p class="url-fallback">
        Nếu nút trên không hoạt động, hãy copy đường dẫn sau vào trình duyệt:<br>
        <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
    </p>
@endsection
