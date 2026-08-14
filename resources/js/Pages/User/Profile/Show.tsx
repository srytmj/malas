import { PropsWithChildren, ReactNode, useEffect, useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ArrowUp, BookOpen, Layers, LogIn, Settings, UserMinus, UserPlus, Users } from 'lucide-react';
import UserLayout from '@/Layouts/UserLayout';
import PageHeader from '@/Components/app/PageHeader';
import { AdultBlurOverlay } from '@/Components/app/AdultBlurOverlay';
import { LoginMethodDialog } from '@/Components/app/LoginMethodDialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import { useTypeFilterOptions } from '@/lib/typeFilters';
import { PageProps } from '@/types';
import { type SeriesType } from '@/lib/types';

function PublicShell({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const { t } = useTranslation();
    const [loginOpen, setLoginOpen] = useState(false);

    return (
        <div className="min-h-screen bg-background">
            <header className="flex h-14 items-center justify-between border-b px-6">
                <span className="text-base font-bold tracking-tight">MALAS</span>
                <Button variant="outline" size="sm" onClick={() => setLoginOpen(true)}>
                    <LogIn className="mr-1.5 h-3.5 w-3.5" />
                    {t('nav.login')}
                </Button>
            </header>
            {header && <div className="border-b bg-background px-6 py-4">{header}</div>}
            <main className="mx-auto max-w-5xl p-6">{children}</main>
            <LoginMethodDialog open={loginOpen} onOpenChange={setLoginOpen} />
        </div>
    );
}

function BackToTop() {
    const { t } = useTranslation('user');
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        function onScroll() {
            setVisible(window.scrollY > 400);
        }
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    if (!visible) return null;

    return (
        <Button
            variant="secondary"
            size="icon"
            className="fixed bottom-24 right-6 z-40 h-10 w-10 rounded-full shadow-lg lg:bottom-6"
            onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
            aria-label={t('profile.backToTop')}
        >
            <ArrowUp className="h-4 w-4" />
        </Button>
    );
}

interface ProfileUser {
    id: string;
    name: string;
    avatar: string | null;
}

interface ProfileCollectionItem {
    series_id: string;
    series_slug: string;
    title_romaji: string;
    cover_url: string | null;
    type: SeriesType;
    is_adult: boolean;
}

interface GenreDistributionItem {
    genre: string;
    count: number;
    percent: number;
}

interface Props extends PageProps {
    profile_user: ProfileUser;
    is_owner: boolean;
    is_guest: boolean;
    is_following: boolean;
    followers_count: number;
    following_count: number;
    collections_count: number;
    collections: ProfileCollectionItem[];
    genre_distribution: GenreDistributionItem[];
    format_breakdown: Record<string, number>;
}

export default function ProfileShow({
    profile_user, is_owner, is_guest, is_following, followers_count, following_count, collections_count, collections, genre_distribution,
    format_breakdown,
}: Props) {
    const { t } = useTranslation('user');
    const typeFilterOptions = useTypeFilterOptions();
    const showCountOptions = [
        { value: '25', label: t('profile.show.25') },
        { value: '50', label: t('profile.show.50') },
        { value: '100', label: t('profile.show.100') },
        { value: 'all', label: t('profile.show.all') },
    ];
    const formatLabels: Record<string, string> = {
        physical: t('common:badge.format.physical'),
        ebook: t('common:badge.format.ebook'),
        online: t('common:badge.format.online'),
        webtoon: t('common:badge.format.webtoon'),
    };
    const Layout = is_guest ? PublicShell : UserLayout;
    const [following, setFollowing] = useState(is_following);
    const [processing, setProcessing] = useState(false);
    const [typeFilter, setTypeFilter] = useState('all');
    const [showCount, setShowCount] = useState('25');
    const [followLoginOpen, setFollowLoginOpen] = useState(false);

    const filteredCollections = useMemo(
        () => (typeFilter === 'all' ? collections : collections.filter((c) => c.type === typeFilter)),
        [collections, typeFilter],
    );

    const visibleCollections = showCount === 'all'
        ? filteredCollections
        : filteredCollections.slice(0, Number(showCount));

    const totalVolumes = Object.values(format_breakdown).reduce((sum, n) => sum + n, 0);

    function handleToggleFollow() {
        setProcessing(true);
        if (following) {
            router.delete(route('profile.unfollow', profile_user.id), {
                preserveScroll: true,
                onSuccess: () => setFollowing(false),
                onFinish: () => setProcessing(false),
            });
        } else {
            router.post(route('profile.follow', profile_user.id), {}, {
                preserveScroll: true,
                onSuccess: () => setFollowing(true),
                onFinish: () => setProcessing(false),
            });
        }
    }

    return (
        <Layout
            header={
                <PageHeader
                    title={profile_user.name}
                    description={t('profile.seriesInCollection', { count: collections_count })}
                    actions={
                        is_owner ? (
                            <Link href={route('settings.index')} className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                                <Settings className="h-4 w-4" />
                                {t('profile.manageProfile')}
                            </Link>
                        ) : is_guest ? (
                            <button
                                type="button"
                                onClick={() => setFollowLoginOpen(true)}
                                className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                            >
                                <UserPlus className="h-4 w-4" />
                                {t('common:profile.loginToFollow')}
                            </button>
                        ) : (
                            <Button variant={following ? 'outline' : 'default'} disabled={processing} onClick={handleToggleFollow}>
                                {following ? (
                                    <>
                                        <UserMinus className="mr-1.5 h-4 w-4" />
                                        {processing ? t('profile.processing') : t('profile.unfollow')}
                                    </>
                                ) : (
                                    <>
                                        <UserPlus className="mr-1.5 h-4 w-4" />
                                        {processing ? t('profile.processing') : t('profile.follow')}
                                    </>
                                )}
                            </Button>
                        )
                    }
                />
            }
        >
            <Head title={profile_user.name} />

            <div className="flex items-center gap-4">
                <Avatar className="h-16 w-16">
                    <AvatarImage src={profile_user.avatar ?? undefined} alt={profile_user.name} />
                    <AvatarFallback>{profile_user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                </Avatar>
                <div className="flex items-center gap-4 text-sm">
                    <div>
                        <p className="font-semibold">{followers_count}</p>
                        <p className="text-xs text-muted-foreground">{t('profile.followers')}</p>
                    </div>
                    <div>
                        <p className="font-semibold">{following_count}</p>
                        <p className="text-xs text-muted-foreground">{t('profile.following')}</p>
                    </div>
                    <div>
                        <p className="font-semibold">{collections_count}</p>
                        <p className="text-xs text-muted-foreground">{t('profile.collection')}</p>
                    </div>
                </div>
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                {genre_distribution.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Users className="h-4 w-4" />
                                {t('profile.genreTaste')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-1.5">
                                {genre_distribution.map((g) => (
                                    <Badge key={g.genre} variant="outline">
                                        {g.genre} · {g.percent}%
                                    </Badge>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {totalVolumes > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Layers className="h-4 w-4" />
                                {t('profile.collectionDetail')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div>
                                    <p className="text-lg font-bold">{totalVolumes}</p>
                                    <p className="text-xs text-muted-foreground">{t('profile.totalVolume')}</p>
                                </div>
                                {Object.entries(format_breakdown).map(([format, count]) => (
                                    <div key={format}>
                                        <p className="text-lg font-bold">{count}</p>
                                        <p className="text-xs text-muted-foreground">{formatLabels[format] ?? format}</p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            <div className="mt-8">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-base font-semibold">
                        {t('profile.collectionHeading', { visible: visibleCollections.length, total: filteredCollections.length })}
                    </h2>
                    <Select value={showCount} onValueChange={(v) => setShowCount(v ?? '25')}>
                        <SelectTrigger className="w-40">
                            <SelectValue>
                                {(value: string) => showCountOptions.find((o) => o.value === value)?.label ?? t('profile.show.25')}
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            {showCountOptions.map((o) => (
                                <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="mb-4 overflow-x-auto">
                    <ToggleGroup value={[typeFilter]} onValueChange={(vals) => setTypeFilter(vals[0] ?? 'all')} variant="outline" size="sm">
                        {typeFilterOptions.map((opt) => (
                            <ToggleGroupItem key={opt.value} value={opt.value}>
                                {opt.label}
                            </ToggleGroupItem>
                        ))}
                    </ToggleGroup>
                </div>
                {visibleCollections.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{t('profile.noneForType')}</p>
                ) : (
                    <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        {visibleCollections.map((c) => {
                            const cover = (
                                <AdultBlurOverlay
                                    isAdult={c.is_adult}
                                    className={cn(
                                        'aspect-[2/3] overflow-hidden rounded-lg bg-muted transition-opacity',
                                        !is_guest && 'hover:opacity-80',
                                    )}
                                >
                                    {c.cover_url ? (
                                        <img src={c.cover_url} alt={c.title_romaji} className="h-full w-full object-cover" />
                                    ) : (
                                        <div className="flex h-full items-center justify-center">
                                            <BookOpen className="h-6 w-6 text-muted-foreground/40" />
                                        </div>
                                    )}
                                </AdultBlurOverlay>
                            );

                            return is_guest ? (
                                <div key={c.series_id} title={c.title_romaji}>{cover}</div>
                            ) : (
                                <Link key={c.series_id} href={route('catalog.show', c.series_slug)}>{cover}</Link>
                            );
                        })}
                    </div>
                )}
            </div>

            {!is_guest && <BackToTop />}
            {is_guest && <LoginMethodDialog open={followLoginOpen} onOpenChange={setFollowLoginOpen} />}
        </Layout>
    );
}
