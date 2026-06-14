<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

interface Agent {
    id: number;
    name: string;
}

interface BudgetRow {
    id: number;
    agent_id: number;
    agent_name: string | null;
    period: string;
    limit_amount: string;
    currency: string;
    current_spent: string;
}

interface AlertRow {
    id: number;
    budget_id: number;
    amount: string;
    channel: string;
    triggered_at: string;
    acknowledged_at: string | null;
    budget_period: string;
    budget_currency: string;
    agent_name: string | null;
}

const props = defineProps<{
    budgets: BudgetRow[];
    alerts: AlertRow[];
    agents: Agent[];
    periods: string[];
}>();

const form = useForm({
    agent_id: props.agents[0]?.id ?? '',
    period: 'daily',
    limit_amount: '',
    currency: 'USD',
});

function submitBudget(): void {
    form.post(route('budgets.store'), {
        onSuccess: () => form.reset(),
    });
}

function deleteBudget(id: number): void {
    router.delete(route('budgets.destroy', id), { preserveScroll: true });
}

function acknowledge(id: number): void {
    router.patch(route('alerts.acknowledge', id), {}, { preserveScroll: true });
}

function pct(spent: string, limit: string): number {
    const l = parseFloat(limit);
    if (l <= 0) return 0;
    return Math.min(100, (parseFloat(spent) / l) * 100);
}

function fmt(value: string, currency: string): string {
    return parseFloat(value).toFixed(4) + ' ' + currency;
}

function fmtDate(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Budgets" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Budgets &amp; Alerts
            </h2>
        </template>

        <div class="space-y-8 p-6">
            <!-- ── Create budget form ──────────────────────────────────── -->
            <section>
                <h3 class="mb-3 font-semibold text-gray-700">Add budget</h3>
                <form
                    class="flex flex-wrap items-end gap-3"
                    @submit.prevent="submitBudget"
                >
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-500">Agent</label>
                        <select
                            v-model="form.agent_id"
                            class="rounded border border-gray-300 px-2 py-1 text-sm"
                        >
                            <option v-for="a in agents" :key="a.id" :value="a.id">
                                {{ a.name }}
                            </option>
                        </select>
                        <span v-if="form.errors.agent_id" class="text-xs text-red-600">
                            {{ form.errors.agent_id }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-500">Period</label>
                        <select
                            v-model="form.period"
                            class="rounded border border-gray-300 px-2 py-1 text-sm"
                        >
                            <option v-for="p in periods" :key="p" :value="p">
                                {{ p }}
                            </option>
                        </select>
                        <span v-if="form.errors.period" class="text-xs text-red-600">
                            {{ form.errors.period }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-gray-500">Limit (USD)</label>
                        <input
                            v-model="form.limit_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="e.g. 10.00"
                            class="rounded border border-gray-300 px-2 py-1 text-sm"
                        />
                        <span v-if="form.errors.limit_amount" class="text-xs text-red-600">
                            {{ form.errors.limit_amount }}
                        </span>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-gray-800 px-4 py-1.5 text-sm text-white hover:bg-gray-700 disabled:opacity-50"
                    >
                        Add
                    </button>
                </form>
            </section>

            <!-- ── Budget list ─────────────────────────────────────────── -->
            <section>
                <h3 class="mb-3 font-semibold text-gray-700">Active budgets</h3>

                <div
                    v-if="budgets.length === 0"
                    class="rounded border border-dashed border-gray-300 py-10 text-center text-sm text-gray-400"
                >
                    No budgets yet. Add one above.
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Agent</th>
                            <th class="py-2">Period</th>
                            <th class="py-2">Spent / Limit</th>
                            <th class="py-2">Progress</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="b in budgets"
                            :key="b.id"
                            class="border-b border-gray-100"
                        >
                            <td class="py-2 text-gray-800">{{ b.agent_name }}</td>
                            <td class="py-2">
                                <span
                                    class="rounded px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        b.period === 'daily'
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-purple-100 text-purple-700'
                                    "
                                >
                                    {{ b.period }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-700">
                                {{ fmt(b.current_spent, b.currency) }}
                                /
                                {{ fmt(b.limit_amount, b.currency) }}
                            </td>
                            <td class="py-2">
                                <div class="h-2 w-32 rounded bg-gray-200">
                                    <div
                                        class="h-2 rounded transition-all"
                                        :class="
                                            pct(b.current_spent, b.limit_amount) >= 100
                                                ? 'bg-red-500'
                                                : pct(b.current_spent, b.limit_amount) >= 80
                                                  ? 'bg-yellow-400'
                                                  : 'bg-green-500'
                                        "
                                        :style="{ width: pct(b.current_spent, b.limit_amount) + '%' }"
                                    />
                                </div>
                            </td>
                            <td class="py-2 text-right">
                                <button
                                    class="text-xs text-red-600 hover:underline"
                                    @click="deleteBudget(b.id)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- ── Alert list ──────────────────────────────────────────── -->
            <section>
                <h3 class="mb-3 font-semibold text-gray-700">Alerts</h3>

                <div
                    v-if="alerts.length === 0"
                    class="rounded border border-dashed border-gray-300 py-10 text-center text-sm text-gray-400"
                >
                    No alerts.
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2">Agent</th>
                            <th class="py-2">Period</th>
                            <th class="py-2">Amount</th>
                            <th class="py-2">Triggered</th>
                            <th class="py-2">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="alert in alerts"
                            :key="alert.id"
                            class="border-b border-gray-100"
                        >
                            <td class="py-2 text-gray-800">{{ alert.agent_name }}</td>
                            <td class="py-2">
                                <span
                                    class="rounded px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        alert.budget_period === 'daily'
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-purple-100 text-purple-700'
                                    "
                                >
                                    {{ alert.budget_period }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-700">{{ fmt(alert.amount, alert.budget_currency) }}</td>
                            <td class="py-2 text-gray-600">
                                {{ fmtDate(alert.triggered_at) }}
                            </td>
                            <td class="py-2">
                                <span
                                    class="rounded px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        alert.acknowledged_at
                                            ? 'bg-gray-100 text-gray-500'
                                            : 'bg-red-100 text-red-700'
                                    "
                                >
                                    {{
                                        alert.acknowledged_at
                                            ? 'acknowledged'
                                            : 'unacknowledged'
                                    }}
                                </span>
                            </td>
                            <td class="py-2 text-right">
                                <button
                                    v-if="!alert.acknowledged_at"
                                    class="text-xs text-blue-600 hover:underline"
                                    @click="acknowledge(alert.id)"
                                >
                                    Acknowledge
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
