<?php

namespace App\Domain\Enum;

enum ProductStatus: string
{
    case AVAILABLE = 'available';
    //case AUCTION='auction';
    case SOLD='sold';
    case DISABLED='disabled';

}