import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Check, Languages } from 'lucide-react';
import i18n from '@/lib/i18n';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';

const LOCALES = ['id', 'en', 'ja'] as const;

export function LanguageSwitcher({ collapsed }: { collapsed?: boolean }) {
    const { locale } = usePage().props;
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    function handleSelect(value: string) {
        void i18n.changeLanguage(value);
        setOpen(false);
        router.patch(route('settings.locale.update'), { locale: value }, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                render={
                    collapsed ? (
                        <Button variant="ghost" size="icon" className="mx-auto flex" aria-label={t('locale.label')}>
                            <Languages className="h-4 w-4" />
                        </Button>
                    ) : (
                        <Button variant="ghost" size="sm" className="w-full justify-start gap-3 text-muted-foreground">
                            <Languages className="h-4 w-4" />
                            {t(`locale.${locale}`)}
                        </Button>
                    )
                }
            />
            <PopoverContent side="right" className="w-44 p-1" align="start">
                {LOCALES.map((l) => (
                    <button
                        key={l}
                        type="button"
                        onClick={() => handleSelect(l)}
                        className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                    >
                        <Check className={cn('h-3.5 w-3.5', locale === l ? 'opacity-100' : 'opacity-0')} />
                        {t(`locale.${l}`)}
                    </button>
                ))}
            </PopoverContent>
        </Popover>
    );
}
