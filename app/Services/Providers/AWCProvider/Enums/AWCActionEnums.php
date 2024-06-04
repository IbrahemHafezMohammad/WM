<?php

namespace App\Services\Providers\AWCProvider\Enums;

enum AWCActionEnums: string
{
    case GET_BALANCE = 'getBalance';

    case PLACE_BET = 'bet';

    case CANCEL_BET = 'cancelBet';
    
    case ADJUST_BET = 'adjustBet';

    case VOID_BET = 'voidBet';

    case UNVOID_BET = 'unvoidBet';

    case REFUND = 'refund';

    case SETTLE = 'settle';

    case UNSETTLE = 'unsettle';

    case VOID_SETTLE = 'voidSettle';

    case UNVOID_SETTLE = 'unvoidSettle';

    case BET_N_SETTLE = 'betNSettle';

    case CANCEL_BET_N_SETTLE = 'cancelBetNSettle';

    case FREE_SPIN = 'freeSpin';

    case GIVE = 'give';

    case RESETTLE = 'resettle';
}