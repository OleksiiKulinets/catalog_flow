import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#f0f4fa',
                    100: '#dce6f2',
                    200: '#b9cfe8',
                    300: '#8caed6',
                    400: '#5c87bd',
                    500: '#3d689e',
                    600: '#2d5081',
                    700: '#223d64',
                    800: '#182c49',
                    900: '#101d32',
                    950: '#0a1220',
                },
            },
        },
    },

    plugins: [forms],
};
