<?php

namespace App\Domain\Enum;

enum OrderStatus: string
{
    case RECEIVED='received';
    case CONFIRMED='confirmed';
    case PREPARING='preparing';
    case ON_ROUTE='on_route';
    case DELIVERED='delivered';
    case CANCELLED='cancelled';
}
