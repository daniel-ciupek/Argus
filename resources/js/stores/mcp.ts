import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface McpRow {
    id: number;
    agent_id: number;
    agent_name: string;
    name: string;
    status: string;
    meta: Record<string, unknown> | null;
}

/** Payload broadcast by McpStatusUpdated (no agent_name — resolved client-side). */
export interface McpBroadcast {
    id: number;
    agent_id: number;
    name: string;
    status: string;
    meta: Record<string, unknown> | null;
}

export const useMcpStore = defineStore('mcp', () => {
    const connections = ref<McpRow[]>([]);
    const isConnected = ref(false);

    function setConnections(list: McpRow[]): void {
        connections.value = list;
    }

    function upsert(payload: McpBroadcast, agentName: string): void {
        const index = connections.value.findIndex((c) => c.id === payload.id);
        if (index !== -1) {
            connections.value[index] = {
                ...connections.value[index],
                ...payload,
            };
        } else {
            connections.value.unshift({ ...payload, agent_name: agentName });
        }
    }

    function setConnected(value: boolean): void {
        isConnected.value = value;
    }

    return { connections, isConnected, setConnections, upsert, setConnected };
});
