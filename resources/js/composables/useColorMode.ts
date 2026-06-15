import { readonly, ref } from 'vue';

type ColorMode = 'dark' | 'light';

const STORAGE_KEY = 'color-mode';

/**
 * Resolve the initial mode: an explicit stored choice wins, otherwise we
 * follow the OS preference, defaulting to dark for this dashboard.
 */
function resolveInitialMode(): ColorMode {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: light)').matches
        ? 'light'
        : 'dark';
}

// Shared singleton state so every component reflects the same mode.
const mode = ref<ColorMode>('dark');
let initialised = false;

function apply(value: ColorMode): void {
    document.documentElement.classList.toggle('dark', value === 'dark');
    mode.value = value;
}

export function useColorMode() {
    if (!initialised) {
        apply(resolveInitialMode());
        initialised = true;
    }

    function setMode(value: ColorMode): void {
        localStorage.setItem(STORAGE_KEY, value);
        apply(value);
    }

    function toggle(): void {
        setMode(mode.value === 'dark' ? 'light' : 'dark');
    }

    return {
        mode: readonly(mode),
        setMode,
        toggle,
    };
}
