import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface TaskRow {
    id: number;
    agent_id: number;
    agent_name: string;
    name: string;
    status: string;
    schedule: string | null;
    last_run_at: string | null;
    next_run_at: string | null;
}

/** Payload broadcast by TaskStatusUpdated (no agent_name — resolved client-side). */
export interface TaskBroadcast {
    id: number;
    agent_id: number;
    name: string;
    status: string;
    schedule: string | null;
    last_run_at: string | null;
    next_run_at: string | null;
}

export const useTaskStore = defineStore('tasks', () => {
    const tasks = ref<TaskRow[]>([]);
    const isConnected = ref(false);

    function setTasks(list: TaskRow[]): void {
        tasks.value = list;
    }

    function upsert(payload: TaskBroadcast, agentName: string): void {
        const index = tasks.value.findIndex((t) => t.id === payload.id);
        if (index !== -1) {
            tasks.value[index] = { ...tasks.value[index], ...payload };
        } else {
            tasks.value.unshift({ ...payload, agent_name: agentName });
        }
    }

    function setConnected(value: boolean): void {
        isConnected.value = value;
    }

    return { tasks, isConnected, setTasks, upsert, setConnected };
});
