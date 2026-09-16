@extends('emails.layout')

@section('title', 'Đặt lại mật khẩu')
@section('header-title', config('app.name'))
@section('header-subtitle', 'Đặt lại mật khẩu')

@section('body')
    <p>Xin chào,</p>
    <p>Để thực hiện đặt lại mật khẩu cho tài khoản, vui lòng nhấn vào nút bên dưới.</p>

    <a href="{{ $resetUrl }}" class="btn">Đặt lại mật khẩu</a>

    <p class="url-fallback">
        Nếu nút trên không hoạt động, hãy copy đường dẫn sau vào trình duyệt:<br>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>

    <p style="color:#6b7280; font-size:13px;">Chức năng đặt lại mật khẩu này sẽ hết hạn sau {{ $expireMinutes }} phút.</p>
    <p style="color:#6b7280; font-size:13px;">Nếu không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
@endsection
