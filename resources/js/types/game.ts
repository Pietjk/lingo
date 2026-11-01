export type LetterStatus = 'correct' | 'present' | 'absent';

export type TileState = LetterStatus | 'empty' | 'filled';

export type BoardRow = {
    letters: string;
    statuses: LetterStatus[] | null;
};

export const WORD_LENGTH = 5;
export const MAX_GUESSES = 5;

export const statusLabels: Record<LetterStatus, string> = {
    correct: 'juiste plek',
    present: 'verkeerde plek',
    absent: 'komt niet voor',
};
