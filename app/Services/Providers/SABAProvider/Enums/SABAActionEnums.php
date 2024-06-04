<?php

namespace App\Services\Providers\SABAProvider\Enums;

enum SABAActionEnums: string
{
    // actions
    case GET_BALANCE = 'GetBalance';
    case PLACE_BET = 'PlaceBet';
    case CONFIRM_BET = 'ConfirmBet';
    case CANCEL_BET = 'CancelBet';
}