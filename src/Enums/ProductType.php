<?php

namespace Eamirgh\Digipay\Enums;

enum ProductType: int
{
    case DURABLE = 1;
    case CONSUMABLE = 2;
    case SERVICE = 3;
    case DURABLE_CONSUMABLE = 4;
}
