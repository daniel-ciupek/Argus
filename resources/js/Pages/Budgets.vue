<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, Check } from '@lucide/vue';

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

function barClass(spent: string, limit: string): string {
    const p = pct(spent, limit);
    if (p >= 100) return 'bg-danger-500';
    if (p >= 80) return 'bg-warning-500';
    return 'bg-success-500';
}

function fmt(value: string, currency: string): string {
    return parseFloat(value).toFixed(4) + ' ' + currency;
}

function fmtDate(iso: string): string {
    return new Date(iso).toLocaleString('en-GB', { hour12: false });
}

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

const periodVariant: Record<string, Variant> = {
    daily: 'info',
    monthly: 'accent',
};

const inputClasses =
    'rounded-md border-surface-300 bg-white px-3 py-1.5 text-sm text-surface-800 focus:border-accent-500 focus:ring-accent-500 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-100';
const labelClasses = 'text-xs font-medium uppercase tracking-wide text-surface-400';
const cardClasses =
    'overflow-hidden rounded-card border border-surface-200 bg-white shadow-card dark:border-surface-800 dark:bg-surface-900';
const thClasses =
    'px-5 py-2.5 font-medium font-mono text-xs uppercase tracking-wide text-surface-400';
</script>

<template>
    <Head title="Budgets" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">Budgets &amp; Alerts</h1>
                <p class="font-mono text-xs text-surface-400">spend limits &amp; threshold alerts</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- ── Create budget form ──────────────────────────────────── -->
            <section :class="cardClasses">
                <h2 class="border-b border-surface-200 px-5 py-3.5 text-sm font-semibold dark:border-surface-800">
                    Add budget
                </h2>
                <form class="flex flex-wrap items-end gap-4 p-5" @submit.prevent="submitBudget">
                    <div class="flex flex-col gap-1.5">
                        <label :class="labelClasses">Agent</label>
                        <select v-model="form.agent_id" :class="inputClasses">
                            <option v-for="a in agents" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                        <span v-if="form.errors.agent_id" class="font-mono text-xs text-danger-500">
                            {{ form.errors.agent_id }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label :class="labelClasses">Period</label>
                        <select v-model="form.period" :class="inputClasses">
                            <option v-for="p in periods" :key="p" :value="p">{{ p }}</option>
                        </select>
                        <span v-if="form.errors.period" class="font-mono text-xs text-danger-500">
                            {{ form.errors.period }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label :class="labelClasses">Limit (USD)</label>
                        <input
                            v-model="form.limit_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="e.g. 10.00"
                            :class="[inputClasses, 'font-mono']"
                        />
                        <span v-if="form.errors.limit_amount" class="font-mono text-xs text-danger-500">
                            {{ form.errors.limit_amount }}
                        </span>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-1.5 rounded-md bg-accent-600 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-accent-500 disabled:opacity-50"
                    >
                        <Plus class="h-4 w-4" />
                        Add
                    </button>
                </form>
            </section>

            <!-- ── Budget list ─────────────────────────────────────────── -->
            <section>
                <h2 class="mb-3 text-sm font-semibold">Active budgets</h2>

                <div
                    v-if="budgets.length === 0"
                    class="rounded-card border border-dashed border-surface-300 py-10 text-center text-sm text-surface-400 dark:border-surface-700"
                >
                    No budgets yet. Add one above.
                </div>

                <div v-else :class="cardClasses">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-200 text-left dark:border-surface-800">
                                    <th :class="thClasses">Agent</th>
                                    <th :class="thClasses">Period</th>
                                    <th :class="thClasses">Spent / Limit</th>
                                    <th :class="thClasses">Progress</th>
                                    <th :class="thClasses"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-100 dark:divide-surface-800/60">
                                <tr v-for="b in budgets" :key="b.id" class="transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                    <td class="px-5 py-3 font-medium text-surface-800 dark:text-surface-100">{{ b.agent_name }}</td>
                                    <td class="px-5 py-3">
                                        <Badge :variant="periodVariant[b.period] ?? 'neutral'">{{ b.period }}</Badge>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-xs text-surface-600 dark:text-surface-300">
                                        {{ fmt(b.current_spent, b.currency) }} / {{ fmt(b.limit_amount, b.currency) }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-28 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-800">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="barClass(b.current_spent, b.limit_amount)"
                                                    :style="{ width: pct(b.current_spent, b.limit_amount) + '%' }"
                                                />
                                            </div>
                                            <span class="font-mono text-xs text-surface-400">{{ Math.round(pct(b.current_spent, b.limit_amount)) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-danger-600 transition-colors hover:bg-danger-500/10 dark:text-danger-400"
                                            @click="deleteBudget(b.id)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ── Alert list ──────────────────────────────────────────── -->
            <section>
                <h2 class="mb-3 text-sm font-semibold">Alerts</h2>

                <div
                    v-if="alerts.length === 0"
                    class="rounded-card border border-dashed border-surface-300 py-10 text-center text-sm text-surface-400 dark:border-surface-700"
                >
                    No alerts.
                </div>

                <div v-else :class="cardClasses">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-surface-200 text-left dark:border-surface-800">
                                    <th :class="thClasses">Agent</th>
                                    <th :class="thClasses">Period</th>
                                    <th :class="thClasses">Amount</th>
                                    <th :class="thClasses">Triggered</th>
                                    <th :class="thClasses">Status</th>
                                    <th :class="thClasses"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-100 dark:divide-surface-800/60">
                                <tr v-for="alert in alerts" :key="alert.id" class="transition-colors hover:bg-surface-50 dark:hover:bg-surface-800/40">
                                    <td class="px-5 py-3 font-medium text-surface-800 dark:text-surface-100">{{ alert.agent_name }}</td>
                                    <td class="px-5 py-3">
                                        <Badge :variant="periodVariant[alert.budget_period] ?? 'neutral'">{{ alert.budget_period }}</Badge>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-xs text-surface-600 dark:text-surface-300">{{ fmt(alert.amount, alert.budget_currency) }}</td>
                                    <td class="px-5 py-3 font-mono text-xs text-surface-500 dark:text-surface-400">{{ fmtDate(alert.triggered_at) }}</td>
                                    <td class="px-5 py-3">
                                        <Badge :variant="alert.acknowledged_at ? 'neutral' : 'danger'" dot>
                                            {{ alert.acknowledged_at ? 'acknowledged' : 'unacknowledged' }}
                                        </Badge>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <button
                                            v-if="!alert.acknowledged_at"
                                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-accent-600 transition-colors hover:bg-accent-500/10 dark:text-accent-400"
                                            @click="acknowledge(alert.id)"
                                        >
                                            <Check class="h-3.5 w-3.5" />
                                            Acknowledge
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
