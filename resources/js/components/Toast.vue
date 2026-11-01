<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        message: string | null;
        duration?: number;
    }>(),
    {
        duration: 3500,
    },
);

const emit = defineEmits<{
    close: [];
}>();

let timer: ReturnType<typeof setTimeout> | null = null;
const visible = ref(Boolean(props.message));

function startTimer() {
    clearTimer();
    if (props.message) {
        visible.value = true;
        if (props.duration > 0) {
            timer = setTimeout(() => {
                dismiss();
            }, props.duration);
        }
    } else {
        visible.value = false;
    }
}

function clearTimer() {
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
}

function dismiss() {
    visible.value = false;
    clearTimer();
    emit('close');
}

watch(
    () => props.message,
    (newVal) => {
        if (newVal) {
            startTimer();
        } else {
            visible.value = false;
            clearTimer();
        }
    },
);

onMounted(() => {
    if (props.message) {
        startTimer();
    }
});

onUnmounted(() => {
    clearTimer();
});
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed top-5 left-1/2 z-[100] w-[calc(100%-2rem)] max-w-sm -translate-x-1/2"
            aria-live="assertive"
        >
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-4 scale-90"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-3 scale-95"
            >
                <div
                    v-if="visible && message"
                    role="alert"
                    class="pointer-events-auto flex items-center justify-between gap-3 rounded-2xl border-2 border-red-300 bg-red-600 px-4 py-3 text-white shadow-[0_12px_32px_rgba(220,38,38,0.65)] ring-4 ring-red-950/30 backdrop-blur-md"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white/20 text-sm font-black"
                        >
                            !
                        </span>
                        <span
                            class="text-xs sm:text-sm font-black tracking-wide break-words"
                        >
                            {{ message }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="ml-2 cursor-pointer rounded-lg p-1 text-white/80 transition-colors hover:bg-red-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                        aria-label="Sluiten"
                        @click="dismiss"
                    >
                        <svg
                            class="size-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                            />
                        </svg>
                    </button>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

