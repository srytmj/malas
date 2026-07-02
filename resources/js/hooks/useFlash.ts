import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

export function useFlash() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error)   toast.error(flash.error);
        if (flash.info)    toast.info(flash.info);
    }, [flash.success, flash.error, flash.info]);
}
