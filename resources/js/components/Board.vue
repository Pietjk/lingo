<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue';
import Tile from '@/components/Tile.vue';
import {
    MAX_GUESSES,
    WORD_LENGTH,
    type BoardRow,
    type TileState,
} from '@/types/game';

const props = defineProps<{
    rows: BoardRow[];
    current?: string;
    size?: 'sm' | 'md';
    shake?: boolean;
}>();

const revealingRowIndex = ref<number | null>(null);
let revealTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.rows.length,
    (newLen, oldLen) => {
        if (newLen > (oldLen ?? 0)) {
            const newlyAdded = newLen - 1;
            revealingRowIndex.value = newlyAdded;
            if (revealTimer) clearTimeout(revealTimer);
            // Stagger: (WORD_LENGTH - 1) * 200ms delay + 500ms flip duration = 1300ms
            revealTimer = setTimeout(() => {
                if (revealingRowIndex.value === newlyAdded) {
                    revealingRowIndex.value = null;
                }
            }, (WORD_LENGTH - 1) * 200 + 600);
        } else if (newLen === 0) {
            revealingRowIndex.value = null;
            if (revealTimer) clearTimeout(revealTimer);
        }
    },
);

onUnmounted(() => {
    if (revealTimer) clearTimeout(revealTimer);
});

function tileFor(
    rowIndex: number,
    col: number,
): { letter: string; state: TileState } {
    const row = props.rows[rowIndex];

    if (row) {
        return {
            letter: row.letters[col] ?? '',
            state: row.statuses?.[col] ?? 'empty',
        };
    }

    if (rowIndex === props.rows.length && props.current !== undefined) {
        const letter = props.current[col] ?? '';

        return { letter, state: letter ? 'filled' : 'empty' };
    }

    return { letter: '', state: 'empty' };
}
</script>

<template>
    <div class="grid gap-1.5 sm:gap-2">
        <div
            v-for="r in MAX_GUESSES"
            :key="r"
            class="flex justify-center gap-1.5 sm:gap-2 [perspective:1000px]"
            :class="{
                'animate-shake': shake && (r - 1) === props.rows.length,
            }"
        >
            <Tile
                v-for="c in WORD_LENGTH"
                :key="c"
                :size="size"
                v-bind="tileFor(r - 1, c - 1)"
                :is-revealing="revealingRowIndex === r - 1"
                :reveal-delay="(c - 1) * 200"
            />
        </div>
    </div>
</template>

