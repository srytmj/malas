import { useState } from 'react';
import { ChevronsUpDown, X } from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList, CommandSeparator,
} from '@/Components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

interface GenreGroup {
    label: string;
    options: string[];
}

interface GenreMultiSelectProps {
    value: string[];
    onChange: (next: string[]) => void;
    groups: GenreGroup[];
    placeholder: string;
    searchPlaceholder: string;
    emptyText: string;
    clearLabel: string;
    selectedLabel: (count: number) => string;
}

/**
 * Filter genre yang bisa diketik (fuzzy match lewat cmdk) + pilih lebih dari satu genre sekaligus.
 * Dipakai buat filter Katalog user — genre terpilih ditampilkan sebagai badge di bawah trigger
 * supaya kelihatan tanpa harus buka popover lagi.
 */
export function GenreMultiSelect({
    value, onChange, groups, placeholder, searchPlaceholder, emptyText, clearLabel, selectedLabel,
}: GenreMultiSelectProps) {
    const [open, setOpen] = useState(false);

    function toggle(genre: string) {
        onChange(value.includes(genre) ? value.filter((v) => v !== genre) : [...value, genre]);
    }

    return (
        <div className="flex flex-col gap-1.5">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger
                    render={<Button type="button" variant="outline" size="sm" className="w-44 justify-between font-normal" />}
                >
                    <span className="truncate">{value.length > 0 ? selectedLabel(value.length) : placeholder}</span>
                    <ChevronsUpDown className="ml-1 h-4 w-4 shrink-0 opacity-50" />
                </PopoverTrigger>
                <PopoverContent className="w-64 p-0" align="start">
                    <Command>
                        <CommandInput placeholder={searchPlaceholder} />
                        <CommandList>
                            <CommandEmpty>{emptyText}</CommandEmpty>
                            {groups.map((group, idx) => group.options.length > 0 && (
                                <div key={group.label}>
                                    {idx > 0 && <CommandSeparator />}
                                    <CommandGroup heading={group.label}>
                                        {group.options.map((g) => (
                                            <CommandItem key={g} value={g} onSelect={() => toggle(g)}>
                                                <Checkbox checked={value.includes(g)} onCheckedChange={() => toggle(g)} className="pointer-events-none" />
                                                <span>{g}</span>
                                            </CommandItem>
                                        ))}
                                    </CommandGroup>
                                </div>
                            ))}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            {value.length > 0 && (
                <div className="flex max-w-xs flex-wrap items-center gap-1">
                    {value.map((g) => (
                        <Badge key={g} variant="secondary" className="gap-1 pr-1">
                            {g}
                            <button
                                type="button"
                                onClick={() => toggle(g)}
                                aria-label={g}
                                className="rounded-full hover:bg-muted-foreground/20"
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))}
                    <button
                        type="button"
                        onClick={() => onChange([])}
                        className="text-xs text-muted-foreground underline-offset-2 hover:underline"
                    >
                        {clearLabel}
                    </button>
                </div>
            )}
        </div>
    );
}
