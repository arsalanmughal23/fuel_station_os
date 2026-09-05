<?php

namespace App\Enums;

enum AccountType: string
{
    case Distributor = 'distributor';
    case Customer = 'customer';
    case Employee = 'employee';
    case Owner = 'owner';
}
