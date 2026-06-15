<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
    }>(),
    {
        type: 'button',
    },
);
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-surface-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-surface-700 shadow-sm transition duration-150 ease-in-out hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-accent-500 focus:ring-offset-2 disabled:opacity-25 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-200 dark:hover:bg-surface-800 dark:focus:ring-offset-surface-900"
    >
        <slot />
    </button>
</template>
