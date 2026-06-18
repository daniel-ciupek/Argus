import { onMounted, onUnmounted } from 'vue';
import { type CommandBroadcast, useCommandStore } from '@/stores/commands';

export function useCommandFeed(agentIds: number[]): void {
    const store = useCommandStore();

    onMounted(() => {
        agentIds.forEach((agentId) => {
            window.Echo.private(`agent.${agentId}`).listen(
                '.CommandUpdated',
                (data: CommandBroadcast) => {
                    store.patch(data);
                },
            );
        });
    });

    onUnmounted(() => {
        agentIds.forEach((agentId) => window.Echo.leave(`agent.${agentId}`));
    });
}
