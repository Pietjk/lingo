<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
import { statusLabels, type TileState } from '@/types/game';

const props = withDefaults(
    defineProps<{
        letter?: string;
        state: TileState;
        size?: 'sm' | 'md';
        isRevealing?: boolean;
        revealDelay?: number;
    }>(),
    {
        letter: '',
        size: 'md',
        isRevealing: false,
        revealDelay: 0,
    },
);

const isFlipping = ref(false);
const isPopping = ref(false);
const displayedState = ref<TileState>(props.state);

let flipTimer: ReturnType<typeof setTimeout> | null = null;
let switchTimer: ReturnType<typeof setTimeout> | null = null;
let endTimer: ReturnType<typeof setTimeout> | null = null;
let popTimer: ReturnType<typeof setTimeout> | null = null;

function clearTimers() {
    if (flipTimer) clearTimeout(flipTimer);
    if (switchTimer) clearTimeout(switchTimer);
    if (endTimer) clearTimeout(endTimer);
    if (popTimer) clearTimeout(popTimer);
    flipTimer = null;
    switchTimer = null;
    endTimer = null;
    popTimer = null;
}

function runRevealAnimation() {
    clearTimers();
    // Prior to reveal, show as filled letter
    displayedState.value = 'filled';
    isFlipping.value = false;

    // Start 3D flip at revealDelay
    flipTimer = setTimeout(() => {
        isFlipping.value = true;
    }, props.revealDelay);

    // Switch state and color at midpoint (225ms into the 450ms flip)
    switchTimer = setTimeout(() => {
        displayedState.value = props.state;
    }, props.revealDelay + 225);

    // End flip
    endTimer = setTimeout(() => {
        isFlipping.value = false;
    }, props.revealDelay + 450);
}

watch(
    () => props.isRevealing,
    (revealing) => {
        if (revealing) {
            runRevealAnimation();
        } else {
            clearTimers();
            isFlipping.value = false;
            displayedState.value = props.state;
        }
    },
    { immediate: true },
);

watch(
    () => props.state,
    (newState) => {
        if (!props.isRevealing) {
            displayedState.value = newState;
        }
    },
);

watch(
    () => props.letter,
    (newVal, oldVal) => {
        if (props.state === 'filled' && newVal && newVal !== oldVal) {
            if (popTimer) clearTimeout(popTimer);
            isPopping.value = true;
            popTimer = setTimeout(() => {
                isPopping.value = false;
            }, 150);
        }
    },
);

onUnmounted(() => {
    clearTimers();
});

// Classic Lingo: red square = juiste plek, yellow circle = verkeerde plek.
const classes = computed(() => ({
    'bg-red-600 border-red-500 text-white rounded-none shadow-[0_4px_12px_rgba(220,38,38,0.4)]':
        displayedState.value === 'correct',
    'bg-yellow-400 border-yellow-300 text-blue-950 font-black rounded-full shadow-[0_4px_12px_rgba(250,204,21,0.4)]':
        displayedState.value === 'present',
    'bg-[#081b49]/90 border-blue-900/80 text-blue-300/40 rounded-lg':
        displayedState.value === 'absent',
    'bg-blue-600/50 border-blue-300 text-white font-black rounded-lg shadow-inner':
        displayedState.value === 'filled',
    'bg-[#081e55]/60 border-blue-600/40 text-transparent rounded-lg':
        displayedState.value === 'empty',
}));

const label = computed(() =>
    displayedState.value === 'empty' || displayedState.value === 'filled'
        ? undefined
        : statusLabels[displayedState.value],
);
</script>

<template>
    <div
        class="flex items-center justify-center border-2 font-black uppercase transition-all select-none"
        :class="[
            classes,
            size === 'sm'
                ? 'size-6 sm:size-8 text-xs sm:text-sm'
                : 'size-11 sm:size-14 text-lg sm:text-2xl',
            isFlipping ? 'animate-tile-flip' : '',
            isPopping ? 'animate-tile-pop' : '',
        ]"
    >
        <span aria-hidden="true">{{ letter }}</span>
        <span v-if="label" class="sr-only">{{ label }}</span>
    </div>
</template>

