<?php

namespace Nganluong\Pay\Controller\Standard;

use Magento\Framework\Controller\ResultFactory;

class Redirect extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;
    protected $resultPageFactory;
    protected $_checkoutSession;
    protected $_orderFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->getOrder();
        $resultPage = $this->resultPageFactory->create();
        
        $block = $resultPage->getLayout()
                ->createBlock(
                    'Nganluong\Pay\Block\Redirect',
                    "redirect",
                    array('data' => array( 'my_arg' =>  $this->_checkoutSession->getLastRealOrderId()))
                )
                ->setData('area', 'frontend')
                ->toHtml();
        $this->getResponse()->setBody($block);
        // $session->unsQuoteId();
    }
    protected function getOrder()
    {
       return $this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
    }
}       