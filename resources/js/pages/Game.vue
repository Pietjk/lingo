<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { useEchoPresence } from '@laravel/echo-vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Board from '@/components/Board.vue';
import Keyboard from '@/components/Keyboard.vue';
import MatchResult from '@/components/MatchResult.vue';
import Toast from '@/components/Toast.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatClock, useCountdown } from '@/lib/useCountdown';
import {
    expire,
    forfeit,
    guess,
    heartbeat,
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
    opponentForfeited: boolean;
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
const toastMessage = ref<string | null>(null);
const shakeActiveRow = ref(false);
let shakeTimeout: ReturnType<typeof setTimeout> | null = null;
let heartbeatInterval: number | null = null;
const scores = ref<Record<string, number>>({ ...props.scores });
const matchOver = ref(props.matchOver);
const forfeited = computed(
    () => props.opponentForfeited || result.value?.reason === 'forfeit',
);
const solvedPoints = ref<number | null>(null);

const roundTimer = useCountdown();
const forfeitTimer = useCountdown();

const http = useHttp({ guess: '' });

function showToast(msg: string) {
    toastMessage.value = msg;
}

function triggerShake() {
    shakeActiveRow.value = false;
    if (shakeTimeout) {
        clearTimeout(shakeTimeout);
    }
    requestAnimationFrame(() => {
        shakeActiveRow.value = true;
        shakeTimeout = setTimeout(() => {
            shakeActiveRow.value = false;
        }, 600);
    });
}

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
        triggerShake();
        const errorMsg = http.errors.guess ?? 'Er ging iets mis.';
        message.value = errorMsg;
        showToast(errorMsg);
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
            triggerShake();
            const errorMsg = 'Vul 5 letters in.';
            message.value = errorMsg;
            showToast(errorMsg);
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
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    if (shakeTimeout) {
        clearTimeout(shakeTimeout);
    }
    if (heartbeatInterval !== null) {
        window.clearInterval(heartbeatInterval);
    }
});

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
        toastMessage.value = null;
        shakeActiveRow.value = false;
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
    const sendHeartbeat = () => {
        if (!matchOver.value) {
            void callHttp.post(heartbeat.url(props.code));
        }
    };

    sendHeartbeat();
    heartbeatInterval = window.setInterval(sendHeartbeat, 10_000);

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

    <div class="flex flex-1 flex-col items-center gap-4 sm:gap-6 w-full">
        <Toast
            v-if="toastMessage"
            :message="toastMessage"
            @close="toastMessage = null"
        />

        <!-- Game HUD bar -->
        <div class="flex w-full flex-col items-center gap-2.5">
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 text-xs sm:text-sm">
                <span
                    class="rounded-full border border-blue-500/30 bg-[#061439]/80 px-3.5 py-1 font-semibold text-blue-200 shadow-inner"
                >
                    Kamer <strong class="font-mono uppercase tracking-wider text-yellow-400">{{ code }}</strong>
                </span>
                <span
                    class="rounded-full border border-blue-500/30 bg-[#061439]/80 px-3.5 py-1 font-semibold text-blue-200 shadow-inner"
                >
                    Ronde <strong class="text-white">{{ round }}</strong> van {{ totalRounds }}
                </span>
            </div>

            <div
                v-if="roundTimer.running.value"
                class="flex items-center gap-2 rounded-2xl border border-yellow-400/30 bg-[#061439]/90 px-4 py-1 shadow-[0_0_20px_rgba(250,204,21,0.15)] backdrop-blur-sm sm:px-5"
                aria-label="Resterende tijd"
            >
                <span class="size-2 rounded-full bg-red-500 animate-ping" />
                <span
                    class="font-mono text-xl sm:text-2xl font-black tracking-wider text-yellow-400 tabular-nums drop-shadow-[0_0_10px_rgba(250,204,21,0.5)]"
                >
                    {{ formatClock(roundTimer.seconds.value) }}
                </span>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                <div
                    v-for="(score, name) in scores"
                    :key="name"
                    class="flex items-center gap-2 rounded-xl border border-blue-500/30 bg-[#061439]/70 px-3 py-1 text-xs sm:text-sm shadow-sm"
                >
                    <span :class="name === me ? 'font-bold text-yellow-400' : 'font-semibold text-blue-100'">
                        {{ name }}{{ name === me ? ' (jij)' : '' }}:
                    </span>
                    <strong class="font-mono font-bold text-white">{{ score }}</strong>
                </div>
            </div>
        </div>

        <div
            v-if="forfeitTimer.running.value"
            role="alert"
            class="rounded-xl border-2 border-amber-400 bg-amber-950/80 px-4 py-3 text-center text-xs sm:text-sm text-amber-200 shadow-xl backdrop-blur-md"
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
            :forfeit="forfeited"
            :processing="callHttp.processing"
            @rematch="playAgain"
        />

        <div v-else class="flex w-full flex-col items-center gap-4 sm:gap-6">
            <div
                class="flex w-full flex-col items-center gap-4 sm:gap-6 md:flex-row md:items-start md:justify-center"
            >
                <section
                    class="order-2 flex flex-col items-center gap-2 rounded-2xl border border-blue-500/30 bg-[#071948]/60 p-3 sm:p-4 shadow-xl backdrop-blur-sm md:order-1"
                    aria-label="Tegenstander"
                >
                    <h2 class="text-center text-xs sm:text-sm font-bold uppercase tracking-wider text-blue-300">
                        {{ opponent ?? 'Tegenstander' }}
                    </h2>
                    <Board :rows="opponentRows" size="sm" />
                </section>

                <section
                    class="order-1 flex flex-col items-center gap-2 rounded-2xl border-2 border-blue-400/40 bg-[#071948]/80 p-3 sm:p-5 shadow-2xl backdrop-blur-sm md:order-2"
                    aria-label="Jouw bord"
                >
                    <h2 class="text-center text-xs sm:text-sm font-black uppercase tracking-wider text-yellow-400">
                        {{ me }} (jij)
                    </h2>
                    <Board :rows="rows" :current="current" :shake="shakeActiveRow" />
                </section>
            </div>

            <p
                v-if="solvedPoints !== null && !finished"
                class="rounded-xl border border-emerald-400/50 bg-emerald-950/80 px-4 py-2 text-center text-xs sm:text-sm font-bold text-emerald-300 shadow-lg"
            >
                🎉 Opgelost! +{{ solvedPoints }} punten. Wachten op de tegenstander…
            </p>
            <p
                v-else-if="outOfGuesses"
                class="rounded-xl border border-blue-500/40 bg-[#061439]/80 px-4 py-2 text-center text-xs sm:text-sm font-medium text-blue-200"
            >
                Geen pogingen meer. Wachten op de tegenstander…
            </p>

            <div
                v-if="finished && result"
                class="w-full max-w-md space-y-3.5 rounded-2xl border-2 border-yellow-400/50 bg-[#081a4d]/95 p-5 text-center shadow-2xl backdrop-blur-md"
            >
                <p class="text-xl sm:text-2xl font-black text-yellow-400 drop-shadow-sm">
                    {{
                        result.winner
                            ? `🏆 ${result.winner} was het snelst!`
                            : 'Niemand heeft het woord geraden.'
                    }}
                </p>
                <p class="flex flex-wrap items-center justify-center gap-2 text-sm font-medium text-blue-100">
                    <span>Het woord was:</span>
                    <span
                        class="inline-block rounded-none border-2 border-red-500 bg-red-600 px-3 py-1 font-mono text-lg sm:text-xl font-black uppercase tracking-widest text-white shadow-md"
                    >
                        {{ result.word }}
                    </span>
                </p>
                <div class="flex flex-wrap items-center justify-center gap-2 text-xs sm:text-sm text-blue-200">
                    <span
                        v-for="(points, name) in result.points"
                        :key="name"
                        class="rounded-lg border border-blue-500/30 bg-[#061439]/80 px-2.5 py-1 font-semibold"
                    >
                        {{ name }}: <strong class="text-yellow-400">+{{ points }}</strong>
                    </span>
                </div>
                <button
                    type="button"
                    :disabled="callHttp.processing"
                    class="w-full rounded-xl bg-gradient-to-b from-yellow-300 to-amber-500 px-6 py-2.5 text-sm sm:text-base font-black text-blue-950 shadow-lg shadow-yellow-500/25 transition-all hover:from-yellow-200 hover:to-amber-400 active:scale-[0.98] disabled:opacity-40 cursor-pointer"
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

        <ul class="flex flex-wrap justify-center gap-4 text-xs font-semibold text-blue-200">
            <li class="flex items-center gap-1.5">
                <span class="inline-block size-3.5 bg-red-600 border border-red-500 shadow-sm" /> Juiste plek
            </li>
            <li class="flex items-center gap-1.5">
                <span class="inline-block size-3.5 rounded-full bg-yellow-400 border border-yellow-300 shadow-sm" />
                Verkeerde plek
            </li>
            <li class="flex items-center gap-1.5">
                <span class="inline-block size-3.5 rounded-md bg-[#081b49] border border-blue-900" />
                Komt niet voor
            </li>
        </ul>
    </div>
</template>
