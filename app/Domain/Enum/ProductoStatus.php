<?php

namespace App\Domain\Enum;

enum ProductStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED='reserved';
    case SOLD='sold';

}