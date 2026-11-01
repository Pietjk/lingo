<script setup lang="ts">
import { computed } from 'vue';
import { statusLabels, type TileState } from '@/types/game';

const props = defineProps<{
    letter?: string;
    state: TileState;
    size?: 'sm' | 'md';
}>();

// Shape reinforces color: square = juiste plek, circle = verkeerde plek.
const classes = computed(() => ({
    'bg-green-600 border-green-600 text-white rounded-none':
        props.state === 'correct',
    'bg-amber-500 border-amber-500 text-white rounded-full':
        props.state === 'present',
    'bg-neutral-500 border-neutral-500 text-white rounded-md':
        props.state === 'absent',
    'border-neutral-400 rounded-md': props.state === 'filled',
    'border-neutral-300 dark:border-neutral-700 rounded-md':
        props.state === 'empty',
}));

const label = computed(() =>
    props.state === 'empty' || props.state === 'filled'
        ? undefined
        : statusLabels[props.state],
);
</script>

<template>
    <div
        class="flex items-center justify-center border-2 font-bold uppercase transition-colors"
        :class="[
            classes,
            size === 'sm' ? 'size-8 text-sm' : 'size-14 text-2xl',
        ]"
    >
        <span aria-hidden="true">{{ letter }}</span>
        <span v-if="label" class="sr-only">{{ label }}</span>
    </div>
</template>
