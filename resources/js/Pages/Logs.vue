<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import { useEventLog } from '@/composables/useEventLog';
import { useEventLogStore } from '@/stores/eventLog';
import { type PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Trash2, Radio } from '@lucide/vue';

const page = usePage<PageProps>();
const agents = computed(() => page.props.auth.agents ?? []);

const selectedAgentId = ref<number | null>(agents.value[0]?.id ?? null);

const store = useEventLogStore();

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

const levelVariant: Record<string, Variant> = {
    debug: 'neutral',
    info: 'info',
    warning: 'warning',
    error: 'danger',
};

const typeVariant: Record<string, Variant> = {
    error: 'danger',
    task_run: 'info',
    tool_call: 'accent',
    log: 'neutral',
};

// Client-side level filter (presentation only — no backend involvement).
const levelFilter = ref<string | null>(null);
const levelOptions = ['info', 'warning', 'error'];

const filteredEvents = computed(() =>
    levelFilter.value === null
        ? store.events
        : store.events.filter((e) => e.level === levelFilter.value),
);

watch(
    selectedAgentId,
    () => {
        store.clear();
    },
    { immediate: false },
);

if (selectedAgentId.value !== null) {
    useEventLog(selectedAgentId.value);
}

function formatTime(iso: string | null): string {
    if (iso === null) return '--:--:--';
    return new Date(iso).toLocaleTimeString('en-GB', { hour12: false });
}
</script>

<template>
    <Head title="Logs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">Live Logs</h1>
                    <p class="font-mono text-xs text-surface-400">realtime event stream</p>
                </div>
                <Badge :variant="store.isConnected ? 'success' : 'neutral'" dot>
                    {{ store.isConnected ? 'connected' : 'offline' }}
                </Badge>
            </div>
        </template>

        <!-- No agents -->
        <div
            v-if="agents.length === 0"
            class="rounded-card border border-surface-200 bg-white p-10 text-center shadow-card dark:border-surface-800 dark:bg-surface-900"
        >
            <p class="text-sm text-surface-500">No active agents.</p>
            <p class="font-mono text-xs text-surface-400">create an agent to start streaming events</p>
        </div>

        <div v-else class="space-y-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Agent selector -->
                <select
                    v-if="agents.length > 1"
                    v-model="selectedAgentId"
                    class="rounded-md border-surface-300 bg-white py-1.5 pl-3 pr-8 font-mono text-sm text-surface-700 focus:border-accent-500 focus:ring-accent-500 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-200"
                >
                    <option v-for="a in agents" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>

                <!-- Level filter chips -->
                <div class="flex items-center gap-1 rounded-md border border-surface-200 bg-white p-0.5 dark:border-surface-800 dark:bg-surface-900">
                    <button
                        type="button"
                        class="rounded px-2.5 py-1 font-mono text-xs transition-colors"
                        :class="levelFilter === null
                            ? 'bg-accent-500/10 text-accent-600 dark:text-accent-400'
                            : 'text-surface-500 hover:text-surface-800 dark:hover:text-surface-200'"
                        @click="levelFilter = null"
                    >
                        all
                    </button>
                    <button
                        v-for="lvl in levelOptions"
                        :key="lvl"
                        type="button"
                        class="rounded px-2.5 py-1 font-mono text-xs transition-colors"
                        :class="levelFilter === lvl
                            ? 'bg-accent-500/10 text-accent-600 dark:text-accent-400'
                            : 'text-surface-500 hover:text-surface-800 dark:hover:text-surface-200'"
                        @click="levelFilter = lvl"
                    >
                        {{ lvl }}
                    </button>
                </div>

                <span class="font-mono text-xs text-surface-400">
                    {{ filteredEvents.length }} event(s)
                </span>

                <button
                    type="button"
                    class="ml-auto flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-800 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                    @click="store.clear()"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    Clear
                </button>
            </div>

            <!-- Console panel -->
            <div class="overflow-hidden rounded-card border border-surface-200 bg-white shadow-card dark:border-surface-800 dark:bg-surface-900">
                <!-- Terminal title bar -->
                <div class="flex items-center gap-2 border-b border-surface-200 bg-surface-50 px-4 py-2 dark:border-surface-800 dark:bg-surface-950/40">
                    <span class="h-3 w-3 rounded-full bg-danger-500/70" />
                    <span class="h-3 w-3 rounded-full bg-warning-500/70" />
                    <span class="h-3 w-3 rounded-full bg-success-500/70" />
                    <span class="ml-2 flex items-center gap-1.5 font-mono text-xs text-surface-400">
                        <Radio class="h-3.5 w-3.5" :class="store.isConnected ? 'text-success-500' : 'text-surface-400'" />
                        agent stream · newest first
                    </span>
                </div>

                <!-- Waiting state -->
                <div
                    v-if="filteredEvents.length === 0"
                    class="flex flex-col items-center justify-center gap-2 py-16 text-center"
                >
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-accent-500 opacity-75" />
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-accent-500" />
                    </span>
                    <p class="font-mono text-sm text-surface-400">waiting for events…</p>
                </div>

                <!-- Log rows -->
                <ul v-else class="max-h-[calc(100vh-18rem)] divide-y divide-surface-100 overflow-y-auto font-mono text-sm dark:divide-surface-800/60">
                    <li
                        v-for="event in filteredEvents"
                        :key="event.id"
                        class="flex items-center gap-3 px-4 py-2 transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/40"
                    >
                        <span class="shrink-0 text-xs text-surface-400">{{ formatTime(event.occurred_at) }}</span>
                        <span class="w-[88px] shrink-0">
                            <Badge :variant="levelVariant[event.level] ?? 'neutral'">{{ event.level || 'log' }}</Badge>
                        </span>
                        <span class="hidden w-[84px] shrink-0 text-xs text-surface-400 sm:inline">[{{ event.type }}]</span>
                        <span
                            class="min-w-0 flex-1 break-all"
                            :class="(typeVariant[event.type] ?? 'neutral') === 'danger' || event.level === 'error'
                                ? 'text-danger-600 dark:text-danger-400'
                                : 'text-surface-700 dark:text-surface-200'"
                        >
                            {{ event.message }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
