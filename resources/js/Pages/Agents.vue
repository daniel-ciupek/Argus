<script setup lang="ts">
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Copy, Check, RefreshCw, Trash2, Pencil, PauseCircle, PlayCircle, StopCircle, MessageSquare, Send } from '@lucide/vue';

interface Agent {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    last_seen_at: string | null;
    created_at: string | null;
}

const props = defineProps<{
    agents: Agent[];
    newSecret?: string | null;
    newSecretAgentId?: number | null;
}>();

const { t } = useI18n();

const createForm = useForm({ name: '' });
const editingId = ref<number | null>(null);
const editName = ref('');
const secretCopied = ref(false);

function submitCreate() {
    createForm.post(route('agents.store'), {
        onSuccess: () => createForm.reset(),
    });
}

function startEdit(agent: Agent) {
    editingId.value = agent.id;
    editName.value = agent.name;
}

function saveEdit(agent: Agent) {
    router.patch(route('agents.update', agent.id), { name: editName.value }, {
        onSuccess: () => { editingId.value = null; },
    });
}

function cancelEdit() {
    editingId.value = null;
}

function toggleActive(agent: Agent) {
    router.patch(route('agents.update', agent.id), { name: agent.name, is_active: !agent.is_active });
}

function rotateSecret(agent: Agent) {
    router.post(route('agents.rotate-secret', agent.id));
}

function deleteAgent(agent: Agent) {
    if (!confirm(t('agents.deleteConfirm'))) return;
    router.delete(route('agents.destroy', agent.id));
}

async function copySecret() {
    if (!props.newSecret) return;
    await navigator.clipboard.writeText(props.newSecret);
    secretCopied.value = true;
    setTimeout(() => { secretCopied.value = false; }, 2000);
}

const showSecretBanner = computed(() => !!props.newSecret);

const instructAgentId = ref<number | null>(null);
const instructText = ref('');

function openInstruct(agent: Agent): void {
    instructAgentId.value = agent.id;
    instructText.value = '';
}

function sendInstruct(agent: Agent): void {
    if (!instructText.value.trim()) return;
    router.post(
        route('commands.store', agent.id),
        { type: 'agent.instruct', payload: { text: instructText.value.trim() } },
        {
            preserveScroll: true,
            onSuccess: () => {
                instructAgentId.value = null;
                instructText.value = '';
            },
        },
    );
}

