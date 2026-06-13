import { onMounted, onUnmounted } from 'vue';
import { type LogEvent, useEventLogStore } from '@/stores/eventLog';

export function useEventLog(agentId: number): void {
    const store = useEventLogStore();

    onMounted(() => {
        const channel = window.Echo.private(`agent.${agentId}`);

        channel
            .listen('.EventReceived', (data: LogEvent) => {
                store.addEvent(data);
            })
            .subscribed(() => {
                store.setConnected(true);
            })
            .error(() => {
                store.setConnected(false);
            });
    });

    onUnmounted(() => {
        window.Echo.leave(`agent.${agentId}`);
        store.setConnected(false);
    });
}
