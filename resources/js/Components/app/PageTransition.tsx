import { useEffect, useState, type ReactNode } from 'react';
import { router } from '@inertiajs/react';

/**
 * Crossfade halus tiap Inertia visit (link klik, router.get/post/patch/delete) — tanpa ini,
 * swap komponen antar halaman berasa abrupt/"ngeblink". Cuma efek visual (opacity), nggak
 * nunda render aktual — start/finish Inertia sendiri yang nentuin timing swap komponennya.
 */
export function PageTransition({ children }: { children: ReactNode }) {
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        const removeStart = router.on('start', () => setVisible(false));
        const removeFinish = router.on('finish', () => setVisible(true));
        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return (
        <div className={visible ? 'opacity-100 transition-opacity duration-150' : 'opacity-0 transition-opacity duration-150'}>
            {children}
        </div>
    );
}
