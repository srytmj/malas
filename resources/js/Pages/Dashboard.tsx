import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { PageProps } from '@/types';

export default function Dashboard({ auth }: PageProps) {
    return (
        <UserLayout header={<PageHeader title="Dashboard" description="Ringkasan koleksimu." />}>
            <div className="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Series dalam Koleksi
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">—</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Volume Dimiliki
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">—</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Sedang Dipinjam
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">—</p>
                    </CardContent>
                </Card>
            </div>

            <p className="mt-6 text-sm text-muted-foreground">
                Halo, {auth.user?.name}! Stats koleksi akan tersedia setelah Phase 5 dan 8.
            </p>
        </UserLayout>
    );
}
