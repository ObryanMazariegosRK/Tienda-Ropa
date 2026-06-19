<?php

namespace App\Domain\Enum;

enum RoleType:string
{
    case ADMIN='admin';
    case CUSTOMER='curstomer';

}