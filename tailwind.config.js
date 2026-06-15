import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Dark mode is toggled by a `dark` class on <html> (see useColorMode).
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Inter for the UI, JetBrains Mono for technical data
                // (metrics, costs, IDs, timestamps, JSON payloads).
                sans: ['Inter Variable', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono Variable', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Surface scale — neutral base for both themes (zinc).
                surface: colors.zinc,

                // Accent: emerald reads as "connected / live / OK" in monitoring.
                accent: colors.emerald,

                // Semantic status colours.
                success: colors.emerald,
                warning: colors.amber,
                danger: colors.red,
                info: colors.sky,
            },

            borderRadius: {
                // Balanced: rounded cards, slightly tighter inputs.
                card: '0.625rem', // 10px
            },

            boxShadow: {
                // Subtle elevation, mainly visible in light mode.
                card: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 1px 3px 0 rgb(0 0 0 / 0.06)',
            },
        },
    },

    plugins: [forms],
};
