<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('settings.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">{{ t('settings.title') }}</h1>
                <p class="font-mono text-xs text-surface-400">{{ t('settings.subtitle') }}</p>
            </div>
        </template>

        <div
            class="flex flex-col items-center justify-center gap-2 rounded-card border border-dashed border-surface-300 py-20 text-center dark:border-surface-700"
        >
            <Settings2 class="h-8 w-8 text-surface-300 dark:text-surface-600" />
            <p class="text-sm text-surface-500">{{ t('settings.comingSoon') }}</p>
            <p class="font-mono text-xs text-surface-400">{{ t('settings.comingSoonSub') }}</p>
        </div>
    </AuthenticatedLayout>
</template>
