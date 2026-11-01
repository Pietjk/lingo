<?php

namespace App\Game;

enum RoomStatus: string
{
    case Waiting = 'waiting';
    case Playing = 'playing';
    case Finished = 'finished';
}
