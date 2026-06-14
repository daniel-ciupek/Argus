<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useMcpFeed } from '@/composables/useMcpFeed';
import { type McpRow, useMcpStore } from '@/stores/mcp';
import { type PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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

const statusClass: Record<string, string> = {
    connected: 'bg-green-100 text-green-700',
    disabled: 'bg-gray-100 text-gray-700',
    error: 'bg-red-100 text-red-700',
};

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

function formatMeta(meta: Record<string, unknown> | null): string {
    return meta && Object.keys(meta).length > 0 ? JSON.stringify(meta) : '—';
}
</script>

<template>
    <Head title="MCP" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    MCP connections
                </h2>
                <span
                    :class="store.isConnected ? 'bg-green-500' : 'bg-gray-400'"
                    class="inline-block h-2.5 w-2.5 rounded-full"
                    :title="store.isConnected ? 'Live' : 'Offline'"
                />
            </div>
        </template>

        <div class="space-y-4 p-6">
            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select
                    :value="filters.status ?? ''"
                    class="rounded border border-gray-300 px-2 py-1 text-sm"
                    @change="
                        applyFilters({
                            status: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option value="">All statuses</option>
                    <option v-for="s in statuses" :key="s" :value="s">
                        {{ s }}
                    </option>
                </select>

                <select
                    v-if="agents.length > 1"
                    :value="filters.agent?.toString() ?? ''"
                    class="rounded border border-gray-300 px-2 py-1 text-sm"
                    @change="
                        applyFilters({
                            agent: ($event.target as HTMLSelectElement).value,
                        })
                    "
                >
                    <option value="">All agents</option>
                    <option v-for="a in agents" :key="a.id" :value="a.id">
                        {{ a.name }}
                    </option>
                </select>
            </div>

            <div
                v-if="store.connections.length === 0"
                class="rounded border border-dashed border-gray-300 py-16 text-center text-gray-400"
            >
                No MCP connections match the current filters.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="py-2">Connection</th>
                        <th v-if="agents.length > 1" class="py-2">Agent</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Meta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="connection in store.connections"
                        :key="connection.id"
                        class="border-b border-gray-100"
                    >
                        <td class="py-2 text-gray-800">{{ connection.name }}</td>
                        <td v-if="agents.length > 1" class="py-2 text-gray-600">
                            {{ connection.agent_name }}
                        </td>
                        <td class="py-2">
                            <span
                                class="rounded px-2 py-0.5 text-xs font-medium"
                                :class="
                                    statusClass[connection.status] ??
                                    'bg-gray-100 text-gray-700'
                                "
                            >
                                {{ connection.status }}
                            </span>
                        </td>
                        <td class="py-2 font-mono text-xs text-gray-500">
                            {{ formatMeta(connection.meta) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
