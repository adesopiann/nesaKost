<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Autentikasi') - eKost</title>
    @vite(['resources/css/app.css', 'resources/js/main.js'])
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="min-h-screen bg-[#F7F8FC] text-slate-800 antialiased">
    @yield('content')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.feather) feather.replace();
            document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const input = document.getElementById(button.dataset.passwordToggle);
                    if (!input) return;
                    input.type = input.type === 'password' ? 'text' : 'password';
                    const icon = button.querySelector('[data-password-icon]');
                    if (icon) {
                        icon.setAttribute('data-feather', input.type === 'password' ? 'eye' : 'eye-off');
                        if (window.feather) feather.replace();
                    }
                });
            });
        });
    </script>
</body>
</html>
