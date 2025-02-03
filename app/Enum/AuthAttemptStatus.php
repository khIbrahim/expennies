<?php

namespace App\Enum;

enum AuthAttemptStatus
{

    case TWO_FACTOR_AUTH;
    case FAILED;
    case SUCCESS;

}
