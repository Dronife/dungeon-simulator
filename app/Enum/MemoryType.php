<?php

namespace App\Enum;

enum MemoryType: string
{
    case TICK = 'tick';
    case RECAP = 'recap';
    case SUMMARY = 'summary';
    case EPOCH = 'epoch';
}
