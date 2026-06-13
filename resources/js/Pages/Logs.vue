<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useEventLog } from '@/composables/useEventLog';
import { useEventLogStore } from '@/stores/eventLog';
import { type PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage<PageProps>();
const agents = computed(() => page.props.auth.agents ?? []);

const selectedAgentId = ref<number | null>(agents.value[0]?.id ?? null);

const store = useEventLogStore();

const levelClass: Record<string, string> = {
    debug: 'text-gray-400',
    info: 'text-blue-400',
    warning: 'text-yellow-400',
    error: 'text-red-400',
};

watch(
    selectedAgentId,
    () => {
        store.clear();
    },
    { immediate: false },
);

if (selectedAgentId.value !== null) {
    useEventLog(selectedAgentId.value);
}
</script>

<template>
    <Head title="Logs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Live Logs
                </h2>
                <span
                    :class="store.isConnected ? 'bg-green-500' : 'bg-gray-400'"
                    class="inline-block h-2.5 w-2.5 rounded-full"
                    :title="store.isConnected ? 'Connected' : 'Disconnected'"
                />
                <select
                    v-if="agents.length > 1"
                    v-model="selectedAgentId"
                    class="rounded border border-gray-300 px-2 py-1 text-sm"
                >
                    <option v-for="a in agents" :key="a.id" :value="a.id">
                        {{ a.name }}
                    </option>
                </select>
            </div>
        </template>

        <div class="p-6">
            <div v-if="agents.length === 0" class="text-gray-500">
                No active agents. Create an agent first.
            </div>

            <div v-else>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        {{ store.events.length }} event(s) — newest first
                    </span>
                    <button
                        class="text-sm text-gray-400 hover:text-gray-700"
                        @click="store.clear()"
                    >
                        Clear
                    </button>
                </div>

                <div
                    v-if="store.events.length === 0"
                    class="py-12 text-center text-gray-400"
                >
                    Waiting for events…
                </div>

                <ul v-else class="space-y-1 font-mono text-sm">
                    <li
                        v-for="event in store.events"
                        :key="event.id"
                        class="flex gap-3 rounded bg-gray-50 px-3 py-2"
                    >
                        <span class="shrink-0 text-gray-400">
                            {{
                                event.occurred_at
                                    ? new Date(event.occurred_at).toLocaleTimeString()
                                    : '—'
                            }}
                        </span>
                        <span
                            class="w-16 shrink-0 uppercase"
                            :class="levelClass[event.level] ?? 'text-gray-600'"
                        >
                            {{ event.level }}
                        </span>
                        <span class="shrink-0 text-gray-500">
                            [{{ event.type }}]
                        </span>
                        <span class="break-all text-gray-800">
                            {{ event.message }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
