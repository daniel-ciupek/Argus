<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useColorMode } from '@/composables/useColorMode';
import { useLocale } from '@/composables/useLocale';
import {
    LayoutDashboard,
    ScrollText,
    CircleDollarSign,
    ListChecks,
    Cable,
    Wallet,
    Settings,
    Radar,
    Sun,
    Moon,
    Menu,
    X,
    LogOut,
    ChevronDown,
    type LucideIcon,
} from '@lucide/vue';

type NavItem = { labelKey: string; route: string; icon: LucideIcon };

const navItems: NavItem[] = [
    { labelKey: 'nav.dashboard', route: 'dashboard', icon: LayoutDashboard },
    { labelKey: 'nav.logs', route: 'logs', icon: ScrollText },
    { labelKey: 'nav.costs', route: 'costs', icon: CircleDollarSign },
    { labelKey: 'nav.tasks', route: 'tasks', icon: ListChecks },
    { labelKey: 'nav.mcp', route: 'mcp', icon: Cable },
    { labelKey: 'nav.budgets', route: 'budgets', icon: Wallet },
    { labelKey: 'nav.settings', route: 'settings', icon: Settings },
];

const { t } = useI18n();
const page = usePage();
const { mode, toggle } = useColorMode();
const { locale, toggle: toggleLocale } = useLocale();

const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-surface-50 text-surface-900 dark:bg-surface-950 dark:text-surface-100">
        <!-- Mobile overlay -->
        <Transition
            enter-active-class="transition-opacity ease-out duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity ease-in duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-30 bg-surface-950/60 backdrop-blur-sm lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-60 shrink-0 flex-col border-r border-surface-200 bg-white transition-transform duration-200 dark:border-surface-800 dark:bg-surface-900 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand -->
            <div class="flex h-16 items-center gap-2.5 border-b border-surface-200 px-4 dark:border-surface-800">
                <div class="flex h-8 w-8 items-center justify-center rounded-card bg-accent-500 text-white">
                    <Radar class="h-5 w-5" :stroke-width="2.25" />
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-semibold tracking-tight">Hermes</p>
                    <p class="font-mono text-[10px] uppercase tracking-widest text-surface-400">cockpit</p>
                </div>
                <button
                    type="button"
                    class="ml-auto rounded-md p-1.5 text-surface-500 hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200 lg:hidden"
                    aria-label="Close navigation"
                    @click="sidebarOpen = false"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 space-y-0.5 overflow-y-auto p-3">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        route().current(item.route)
                            ? 'bg-accent-500/10 text-accent-600 dark:text-accent-400'
                            : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900 dark:text-surface-400 dark:hover:bg-surface-800 dark:hover:text-surface-100'
                    "
                    @click="sidebarOpen = false"
                >
                    <component
                        :is="item.icon"
                        class="h-[18px] w-[18px] shrink-0"
                        :class="
                            route().current(item.route)
                                ? 'text-accent-500'
                                : 'text-surface-400 group-hover:text-surface-600 dark:group-hover:text-surface-300'
                        "
                    />
                    {{ t(item.labelKey) }}
                </Link>
            </nav>

            <!-- Sidebar footer: live status -->
            <div class="border-t border-surface-200 px-4 py-3 dark:border-surface-800">
                <div class="flex items-center gap-2 font-mono text-[11px] text-surface-400">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-500 opacity-75" />
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-success-500" />
                    </span>
                    <span>{{ t('nav.realtimeConnected') }}</span>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <div class="lg:pl-60">
            <!-- Topbar -->
            <header
                class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-surface-200 bg-white/80 px-4 backdrop-blur dark:border-surface-800 dark:bg-surface-900/80 sm:px-6"
            >
                <button
                    type="button"
                    class="rounded-md p-2 text-surface-500 hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200 lg:hidden"
                    aria-label="Open navigation"
                    @click="sidebarOpen = true"
                >
                    <Menu class="h-5 w-5" />
                </button>

                <!-- Page heading slot -->
                <div class="min-w-0 flex-1">
                    <slot name="header" />
                </div>

                <!-- Locale toggle -->
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1.5 font-mono text-xs font-medium text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                    :aria-label="locale === 'pl' ? 'Switch to English' : 'Przełącz na polski'"
                    @click="toggleLocale"
                >
                    {{ locale === 'pl' ? 'EN' : 'PL' }}
                </button>

                <!-- Theme toggle -->
                <button
                    type="button"
                    class="rounded-md p-2 text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-700 dark:hover:bg-surface-800 dark:hover:text-surface-200"
                    :aria-label="mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    @click="toggle"
                >
                    <Sun v-if="mode === 'dark'" class="h-5 w-5" />
                    <Moon v-else class="h-5 w-5" />
                </button>

                <!-- User menu -->
                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-md py-1.5 pl-1.5 pr-2 text-sm font-medium text-surface-700 transition-colors hover:bg-surface-100 dark:text-surface-200 dark:hover:bg-surface-800"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-accent-500/15 font-mono text-xs font-semibold uppercase text-accent-600 dark:text-accent-400"
                        >
                            {{ page.props.auth.user.name.slice(0, 2) }}
                        </span>
                        <span class="hidden sm:inline">{{ page.props.auth.user.name }}</span>
                        <ChevronDown class="h-4 w-4 text-surface-400" />
                    </button>

                    <!-- Click-away overlay -->
                    <div v-show="userMenuOpen" class="fixed inset-0 z-30" @click="userMenuOpen = false" />

                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-show="userMenuOpen"
                            class="absolute right-0 z-40 mt-2 w-56 origin-top-right overflow-hidden rounded-card border border-surface-200 bg-white shadow-lg dark:border-surface-800 dark:bg-surface-900"
                        >
                            <div class="border-b border-surface-200 px-4 py-3 dark:border-surface-800">
                                <p class="truncate text-sm font-medium">{{ page.props.auth.user.name }}</p>
                                <p class="truncate font-mono text-xs text-surface-400">
                                    {{ page.props.auth.user.email }}
                                </p>
                            </div>
                            <div class="p-1">
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm text-surface-700 hover:bg-surface-100 dark:text-surface-200 dark:hover:bg-surface-800"
                                    @click="userMenuOpen = false"
                                >
                                    <Settings class="h-4 w-4 text-surface-400" />
                                    {{ t('nav.profile') }}
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm text-danger-600 hover:bg-danger-500/10 dark:text-danger-400"
                                >
                                    <LogOut class="h-4 w-4" />
                                    {{ t('nav.logout') }}
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
