<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
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

const numberFormat = new Intl.NumberFormat('en-US');

function formatCost(value: string | number): string {
    return `$${Number(value).toFixed(4)}`;
}

function formatTokens(value: number): string {
    return numberFormat.format(value);
}

// Build a continuous date axis for the window and zero-fill missing days.
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
    chart: { id: 'daily-cost', toolbar: { show: false }, fontFamily: 'inherit' },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    xaxis: {
        categories: timeline.value.dates,
        labels: { rotate: -45, hideOverlappingLabels: true },
    },
    yaxis: { labels: { formatter: (v: number) => `$${v.toFixed(2)}` } },
    tooltip: { y: { formatter: (v: number) => formatCost(v) } },
    colors: ['#6366f1'],
}));

const dailyChartSeries = computed(() => [
    { name: 'Daily cost', data: timeline.value.costs },
]);

const modelChartOptions = computed((): ApexOptions => ({
    chart: { id: 'per-model', fontFamily: 'inherit' },
    labels: props.perModel.map((m) => `${m.provider}/${m.name}`),
    legend: { position: 'bottom' },
    tooltip: { y: { formatter: (v: number) => formatCost(v) } },
    dataLabels: { enabled: true, formatter: (val: number) => `${val.toFixed(1)}%` },
}));

const modelChartSeries = computed(() =>
    props.perModel.map((m) => Number(m.cost)),
);

const totalTokens = computed(
    () => props.totals.input_tokens + props.totals.output_tokens,
);

const hasData = computed(() => props.totals.calls > 0);
</script>

<template>
    <Head title="Costs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Costs &amp; Tokens
                <span class="text-sm font-normal text-gray-500">
                    (last {{ periodDays }} days)
                </span>
            </h2>
        </template>

        <div class="space-y-6 p-6">
            <!-- Summary counters -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded border border-gray-200 bg-white p-4">
                    <div class="text-sm text-gray-500">Total cost</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ formatCost(totals.cost) }}
                    </div>
                </div>
                <div class="rounded border border-gray-200 bg-white p-4">
                    <div class="text-sm text-gray-500">Total tokens</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ formatTokens(totalTokens) }}
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        {{ formatTokens(totals.input_tokens) }} in /
                        {{ formatTokens(totals.output_tokens) }} out
                    </div>
                </div>
                <div class="rounded border border-gray-200 bg-white p-4">
                    <div class="text-sm text-gray-500">API calls</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ formatTokens(totals.calls) }}
                    </div>
                </div>
                <div class="rounded border border-gray-200 bg-white p-4">
                    <div class="text-sm text-gray-500">Models used</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ perModel.length }}
                    </div>
                </div>
            </div>

            <div
                v-if="!hasData"
                class="rounded border border-dashed border-gray-300 py-16 text-center text-gray-400"
            >
                No usage recorded in the last {{ periodDays }} days.
            </div>

            <template v-else>
                <!-- Daily cost chart -->
                <div class="rounded border border-gray-200 bg-white p-4">
                    <h3 class="mb-2 text-sm font-medium text-gray-700">
                        Daily cost
                    </h3>
                    <VueApexCharts
                        type="area"
                        height="300"
                        :options="dailyChartOptions"
                        :series="dailyChartSeries"
                    />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Per-model cost share -->
                    <div class="rounded border border-gray-200 bg-white p-4">
                        <h3 class="mb-2 text-sm font-medium text-gray-700">
                            Cost by model
                        </h3>
                        <VueApexCharts
                            type="donut"
                            height="300"
                            :options="modelChartOptions"
                            :series="modelChartSeries"
                        />
                    </div>

                    <!-- Per-model table -->
                    <div class="rounded border border-gray-200 bg-white p-4">
                        <h3 class="mb-2 text-sm font-medium text-gray-700">
                            Breakdown
                        </h3>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="py-1">Model</th>
                                    <th class="py-1 text-right">Tokens</th>
                                    <th class="py-1 text-right">Calls</th>
                                    <th class="py-1 text-right">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="m in perModel"
                                    :key="`${m.provider}/${m.name}`"
                                    class="border-b border-gray-100"
                                >
                                    <td class="py-1 text-gray-800">
                                        {{ m.provider }}/{{ m.name }}
                                    </td>
                                    <td class="py-1 text-right text-gray-600">
                                        {{ formatTokens(m.tokens) }}
                                    </td>
                                    <td class="py-1 text-right text-gray-600">
                                        {{ formatTokens(m.calls) }}
                                    </td>
                                    <td class="py-1 text-right text-gray-800">
                                        {{ formatCost(m.cost) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
