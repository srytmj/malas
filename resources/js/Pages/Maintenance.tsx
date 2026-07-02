import { router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';

interface MaintenanceProps {
    message: string | null;
}

export default function Maintenance({ message }: MaintenanceProps) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <CardTitle className="text-2xl">Sedang Dalam Pemeliharaan</CardTitle>
                    <CardDescription>
                        Halaman ini sedang tidak tersedia untuk sementara waktu.
                    </CardDescription>
                </CardHeader>

                {message && (
                    <CardContent>
                        <p className="text-center text-sm text-muted-foreground">{message}</p>
                    </CardContent>
                )}

                <CardFooter>
                    <Button variant="outline" className="w-full" onClick={() => router.visit('/')}>
                        Kembali ke Beranda
                    </Button>
                </CardFooter>
            </Card>
        </div>
    );
}
