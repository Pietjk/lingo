<?php

namespace App\Game;

enum RoomStatus: string
{
    case Waiting = 'waiting';
    case Playing = 'playing';
    case Finished = 'finished';
    case MatchFinished = 'match_finished';
}
