<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { useEchoPresence } from '@laravel/echo-vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Board from '@/components/Board.vue';
import Keyboard from '@/components/Keyboard.vue';
import MatchResult from '@/components/MatchResult.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatClock, useCountdown } from '@/lib/useCountdown';
import {
    expire,
    forfeit,
    guess,
    opponentLeft,
    opponentReturned,
    rematch,
    start,
} from '@/routes/rooms';
import {
    MAX_GUESSES,
    WORD_LENGTH,
    type BoardRow,
    type LetterStatus,
} from '@/types/game';

defineOptions({ layout: AppLayout });

type RoundResult = {
    word: string;
    winner: string | null;
    scores: Record<string, number>;
    points: Record<string, number>;
    matchOver: boolean;
    matchWinner: string | null;
    reason: string;
};

const props = defineProps<{
    code: string;
    me: string;
    opponent: string | null;
    round: number;
    finished: boolean;
    matchOver: boolean;
    totalRounds: number;
    scores: Record<string, number>;
    secondsLeft: number;
}>();

const rows = ref<BoardRow[]>([]);
const opponentRows = ref<BoardRow[]>([]);
const current = ref('');
const finished = ref(props.finished);
const result = ref<RoundResult | null>(null);
const message = ref<string | null>(null);
const scores = ref<Record<string, number>>({ ...props.scores });
const matchOver = ref(props.matchOver);
const solvedPoints = ref<number | null>(null);

const roundTimer = useCountdown();
const forfeitTimer = useCountdown();

const http = useHttp({ guess: '' });

const matchWinner = computed(() => {
    if (result.value?.matchOver) {
        return result.value.matchWinner;
    }

    const [first, second] = Object.entries(scores.value).sort(
        ([, a], [, b]) => b - a,
    );

    return first && first[1] !== second?.[1] ? first[0] : null;
});

const isLocked = computed(
    () =>
        finished.value ||
        solvedPoints.value !== null ||
        http.processing ||
        rows.value.length >= MAX_GUESSES,
);

const rank: Record<LetterStatus, number> = {
    absent: 0,
    present: 1,
    correct: 2,
};

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
            solved: boolean;
            points: number;
        };

        rows.value.push({
            letters: current.value,
            statuses: response.statuses,
        });
        current.value = '';

        if (response.solved) {
            solvedPoints.value = response.points;
        }
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

    if (event.target instanceof HTMLButtonElement && event.key === 'Enter') {
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
            opponentRows.value.push({
                letters: '',
                statuses: payload.statuses,
            });
        }
    },
);

useEchoPresence(
    `room.${props.code}`,
    'RoundFinished',
    (payload: RoundResult) => {
        roundTimer.stop();
        finished.value = true;
        result.value = payload;
        scores.value = payload.scores;
        matchOver.value = payload.matchOver;

        if (payload.matchOver) {
            forfeitTimer.stop();
        }
    },
);

useEchoPresence(
    `room.${props.code}`,
    'RoundStarted',
    (payload: { round: number; secondsLeft: number }) => {
        rows.value = [];
        opponentRows.value = [];
        current.value = '';
        message.value = null;
        result.value = null;
        finished.value = false;
        matchOver.value = false;
        solvedPoints.value = null;
        roundTimer.start(payload.secondsLeft, expireRound);
        router.reload({ only: ['round'] });
    },
);

const callHttp = useHttp({});

function expireRound() {
    void callHttp.post(expire.url(props.code));
}

function claimForfeit() {
    void callHttp.post(forfeit.url(props.code));
}

async function opponentGone() {
    if (matchOver.value || forfeitTimer.running.value) {
        return;
    }

    try {
        const response = (await callHttp.post(
            opponentLeft.url(props.code),
        )) as {
            seconds: number;
        };

        forfeitTimer.start(response.seconds, claimForfeit);
    } catch {
        // No match in progress; nothing to forfeit.
    }
}

function opponentBack() {
    forfeitTimer.stop();
    void callHttp.post(opponentReturned.url(props.code));
}

const presence = useEchoPresence(`room.${props.code}`);

