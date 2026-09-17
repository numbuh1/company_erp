@extends('emails.layout')

@section('title', __('Reset Password'))
@section('header-title', config('app.name'))
@section('header-subtitle', __('Reset Password'))

@section('body')
    <p>{{ __('Hello') }},</p>
    <p>{{ __('To reset your account password, please click the button below.') }}</p>

    <a href="{{ $resetUrl }}" class="btn">{{ __('Reset Password') }}</a>

    <p class="url-fallback">
        {{ __('If the button above does not work, copy and paste the following link into your browser:') }}<br>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>

    <p style="color:#6b7280; font-size:13px;">{{ __('This password reset link will expire in :minutes minutes.', ['minutes' => $expireMinutes]) }}</p>
    <p style="color:#6b7280; font-size:13px;">{{ __('If you did not request a password reset, please ignore this email.') }}</p>
@endsection
