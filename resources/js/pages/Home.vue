<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    'w-full rounded-lg border border-[#e3e3e0] bg-white px-4 py-2 dark:border-[#3E3E3A] dark:bg-[#161615]';
const buttonClasses =
    'rounded-lg px-5 py-2 font-medium disabled:opacity-50';
</script>

<template>
    <Head title="Start" />

    <div class="m-auto w-full max-w-sm space-y-6">
        <h1 class="text-2xl font-semibold">Lingo voor twee spelers</h1>

        <div class="space-y-1">
            <label for="nickname" class="text-sm font-medium">Je naam</label>
            <input
                id="nickname"
                v-model="http.nickname"
                v-focus
                maxlength="20"
                autocomplete="nickname"
                :class="inputClasses"
            />
            <p v-if="http.errors.nickname" class="text-sm text-red-600">
                Vul een naam in (maximaal 20 tekens).
            </p>
        </div>

        <button
            type="button"
            :disabled="http.processing || !http.nickname.trim()"
            :class="[buttonClasses, 'w-full bg-[#1b1b18] text-white dark:bg-[#eeeeec] dark:text-[#1C1C1A]']"
            @click="submit(true)"
        >
            Kamer maken
        </button>

        <div class="flex items-center gap-3 text-sm text-[#706f6c]">
            <span class="h-px flex-1 bg-[#e3e3e0] dark:bg-[#3E3E3A]" />
            of
            <span class="h-px flex-1 bg-[#e3e3e0] dark:bg-[#3E3E3A]" />
        </div>

        <form class="space-y-3" @submit.prevent="submit(false)">
            <label for="code" class="text-sm font-medium">Kamercode</label>
            <input
                id="code"
                v-model="http.code"
                maxlength="4"
                placeholder="ABCD"
                autocapitalize="characters"
                :class="[inputClasses, 'text-center tracking-[0.5em] uppercase']"
            />
            <p v-if="http.errors.code" class="text-sm text-red-600">
                {{ http.errors.code }}
            </p>
            <button
                type="submit"
                :disabled="
                    http.processing ||
                    !http.nickname.trim() ||
                    http.code.length !== 4
                "
                :class="[buttonClasses, 'w-full border border-[#e3e3e0] dark:border-[#3E3E3A]']"
            >
                Deelnemen
            </button>
        </form>

        <p v-if="failure" class="text-sm text-red-600">{{ failure }}</p>
    </div>
</template>
