import { onMounted, onUnmounted } from 'vue';
import { type TaskBroadcast, useTaskStore } from '@/stores/tasks';

/**
 * Subscribes to live task-status updates across the user's agents. New tasks
 * arriving over the wire have their agent name resolved from the provided map.
 */
export function useTaskFeed(
    agentIds: number[],
    agentNameById: Record<number, string>,
): void {
    const store = useTaskStore();

    onMounted(() => {
        agentIds.forEach((agentId, index) => {
            window.Echo.private(`agent.${agentId}`)
                .listen('.TaskStatusUpdated', (data: TaskBroadcast) => {
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
