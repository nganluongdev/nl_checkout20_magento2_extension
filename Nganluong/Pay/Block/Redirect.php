<?php

namespace Nganluong\Pay\Block;

use Magento\Sales\Model\Order;

class Redirect extends \Magento\Framework\View\Element\Template
{
    protected $_systemStore;
    protected $_formFactory;
    protected $_storeManager;
    protected $dataHelper;
    protected $_orderFactory;
    protected $_checkoutSession;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Store\Model\System\Store       $systemStore
     * @param array                                   $data
     */
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Nganluong\Pay\Helper\Data $dataHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\PageCache\Model\Cache\Type $type,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->_systemStore = $systemStore;
        $this->_formFactory = $formFactory;
        $this->_storeManager = $storeManager;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }
    
    protected function _toHtml()
    {  
        $this->getQuoteData('redirect');
        echo '<meta http-equiv="refresh" content="0; url='.$this->getQuoteData('redirect').'" >';       
    }

    public function getQuoteData($option = '')
    {
        if ($option == 'redirect'){
            $quote =  $order = $this->getOrder();
            $quote->setStatus(Order::STATE_PROCESSING);
            $quote->setState(Order::STATE_PROCESSING);
            $quote->save();
        }
        else{
            $quote = $this->getQuote();
            $quote->setStatus(Order::STATE_PROCESSING);
            $quote->setState(Order::STATE_PROCESSING);
            $quote->save();
            
        }
        $data = [];
        if ($quote){
            $BillingAddress = '';
            $ShippingAddress = '';
                if ($quote->getShippingAddress()) {
                    if($quote->getIsVirtual()){
                        $BillingAddress = $quote->getBillingAddress();
                        $ShippingAddress = $quote->getShippingAddress();
                    } else {
                        $BillingAddress = $quote->getShippingAddress();
                        $ShippingAddress = $quote->getBillingAddress();
                    }
                } else {
                    $BillingAddress = $quote->getBillingAddress();
                    $ShippingAddress = $quote->getBillingAddress();
                }
            $_items = '';
            foreach ($quote->getAllVisibleItems() as $_item) {
                $_items = $_items . $_item->getName().', ';
            }
            $_items = rtrim(rtrim($_items, ' '), ',');
            $totalAmt = number_format((float)$quote->getGrandTotal(), 2, '.', '');
            $taxAmt = number_format((float)$quote->getBaseTaxAmount(), 2, '.', '');
            $discountAmt = number_format((float)$quote->getBaseDiscountAmount(), 2, '.', '');

            $order_code=$quote->getIncrementId();
            $totalQtyOrderd=(int)$quote->getTotalQtyOrdered();
            $fee_cal=0;
            $fee_shipping=0;
            $transaction_info = __('Products').':'.$_items;            
            $order_description = __($_items.','.$totalQtyOrderd.','.$totalAmt);

            $BillingAddress=$quote->getBillingAddress();
            $street=implode(" ",$BillingAddress->getStreet());

            $buyer_info= $BillingAddress->getFirstName().' '.$BillingAddress->getLastName().'*|*'.$BillingAddress->getEmail().'*|*'.$BillingAddress->getTelephone().'*|*'.$street;
            $affiliate_code='';

            $arr_param = array(
            'merchant_site_code'=>  strval($this->dataHelper->merchantId()),
            'return_url'        =>  strval(strtolower($this->dataHelper->getUrl("pay/standard/response"))),    
            'receiver'          =>  strval($this->dataHelper->receiver()),
            'transaction_info'  =>  strval($transaction_info),
            'order_code'        =>  strval($order_code),
            'price'             =>  strval($totalAmt),
            'currency'          =>  strval($quote->getOrderCurrencyCode()),
            'quantity'          =>  strval($totalQtyOrderd),
            'tax'               =>  strval($taxAmt),
            'discount'          =>  strval($discountAmt),
            'fee_cal'           =>  strval($fee_cal),
            'fee_shipping'      =>  strval($fee_shipping),
            'order_description' =>  strval($order_description),
            'buyer_info'        =>  strval($buyer_info),
            'affiliate_code'    =>  strval($affiliate_code)
        );

        $secure_code ='';
        $secure_code = implode(' ',$arr_param).' '.$this->dataHelper->merchantPass();
        $arr_param['secure_code'] = md5($secure_code);

        $redirect_url = $this->dataHelper->getNganluongUrl();
        if (strpos($redirect_url, '?') === false){
            $redirect_url .= '?';
        } else if (substr($redirect_url, strlen($redirect_url)-1, 1) != '?' && strpos($redirect_url, '&') === false) {
            $redirect_url .= '&';           
        }
        $url = '';
        foreach ($arr_param as $key=>$value) {
            $value = urlencode($value);
            if ($url == '') {
                $url .= $key . '=' . $value;
            } else {
                $url .= '&' . $key . '=' . $value;
            }
        }

        $url .='&lang='.strval($this->dataHelper->lang()); 
        $url .='&cancel_url='.strtolower($this->dataHelper->getUrl("pay/standard/cancel"));         
        return $redirect_url.$url;
        }
    }
    public function getCheckout(){
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $object_manager->get('Magento\Checkout\Model\Session');
        return $logger;
    }
    public function getQuote(){
          return $this->getCheckout()->getQuote();
    }
    protected function getOrder(){
        return $this->_orderFactory->create()->loadByIncrementId($this->getMyArg());
    }
}