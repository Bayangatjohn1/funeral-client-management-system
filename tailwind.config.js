import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: '#22324A',
                    light: '#2f4563',
                    dark: '#192539',
                    50: '#EEF1F6',
                },
                accent: {
                    DEFAULT: '#9C5A1A',
                    light: '#B26A22',
                    dark: '#7A440F',
                    50: '#F5E8D9',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    panel: '#F7F8FA',
                    muted: '#E6EAF0',
                },
            },
            letterSpacing: {
                tightest: '-0.03em',
                tighter: '-0.02em',
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
};
