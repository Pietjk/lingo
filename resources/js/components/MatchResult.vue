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
        ? '🏆 Jij hebt gewonnen!'
        : `🏆 ${props.winner} heeft gewonnen!`;
});
</script>

<template>
    <section
        class="w-full max-w-sm space-y-5 rounded-2xl border-2 border-yellow-400/40 bg-[#081a4d]/90 p-6 text-center text-white shadow-2xl backdrop-blur-md"
        aria-label="Einde van de wedstrijd"
    >
        <h2 class="text-2xl font-black text-yellow-400 drop-shadow-sm">
            {{ headline }}
        </h2>
        <p v-if="forfeit" class="text-xs font-semibold text-amber-300">
            Gewonnen met forfait: de tegenstander is niet teruggekomen.
        </p>

        <ol class="space-y-2">
            <li
                v-for="[name, score] in standings"
                :key="name"
                class="flex items-center justify-between rounded-xl border border-blue-500/30 bg-[#061439]/70 px-4 py-2.5 text-sm"
            >
                <span
                    :class="
                        name === winner
                            ? 'font-black text-yellow-300'
                            : 'font-semibold text-white'
                    "
                >
                    {{ name }}{{ name === me ? ' (jij)' : '' }}
                </span>
                <span class="font-mono font-bold text-white">
                    {{ score }} punten
                </span>
            </li>
        </ol>

        <p v-if="forfeit" class="text-xs text-blue-200">
            De tegenstander heeft de kamer verlaten, opnieuw spelen kan niet.
        </p>
        <button
            v-else
            type="button"
            :disabled="processing"
            class="w-full rounded-xl bg-gradient-to-b from-yellow-300 to-amber-500 px-5 py-3 font-black text-blue-950 shadow-lg shadow-yellow-500/25 transition-all hover:from-yellow-200 hover:to-amber-400 active:scale-[0.98] disabled:opacity-40 cursor-pointer"
            @click="$emit('rematch')"
        >
            Opnieuw spelen
        </button>
    </section>
</template>

