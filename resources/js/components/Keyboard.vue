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

// Same shapes as the tiles: square = juiste plek, circle = verkeerde plek.
const keyClasses: Record<LetterStatus, string> = {
    correct: 'bg-green-600 text-white rounded-none',
    present: 'bg-amber-500 text-white rounded-full',
    absent: 'bg-neutral-500 text-white rounded-md',
};
</script>

<template>
    <div class="grid gap-1.5">
        <div
            v-for="(row, i) in rows"
            :key="row"
            class="flex justify-center gap-1"
        >
            <button
                v-if="i === 2"
                type="button"
                :disabled="disabled"
                class="rounded-md bg-neutral-200 px-3 text-xs font-semibold dark:bg-neutral-800"
                @click="emit('key', 'Enter')"
            >
                Enter
            </button>
            <button
                v-for="letter in row"
                :key="letter"
                type="button"
                :disabled="disabled"
                class="h-12 w-8 text-sm font-semibold uppercase sm:w-10"
                :class="
                    letterStatuses[letter]
                        ? keyClasses[letterStatuses[letter]]
                        : 'rounded-md bg-neutral-200 dark:bg-neutral-800'
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
                class="rounded-md bg-neutral-200 px-3 text-xs font-semibold dark:bg-neutral-800"
                @click="emit('key', 'Backspace')"
            >
                ⌫
            </button>
        </div>
    </div>
</template>
