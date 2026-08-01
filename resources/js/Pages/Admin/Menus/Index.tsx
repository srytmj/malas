import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Menu as MenuIcon, Users } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import EmptyState from '@/Components/app/EmptyState';
import { buttonVariants } from '@/Components/ui/button';
import { SortableMenuList, type SortableMenuRow } from '@/Components/app/SortableMenuList';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

interface MenuGroup {
    parent_key: string;
    label: string;
    items: SortableMenuRow[];
}

interface Props extends PageProps {
    topLevel: SortableMenuRow[];
    groups: MenuGroup[];
}

export default function MenusIndex({ topLevel, groups }: Props) {
    const { t } = useTranslation('admin');
    const isEmpty = topLevel.length === 0 && groups.length === 0;

    return (
        <AdminLayout
            header={
                <PageHeader
                    title={t('menusPage.adminTitle')}
                    description={t('menusPage.adminDescription')}
                    actions={
                        <Link
                            href={route('admin.menus.user-sidebar')}
                            className={cn(buttonVariants({ variant: 'outline', size: 'sm' }))}
                        >
                            <Users className="mr-1.5 h-4 w-4" />
                            {t('menusPage.userSidebar')}
                        </Link>
                    }
                />
            }
        >
            <Head title={t('menusPage.adminTitle')} />
            {isEmpty ? (
                <EmptyState
                    title={t('menusPage.emptyTitle')}
                    description={t('menusPage.adminEmptyDescription')}
                    icon={MenuIcon}
                />
            ) : (
                <>
                    <SortableMenuList title={t('menusPage.mainMenu')} items={topLevel} />
                    {groups.map((group) => (
                        <SortableMenuList key={group.parent_key} title={group.label} items={group.items} />
                    ))}
                </>
            )}
        </AdminLayout>
    );
}
