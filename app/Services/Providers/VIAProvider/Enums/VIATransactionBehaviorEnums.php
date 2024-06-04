<?php

namespace App\Services\Providers\VIAProvider\Enums;

enum VIATransactionBehaviorEnums: string
{
    case TIP = 'TIP';

    case BET = 'BET';

    case SETTLE = 'SETTLE';

    case VOID_CANCEL = 'VOID_CANCEL';

    case RESETTLE = 'RESETTLE';

    case CANCELLED = 'CANCELLED';

}