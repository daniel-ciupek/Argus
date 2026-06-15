<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import { useMcpFeed } from '@/composables/useMcpFeed';
import { type McpRow, useMcpStore } from '@/stores/mcp';
import { type PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Cable } from '@lucide/vue';

const props = defineProps<{
    connections: McpRow[];
    filters: { agent: number | null; status: string | null };
    statuses: string[];
}>();

const page = usePage<PageProps>();
const agents = computed(() => page.props.auth.agents ?? []);

const store = useMcpStore();
store.setConnections(props.connections);

// Re-seed when server-side filters change the loaded list.
watch(
    () => props.connections,
    (list) => store.setConnections(list),
);

const agentNameById = computed<Record<number, string>>(() =>
    Object.fromEntries(agents.value.map((a) => [a.id, a.name])),
);

useMcpFeed(
    agents.value.map((a) => a.id),
    agentNameById.value,
);

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

const statusVariant: Record<string, Variant> = {
    connected: 'success',
    disabled: 'neutral',
    error: 'danger',
};

// Icon tint per status, so the connection card reads at a glance.
const statusIconClass: Record<string, string> = {
    connected: 'bg-success-500/10 text-success-500',
    disabled: 'bg-surface-100 text-surface-400 dark:bg-surface-800',
    error: 'bg-danger-500/10 text-danger-500',
};

const showAgent = computed(() => agents.value.length > 1);

function applyFilters(patch: Partial<{ status: string; agent: string }>): void {
    const query: Record<string, string> = {};
    const status = patch.status ?? props.filters.status ?? '';
    const agent = patch.agent ?? (props.filters.agent?.toString() ?? '');
    if (status) query.status = status;
    if (agent) query.agent = agent;

    router.get('/mcp', query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function formatMeta(meta: Record<string, unknown> | null): string | null {
    return meta && Object.keys(meta).length > 0 ? JSON.stringify(meta, null, 2) : null;
}

const selectClasses =
    'rounded-md border-surface-300 bg-white py-1.5 pl-3 pr-8 font-mono text-sm text-surface-700 focus:border-accent-500 focus:ring-accent-500 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-200';
</script>

<template>
    <Head title="MCP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">MCP connections</h1>
                    <p class="font-mono text-xs text-surface-400">model context protocol servers</p>
                </div>
                <Badge :variant="store.isConnected ? 'success' : 'neutral'" dot>
                    {{ store.isConnected ? 'live' : 'offline' }}
                </Badge>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-4 flex flex-wrap gap-3">
            <select
                :value="filters.status ?? ''"
                :class="selectClasses"
                @change="applyFilters({ status: ($event.target as HTMLSelectElement).value })"
            >
                <option value="">All statuses</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>

            <select
                v-if="showAgent"
                :value="filters.agent?.toString() ?? ''"
                :class="selectClasses"
                @change="applyFilters({ agent: ($event.target as HTMLSelectElement).value })"
            >
                <option value="">All agents</option>
                <option v-for="a in agents" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
        </div>

        <!-- Empty state -->
        <div
            v-if="store.connections.length === 0"
            class="rounded-card border border-dashed border-surface-300 py-16 text-center dark:border-surface-700"
        >
            <p class="text-sm text-surface-500">No MCP connections match the current filters.</p>
        </div>

        <!-- Connection cards -->
        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="connection in store.connections"
                :key="connection.id"
                class="flex flex-col rounded-card border border-surface-200 bg-white p-4 shadow-card dark:border-surface-800 dark:bg-surface-900"
            >
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-card"
                        :class="statusIconClass[connection.status] ?? statusIconClass.disabled"
                    >
                        <Cable class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-surface-800 dark:text-surface-100">{{ connection.name }}</p>
                        <p v-if="showAgent" class="truncate font-mono text-xs text-surface-400">
                            {{ connection.agent_name }}
                        </p>
                    </div>
                    <Badge :variant="statusVariant[connection.status] ?? 'neutral'" dot>
                        {{ connection.status }}
                    </Badge>
                </div>

                <pre
                    v-if="formatMeta(connection.meta)"
                    class="mt-3 max-h-32 overflow-auto rounded-md bg-surface-50 p-2.5 font-mono text-xs leading-relaxed text-surface-500 dark:bg-surface-950/40 dark:text-surface-400"
                >{{ formatMeta(connection.meta) }}</pre>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
