@php
    $brandColor = $brandColor ?? '#DB2777';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; color: #1f2937; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: {{ $brandColor }}; padding: 28px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; }
        .header p { color: rgba(255,255,255,.75); margin: 4px 0 0; font-size: 13px; }
        .body { padding: 32px 40px; color: #374151; font-size: 15px; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .detail-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .detail-box table { border-collapse: collapse; width: 100%; }
        .detail-box td { padding: 5px 0; font-size: 14px; vertical-align: top; }
        .detail-box td:first-child { color: #6b7280; width: 140px; }
        .detail-box td:last-child { font-weight: 600; color: #111827; }
        .btn { display: inline-block; margin: 8px 0 20px; padding: 12px 28px; background: {{ $brandColor }}; color: #fff; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .url-fallback { color: #6b7280; font-size: 13px; }
        .url-fallback a { color: {{ $brandColor }}; }
        table.data { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.data th { background: #f9fafb; text-align: left; padding: 8px 10px; color: #6b7280; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        table.data td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
        table.data tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-leave { background: #dbeafe; color: #1d4ed8; }
        .badge-ot { background: #fef3c7; color: #92400e; }
        .badge-type { background: #f3f4f6; color: #374151; }
        .desc { font-weight: normal !important; color: #374151 !important; white-space: pre-wrap; }
        .summary-box { background: #f5f3ff; border-left: 4px solid {{ $brandColor }}; padding: 12px 16px; border-radius: 4px; margin-bottom: 24px; font-size: 14px; }
        .cta { text-align: center; margin: 28px 0 8px; }
        .cta a { background: {{ $brandColor }}; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-block; }
        h2.section { font-size: 15px; font-weight: 600; color: #374151; margin: 24px 0 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
        .footer { padding: 20px 40px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; text-align: center; }
        @yield('extra-styles')
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>@yield('header-title', config('app.name'))</h1>
            @hasSection('header-subtitle')
                <p>@yield('header-subtitle')</p>
            @endif
        </div>

        <div class="body">
            @yield('body')
        </div>

        <div class="footer">
            @yield('footer', 'Email này được gửi tự động từ ' . config('app.name') . '. Vui lòng không trả lời email này.')
        </div>
    </div>
</body>
</html>
