import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Check, Monitor, Moon, Sun, type LucideIcon } from 'lucide-react';
import { useTheme, type Theme } from '@/hooks/useTheme';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/lib/utils';

const THEMES: readonly Theme[] = ['light', 'dark', 'system'];

const THEME_ICONS: Record<Theme, LucideIcon> = {
    light: Sun,
    dark: Moon,
    system: Monitor,
};

export function ThemeSwitcher({ collapsed }: { collapsed?: boolean }) {
    const { theme, resolvedTheme, setTheme } = useTheme();
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    // Icon trigger (mode collapsed) ikutin tema yang KETERAPKAN (light/dark), bukan pilihan
    // 'system' mentah — biar ikonnya selalu representatif walau lagi ikut preferensi OS.
    const TriggerIcon = collapsed ? THEME_ICONS[resolvedTheme] : THEME_ICONS[theme];

    function handleSelect(value: Theme) {
        setTheme(value);
        setOpen(false);
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger
                render={
                    collapsed ? (
                        <Button variant="ghost" size="icon" className="mx-auto flex" aria-label={t('theme.label')}>
                            <TriggerIcon className="h-4 w-4" />
                        </Button>
                    ) : (
                        <Button variant="ghost" size="sm" className="w-full justify-start gap-3 text-muted-foreground">
                            <TriggerIcon className="h-4 w-4" />
                            {t(`theme.${theme}`)}
                        </Button>
                    )
                }
            />
            <PopoverContent side="right" className="w-44 p-1" align="start">
                {THEMES.map((option) => {
                    const OptionIcon = THEME_ICONS[option];
                    return (
                        <button
                            key={option}
                            type="button"
                            onClick={() => handleSelect(option)}
                            className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                        >
                            <Check className={cn('h-3.5 w-3.5', theme === option ? 'opacity-100' : 'opacity-0')} />
                            <OptionIcon className="h-3.5 w-3.5" />
                            {t(`theme.${option}`)}
                        </button>
                    );
                })}
            </PopoverContent>
        </Popover>
    );
}
