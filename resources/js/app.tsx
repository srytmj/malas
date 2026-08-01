import '../css/app.css';
import './bootstrap';
import i18n from '@/lib/i18n';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/Components/ui/sonner';
import { type PageProps } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const initialLocale = (props.initialPage.props as unknown as PageProps).locale;
        if (initialLocale) void i18n.changeLanguage(initialLocale);

        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <Toaster richColors position="top-right" closeButton />
            </>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
