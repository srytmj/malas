import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

export type Theme = 'light' | 'dark' | 'system';
type ResolvedTheme = 'light' | 'dark';

function resolveSystemTheme(): ResolvedTheme {
    if (typeof window === 'undefined') return 'light';
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function resolve(theme: Theme): ResolvedTheme {
    return theme === 'system' ? resolveSystemTheme() : theme;
}

function applyTheme(theme: Theme) {
    document.documentElement.classList.toggle('dark', resolve(theme) === 'dark');
}

function getInitialTheme(userTheme?: Theme): Theme {
    if (userTheme) return userTheme;
    if (typeof window === 'undefined') return 'system';
    return (localStorage.getItem('theme') as Theme | null) ?? 'system';
}

/**
 * Tema tersimpan per-user (users.theme) sama pola dengan locale — sync ke server kalau login,
 * localStorage-only kalau guest (Landing/PublicShell). 'system' ikutin prefers-color-scheme OS
 * dan live-update kalau preferensi OS berubah selagi app kebuka.
 */
export function useTheme() {
    const { auth } = usePage().props;
    const userTheme = auth.user?.theme;
    const [theme, setThemeState] = useState<Theme>(() => getInitialTheme(userTheme));
    const lastSyncedProp = useRef(userTheme);

    // Apply + persist tiap kali `theme` berubah (baik dari user klik atau sync dari server).
    useEffect(() => {
        applyTheme(theme);
        localStorage.setItem('theme', theme);
    }, [theme]);

    // Kalau shared prop dari server berubah (mis. diganti dari device/tab lain, ke-refresh pas
    // navigasi Inertia) dan beda dari state lokal, ikutin nilai server.
    useEffect(() => {
        if (userTheme && userTheme !== lastSyncedProp.current) {
            lastSyncedProp.current = userTheme;
            setThemeState(userTheme);
        }
    }, [userTheme]);

    // Live-update kalau lagi mode 'system' dan preferensi OS berubah selagi app kebuka.
    useEffect(() => {
        if (theme !== 'system') return;
        const mql = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = () => applyTheme('system');
        mql.addEventListener('change', handleChange);
        return () => mql.removeEventListener('change', handleChange);
    }, [theme]);

    function setTheme(value: Theme) {
        setThemeState(value);
        lastSyncedProp.current = value;
        if (!auth.user) return;
        router.patch(route('settings.theme.update'), { theme: value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    return { theme, resolvedTheme: resolve(theme), setTheme };
}
