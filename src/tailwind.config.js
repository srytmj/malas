import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // TailAdmin design tokens
                'boxdark':    '#24303F',
                'boxdark-2':  '#1A222C',
                'stroke':     '#E2E8F0',
                'strokedark': '#2E3A47',
                'whiten':     '#F1F5F9',
                'bodydark':   '#AEB7C0',
                'bodydark2':  '#8A99AF',
                'graydark':   '#333A48',
                'meta-3':     '#10B981',
                'meta-6':     '#FFBA00',
            },
            boxShadow: {
                'default': '0px 8px 13px -3px rgba(0, 0, 0, 0.07)',
                '1': '0px 1px 3px rgba(0, 0, 0, 0.08), 0px 1px 2px rgba(0, 0, 0, 0.06)',
            },
        },
    },

    plugins: [forms, require('flowbite/plugin')],
};
