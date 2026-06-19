<?php

namespace App\Domain\Enum;

enum AuctionStatus:string
{
    case ACTIVE='active';
    case FINISHED='finished';
    case CANCELLED='cancelled';

}