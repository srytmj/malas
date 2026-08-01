import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Lock, Search, Users } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Input } from '@/Components/ui/input';
import { PageProps } from '@/types';

interface DirectoryUser {
    id: string;
    name: string;
    username: string | null;
    avatar: string | null;
    is_profile_public: boolean;
}

interface Props extends PageProps {
    users: DirectoryUser[];
    filters: { q: string };
}

export default function DirectoryIndex({ users, filters }: Props) {
    const { t } = useTranslation('user');
    const [search, setSearch] = useState(filters.q);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            router.get(
                route('directory.index'),
                { q: search.trim() || undefined },
                { preserveState: true, replace: true },
            );
        }, 400);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [search]);

    return (
        <UserLayout
            header={
                <PageHeader
                    title={t('directory.title')}
                    description={t('directory.description')}
                />
            }
        >
            <Head title={t('directory.title')} />

            <div className="relative mb-6 max-w-lg">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    className="pl-9"
                    placeholder={t('directory.searchPlaceholder')}
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    autoFocus
                />
            </div>

            {!filters.q.trim() ? (
                <div className="flex flex-col items-center justify-center py-24 text-muted-foreground">
                    <Users className="mb-4 h-12 w-12 opacity-30" />
                    <p className="text-sm">{t('directory.promptSearch')}</p>
                </div>
            ) : users.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-24 text-muted-foreground">
                    <Users className="mb-4 h-12 w-12 opacity-30" />
                    <p className="text-sm">{t('directory.noResults')}</p>
                </div>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {users.map((u) => {
                        const content = (
                            <>
                                <Avatar className="h-10 w-10 shrink-0">
                                    <AvatarImage src={u.avatar ?? undefined} alt={u.name} />
                                    <AvatarFallback>{u.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                                </Avatar>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">{u.name}</p>
                                    {u.is_profile_public ? (
                                        u.username && (
                                            <p className="truncate text-xs text-muted-foreground">@{u.username}</p>
                                        )
                                    ) : (
                                        <p className="truncate text-xs text-muted-foreground">{t('directory.privateProfile')}</p>
                                    )}
                                </div>
                                {!u.is_profile_public && (
                                    <Lock className="ml-auto h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                )}
                            </>
                        );

                        return u.is_profile_public ? (
                            <Link
                                key={u.id}
                                href={route('profile.show', u.username ?? u.id)}
                                className="flex items-center gap-3 rounded-lg border bg-card p-3 transition-shadow hover:shadow-sm"
                            >
                                {content}
                            </Link>
                        ) : (
                            <div
                                key={u.id}
                                className="flex cursor-not-allowed items-center gap-3 rounded-lg border bg-card p-3 opacity-50"
                            >
                                {content}
                            </div>
                        );
                    })}
                </div>
            )}
        </UserLayout>
    );
}
