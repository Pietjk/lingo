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
}

async function startGame() {
    await http.post(start.url(props.code));
    router.visit(show.url(props.code));
}
</script>

<template>
    <Head title="Lobby" />

    <div class="m-auto w-full max-w-sm space-y-6 text-center">
        <h1 class="text-2xl font-semibold">Wachtkamer</h1>
        <p class="text-4xl font-bold tracking-[0.4em]">{{ code }}</p>

        <div v-if="isCreator && !isReady" class="space-y-2">
            <p class="text-sm text-[#706f6c]">
                Deel deze link met je tegenstander:
            </p>
            <div class="flex gap-2">
                <input
                    readonly
                    :value="shareUrl"
                    class="min-w-0 flex-1 rounded-lg border border-[#e3e3e0] bg-white px-3 py-2 text-sm dark:border-[#3E3E3A] dark:bg-[#161615]"
                    @focus="($event.target as HTMLInputElement).select()"
                />
                <button
                    type="button"
                    class="rounded-lg border border-[#e3e3e0] px-4 py-2 text-sm dark:border-[#3E3E3A]"
                    @click="copyLink"
                >
                    {{ copied ? 'Gekopieerd!' : 'Kopiëren' }}
                </button>
            </div>
        </div>

        <ul class="space-y-1">
            <li v-for="name in names" :key="name" class="font-medium">
                {{ name }}<span v-if="name === me"> (jij)</span>
            </li>
        </ul>

        <p v-if="!isReady" class="animate-pulse text-sm text-[#706f6c]">
            Wachten op tegenstander… ({{ names.length }}/2)
        </p>

        <button
            v-else-if="isCreator"
            type="button"
            :disabled="http.processing"
            class="w-full rounded-lg bg-[#1b1b18] px-5 py-2 font-medium text-white disabled:opacity-50 dark:bg-[#eeeeec] dark:text-[#1C1C1A]"
            @click="startGame"
        >
            Start
        </button>
        <p v-else class="animate-pulse text-sm text-[#706f6c]">
            Wachten tot {{ names[0] }} het spel start…
        </p>
    </div>
</template>
