<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { useEchoPresence } from '@laravel/echo-vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Board from '@/components/Board.vue';
import Keyboard from '@/components/Keyboard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { guess, start } from '@/routes/rooms';
import {
    MAX_GUESSES,
    WORD_LENGTH,
    type BoardRow,
    type LetterStatus,
} from '@/types/game';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    code: string;
    me: string;
    opponent: string | null;
    round: number;
    finished: boolean;
}>();

const rows = ref<BoardRow[]>([]);
const opponentRows = ref<BoardRow[]>([]);
const current = ref('');
const finished = ref(props.finished);
const result = ref<{ word: string; winner: string | null } | null>(null);
const message = ref<string | null>(null);

const http = useHttp({ guess: '' });

const isLocked = computed(
    () =>
        finished.value ||
        http.processing ||
        rows.value.length >= MAX_GUESSES,
);

const rank: Record<LetterStatus, number> = { absent: 0, present: 1, correct: 2 };

const letterStatuses = computed(() => {
    const best: Record<string, LetterStatus> = {};

    for (const row of rows.value) {
        row.statuses?.forEach((status, i) => {
            const letter = row.letters[i];

            if (!(letter in best) || rank[status] > rank[best[letter]]) {
                best[letter] = status;
            }
        });
    }

    return best;
});

async function submitGuess() {
    http.guess = current.value;
    message.value = null;

    try {
        const response = (await http.post(guess.url(props.code))) as {
            statuses: LetterStatus[];
        };

        rows.value.push({ letters: current.value, statuses: response.statuses });
        current.value = '';
    } catch {
        message.value = http.errors.guess ?? 'Er ging iets mis.';
    }
}

function handleKey(key: string) {
    if (isLocked.value) {
        return;
    }

    if (key === 'Enter') {
        if (current.value.length === WORD_LENGTH) {
            void submitGuess();
        } else {
            message.value = 'Vul 5 letters in.';
        }
    } else if (key === 'Backspace') {
        current.value = current.value.slice(0, -1);
    } else if (/^[a-z]$/i.test(key) && current.value.length < WORD_LENGTH) {
        current.value += key.toLowerCase();
    }
}

function onKeydown(event: KeyboardEvent) {
    if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    if (
        event.target instanceof HTMLButtonElement &&
        event.key === 'Enter'
    ) {
        return;
    }

    handleKey(event.key);
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

useEchoPresence(
    `room.${props.code}`,
    'OpponentGuessed',
    (payload: { nickname: string; statuses: LetterStatus[] }) => {
        if (payload.nickname !== props.me) {
            opponentRows.value.push({ letters: '', statuses: payload.statuses });
        }
    },
);

useEchoPresence(
    `room.${props.code}`,
    'RoundFinished',
    (payload: { word: string; winner: string | null }) => {
        finished.value = true;
        result.value = payload;
    },
);

useEchoPresence(`room.${props.code}`, 'RoundStarted', () => {
    rows.value = [];
    opponentRows.value = [];
    current.value = '';
    message.value = null;
    result.value = null;
    finished.value = false;
    router.reload({ only: ['round'] });
});

const startHttp = useHttp({});

function nextRound() {
    void startHttp.post(start.url(props.code));
}

const outOfGuesses = computed(
    () => !finished.value && rows.value.length >= MAX_GUESSES,
);
</script>

<template>
    <Head title="Spel" />

    <div class="flex flex-1 flex-col items-center gap-6">
        <p class="text-sm text-[#706f6c]">
            Kamer {{ code }} · Ronde {{ round }}
        </p>

        <div class="flex w-full flex-col items-center gap-6 md:flex-row md:items-start md:justify-center">
            <section class="order-2 space-y-2 md:order-1" aria-label="Tegenstander">
                <h2 class="text-center text-sm font-medium">
                    {{ opponent ?? 'Tegenstander' }}
                </h2>
                <Board :rows="opponentRows" size="sm" />
            </section>

            <section class="order-1 space-y-2 md:order-2" aria-label="Jouw bord">
                <h2 class="text-center text-sm font-medium">{{ me }} (jij)</h2>
                <Board :rows="rows" :current="current" />
            </section>
        </div>

        <p v-if="message" role="alert" class="text-sm text-red-600">
            {{ message }}
        </p>
        <p v-else-if="outOfGuesses" class="text-sm text-[#706f6c]">
            Geen pogingen meer. Wachten op de tegenstander…
        </p>

        <div
            v-if="finished && result"
            class="space-y-3 text-center"
        >
            <p class="text-lg font-semibold">
                {{
                    result.winner
                        ? `${result.winner} heeft gewonnen!`
                        : 'Niemand heeft het woord geraden.'
                }}
            </p>
            <p>
                Het woord was
                <span class="font-bold uppercase">{{ result.word }}</span>
            </p>
            <button
                type="button"
                :disabled="startHttp.processing"
                class="rounded-lg bg-[#1b1b18] px-5 py-2 font-medium text-white disabled:opacity-50 dark:bg-[#eeeeec] dark:text-[#1C1C1A]"
                @click="nextRound"
            >
                Volgende ronde
            </button>
        </div>

        <Keyboard
            :letter-statuses="letterStatuses"
            :disabled="isLocked"
            @key="handleKey"
        />

        <ul class="flex gap-4 text-xs text-[#706f6c]">
            <li class="flex items-center gap-1">
                <span class="inline-block size-3 bg-green-600" /> Juiste plek
            </li>
            <li class="flex items-center gap-1">
                <span class="inline-block size-3 rounded-full bg-amber-500" />
                Verkeerde plek
            </li>
            <li class="flex items-center gap-1">
                <span class="inline-block size-3 rounded-md bg-neutral-500" />
                Komt niet voor
            </li>
        </ul>
    </div>
</template>
