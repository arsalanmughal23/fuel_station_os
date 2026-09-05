<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Correction = 'correction';
    case Spillage = 'spillage';
    case Evaporation = 'evaporation';
    case Theft = 'theft';
    case Return = 'return';
    case Other = 'other';
}
