<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Radar } from '@lucide/vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-surface-50 px-4 py-10 dark:bg-surface-950"
    >
        <!-- Brand -->
        <Link href="/" class="mb-6 flex items-center gap-2.5">
            <span class="flex h-10 w-10 items-center justify-center rounded-card bg-accent-500 text-white">
                <Radar class="h-6 w-6" :stroke-width="2.25" />
            </span>
            <span class="leading-tight">
                <span class="block text-base font-semibold tracking-tight text-surface-900 dark:text-surface-50">Hermes</span>
                <span class="block font-mono text-[10px] uppercase tracking-widest text-surface-400">cockpit</span>
            </span>
        </Link>

        <div
            class="w-full overflow-hidden rounded-card border border-surface-200 bg-white px-6 py-6 shadow-card dark:border-surface-800 dark:bg-surface-900 sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