onMounted(() => {
    if (!finished.value && props.secondsLeft > 0) {
        roundTimer.start(props.secondsLeft, expireRound);
    }

    type Member = { name: string };

    presence
        .channel()
        .here((members: Member[]) => {
            if (
                props.opponent !== null &&
                !members.some((member) => member.name === props.opponent)
            ) {
                void opponentGone();
            }
        })
        .joining((member: Member) => {
            if (member.name === props.opponent) {
                opponentBack();
            }
        })
        .leaving((member: Member) => {
            if (member.name === props.opponent) {
                void opponentGone();
            }
        });
});

function nextRound() {
    void callHttp.post(start.url(props.code));
}

function playAgain() {
    scores.value = Object.fromEntries(
        Object.keys(scores.value).map((name) => [name, 0]),
    );
    void callHttp.post(rematch.url(props.code));
}

const outOfGuesses = computed(
    () => !finished.value && rows.value.length >= MAX_GUESSES,
);
</script>

<template>
    <Head title="Spel" />

    <div class="flex flex-1 flex-col items-center gap-6">
        <p class="text-sm text-[#706f6c]">
            Kamer {{ code }} · Ronde {{ round }} van {{ totalRounds }}
        </p>

        <p
            v-if="roundTimer.running.value"
            class="font-mono text-lg tabular-nums"
            aria-label="Resterende tijd"
        >
            {{ formatClock(roundTimer.seconds.value) }}
        </p>

        <p class="text-sm">
            <template v-for="(score, name) in scores" :key="name">
                <span class="mr-3"
                    >{{ name }}: <strong>{{ score }}</strong></span
                >
            </template>
        </p>

        <div
            v-if="forfeitTimer.running.value"
            role="alert"
            class="rounded-lg border border-amber-500 bg-amber-50 px-4 py-3 text-center text-sm text-amber-900"
        >
            {{ opponent ?? 'De tegenstander' }} heeft de verbinding verbroken.
            Komt niet terug? Dan win jij met forfait over
            <strong>{{ forfeitTimer.seconds.value }}</strong> seconden.
        </div>

        <MatchResult
            v-if="matchOver"
            :me="me"
            :scores="scores"
            :winner="matchWinner"
            :forfeit="result?.reason === 'forfeit'"
            :processing="callHttp.processing"
            @rematch="playAgain"
        />

        <div v-else class="flex w-full flex-col items-center gap-6">
            <div
                class="flex w-full flex-col items-center gap-6 md:flex-row md:items-start md:justify-center"
            >
                <section
                    class="order-2 space-y-2 md:order-1"
                    aria-label="Tegenstander"
                >
                    <h2 class="text-center text-sm font-medium">
                        {{ opponent ?? 'Tegenstander' }}
                    </h2>
                    <Board :rows="opponentRows" size="sm" />
                </section>

                <section
                    class="order-1 space-y-2 md:order-2"
                    aria-label="Jouw bord"
                >
                    <h2 class="text-center text-sm font-medium">
                        {{ me }} (jij)
                    </h2>
                    <Board :rows="rows" :current="current" />
                </section>
            </div>

            <p v-if="message" role="alert" class="text-sm text-red-600">
                {{ message }}
            </p>
            <p
                v-else-if="solvedPoints !== null && !finished"
                class="text-sm font-medium text-green-700"
            >
                Opgelost! +{{ solvedPoints }} punten. Wachten op de
                tegenstander…
            </p>
            <p v-else-if="outOfGuesses" class="text-sm text-[#706f6c]">
                Geen pogingen meer. Wachten op de tegenstander…
            </p>

            <div v-if="finished && result" class="space-y-3 text-center">
                <p class="text-lg font-semibold">
                    {{
                        result.winner
                            ? `${result.winner} was het snelst!`
                            : 'Niemand heeft het woord geraden.'
                    }}
                </p>
                <p>
                    Het woord was
                    <span class="font-bold uppercase">{{ result.word }}</span>
                </p>
                <p class="text-sm">
                    <template
                        v-for="(points, name) in result.points"
                        :key="name"
                    >
                        <span class="mr-3">{{ name }}: +{{ points }}</span>
                    </template>
                </p>
                <button
                    type="button"
                    :disabled="callHttp.processing"
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
        </div>

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
