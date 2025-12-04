<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Expense = 'expense';
    case Refund = 'refund';
}
