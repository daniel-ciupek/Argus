<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    status?: string;
}>();

const { t } = useI18n();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.verifyEmail')" />

        <div class="mb-4 text-sm text-surface-500 dark:text-surface-400">
            {{ t('auth.verifyEmailDesc') }}
        </div>

        <div
            class="mb-4 text-sm font-medium text-success-600 dark:text-success-400"
            v-if="verificationLinkSent"
        >
            {{ t('auth.verifyEmailSent') }}
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('auth.resendVerification') }}
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-surface-600 underline hover:text-accent-600 focus:outline-none focus:ring-2 focus:ring-accent-500 focus:ring-offset-2 dark:text-surface-400 dark:hover:text-accent-400 dark:focus:ring-offset-surface-900"
                    >{{ t('nav.logout') }}</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
