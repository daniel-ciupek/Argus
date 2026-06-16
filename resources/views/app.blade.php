<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Apply saved colour mode and locale before first paint to avoid a flash.
             Mirrors the logic in useColorMode.ts and useLocale.ts. --}}
        <script>
            (function () {
                var cm = localStorage.getItem('color-mode');
                var dark = cm === 'dark'
                    || (cm !== 'light'
                        && !window.matchMedia('(prefers-color-scheme: light)').matches);
                document.documentElement.classList.toggle('dark', dark);

                var loc = localStorage.getItem('locale');
                if (loc === 'pl' || loc === 'en') {
                    document.documentElement.lang = loc;
                }
            })();
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
