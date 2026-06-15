<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-danger-600 dark:text-danger-400">
            {{ message }}
        </p>
    </div>
</template>
