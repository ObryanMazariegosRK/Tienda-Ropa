<?php

namespace App\Domain\Enum;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case ON_ROUTE = 'on_route';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}