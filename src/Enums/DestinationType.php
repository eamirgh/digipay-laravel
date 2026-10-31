<?php

namespace Eamirgh\Digipay\Enums;

enum DestinationType: int
{
    case MASKED_PAN = 0;
    case IBAN = 1;
    case WALLET = 2;
    case CREDIT = 3;
}
