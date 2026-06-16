import { createI18n } from 'vue-i18n';
import pl from './pl';
import en from './en';

export type Locale = 'pl' | 'en';

const STORAGE_KEY = 'locale';

function getStoredLocale(): Locale {
    if (typeof localStorage === 'undefined') return 'pl';
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored === 'pl' || stored === 'en' ? stored : 'pl';
}

export const i18n = createI18n({
    legacy: false,
    locale: getStoredLocale(),
    fallbackLocale: 'en',
    messages: { pl, en },
});
