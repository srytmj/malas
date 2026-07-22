import { Head } from '@inertiajs/react';
import { BookOpen, HandCoins, Library, LogIn, Megaphone } from 'lucide-react';
import { buttonVariants } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { cn } from '@/lib/utils';

const FEATURES = [
    {
        icon: BookOpen,
        title: 'Katalog',
        description: 'Jelajahi dan cari manga, manhwa, manhua, hingga novel lengkap dengan metadata dari MyAnimeList.',
    },
    {
        icon: Library,
        title: 'Koleksi Pribadi',
        description: 'Tandai volume yang sudah kamu miliki dan kelola koleksimu sendiri.',
    },
    {
        icon: HandCoins,
        title: 'Peminjaman',
        description: 'Catat volume yang kamu pinjamkan ke orang lain beserta tanggal jatuh temponya.',
    },
    {
        icon: Megaphone,
        title: 'Pengumuman',
        description: 'Dapatkan info terbaru seputar koleksi dan layanan langsung dari admin.',
    },
];

export default function Landing() {
    return (
        <>
            <Head title="Selamat Datang" />

            <div className="flex min-h-screen flex-col items-center bg-background px-4 py-16">
                <div className="w-full max-w-3xl text-center">
                    <p className="text-sm font-medium text-muted-foreground">MALAS</p>
                    <h1 className="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                        Manajemen Koleksi Manga & Light Novel
                    </h1>
                    <p className="mx-auto mt-4 max-w-xl text-muted-foreground">
                        Kelola katalog, koleksi pribadi, dan peminjaman manga dan light novel-mu di satu tempat.
                    </p>

                    <div className="mt-8">
                        <a
                            href={route('sso.redirect')}
                            className={cn(buttonVariants({ size: 'lg' }))}
                        >
                            <LogIn className="mr-2 h-4 w-4" />
                            Login dengan whitearchive.id
                        </a>
                    </div>
                </div>

                <div className="mt-16 grid w-full max-w-3xl gap-4 sm:grid-cols-2">
                    {FEATURES.map((feature) => (
                        <Card key={feature.title}>
                            <CardHeader className="flex flex-row items-center gap-3 space-y-0">
                                <div className="rounded-md bg-muted p-2">
                                    <feature.icon className="h-5 w-5 text-foreground" />
                                </div>
                                <CardTitle className="text-base">{feature.title}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">{feature.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
