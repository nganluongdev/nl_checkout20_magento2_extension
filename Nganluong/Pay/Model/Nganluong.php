<?php

namespace Nganluong\Pay\Model;

class Nganluong extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_CODE = 'nganluong';

    protected $_code = self::PAYMENT_METHOD_CODE;
}
