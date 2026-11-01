import { onUnmounted, ref } from 'vue';

export function useCountdown() {
    const seconds = ref(0);
    const running = ref(false);
    let timer: ReturnType<typeof setInterval> | null = null;

    function stop() {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }

        running.value = false;
    }

    function start(from: number, onDone?: () => void) {
        stop();
        seconds.value = from;
        running.value = true;

        const endsAt = Date.now() + from * 1000;

        timer = setInterval(() => {
            seconds.value = Math.max(
                0,
                Math.ceil((endsAt - Date.now()) / 1000),
            );

            if (seconds.value === 0) {
                stop();
                onDone?.();
            }
        }, 250);
    }

    onUnmounted(stop);

    return { seconds, running, start, stop };
}

export function formatClock(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const rest = String(totalSeconds % 60).padStart(2, '0');

    return `${minutes}:${rest}`;
}
