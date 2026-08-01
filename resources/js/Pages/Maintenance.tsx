import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';

interface MaintenanceProps {
    message: string | null;
}

export default function Maintenance({ message }: MaintenanceProps) {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-4">
            <Head title={t('maintenancePage.title')} />
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <CardTitle className="text-2xl">{t('maintenancePage.heading')}</CardTitle>
                    <CardDescription>
                        {t('maintenancePage.description')}
                    </CardDescription>
                </CardHeader>

                {message && (
                    <CardContent>
                        <p className="text-center text-sm text-muted-foreground">{message}</p>
                    </CardContent>
                )}

                <CardFooter>
                    <Button variant="outline" className="w-full" onClick={() => router.visit('/')}>
                        {t('maintenancePage.backHome')}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    );
}
