<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Dashboard') - nesaKost</title>

    @vite(['resources/css/app.css', 'resources/js/main.js'])
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="min-h-screen bg-[#F7F8FC] text-slate-800 antialiased">
    @include('components.layouts.owner.sidebar')
    @include('components.layouts.owner.navbar')

    <main id="main-content" class="min-h-screen pt-[88px] pl-0 transition-all duration-300 lg:pl-[260px]">
        <div class="px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
            @yield('main')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.feather) feather.replace();

            const sidebar = document.getElementById('owner-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const openButton = document.getElementById('sidebar-open');
            const closeButtons = document.querySelectorAll('[data-sidebar-close]');

            function openSidebar() {
                sidebar?.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeSidebar() {
                sidebar?.classList.add('-translate-x-full');
                overlay?.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            openButton?.addEventListener('click', openSidebar);
            overlay?.addEventListener('click', closeSidebar);
            closeButtons.forEach(button => button.addEventListener('click', closeSidebar));

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    overlay?.classList.add('hidden');
                    sidebar?.classList.remove('-translate-x-full');
                    document.body.classList.remove('overflow-hidden');
                } else {
                    sidebar?.classList.add('-translate-x-full');
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
