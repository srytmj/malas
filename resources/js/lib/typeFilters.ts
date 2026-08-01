import { useTranslation } from 'react-i18next';

/**
 * Opsi Segmented Control tipe series, dipakai di semua halaman list series/koleksi
 * (lihat aturan "filter tipe wajib" di CLAUDE.md). Satu sumber terjemahan, bukan
 * di-duplikasi per halaman.
 */
export function useTypeFilterOptions(): { value: string; label: string }[] {
    const { t } = useTranslation();
    return [
        { value: 'all', label: t('common.allTypes') },
        { value: 'manga', label: t('common.manga') },
        { value: 'novel', label: t('common.lightNovel') },
        { value: 'one_shot', label: t('common.oneShot') },
        { value: 'doujinshi', label: t('common.doujinshi') },
        { value: 'manhwa', label: t('common.manhwa') },
        { value: 'manhua', label: t('common.manhua') },
    ];
}
