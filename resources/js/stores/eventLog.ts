import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface LogEvent {
    id: number;
    type: string;
    level: string;
    message: string;
    payload: Record<string, unknown> | null;
    occurred_at: string | null;
}

export const useEventLogStore = defineStore('eventLog', () => {
    const events = ref<LogEvent[]>([]);
    const isConnected = ref(false);

    function addEvent(event: LogEvent): void {
        events.value.unshift(event);
        if (events.value.length > 200) {
            events.value.pop();
        }
    }

    function setConnected(value: boolean): void {
        isConnected.value = value;
    }

    function clear(): void {
        events.value = [];
    }

    return { events, isConnected, addEvent, setConnected, clear };
});
