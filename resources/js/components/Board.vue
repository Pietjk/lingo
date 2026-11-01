<script setup lang="ts">
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
}>();

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
    <div class="grid gap-1.5">
        <div
            v-for="r in MAX_GUESSES"
            :key="r"
            class="flex justify-center gap-1.5"
        >
            <Tile
                v-for="c in WORD_LENGTH"
                :key="c"
                :size="size"
                v-bind="tileFor(r - 1, c - 1)"
            />
        </div>
    </div>
</template>
