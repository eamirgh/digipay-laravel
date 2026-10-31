<?php

namespace Eamirgh\Digipay\Enums;

enum TicketType: int
{
    case IPG = 0;
    case CREDIT = 5;
    case WALLET = 11;
    case BNPL = 13;
    case CREDIT_CARD = 24;
}
