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
            colors: {
                orange: {
                    50: '#fff4ef',
                    100: '#ffe6dc',
                    200: '#ffcbb8',
                    300: '#ffae92',
                    400: '#ff8c67',
                    500: '#f26225',
                    600: '#db4f13',
                    700: '#b74010',
                    800: '#933511',
                    900: '#782f13',
                    950: '#411609',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
