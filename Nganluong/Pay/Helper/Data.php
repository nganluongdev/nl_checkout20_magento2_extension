<?php

namespace Nganluong\Pay\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $session;
    protected $_scopeConfig;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->session = $session;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function merchantId()
    {
          $service_id = $this->_scopeConfig->getValue('payment/nganluong/merchant_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          return $service_id;
    }

    public function merchantPass()
    {
          $secret_key = $this->_scopeConfig->getValue('payment/nganluong/merchant_pass', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          return $secret_key;
    }

    public function receiver()
    {
          $secret_key = $this->_scopeConfig->getValue('payment/nganluong/receiver', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          return $secret_key;
    }

    public function lang()
    {
          $secret_key = $this->_scopeConfig->getValue('payment/nganluong/lang', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          return $secret_key;
    }

    public function cancelCurrentOrder($comment)
    {
        $order = $this->session->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    public function restoreQuote()
    {
        return $this->session->restoreQuote();
    }

    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }

    public function getNganluongUrl()
    {
        $isMode=$this->_scopeConfig->getValue('payment/nganluong/mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if($isMode=='1'){
            return "https://sandbox.nganluong.vn:8088/nl35/checkout.php";
        }
        else{
            return "https://www.nganluong.vn/checkout.php";
        }
    }

    public function getNganluongApiUrl()
    {
        $isMode=$this->_scopeConfig->getValue('payment/nganluong/mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if($isMode=='1'){
            return "https://sandbox.nganluong.vn:8088/nl35/service/order/check";
        }
        else{
            return "https://www.nganluong.vn/service/order/check";
        }
    }    
}

