<?php
namespace Nganluong\Pay\Controller\Standard;

class Cancel extends \Magento\Framework\App\Action\Action 
{
    protected $checkoutSession;
    protected $orderRepository;

    /**
     * Constructor
     * 
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
                                \Magento\Framework\App\Action\Context $context,
                                \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
                                \Magento\Checkout\Model\Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Execute view action
     * 
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->messageManager->addError(__('Payment has been cancelled.'));
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        //change order status to cancel
        $order = $this->orderRepository->get($this->checkoutSession->getLastOrderId());
        if ($order) {
            $order->cancel();
            $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED, __('Canceled by customer.'));
            $order->save();
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/cart');
        return $resultRedirect;
        //return $this->resultPageFactory->create();
    }
}