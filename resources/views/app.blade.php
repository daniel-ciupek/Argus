<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Apply the saved colour mode before first paint to avoid a flash.
             Mirrors the logic in resources/js/composables/useColorMode.ts. --}}
        <script>
            (function () {
                var stored = localStorage.getItem('color-mode');
                var dark = stored === 'dark'
                    || (stored !== 'light'
                        && !window.matchMedia('(prefers-color-scheme: light)').matches);
                document.documentElement.classList.toggle('dark', dark);
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