function sendAgentCommand(type: string, agent: Agent): void {
    router.post(
        route('commands.store', agent.id),
        { type },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('agents.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">{{ t('agents.title') }}</h1>
                <p class="font-mono text-xs text-surface-400">{{ t('agents.subtitle') }}</p>
            </div>
        </template>

        <!-- One-time secret banner -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition ease-in duration-150"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="showSecretBanner"
                class="mb-4 rounded-card border border-warning-400 bg-warning-50 p-4 dark:border-warning-500 dark:bg-warning-950"
            >
                <p class="mb-1 text-sm font-semibold text-warning-800 dark:text-warning-300">
                    {{ t('agents.secretTitle') }}
                </p>
                <p class="mb-2 font-mono text-xs text-warning-700 dark:text-warning-400">
                    {{ t('agents.secretWarning') }}
                </p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 rounded bg-warning-100 px-3 py-1.5 font-mono text-xs text-warning-900 dark:bg-warning-900 dark:text-warning-100">
                        {{ newSecret }}
                    </code>
                    <button
                        @click="copySecret"
                        class="flex items-center gap-1 rounded px-3 py-1.5 text-xs font-medium transition hover:bg-warning-200 dark:hover:bg-warning-800"
                    >
                        <Check v-if="secretCopied" class="h-3.5 w-3.5 text-success-600" />
                        <Copy v-else class="h-3.5 w-3.5" />
                        {{ secretCopied ? t('agents.copiedSecret') : t('agents.copySecret') }}
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Add agent form -->
        <div class="mb-4 rounded-card border border-surface-200 bg-white p-4 shadow-card dark:border-surface-800 dark:bg-surface-900">
            <form @submit.prevent="submitCreate" class="flex gap-2">
                <input
                    v-model="createForm.name"
                    type="text"
                    :placeholder="t('agents.agentName')"
                    required
                    class="flex-1 rounded-lg border border-surface-300 bg-transparent px-3 py-2 text-sm focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500 dark:border-surface-700"
                />
                <button
                    type="submit"
                    :disabled="createForm.processing"
                    class="rounded-lg bg-accent-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-accent-700 disabled:opacity-50"
                >
                    {{ t('agents.addAgent') }}
                </button>
            </form>
        </div>

        <!-- Agents table -->
        <div class="rounded-card border border-surface-200 bg-white shadow-card dark:border-surface-800 dark:bg-surface-900">
            <div v-if="agents.length === 0" class="flex flex-col items-center gap-2 py-16 text-center">
                <p class="text-sm text-surface-500">{{ t('agents.noAgents') }}</p>
                <p class="font-mono text-xs text-surface-400">{{ t('agents.noAgentsSub') }}</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-200 dark:border-surface-800">
                        <th class="px-4 py-3 text-left font-medium text-surface-500">{{ t('agents.agentName') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-surface-500">{{ t('agents.slug') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-surface-500">{{ t('common.status') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-surface-500">{{ t('agents.lastSeen') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-surface-500">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                    <tr v-for="agent in agents" :key="agent.id" class="group">
                        <!-- Name / inline edit -->
                        <td class="px-4 py-3">
                            <div v-if="editingId === agent.id" class="flex items-center gap-2">
                                <input
                                    v-model="editName"
                                    class="rounded border border-surface-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-accent-500 dark:border-surface-700 dark:bg-surface-800"
                                    @keyup.enter="saveEdit(agent)"
                                    @keyup.escape="cancelEdit"
                                    autofocus
                                />
                                <button @click="saveEdit(agent)" class="text-xs text-accent-600 hover:underline">{{ t('agents.saveName') }}</button>
                                <button @click="cancelEdit" class="text-xs text-surface-400 hover:underline">{{ t('agents.cancelEdit') }}</button>
                            </div>
                            <span v-else class="font-medium">{{ agent.name }}</span>
                        </td>

                        <td class="px-4 py-3 font-mono text-xs text-surface-500">{{ agent.slug }}</td>

                        <!-- Status badge -->
                        <td class="px-4 py-3">
                            <span
                                :class="agent.is_active
                                    ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300'
                                    : 'bg-surface-100 text-surface-500 dark:bg-surface-800 dark:text-surface-400'"
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                {{ agent.is_active ? t('agents.active') : t('agents.inactive') }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-xs text-surface-500">
                            {{ agent.last_seen_at ? new Date(agent.last_seen_at).toLocaleString() : t('agents.never') }}
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Command: Pause / Resume -->
                                <button
                                    v-if="agent.is_active"
                                    :title="t('commands.pause')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-warning-50 hover:text-warning-600 dark:hover:bg-warning-950 dark:hover:text-warning-400"
                                    @click="sendAgentCommand('agent.pause', agent)"
                                >
                                    <PauseCircle class="h-4 w-4" />
                                </button>
                                <button
                                    v-else
                                    :title="t('commands.resume')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-success-50 hover:text-success-600 dark:hover:bg-success-950 dark:hover:text-success-400"
                                    @click="sendAgentCommand('agent.resume', agent)"
                                >
                                    <PlayCircle class="h-4 w-4" />
                                </button>
                                <!-- Command: Stop -->
                                <button
                                    :title="t('commands.stop')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-950 dark:hover:text-danger-400"
                                    @click="sendAgentCommand('agent.stop', agent)"
                                >
                                    <StopCircle class="h-4 w-4" />
                                </button>
                                <!-- Command: Instruct -->
                                <button
                                    :title="t('commands.instruct')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-accent-50 hover:text-accent-600 dark:hover:bg-accent-950 dark:hover:text-accent-400"
                                    @click="openInstruct(agent)"
                                >
                                    <MessageSquare class="h-4 w-4" />
                                </button>

                                <span class="mx-1 h-4 w-px bg-surface-200 dark:bg-surface-700" />

                                <button
                                    @click="startEdit(agent)"
                                    :title="t('agents.editName')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <button
                                    @click="toggleActive(agent)"
                                    :title="agent.is_active ? t('agents.deactivate') : t('agents.activate')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                                >
                                    <span class="text-xs font-medium">
                                        {{ agent.is_active ? t('agents.deactivate') : t('agents.activate') }}
                                    </span>
                                </button>
                                <button
                                    @click="rotateSecret(agent)"
                                    :title="t('agents.rotateSecret')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                                >
                                    <RefreshCw class="h-4 w-4" />
                                </button>
                                <button
                                    @click="deleteAgent(agent)"
                                    :title="t('common.delete')"
                                    class="rounded p-1 text-surface-400 transition hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-950 dark:hover:text-error-400"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                            <!-- Instruct panel -->
                            <div
                                v-if="instructAgentId === agent.id"
                                class="mt-2 flex gap-2"
                            >
                                <textarea
                                    v-model="instructText"
                                    rows="2"
                                    :placeholder="t('commands.instructPlaceholder')"
                                    class="flex-1 resize-none rounded-lg border border-surface-300 bg-transparent px-3 py-2 font-mono text-xs focus:border-accent-500 focus:outline-none focus:ring-1 focus:ring-accent-500 dark:border-surface-700"
                                />
                                <div class="flex flex-col gap-1">
                                    <button
                                        :disabled="!instructText.trim()"
                                        class="flex items-center gap-1 rounded-lg bg-accent-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-accent-700 disabled:opacity-50"
                                        @click="sendInstruct(agent)"
                                    >
                                        <Send class="h-3.5 w-3.5" />
                                        {{ t('commands.send') }}
                                    </button>
                                    <button
                                        class="rounded-lg border border-surface-300 px-3 py-1.5 text-xs text-surface-500 transition hover:bg-surface-100 dark:border-surface-700 dark:hover:bg-surface-800"
                                        @click="instructAgentId = null"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
