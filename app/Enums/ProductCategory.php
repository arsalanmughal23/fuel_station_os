<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Lubricant = 'lubricant';
    case Accessory = 'accessory';
    case Snack = 'snack';
    case Other = 'other';
}