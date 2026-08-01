import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import commonId from '@/lang/id/common.json';
import commonEn from '@/lang/en/common.json';
import commonJa from '@/lang/ja/common.json';
import dashboardId from '@/lang/id/dashboard.json';
import dashboardEn from '@/lang/en/dashboard.json';
import dashboardJa from '@/lang/ja/dashboard.json';
import userId from '@/lang/id/user.json';
import userEn from '@/lang/en/user.json';
import userJa from '@/lang/ja/user.json';
import catalogId from '@/lang/id/catalog.json';
import catalogEn from '@/lang/en/catalog.json';
import catalogJa from '@/lang/ja/catalog.json';
import collectionId from '@/lang/id/collection.json';
import collectionEn from '@/lang/en/collection.json';
import collectionJa from '@/lang/ja/collection.json';
import adminId from '@/lang/id/admin.json';
import adminEn from '@/lang/en/admin.json';
import adminJa from '@/lang/ja/admin.json';

i18n.use(initReactI18next).init({
    resources: {
        id: {
            common: commonId, dashboard: dashboardId, user: userId, catalog: catalogId,
            collection: collectionId, admin: adminId,
        },
        en: {
            common: commonEn, dashboard: dashboardEn, user: userEn, catalog: catalogEn,
            collection: collectionEn, admin: adminEn,
        },
        ja: {
            common: commonJa, dashboard: dashboardJa, user: userJa, catalog: catalogJa,
            collection: collectionJa, admin: adminJa,
        },
    },
    lng: 'id',
    fallbackLng: 'id',
    defaultNS: 'common',
    ns: ['common', 'dashboard', 'user', 'catalog', 'collection', 'admin'],
    interpolation: { escapeValue: false },
    returnNull: false,
});

export default i18n;
