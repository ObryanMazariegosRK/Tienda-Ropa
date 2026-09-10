<?php

namespace App\Domain\Enum;

enum ProductStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    //case AUCTION='auction';
    case SOLD='sold';
    case DISABLED='disabled';
    case DELETED = 'deleted';

}