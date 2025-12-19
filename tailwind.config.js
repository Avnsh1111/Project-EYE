import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Google Sans', 'Roboto', 'Inter', ...defaultTheme.fontFamily.sans],
                display: ['Google Sans', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Material Design 3 Color System
                primary: {
                    50: '#e8f0fe',
                    100: '#d2e3fc',
                    200: '#aecbfa',
                    300: '#8ab4f8',
                    400: '#669df6',
                    500: '#4285f4',
                    600: '#1a73e8',
                    700: '#1967d2',
                    800: '#185abc',
                    900: '#174ea6',
                },
                surface: {
                    DEFAULT: '#ffffff',
                    variant: '#f8f9fa',
                    dim: '#f1f3f4',
                    bright: '#ffffff',
                },
                outline: {
                    DEFAULT: '#dadce0',
                    variant: '#e8eaed',
                },
                google: {
                    blue: '#4285f4',
                    red: '#ea4335',
                    yellow: '#fbbc04',
                    green: '#34a853',
                },
            },
            boxShadow: {
                'md3-1': '0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15)',
                'md3-2': '0 1px 2px 0 rgba(60,64,67,0.3), 0 2px 6px 2px rgba(60,64,67,0.15)',
                'md3-3': '0 1px 3px 0 rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15)',
                'md3-4': '0 2px 3px 0 rgba(60,64,67,0.3), 0 6px 10px 4px rgba(60,64,67,0.15)',
                'md3-5': '0 4px 4px 0 rgba(60,64,67,0.3), 0 8px 12px 6px rgba(60,64,67,0.15)',
            },
            borderRadius: {
                'md3-xs': '4px',
                'md3-sm': '8px',
                'md3-md': '12px',
                'md3-lg': '16px',
                'md3-xl': '28px',
            },
            animation: {
                'fade-in': 'fadeIn 0.2s ease-out',
                'slide-up': 'slideUp 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'ripple': 'ripple 0.6s linear',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.9)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                ripple: {
                    '0%': { transform: 'scale(0)', opacity: '1' },
                    '100%': { transform: 'scale(4)', opacity: '0' },
                },
            },
            transitionTimingFunction: {
                'emphasized': 'cubic-bezier(0.2, 0, 0, 1)',
                'emphasized-decelerate': 'cubic-bezier(0.05, 0.7, 0.1, 1)',
                'emphasized-accelerate': 'cubic-bezier(0.3, 0, 0.8, 0.15)',
            },
        },
    },
    plugins: [],
};
