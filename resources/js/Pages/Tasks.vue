<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useTaskFeed } from '@/composables/useTaskFeed';
import { type TaskRow, useTaskStore } from '@/stores/tasks';
import { type PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    tasks: TaskRow[];
    filters: { agent: number | null; status: string | null };
    statuses: string[];
}>();

const page = usePage<PageProps>();
const agents = computed(() => page.props.auth.agents ?? []);

const store = useTaskStore();
store.setTasks(props.tasks);

// Re-seed when server-side filters change the loaded list.
watch(
    () => props.tasks,
    (list) => store.setTasks(list),
);

const agentNameById = computed<Record<number, string>>(() =>
    Object.fromEntries(agents.value.map((a) => [a.id, a.name])),
);

useTaskFeed(
    agents.value.map((a) => a.id),
    agentNameById.value,
);

const statusClass: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-700',
    running: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};

function applyFilters(patch: Partial<{ status: string; agent: string }>): void {
    const query: Record<string, string> = {};
    const status = patch.status ?? props.filters.status ?? '';
    const agent = patch.agent ?? (props.filters.agent?.toString() ?? '');
    if (status) query.status = status;
    if (agent) query.agent = agent;

    router.get('/tasks', query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString() : '—';
}
</script>

<template>
    <Head title="Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Tasks
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
                v-if="store.tasks.length === 0"
                class="rounded border border-dashed border-gray-300 py-16 text-center text-gray-400"
            >
                No tasks match the current filters.
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="py-2">Task</th>
                        <th v-if="agents.length > 1" class="py-2">Agent</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Schedule</th>
                        <th class="py-2">Last run</th>
                        <th class="py-2">Next run</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="task in store.tasks"
                        :key="task.id"
                        class="border-b border-gray-100"
                    >
                        <td class="py-2 text-gray-800">{{ task.name }}</td>
                        <td v-if="agents.length > 1" class="py-2 text-gray-600">
                            {{ task.agent_name }}
                        </td>
                        <td class="py-2">
                            <span
                                class="rounded px-2 py-0.5 text-xs font-medium"
                                :class="
                                    statusClass[task.status] ??
                                    'bg-gray-100 text-gray-700'
                                "
                            >
                                {{ task.status }}
                            </span>
                        </td>
                        <td class="py-2 font-mono text-gray-600">
                            {{ task.schedule ?? '—' }}
                        </td>
                        <td class="py-2 text-gray-600">
                            {{ formatDate(task.last_run_at) }}
                        </td>
                        <td class="py-2 text-gray-600">
                            {{ formatDate(task.next_run_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
