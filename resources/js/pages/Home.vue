<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import Toast from '@/components/Toast.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { join, show, store } from '@/routes/rooms';

defineOptions({ layout: AppLayout });

const props = defineProps<{ code?: string }>();

const http = useHttp({ nickname: '', code: props.code ?? '' });
const failure = ref<string | null>(null);

async function joinRoom(code: string) {
    http.code = code;
    await http.post(join.url());
    router.visit(show.url(code));
}

async function submit(createRoom: boolean) {
    failure.value = null;

    try {
        if (createRoom) {
            const room = await http.post(store.url());
            await joinRoom((room as { code: string }).code);
        } else {
            await joinRoom(http.code);
        }
    } catch {
        failure.value = 'Er ging iets mis. Probeer het opnieuw.';
    }
}

const inputClasses =
    'w-full rounded-xl border border-blue-500/40 bg-[#061439]/80 px-4 py-2.5 text-white placeholder-blue-300/40 shadow-inner transition-colors focus:border-yellow-400 focus:outline-none focus:ring-2 focus:ring-yellow-400/30';
const buttonClasses =
    'rounded-xl px-5 py-2.5 font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer';
</script>

<template>
    <Head title="Start" />

    <div class="m-auto w-full max-w-sm space-y-6">
        <Toast
            v-if="failure"
            :message="failure"
            @close="failure = null"
        />

        <div
            class="w-full space-y-6 rounded-2xl border border-blue-400/30 bg-[#081a4d]/80 p-6 shadow-2xl backdrop-blur-md"
        >
            <h1 class="text-center text-2xl font-black text-white">
                Lingo voor twee spelers
            </h1>

            <div class="space-y-1.5">
                <label for="nickname" class="text-sm font-semibold text-blue-200">
                    Je naam
                </label>
                <input
                    id="nickname"
                    v-model="http.nickname"
                    v-focus
                    maxlength="20"
                    autocomplete="nickname"
                    :class="inputClasses"
                />
                <p v-if="http.errors.nickname" class="text-xs font-semibold text-red-400">
                    Vul een naam in (maximaal 20 tekens).
                </p>
            </div>

            <button
                type="button"
                :disabled="http.processing || !http.nickname.trim()"
                :class="[
                    buttonClasses,
                    'w-full bg-gradient-to-b from-yellow-300 to-amber-500 text-blue-950 shadow-lg shadow-yellow-500/20 hover:from-yellow-200 hover:to-amber-400 active:scale-[0.98]',
                ]"
                @click="submit(true)"
            >
                Kamer maken
            </button>

            <div class="flex items-center gap-3 text-xs font-semibold uppercase tracking-wider text-blue-300/60">
                <span class="h-px flex-1 bg-blue-500/30" />
                of
                <span class="h-px flex-1 bg-blue-500/30" />
            </div>

            <form class="space-y-3" @submit.prevent="submit(false)">
                <div class="space-y-1.5">
                    <label for="code" class="text-sm font-semibold text-blue-200">
                        Kamercode
                    </label>
                    <input
                        id="code"
                        v-model="http.code"
                        maxlength="4"
                        placeholder="ABCD"
                        autocapitalize="characters"
                        :class="[inputClasses, 'text-center font-mono font-bold tracking-[0.5em] uppercase']"
                    />
                </div>
                <p v-if="http.errors.code" class="text-xs font-semibold text-red-400">
                    {{ http.errors.code }}
                </p>
                <button
                    type="submit"
                    :disabled="
                        http.processing ||
                        !http.nickname.trim() ||
                        http.code.length !== 4
                    "
                    :class="[
                        buttonClasses,
                        'w-full border border-blue-400/40 bg-blue-900/60 text-white hover:bg-blue-800/80 active:scale-[0.98]',
                    ]"
                >
                    Deelnemen
                </button>
            </form>

            <p v-if="failure" class="text-center text-xs font-semibold text-red-400">{{ failure }}</p>
        </div>
    </div>
</template>

