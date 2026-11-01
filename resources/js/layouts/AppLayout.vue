<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-gradient-to-b from-[#0a1e58] via-[#0d2875] to-[#07153d] text-white selection:bg-yellow-400 selection:text-blue-950"
    >
        <header
            class="border-b border-blue-500/30 bg-[#071746]/80 px-4 py-3 shadow-lg shadow-blue-950/50 backdrop-blur-md sm:px-6 sm:py-3.5"
        >
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <Link
                    :href="home()"
                    class="group flex items-center gap-2 rounded-lg px-1 focus:outline-none focus:ring-2 focus:ring-yellow-400/50"
                >
                    <span
                        class="text-2xl font-black tracking-[0.28em] text-yellow-400 drop-shadow-[0_2px_10px_rgba(250,204,21,0.5)] transition-transform group-hover:scale-105 sm:text-3xl"
                    >
                        LINGO
                    </span>
                </Link>
            </div>
        </header>
        <main class="mx-auto flex w-full max-w-4xl flex-1 flex-col p-3 sm:p-6">
            <slot />
        </main>
    </div>
</template>

