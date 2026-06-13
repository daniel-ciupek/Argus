import pluginVue from 'eslint-plugin-vue';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting';

export default defineConfigWithVueTs(
    {
        name: 'app/files-to-lint',
        files: ['resources/js/**/*.{ts,mts,tsx,vue}'],
    },
    {
        name: 'app/files-to-ignore',
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
        ],
    },
    pluginVue.configs['flat/essential'],
    vueTsConfigs.recommended,
    skipFormatting,
    {
        // Inertia mapuje nazwy stron 1:1 na pliki, a Breeze dostarcza jednowyrazowe
        // komponenty (Dropdown, Modal...). Jednowyrazowe nazwy są tu konwencją.
        name: 'app/vue-single-word-names',
        files: ['resources/js/**/*.vue'],
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
);
