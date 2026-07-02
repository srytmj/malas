import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Ban, Home, ServerCrash } from 'lucide-react';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

interface Props {
    status: 403 | 404 | 500 | 503;
}

interface ErrorConfig {
    icon: LucideIcon;
    title: string;
    description: string;
}

const ERRORS: Record<number, ErrorConfig> = {
    403: {
        icon: Ban,
        title: 'Akses Ditolak',
        description: 'Kamu tidak punya izin untuk mengakses halaman ini.',
    },
    404: {
        icon: AlertTriangle,
        title: 'Halaman Tidak Ditemukan',
        description: 'Halaman yang kamu cari tidak ada atau sudah dipindahkan.',
    },
    500: {
        icon: ServerCrash,
        title: 'Terjadi Kesalahan',
        description: 'Server mengalami masalah. Silakan coba lagi nanti.',
    },
    503: {
        icon: ServerCrash,
        title: 'Server Tidak Tersedia',
        description: 'Layanan sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.',
    },
};

export default function Error({ status }: Props) {
    const config = ERRORS[status] ?? ERRORS[500];
    const Icon = config.icon;

    return (
        <>
            <Head title={`${status} — ${config.title}`} />

            <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4 text-center">
                <div className="rounded-full bg-muted p-5">
                    <Icon className="h-10 w-10 text-muted-foreground" />
                </div>

                <p className="mt-6 text-6xl font-bold tracking-tight text-foreground">{status}</p>
                <h1 className="mt-3 text-xl font-semibold">{config.title}</h1>
                <p className="mt-2 max-w-sm text-sm text-muted-foreground">{config.description}</p>

                <div className="mt-8 flex gap-3">
                    <button
                        type="button"
                        onClick={() => window.history.back()}
                        className={cn(buttonVariants({ variant: 'outline' }))}
                    >
                        Kembali
                    </button>
                    <Link href="/" className={cn(buttonVariants())}>
                        <Home className="mr-2 h-4 w-4" />
                        Beranda
                    </Link>
                </div>
            </div>
        </>
    );
}
