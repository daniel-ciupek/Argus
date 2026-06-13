<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetPeriod: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';
}
