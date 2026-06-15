<script setup lang="ts">
import { computed } from 'vue';

type Variant = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        dot?: boolean;
    }>(),
    {
        variant: 'neutral',
        dot: false,
    },
);

const variantClasses: Record<Variant, string> = {
    neutral:
        'bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-300',
    success:
        'bg-success-500/10 text-success-700 dark:text-success-400',
    warning:
        'bg-warning-500/10 text-warning-700 dark:text-warning-400',
    danger: 'bg-danger-500/10 text-danger-700 dark:text-danger-400',
    info: 'bg-info-500/10 text-info-700 dark:text-info-400',
    accent: 'bg-accent-500/10 text-accent-700 dark:text-accent-400',
};

const dotColor: Record<Variant, string> = {
    neutral: 'bg-surface-400',
    success: 'bg-success-500',
    warning: 'bg-warning-500',
    danger: 'bg-danger-500',
    info: 'bg-info-500',
    accent: 'bg-accent-500',
};

const classes = computed(() => variantClasses[props.variant]);
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 font-mono text-xs font-medium"
        :class="classes"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full" :class="dotColor[variant]" />
        <slot />
    </span>
</template>
