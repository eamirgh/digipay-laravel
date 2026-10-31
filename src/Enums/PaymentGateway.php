<?php

namespace Eamirgh\Digipay\Enums;

enum PaymentGateway: int
{
    case IPG = 0;
    case WALLET = 3;
    case CPG = 4;
}
