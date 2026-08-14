import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, KeyRound, Loader2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useFlash } from '@/hooks/useFlash';

export default function SsoFallback() {
    const { t } = useTranslation();
    useFlash();

    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSubmitting(true);
        router.post(route('sso.fallback.send'), { email }, {
            preserveScroll: true,
            onFinish: () => setSubmitting(false),
        });
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-4">
            <Head title={t('authFallback.pageTitle')} />
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                        <KeyRound className="h-5 w-5" />
                    </div>
                    <CardTitle className="text-xl">{t('authFallback.heading')}</CardTitle>
                    <CardDescription>{t('authFallback.description')}</CardDescription>
                </CardHeader>

                <form onSubmit={handleSubmit}>
                    <CardContent className="space-y-2">
                        <Label htmlFor="email">{t('authFallback.emailLabel')}</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autoFocus
                            placeholder={t('authFallback.emailPlaceholder')}
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                        />
                        <p className="pt-1 text-xs text-muted-foreground">{t('authFallback.note')}</p>
                    </CardContent>

                    <CardFooter className="flex flex-col gap-3">
                        <Button type="submit" className="w-full" disabled={submitting}>
                            {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {submitting ? t('authFallback.submitting') : t('authFallback.submitButton')}
                        </Button>
                        <Link href="/" className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground hover:underline">
                            <ArrowLeft className="h-3 w-3" />
                            {t('authFallback.backToLogin')}
                        </Link>
                    </CardFooter>
                </form>
            </Card>
        </div>
    );
}
