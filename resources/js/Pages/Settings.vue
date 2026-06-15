<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">Settings</h1>
                <p class="font-mono text-xs text-surface-400">agents &amp; application configuration</p>
            </div>
        </template>

        <div
            class="flex flex-col items-center justify-center gap-2 rounded-card border border-dashed border-surface-300 py-20 text-center dark:border-surface-700"
        >
            <Settings2 class="h-8 w-8 text-surface-300 dark:text-surface-600" />
            <p class="text-sm text-surface-500">Settings are coming soon</p>
            <p class="font-mono text-xs text-surface-400">agent management &amp; app preferences will live here</p>
        </div>
    </AuthenticatedLayout>
</template>
