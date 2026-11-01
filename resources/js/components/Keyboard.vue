<script setup lang="ts">
import type { LetterStatus } from '@/types/game';

defineProps<{
    letterStatuses: Record<string, LetterStatus>;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    key: [key: string];
}>();

const rows = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm'];

// Classic Lingo: red square = juiste plek, yellow circle = verkeerde plek.
const keyClasses: Record<LetterStatus, string> = {
    correct:
        'bg-red-600 hover:bg-red-500 border-2 border-red-400 text-white rounded-none shadow-md shadow-red-950/40',
    present:
        'bg-yellow-400 hover:bg-yellow-300 border-2 border-yellow-200 text-blue-950 font-black rounded-full shadow-md shadow-yellow-950/40',
    absent:
        'bg-[#051336]/90 border border-blue-900/60 text-blue-400/30 rounded-md',
};
</script>

<template>
    <div class="w-full max-w-lg mx-auto grid gap-1.5 select-none px-0.5 sm:px-2">
        <div
            v-for="(row, i) in rows"
            :key="row"
            class="flex w-full justify-center gap-1 sm:gap-1.5 touch-manipulation"
            :class="{ 'px-2 sm:px-4': i === 1 }"
        >
            <button
                v-if="i === 2"
                type="button"
                :disabled="disabled"
                class="flex-[1.4] h-10 sm:h-12 max-w-16 min-w-0 rounded-md border border-blue-400/50 bg-[#183780] hover:bg-[#234dae] text-white text-xs sm:text-sm font-bold flex items-center justify-center shadow-sm active:scale-95 transition-all disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
                @click="emit('key', 'Enter')"
            >
                Enter
            </button>
            <button
                v-for="letter in row"
                :key="letter"
                type="button"
                :disabled="disabled"
                class="flex-1 h-10 sm:h-12 max-w-10 min-w-0 text-xs sm:text-base font-bold uppercase flex items-center justify-center transition-all active:scale-95 disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
                :class="
                    letterStatuses[letter]
                        ? keyClasses[letterStatuses[letter]]
                        : 'rounded-md border border-blue-500/40 bg-[#102b6e] hover:bg-[#183d99] text-white shadow-sm active:bg-[#1e49b3]'
                "
                @click="emit('key', letter)"
            >
                {{ letter }}
            </button>
            <button
                v-if="i === 2"
                type="button"
                :disabled="disabled"
                aria-label="Wissen"
                class="flex-[1.4] h-10 sm:h-12 max-w-16 min-w-0 rounded-md border border-blue-400/50 bg-[#183780] hover:bg-[#234dae] text-white text-sm sm:text-base font-bold flex items-center justify-center shadow-sm active:scale-95 transition-all disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
                @click="emit('key', 'Backspace')"
            >
                ⌫
            </button>
        </div>
    </div>
</template>

