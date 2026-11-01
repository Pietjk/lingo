<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { useEchoPresence } from '@laravel/echo-vue';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { show, start } from '@/routes/rooms';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    code: string;
    me: string;
    players: string[];
    shareUrl: string;
}>();

const names = computed(() => props.players);
const copied = ref(false);
const http = useHttp({});

const isReady = computed(() => names.value.length >= 2);
const isCreator = computed(() => names.value[0] === props.me);

useEchoPresence(`room.${props.code}`, 'PlayerJoined', () =>
    router.reload({ only: ['players'] }),
);

useEchoPresence(`room.${props.code}`, 'RoundStarted', () =>
    router.visit(show.url(props.code)),
);

async function copyLink() {
    await navigator.clipboard.writeText(props.shareUrl);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

async function startGame() {
    await http.post(start.url(props.code));
    router.visit(show.url(props.code));
}
</script>

<template>
    <Head title="Lobby" />

    <div
        class="m-auto w-full max-w-sm space-y-6 rounded-2xl border border-blue-400/30 bg-[#081a4d]/80 p-6 text-center shadow-2xl backdrop-blur-md"
    >
        <div class="space-y-1">
            <h1 class="text-xs font-bold uppercase tracking-widest text-blue-300">
                Wachtkamer
            </h1>
            <p
                class="font-mono text-4xl sm:text-5xl font-black tracking-[0.35em] text-yellow-400 drop-shadow-[0_2px_12px_rgba(250,204,21,0.5)]"
            >
                {{ code }}
            </p>
        </div>

        <div v-if="isCreator && !isReady" class="space-y-2 text-left">
            <p class="text-xs font-semibold text-blue-200">
                Deel deze link met je tegenstander:
            </p>
            <div class="flex gap-2">
                <input
                    readonly
                    :value="shareUrl"
                    class="min-w-0 flex-1 rounded-xl border border-blue-500/40 bg-[#061439]/80 px-3 py-2 text-xs sm:text-sm text-blue-100 shadow-inner focus:outline-none"
                    @focus="($event.target as HTMLInputElement).select()"
                />
                <button
                    type="button"
                    class="rounded-xl border border-blue-400/40 bg-blue-900/60 px-3.5 py-2 text-xs sm:text-sm font-bold text-white transition-all hover:bg-blue-800 active:scale-95 cursor-pointer"
                    @click="copyLink"
                >
                    {{ copied ? 'Gekopieerd!' : 'Kopiëren' }}
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-300/80">
                Spelers ({{ names.length }}/2)
            </p>
            <ul class="space-y-2">
                <li
                    v-for="name in names"
                    :key="name"
                    class="flex items-center justify-between rounded-xl border border-blue-500/30 bg-[#061439]/70 px-4 py-2.5 text-sm font-bold text-white"
                >
                    <span class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-emerald-400" />
                        {{ name }}
                    </span>
                    <span v-if="name === me" class="text-xs text-yellow-400">
                        (jij)
                    </span>
                </li>
            </ul>
        </div>

        <p v-if="!isReady" class="animate-pulse text-xs font-medium text-blue-200">
            Wachten op tegenstander…
        </p>

        <button
            v-else-if="isCreator"
            type="button"
            :disabled="http.processing"
            class="w-full rounded-xl bg-gradient-to-b from-yellow-300 to-amber-500 px-5 py-3 font-black text-blue-950 shadow-lg shadow-yellow-500/25 transition-all hover:from-yellow-200 hover:to-amber-400 active:scale-[0.98] disabled:opacity-40 cursor-pointer"
            @click="startGame"
        >
            Start spel
        </button>
        <p v-else class="animate-pulse text-xs font-medium text-blue-200">
            Wachten tot {{ names[0] }} het spel start…
        </p>
    </div>
</template>

