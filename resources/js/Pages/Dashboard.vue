<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import {
    CircleDollarSign,
    Activity,
    Bot,
    Cable,
    ArrowUpRight,
    Inbox,
    type LucideIcon,
} from '@lucide/vue';

type Stats = {
    cost: string;
    events: number;
    errors: number;
    agents: { active: number; total: number };
    mcp: { connected: number; total: number };
    tasks: { failed: number; total: number };
};

type RecentEvent = {
    id: number;
    type: string;
    level: string | null;
    message: string | null;
    agent_name: string;
    occurred_at: string | null;
};

const props = defineProps<{
    periodDays: number;
    stats: Stats;
    recent: RecentEvent[];
}>();

const { t } = useI18n();

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

type Kpi = {
    label: string;
    value: string;
    hint: string;
    hintVariant: Variant;
    icon: LucideIcon;
    route: string;
};

const usd = (value: string): string =>
    `$${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const kpis = computed<Kpi[]>(() => [
    {
        label: t('dashboard.totalCost'),
        value: usd(props.stats.cost),
        hint: t('dashboard.lastNDays', { n: props.periodDays }),
        hintVariant: 'neutral',
        icon: CircleDollarSign,
        route: 'costs',
    },
    {
        label: t('dashboard.events'),
        value: props.stats.events.toLocaleString('en-US'),
        hint: props.stats.errors > 0
            ? t('dashboard.errorsCount', { n: props.stats.errors })
            : t('dashboard.noErrors'),
        hintVariant: props.stats.errors > 0 ? 'danger' : 'success',
        icon: Activity,
        route: 'logs',
    },
    {
        label: t('dashboard.agents'),
        value: `${props.stats.agents.active}/${props.stats.agents.total}`,
        hint: t('dashboard.active'),
        hintVariant: props.stats.agents.active > 0 ? 'success' : 'neutral',
        icon: Bot,
        route: 'dashboard',
    },
    {
        label: t('dashboard.mcpConnections'),
        value: `${props.stats.mcp.connected}/${props.stats.mcp.total}`,
        hint: t('dashboard.connected'),
        hintVariant: props.stats.mcp.connected === props.stats.mcp.total && props.stats.mcp.total > 0
            ? 'success'
            : props.stats.mcp.connected === 0 && props.stats.mcp.total > 0
                ? 'danger'
                : 'neutral',
        icon: Cable,
        route: 'mcp',
    },
]);

const typeVariant: Record<string, Variant> = {
    error: 'danger',
    task_run: 'info',
    tool_call: 'accent',
    log: 'neutral',
};

const levelVariant: Record<string, Variant> = {
    error: 'danger',
    warning: 'warning',
    info: 'info',
};

function relativeTime(iso: string | null): string {
    if (iso === null) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    const s = Math.round(diff / 1000);
    if (s < 60) return t('dashboard.justNow');
    const m = Math.round(s / 60);
    if (m < 60) return t('dashboard.minutesAgo', { n: m });
    const h = Math.round(m / 60);
    if (h < 24) return t('dashboard.hoursAgo', { n: h });
    return t('dashboard.daysAgo', { n: Math.round(h / 24) });
}
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">{{ t('dashboard.title') }}</h1>
                <p class="font-mono text-xs text-surface-400">{{ t('dashboard.subtitle') }}</p>
            </div>
        </template>

        <!-- KPI grid -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="kpi in kpis"
                :key="kpi.label"
                :href="route(kpi.route)"
                class="group rounded-card border border-surface-200 bg-white p-5 shadow-card transition-colors hover:border-accent-500/40 dark:border-surface-800 dark:bg-surface-900"
            >
                <div class="flex items-start justify-between">
                    <span class="flex h-9 w-9 items-center justify-center rounded-card bg-accent-500/10 text-accent-500">
                        <component :is="kpi.icon" class="h-5 w-5" />
                    </span>
                    <ArrowUpRight
                        class="h-4 w-4 text-surface-300 transition-colors group-hover:text-accent-500 dark:text-surface-600"
                    />
                </div>
                <p class="mt-4 text-xs font-medium uppercase tracking-wide text-surface-400">{{ kpi.label }}</p>
                <p class="mt-1 font-mono text-2xl font-semibold tracking-tight">{{ kpi.value }}</p>
                <div class="mt-2">
                    <Badge :variant="kpi.hintVariant" dot>{{ kpi.hint }}</Badge>
                </div>
            </Link>
        </div>

        <!-- Recent activity -->
        <section
            class="mt-6 overflow-hidden rounded-card border border-surface-200 bg-white shadow-card dark:border-surface-800 dark:bg-surface-900"
        >
            <div class="flex items-center justify-between border-b border-surface-200 px-5 py-3.5 dark:border-surface-800">
                <h2 class="text-sm font-semibold">{{ t('dashboard.recentActivity') }}</h2>
                <Link
                    :href="route('logs')"
                    class="font-mono text-xs text-accent-600 hover:underline dark:text-accent-400"
                >
                    {{ t('dashboard.viewAll') }}
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="recent.length === 0" class="flex flex-col items-center justify-center gap-2 px-6 py-14 text-center">
                <Inbox class="h-8 w-8 text-surface-300 dark:text-surface-600" />
                <p class="text-sm text-surface-500">{{ t('dashboard.noRecentActivity') }}</p>
                <p class="font-mono text-xs text-surface-400">{{ t('dashboard.noRecentActivitySub') }}</p>
            </div>

            <ul v-else class="divide-y divide-surface-100 dark:divide-surface-800">
                <li
                    v-for="event in recent"
                    :key="event.id"
                    class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/50"
                >
                    <Badge :variant="typeVariant[event.type] ?? 'neutral'">{{ event.type }}</Badge>
                    <Badge v-if="event.level" :variant="levelVariant[event.level] ?? 'neutral'">
                        {{ event.level }}
                    </Badge>
                    <p class="min-w-0 flex-1 truncate text-sm text-surface-700 dark:text-surface-200">
                        {{ event.message ?? '—' }}
                    </p>
                    <span class="hidden font-mono text-xs text-surface-400 sm:inline">{{ event.agent_name }}</span>
                    <span class="shrink-0 font-mono text-xs text-surface-400">{{ relativeTime(event.occurred_at) }}</span>
                </li>
            </ul>
        </section>
    </AuthenticatedLayout>
</template>
