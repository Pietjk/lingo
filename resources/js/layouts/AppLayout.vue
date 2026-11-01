<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <header
            class="border-b border-[#e3e3e0] px-6 py-4 dark:border-[#3E3E3A]"
        >
            <Link :href="home()" class="text-lg font-semibold tracking-widest">
                LINGO
            </Link>
        </header>
        <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col p-6">
            <slot />
        </main>
    </div>
</template>
