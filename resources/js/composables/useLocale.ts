import { readonly, ref } from 'vue';
import { i18n, type Locale } from '@/i18n';

const STORAGE_KEY = 'locale';

const locale = ref<Locale>('pl');
let initialised = false;

function apply(value: Locale): void {
    locale.value = value;
    (i18n.global.locale as unknown as { value: Locale }).value = value;
    document.documentElement.lang = value;
}

export function useLocale() {
    if (!initialised) {
        const stored = localStorage.getItem(STORAGE_KEY);
        apply(stored === 'pl' || stored === 'en' ? stored : 'pl');
        initialised = true;
    }

    function setLocale(value: Locale): void {
        localStorage.setItem(STORAGE_KEY, value);
        apply(value);
    }

    function toggle(): void {
        setLocale(locale.value === 'pl' ? 'en' : 'pl');
    }

    return {
        locale: readonly(locale),
        setLocale,
        toggle,
    };
}
