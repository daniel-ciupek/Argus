<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import { useTaskFeed } from '@/composables/useTaskFeed';
import { type TaskRow, useTaskStore } from '@/stores/tasks';
import { type PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed, watch } from 'vue';
import { Play, Power, PowerOff, X } from '@lucide/vue';

const props = defineProps<{
    tasks: TaskRow[];
    filters: { agent: number | null; status: string | null };
    statuses: string[];
}>();

const { t } = useI18n();
const page = usePage<PageProps>();
const agents = computed(() => page.props.auth.agents ?? []);

const store = useTaskStore();
store.setTasks(props.tasks);

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

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

const statusVariant: Record<string, Variant> = {
    pending: 'neutral',
    running: 'info',
    completed: 'success',
    failed: 'danger',
};

const showAgentColumn = computed(() => agents.value.length > 1);

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
    return iso ? new Date(iso).toLocaleString('en-GB', { hour12: false }) : '—';
}

function sendTaskCommand(type: string, taskId: number, agentId: number): void {
    router.post(
        route('commands.store', agentId),
        { type, payload: { task_id: taskId } },
        { preserveScroll: true },
    );
}

const selectClasses =
    'rounded-md border-surface-300 bg-white py-1.5 pl-3 pr-8 font-mono text-sm text-surface-700 focus:border-accent-500 focus:ring-accent-500 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-200';
</script>

<template>
    <Head :title="t('tasks.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">{{ t('tasks.title') }}</h1>
                    <p class="font-mono text-xs text-surface-400">{{ t('tasks.subtitle') }}</p>
                </div>
                <Badge :variant="store.isConnected ? 'success' : 'neutral'" dot>
                    {{ store.isConnected ? t('common.live') : t('common.offline') }}
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
                <option value="">{{ t('common.allStatuses') }}</option>
                <option v-for="s in statuses" :key="s" :value="s">
                    {{ t(`tasks.status.${s}`, s) }}
                </option>
            </select>

            <select
                v-if="showAgentColumn"
                :value="filters.agent?.toString() ?? ''"
                :class="selectClasses"
                @change="applyFilters({ agent: ($event.target as HTMLSelectElement).value })"
            >
                <option value="">{{ t('common.agentAll') }}</option>
                <option v-for="a in agents" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
        </div>

        <!-- Empty state -->
        <div
            v-if="store.tasks.length === 0"
            class="rounded-card border border-dashed border-surface-300 py-16 text-center dark:border-surface-700"
        >
            <p class="text-sm text-surface-500">{{ t('tasks.noMatch') }}</p>
        </div>

        <!-- Table -->
        <div
            v-else
            class="overflow-hidden rounded-card border border-surface-200 bg-white shadow-card dark:border-surface-800 dark:bg-surface-900"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-surface-200 text-left font-mono text-xs uppercase tracking-wide text-surface-400 dark:border-surface-800">
                            <th class="px-5 py-2.5 font-medium">{{ t('tasks.task') }}</th>
                            <th v-if="showAgentColumn" class="px-5 py-2.5 font-medium">{{ t('common.agent') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ t('common.status') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ t('tasks.schedule') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ t('tasks.lastRun') }}</th>
                            <th class="px-5 py-2.5 font-medium">{{ t('tasks.nextRun') }}</th>
                            <th class="px-5 py-2.5 text-right font-medium">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800/60">
                        <tr
                            v-for="task in store.tasks"
                            :key="task.id"
                            class="transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/40"
                        >
                            <td class="px-5 py-3 font-medium text-surface-800 dark:text-surface-100">{{ task.name }}</td>
                            <td v-if="showAgentColumn" class="px-5 py-3 font-mono text-xs text-surface-500 dark:text-surface-400">
                                {{ task.agent_name }}
                            </td>
                            <td class="px-5 py-3">
                                <Badge :variant="statusVariant[task.status] ?? 'neutral'" dot>
                                    {{ t(`tasks.status.${task.status}`, task.status) }}
                                </Badge>
                            </td>
                            <td class="px-5 py-3 font-mono text-surface-600 dark:text-surface-300">{{ task.schedule ?? '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-surface-500 dark:text-surface-400">{{ formatDate(task.last_run_at) }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-surface-500 dark:text-surface-400">{{ formatDate(task.next_run_at) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        :title="t('commands.run')"
                                        class="rounded p-1 text-surface-400 transition hover:bg-accent-50 hover:text-accent-600 dark:hover:bg-accent-950 dark:hover:text-accent-400"
                                        @click="sendTaskCommand('task.run', task.id, task.agent_id)"
                                    >
                                        <Play class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="task.status === 'pending' || task.status === 'failed'"
                                        :title="t('commands.enable')"
                                        class="rounded p-1 text-surface-400 transition hover:bg-success-50 hover:text-success-600 dark:hover:bg-success-950 dark:hover:text-success-400"
                                        @click="sendTaskCommand('task.enable', task.id, task.agent_id)"
                                    >
                                        <Power class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="task.status === 'running' || task.status === 'completed'"
                                        :title="t('commands.disable')"
                                        class="rounded p-1 text-surface-400 transition hover:bg-warning-50 hover:text-warning-600 dark:hover:bg-warning-950 dark:hover:text-warning-400"
                                        @click="sendTaskCommand('task.disable', task.id, task.agent_id)"
                                    >
                                        <PowerOff class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="task.status === 'running'"
                                        :title="t('commands.cancel')"
                                        class="rounded p-1 text-surface-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-950 dark:hover:text-danger-400"
                                        @click="sendTaskCommand('task.cancel', task.id, task.agent_id)"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
