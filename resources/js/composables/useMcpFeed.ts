import { onMounted, onUnmounted } from 'vue';
import { type McpBroadcast, useMcpStore } from '@/stores/mcp';

/**
 * Subscribes to live MCP connection-status updates across the user's agents.
 * New connections arriving over the wire resolve their agent name from the map.
 */
export function useMcpFeed(
    agentIds: number[],
    agentNameById: Record<number, string>,
): void {
    const store = useMcpStore();

    onMounted(() => {
        agentIds.forEach((agentId, index) => {
            window.Echo.private(`agent.${agentId}`)
                .listen('.McpStatusUpdated', (data: McpBroadcast) => {
                    store.upsert(data, agentNameById[data.agent_id] ?? '');
                })
                .subscribed(() => {
                    if (index === 0) {
                        store.setConnected(true);
                    }
                })
                .error(() => {
                    store.setConnected(false);
                });
        });
    });

    onUnmounted(() => {
        agentIds.forEach((agentId) => window.Echo.leave(`agent.${agentId}`));
        store.setConnected(false);
    });
}
