<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useColorMode } from '@/composables/useColorMode';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import type { ApexOptions } from 'apexcharts';

interface Totals {
    cost: string;
    input_tokens: number;
    output_tokens: number;
    calls: number;
}

interface DailyBucket {
    date: string;
    cost: string;
    tokens: number;
}

interface ModelBreakdown {
    provider: string;
    name: string;
    cost: string;
    tokens: number;
    calls: number;
}

const props = defineProps<{
    periodDays: number;
    totals: Totals;
    daily: DailyBucket[];
    perModel: ModelBreakdown[];
}>();

const { t } = useI18n();
const { mode } = useColorMode();

const numberFormat = new Intl.NumberFormat('en-US');

function formatCost(value: string | number): string {
    return `$${Number(value).toFixed(4)}`;
}

function formatTokens(value: number): string {
    return numberFormat.format(value);
}

const palette = ['#10b981', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6', '#ec4899'];

const chartTheme = computed(() => {
    const isDark = mode.value === 'dark';
    return {
        isDark,
        foreColor: isDark ? '#a1a1aa' : '#71717a',
        gridBorder: isDark ? '#27272a' : '#e4e4e7',
    };
});

const timeline = computed(() => {
    const byDate = new Map(props.daily.map((d) => [d.date, d]));
    const dates: string[] = [];
    const costs: number[] = [];

    const today = new Date();
    for (let i = props.periodDays - 1; i >= 0; i--) {
        const day = new Date(today);
        day.setDate(today.getDate() - i);
        const key = day.toISOString().slice(0, 10);
        dates.push(key);
        costs.push(Number(byDate.get(key)?.cost ?? 0));
    }

    return { dates, costs };
});

const dailyChartOptions = computed((): ApexOptions => ({
    chart: {
        id: 'daily-cost',
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: chartTheme.value.foreColor,
        background: 'transparent',
    },
    theme: { mode: chartTheme.value.isDark ? 'dark' : 'light' },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    grid: { borderColor: chartTheme.value.gridBorder, strokeDashArray: 4 },
    xaxis: {
        categories: timeline.value.dates,
        labels: { rotate: -45, hideOverlappingLabels: true, style: { fontFamily: 'inherit' } },
        axisBorder: { color: chartTheme.value.gridBorder },
        axisTicks: { color: chartTheme.value.gridBorder },
    },
    yaxis: { labels: { formatter: (v: number) => `$${v.toFixed(2)}` } },
    tooltip: { theme: chartTheme.value.isDark ? 'dark' : 'light', y: { formatter: (v: number) => formatCost(v) } },
    colors: [palette[0]],
}));

const dailyChartSeries = computed(() => [
    { name: t('costs.dailySpend'), data: timeline.value.costs },
]);

const modelChartOptions = computed((): ApexOptions => ({
    chart: { id: 'per-model', fontFamily: 'inherit', foreColor: chartTheme.value.foreColor, background: 'transparent' },
    theme: { mode: chartTheme.value.isDark ? 'dark' : 'light' },
    labels: props.perModel.map((m) => `${m.provider}/${m.name}`),
    colors: palette,
    legend: { position: 'bottom', fontFamily: 'inherit' },
    stroke: { width: 0 },
    plotOptions: { pie: { donut: { labels: { show: false } } } },
    tooltip: { theme: chartTheme.value.isDark ? 'dark' : 'light', y: { formatter: (v: number) => formatCost(v) } },
    dataLabels: { enabled: true, formatter: (val: number) => `${val.toFixed(1)}%` },
}));

const modelChartSeries = computed(() =>
    props.perModel.map((m) => Number(m.cost)),
);

const totalTokens = computed(
    () => props.totals.input_tokens + props.totals.output_tokens,
);

const counters = computed(() => [
    { label: t('costs.totalCost'), value: formatCost(props.totals.cost), sub: null as string | null },
    {
        label: t('costs.totalTokens'),
        value: formatTokens(totalTokens.value),
        sub: t('costs.tokensSub', {
            input: formatTokens(props.totals.input_tokens),
            output: formatTokens(props.totals.output_tokens),
        }),
    },
    { label: t('costs.apiCalls'), value: formatTokens(props.totals.calls), sub: null },
    { label: t('costs.modelsUsed'), value: String(props.perModel.length), sub: null },
]);

const hasData = computed(() => props.totals.calls > 0);
</script>

<template>
    <Head :title="t('costs.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">{{ t('costs.title') }}</h1>
                <p class="font-mono text-xs text-surface-400">{{ t('costs.spendSubtitle', { n: periodDays }) }}</p>
            </div>
        </template>

        <!-- Summary counters -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="c in counters"
                :key="c.label"
                class="rounded-card border border-surface-200 bg-white p-5 shadow-card dark:border-surface-800 dark:bg-surface-900"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-surface-400">{{ c.label }}</p>
                <p class="mt-1 font-mono text-2xl font-semibold tracking-tight">{{ c.value }}</p>
                <p v-if="c.sub" class="mt-1 font-mono text-xs text-surface-400">{{ c.sub }}</p>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="!hasData"
            class="mt-6 rounded-card border border-dashed border-surface-300 py-16 text-center dark:border-surface-700"
        >
            <p class="text-sm text-surface-500">{{ t('costs.noDataNDays', { n: periodDays }) }}</p>
            <p class="font-mono text-xs text-surface-400">{{ t('costs.noDataSub') }}</p>
        </div>

        <template v-else>
            <!-- Daily cost chart -->
            <div class="mt-6 rounded-card border border-surface-200 bg-white p-5 shadow-card dark:border-surface-800 dark:bg-surface-900">
                <h2 class="mb-3 text-sm font-semibold">{{ t('costs.dailySpend') }}</h2>
                <VueApexCharts
                    type="area"
                    height="300"
                    :options="dailyChartOptions"
                    :series="dailyChartSeries"
                />
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Per-model cost share -->
                <div class="rounded-card border border-surface-200 bg-white p-5 shadow-card dark:border-surface-800 dark:bg-surface-900">
                    <h2 class="mb-3 text-sm font-semibold">{{ t('costs.byModel') }}</h2>
                    <VueApexCharts
                        type="donut"
                        height="300"
                        :options="modelChartOptions"
                        :series="modelChartSeries"
                    />
                </div>

                <!-- Per-model table -->
                <div class="overflow-hidden rounded-card border border-surface-200 bg-white shadow-card dark:border-surface-800 dark:bg-surface-900">
                    <h2 class="border-b border-surface-200 px-5 py-3.5 text-sm font-semibold dark:border-surface-800">
                        {{ t('costs.breakdown') }}
                    </h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-surface-200 text-left font-mono text-xs uppercase tracking-wide text-surface-400 dark:border-surface-800">
                                <th class="px-5 py-2 font-medium">{{ t('costs.model') }}</th>
                                <th class="px-5 py-2 text-right font-medium">{{ t('costs.tokens') }}</th>
                                <th class="px-5 py-2 text-right font-medium">{{ t('costs.calls') }}</th>
                                <th class="px-5 py-2 text-right font-medium">{{ t('costs.cost') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-100 dark:divide-surface-800/60">
                            <tr
                                v-for="m in perModel"
                                :key="`${m.provider}/${m.name}`"
                                class="transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/40"
                            >
                                <td class="px-5 py-2.5 font-mono text-surface-700 dark:text-surface-200">
                                    {{ m.provider }}/{{ m.name }}
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-surface-500 dark:text-surface-400">
                                    {{ formatTokens(m.tokens) }}
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-surface-500 dark:text-surface-400">
                                    {{ formatTokens(m.calls) }}
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono font-medium text-surface-800 dark:text-surface-100">
                                    {{ formatCost(m.cost) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </AuthenticatedLayout>
</template>
