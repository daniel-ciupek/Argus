<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="MCP" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                MCP connections
            </h2>
        </template>

        <div class="p-6 text-gray-900">
            Placeholder — MCP connection statuses (Faza 6).
        </div>
    </AuthenticatedLayout>
</template>
