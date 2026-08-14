import { useState } from 'react';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, KeyRound, Loader2, LogIn, Mail } from 'lucide-react';
import {
    Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

type Step = 'choice' | 'email-form' | 'sent';

interface LoginMethodDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** 'link' = "Tambah Akun" (user sudah login, mau nyambungin akun lain) — bukan login awal. */
    mode?: 'login' | 'link';
}

/**
 * Modal pilihan cara login — SSO whitearchive.id atau magic link lewat email — dipakai dari
 * tombol "Login" di Landing page (mode="login") DAN dari AccountSwitcher buat "Tambah Akun"
 * (mode="link", session-based, lihat AccountLinkService). Login lewat email TIDAK men-sync ulang
 * profil (nama/avatar/username) dari SSO — itu cuma terjadi pas login lewat SSO.
 */
export function LoginMethodDialog({ open, onOpenChange, mode = 'login' }: LoginMethodDialogProps) {
    const { t } = useTranslation();
    const isLinkMode = mode === 'link';
    const [step, setStep] = useState<Step>('choice');
    const [email, setEmail] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function reset() {
        setStep('choice');
        setEmail('');
        setSubmitting(false);
    }

    function handleOpenChange(next: boolean) {
        onOpenChange(next);
        if (!next) {
            // Tunda reset dikit biar nggak keliatan "loncat" pas animasi close jalan.
            setTimeout(reset, 200);
        }
    }

    function handleEmailSubmit(e: React.FormEvent) {
        e.preventDefault();
        setSubmitting(true);
        router.post(route('sso.fallback.send'), { email, link: isLinkMode }, {
            preserveScroll: true,
            onFinish: () => { setSubmitting(false); setStep('sent'); },
        });
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-sm">
                {step === 'choice' && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{isLinkMode ? t('loginDialog.linkTitle') : t('loginDialog.title')}</DialogTitle>
                            <DialogDescription>{isLinkMode ? t('loginDialog.linkDescription') : t('loginDialog.description')}</DialogDescription>
                        </DialogHeader>
                        <div className="flex flex-col gap-2">
                            <a href={route('sso.redirect', isLinkMode ? { link: 1 } : undefined)} className="w-full">
                                <Button className="w-full justify-start" size="lg">
                                    <LogIn className="mr-2 h-4 w-4" />
                                    {t('loginDialog.ssoOption')}
                                </Button>
                            </a>
                            <Button
                                variant="outline"
                                size="lg"
                                className="w-full justify-start"
                                onClick={() => setStep('email-form')}
                            >
                                <Mail className="mr-2 h-4 w-4" />
                                {t('loginDialog.emailOption')}
                            </Button>
                        </div>
                        <p className="text-center text-xs text-muted-foreground">
                            {t('loginDialog.syncHint')}
                        </p>
                    </>
                )}

                {step === 'email-form' && (
                    <>
                        <DialogHeader>
                            <div className="mx-auto mb-1 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                <KeyRound className="h-5 w-5" />
                            </div>
                            <DialogTitle>{t('loginDialog.emailTitle')}</DialogTitle>
                            <DialogDescription>{t('loginDialog.emailDescription')}</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleEmailSubmit} className="space-y-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="login-dialog-email">{t('loginDialog.emailLabel')}</Label>
                                <Input
                                    id="login-dialog-email"
                                    type="email"
                                    required
                                    autoFocus
                                    placeholder={t('loginDialog.emailPlaceholder')}
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">{t('loginDialog.emailNote')}</p>
                            </div>
                            <Button type="submit" className="w-full" disabled={submitting}>
                                {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                {submitting ? t('loginDialog.sending') : t('loginDialog.sendLink')}
                            </Button>
                            <button
                                type="button"
                                className="flex w-full items-center justify-center gap-1.5 text-xs text-muted-foreground hover:text-foreground hover:underline"
                                onClick={() => setStep('choice')}
                            >
                                <ArrowLeft className="h-3 w-3" />
                                {t('loginDialog.back')}
                            </button>
                        </form>
                    </>
                )}

                {step === 'sent' && (
                    <>
                        <DialogHeader>
                            <div className="mx-auto mb-1 flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                <Mail className="h-5 w-5" />
                            </div>
                            <DialogTitle>{t('loginDialog.sentTitle')}</DialogTitle>
                            <DialogDescription>{t('loginDialog.sentDescription')}</DialogDescription>
                        </DialogHeader>
                        <Button variant="outline" className="w-full" onClick={() => handleOpenChange(false)}>
                            {t('common:common.close')}
                        </Button>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
