<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'nesaKost - Temukan Kost Dekat UNESA')</title>
    <meta name="description" content="Temukan kost dekat UNESA Kampus Ketintang dan Lidah Wetan dengan nesaKost.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; }
        html { scroll-behavior: smooth; }
    </style>

    @stack('styles')
</head>
<body class="bg-white text-slate-800 antialiased">
    @include('components.customer.navbar')

    <main>
        @yield('content')
    </main>

    @include('components.customer.footer')

    @stack('scripts')
</body>
</html>
