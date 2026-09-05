<?php

namespace App\Enums;

enum ScaleUnit: string
{
    case Liter = 'ltr';
    case Pcs = 'pcs';
    case Box = 'box';
    case Kg = 'kg';
    case Ml = 'ml';

    public function label(): string
    {
        return match ($this) {
            self::Liter => 'Liter',
            self::Pcs => 'Piece',
            self::Box => 'Box',
            self::Kg => 'Kg',
            self::Ml => 'Ml',
        };
    }
}
