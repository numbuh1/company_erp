@extends('emails.layout')

@section('title', __('New Account'))
@section('header-title', config('app.name'))

@section('body')
    <p>{{ __('Hello') }} <strong>{{ $user->name }}</strong>,</p>
    <p>{{ __('Your account has been created.') }}</p>

    <div class="detail-box">
        <table>
            <tr>
                <td>Email:</td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td>{{ __('Password') }}:</td>
                <td style="word-break:break-all;">{{ $plainPassword }}</td>
            </tr>
        </table>
    </div>

    <p>{{ __('Please change your password after your first login.') }}</p>

    <a href="{{ $loginUrl }}" class="btn">{{ __('Login now') }}</a>

    <p class="url-fallback">
        {{ __('If the button above does not work, copy and paste the following link into your browser:') }}<br>
        <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
    </p>
@endsection
