import { useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

export function useFlash() {
    const { flash } = usePage().props;

    useEffect(() => {
        const undoUrl = flash.undo_url;
        const action = undoUrl
            ? {
                label: 'Undo',
                onClick: () => router.patch(undoUrl, flash.undo_payload ?? {}, { preserveScroll: true }),
            }
            : undefined;

        if (flash.success) toast.success(flash.success, { action });
        if (flash.error)   toast.error(flash.error);
        if (flash.info)    toast.info(flash.info);
    }, [flash.success, flash.error, flash.info, flash.undo_url, flash.undo_payload]);
}
