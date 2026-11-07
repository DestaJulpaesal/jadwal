import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'button',
        'button--primary',
        'button--secondary',
        'button--ghost',
        'button--danger',
        'button--success',
        'button--sm',
        'button--md',
        'button--lg',
        'button--icon-only',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                canvas: 'var(--color-canvas)',
                surface: 'var(--color-surface)',
                ink: 'var(--color-ink)',
                muted: 'var(--color-muted)',
                line: 'var(--color-line)',
                primary: {
                    50: 'var(--color-primary-50)',
                    100: 'var(--color-primary-100)',
                    600: 'var(--color-primary-600)',
                    700: 'var(--color-primary-700)',
                    800: 'var(--color-primary-800)',
                    900: 'var(--color-primary-900)',
                },
                success: 'var(--color-success)',
                'success-soft': 'var(--color-success-soft)',
                warning: 'var(--color-warning)',
                'warning-soft': 'var(--color-warning-soft)',
                danger: 'var(--color-danger)',
                'danger-soft': 'var(--color-danger-soft)',
                info: 'var(--color-info)',
                'info-soft': 'var(--color-info-soft)',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
            },
            borderRadius: {
                card: '0.75rem',
            },
        },
    },

    plugins: [forms],
};
