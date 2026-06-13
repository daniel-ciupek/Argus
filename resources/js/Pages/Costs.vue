<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Costs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Costs
            </h2>
        </template>

        <div class="p-6 text-gray-900">
            Placeholder — token usage and cost charts (Faza 5).
        </div>
    </AuthenticatedLayout>
</template>
