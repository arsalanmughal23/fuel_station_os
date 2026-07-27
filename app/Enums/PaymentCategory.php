<?php

namespace App\Enums;

enum PaymentCategory: string
{
    case FuelPurchase = 'fuel_purchase';
    case FuelSale = 'fuel_sale';
    case Salary = 'salary';
    case Utility = 'utility';
    case Maintenance = 'maintenance';
    case Other = 'other';
}
