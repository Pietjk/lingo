<?php

namespace App\Game;

enum LetterStatus: string
{
    case Correct = 'correct';
    case Present = 'present';
    case Absent = 'absent';
}
