<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    me: string;
    scores: Record<string, number>;
    winner: string | null;
    forfeit: boolean;
    processing: boolean;
}>();

defineEmits<{ rematch: [] }>();

const standings = computed(() =>
    Object.entries(props.scores).sort(([, a], [, b]) => b - a),
);

const headline = computed(() => {
    if (props.winner === null) {
        return 'Gelijkspel!';
    }

    return props.winner === props.me
        ? 'Jij hebt de wedstrijd gewonnen!'
        : `${props.winner} heeft de wedstrijd gewonnen.`;
});
</script>

<template>
    <section
        class="w-full max-w-sm space-y-4 rounded-xl border border-[#e3e3e0] p-6 text-center dark:border-[#3E3E3A]"
        aria-label="Einde van de wedstrijd"
    >
        <h2 class="text-xl font-semibold">{{ headline }}</h2>
        <p v-if="forfeit" class="text-sm text-[#706f6c]">
            Gewonnen met forfait: de tegenstander is niet teruggekomen.
        </p>

        <ol class="space-y-1">
            <li
                v-for="[name, score] in standings"
                :key="name"
                class="flex justify-between"
            >
                <span :class="{ 'font-semibold': name === winner }">
                    {{ name }}{{ name === me ? ' (jij)' : '' }}
                </span>
                <span>{{ score }} punten</span>
            </li>
        </ol>

        <button
            type="button"
            :disabled="processing"
            class="rounded-lg bg-[#1b1b18] px-5 py-2 font-medium text-white disabled:opacity-50 dark:bg-[#eeeeec] dark:text-[#1C1C1A]"
            @click="$emit('rematch')"
        >
            Opnieuw spelen
        </button>
    </section>
</template>
