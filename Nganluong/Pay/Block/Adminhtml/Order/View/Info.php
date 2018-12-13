<?php
namespace Nganluong\Pay\Block\Adminhtml\Order\View;

use Magento\Eav\Model\AttributeDataFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Address;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{
    public function getBillingAddressCustomerName($alias = '', $useCache = true)
    {
        $order=$this->getOrder();
        $billingAddress=$order->getBillingAddress();
        return  $billingAddress->getFirstName().' '.$billingAddress->getLastName();        
    }
}
