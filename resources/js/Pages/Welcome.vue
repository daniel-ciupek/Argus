<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useColorMode } from '@/composables/useColorMode';
import { useLocale } from '@/composables/useLocale';
import {
    Radar,
    Sun,
    Moon,
    ScrollText,
    CircleDollarSign,
    ListChecks,
    Cable,
    Wallet,
    Zap,
    ArrowRight,
    type LucideIcon,
} from '@lucide/vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();

const { mode, toggle } = useColorMode();
const { locale, toggle: toggleLocale } = useLocale();

type Feature = { title: string; description: string; icon: LucideIcon };

const features: Feature[] = [
    { title: 'Live logs', description: 'Stream agent events in real time over WebSockets with level filtering.', icon: ScrollText },
    { title: 'Costs & tokens', description: 'Track spend and token usage per model with daily charts.', icon: CircleDollarSign },
    { title: 'Tasks', description: 'Monitor scheduled jobs, their status and next run at a glance.', icon: ListChecks },
    { title: 'MCP connections', description: 'See the health of every Model Context Protocol server.', icon: Cable },
    { title: 'Budgets & alerts', description: 'Set spend limits and get alerted when a threshold is crossed.', icon: Wallet },
    { title: 'Realtime by default', description: 'Powered by Laravel Reverb — the UI updates without a refresh.', icon: Zap },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-surface-50 text-surface-900 dark:bg-surface-950 dark:text-surface-100">
        <!-- Nav -->
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-card bg-accent-500 text-white">
                    <Radar class="h-5 w-5" :stroke-width="2.25" />
                </span>
                <span class="leading-tight">
                    <span class="block text-sm font-semibold tracking-tight">Hermes</span>
                    <span class="block font-mono text-[10px] uppercase tracking-widest text-surface-400">cockpit</span>
                </span>
            </div>

            <nav class="flex items-center gap-2">
                <!-- Locale toggle -->
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1.5 font-mono text-xs font-medium text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                    :aria-label="locale === 'pl' ? 'Switch to English' : 'Przełącz na polski'"
                    @click="toggleLocale"
                >
                    {{ locale === 'pl' ? 'EN' : 'PL' }}
                </button>

                <!-- Theme toggle -->
                <button
                    type="button"
                    class="rounded-md p-2 text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                    :aria-label="mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="toggle"
                >
                    <Sun v-if="mode === 'dark'" class="h-5 w-5" />
                    <Moon v-else class="h-5 w-5" />
                </button>

                <template v-if="canLogin">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-accent-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-500"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-md px-4 py-2 text-sm font-medium text-surface-600 transition-colors hover:text-surface-900 dark:text-surface-300 dark:hover:text-surface-100"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-md bg-accent-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-500"
                        >
                            Get started
                        </Link>
                    </template>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-6xl px-6 pb-16 pt-16 text-center sm:pt-24">
            <span class="inline-flex items-center gap-2 rounded-full border border-surface-200 bg-white px-3 py-1 font-mono text-xs text-surface-500 dark:border-surface-800 dark:bg-surface-900 dark:text-surface-400">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-500 opacity-75" />
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-success-500" />
                </span>
                self-hosted · realtime
            </span>

            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
                Mission control for your
                <span class="text-accent-500">AI agents</span>
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-surface-500 dark:text-surface-400">
                A self-hosted dashboard to supervise and control AI agents — live logs, token costs,
                scheduled tasks, MCP connections and budget alerts, all in one place.
            </p>

            <div class="mt-8 flex items-center justify-center gap-3">
                <Link
                    v-if="canRegister && !$page.props.auth.user"
                    :href="route('register')"
                    class="inline-flex items-center gap-2 rounded-md bg-accent-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-500"
                >
                    Get started
                    <ArrowRight class="h-4 w-4" />
                </Link>
                <Link
                    v-if="canLogin && !$page.props.auth.user"
                    :href="route('login')"
                    class="inline-flex items-center rounded-md border border-surface-300 px-5 py-2.5 text-sm font-medium text-surface-700 transition-colors hover:bg-surface-100 dark:border-surface-700 dark:text-surface-200 dark:hover:bg-surface-800"
                >
                    Log in
                </Link>
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-2 rounded-md bg-accent-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-500"
                >
                    Open dashboard
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-6xl px-6 pb-20">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-card border border-surface-200 bg-white p-5 shadow-card transition-colors hover:border-accent-500/40 dark:border-surface-800 dark:bg-surface-900"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-card bg-accent-500/10 text-accent-500">
                        <component :is="feature.icon" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 font-semibold">{{ feature.title }}</h3>
                    <p class="mt-1 text-sm text-surface-500 dark:text-surface-400">{{ feature.description }}</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-surface-200 dark:border-surface-800">
            <div class="mx-auto max-w-6xl px-6 py-6 text-center font-mono text-xs text-surface-400">
                Hermes Cockpit · built with Laravel {{ laravelVersion }} · PHP {{ phpVersion }} · Reverb
            </div>
        </footer>
    </div>
</template>
