import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface CommandRow {
    id: number;
    agent_id: number;
    type: string;
    status: string;
    payload: Record<string, unknown> | null;
    result: Record<string, unknown> | null;
    created_at: string;
    dispatched_at: string | null;
    completed_at: string | null;
}

/** Subset broadcast by CommandUpdated event (no agent_id — inferred from channel). */
export interface CommandBroadcast {
    id: number;
    type: string;
    status: string;
    result: Record<string, unknown> | null;
    completed_at: string | null;
}

export const useCommandStore = defineStore('commands', () => {
    const commands = ref<CommandRow[]>([]);

    function setCommands(list: CommandRow[]): void {
        commands.value = list;
    }

    function patch(payload: CommandBroadcast): void {
        const index = commands.value.findIndex((c) => c.id === payload.id);
        if (index !== -1) {
            commands.value[index] = {
                ...commands.value[index],
                status: payload.status,
                result: payload.result,
                completed_at: payload.completed_at,
            };
        }
    }

    function addOptimistic(command: CommandRow): void {
        commands.value.unshift(command);
    }

    function forAgent(agentId: number): CommandRow[] {
        return commands.value.filter((c) => c.agent_id === agentId);
    }

    return { commands, setCommands, patch, addOptimistic, forAgent };
});
