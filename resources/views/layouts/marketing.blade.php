<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', __('marketing.meta.default_description'))">

    <meta property="og:title" content="@yield('title', __('marketing.meta.default_title'))">
    <meta property="og:description" content="@yield('description', __('marketing.meta.default_description'))">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __('marketing.meta.default_title'))">
    <meta name="twitter:description" content="@yield('description', __('marketing.meta.default_description'))">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-mark.svg') }}">

    @vite(['resources/css/marketing.css'])

    <title>@yield('title', __('marketing.meta.default_title')) - يلا حجيز</title>
</head>
<body class="bg-navy-900 text-white font-sans antialiased">
    @include('marketing.partials.nav')

    @yield('content')

    @include('marketing.partials.footer')

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
