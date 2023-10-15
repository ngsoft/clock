<?php

declare(strict_types=1);

namespace NGSOFT\Clock;

enum State: int
{
    case Idle    = 0;
    case Started = 1;
    case Paused  = 2;
    case Stopped = 3;
}
