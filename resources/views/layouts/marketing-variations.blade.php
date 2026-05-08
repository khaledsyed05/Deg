<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'يلا حجيز')</title>
    <meta name="description" content="@yield('description', 'منصة حجز الملاعب الأولى بسوريا')">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/marketing-shared.css') }}">
    @stack('styles')

    <style>
        html { direction: rtl; }
        body { direction: rtl; }
    </style>
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
