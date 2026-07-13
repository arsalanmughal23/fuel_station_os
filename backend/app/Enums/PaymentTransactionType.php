<?php

namespace App\Enums;

enum PaymentTransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
}
