<?php

namespace App\Domain\Enum;

//Para controlar el estado de las subastas

enum AuctionStatus:string
{
    case ACTIVE='active';
    case FINISHED='finished';
    case CANCELLED='cancelled';

}