<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const { t } = useI18n();

const cardClasses =
    'rounded-card border border-surface-200 bg-white p-5 shadow-card dark:border-surface-800 dark:bg-surface-900 sm:p-8';
</script>

<template>
    <Head :title="t('profile.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-lg font-semibold tracking-tight">{{ t('profile.title') }}</h1>
                <p class="font-mono text-xs text-surface-400">{{ t('profile.subtitle') }}</p>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
            <div :class="cardClasses">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div :class="cardClasses">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div :class="cardClasses">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
