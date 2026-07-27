<?php

namespace App\Enums;

enum ScaleUnit: string
{
    case Litr = 'ltr';
    case Pcs = 'pcs';
    case Box = 'box';
    case Kg = 'kg';
    case Ml = 'ml';

    public function label(): string
    {
        return match($this) {
            self::Litr => 'Liter',
            self::Pcs => 'Piece',
            self::Box => 'Box',
            self::Kg => 'Kg',
            self::Ml => 'Ml',
        };
    }
}