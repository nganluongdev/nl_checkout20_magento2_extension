<?php
namespace Nganluong\Pay\Controller\Standard;

use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Payment\Transaction;
use Nganluong\Pay\Model\System\Config\Order\Status\NganluongPaid;

class Response extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_order;
    protected $_transactionBuilder;
    protected $dataHelper;
    protected $_orderHistoryFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order $order,
        \Nganluong\Pay\Helper\Data $dataHelper,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_order = $order;
        $this->_transactionBuilder = $transactionBuilder;
        $this->dataHelper = $dataHelper;
        $this->_orderHistoryFactory = $orderHistoryFactory;        
        return parent::__construct($context);
    }

    public function execute()
    {
        $request = array_diff($this->getRequest()->getParams(), ['']);
        if (!array_key_exists("error_text",$request))
        {
            $request['error_text']='';
        }            
        $paymentStatus = $this->verifyPaymentUrl($request['transaction_info'], $request['order_code'],$request['price'],$request['payment_id'],$request['payment_type'],$request['error_text'],$request['secure_code']);

        $transactionDataJSON=$this->transactionDetail($request['token_nl']);        
        $transactionDataArray = json_decode($transactionDataJSON,true);

        $transactionDataErroCode = $transactionDataArray['error_code'];
        $transactionData = $transactionDataArray['data'];

        unset($transactionData['affiliate_code']);    
        unset($transactionData['cancel_url']);    
        unset($transactionData['return_url']);            
        unset($transactionData['fee_shipping']);            
        unset($transactionData['discount_amount']);            
        unset($transactionData['tax_amount']);            
        unset($transactionData['description']);            
        unset($transactionData['receiver_email']);

        $IdData = explode("_", $request['order_code']);
        $orderId = $IdData[0];

        if(!empty($transactionDataJSON))
        {
            if($transactionData['transaction_status']==00){
            $transactionData['transaction_status']= __('Transaction is successfully');        
            }            
            if($paymentStatus === true ){
                $order = $this->_order->loadByIncrementId($orderId);
                if ($transactionDataArray['error_code'] == 00) {                       
                    $order->setStatus(NganluongPaid::NGANLUAONG_CODE);                
                    $this->createTransaction($order, $transactionData);
                    $comment = __("Nganluong Transaction Has Been Successful.");
                    $order->setExtOrderId($orderId);
                    $this->addOrderHistory($order, $comment);
                    $order->save();
                    $this->getResponse()->setRedirect($this->dataHelper->getUrl('checkout/onepage/success'));
                }
                else {
                    $comment = __("Nganluong Payment Failed.");
                    $order->setState($order::STATE_CANCELED);
                    $order->setStatus($order::STATE_CANCELED);
                    $order->setExtOrderId($orderId);
                    $this->addOrderHistory($order, $comment);
                    $order->save();
                    $this->getResponse()->setRedirect($this->dataHelper->getUrl('checkout/onepage/failure'));
                }
            }
        }
        else
        {
            if($paymentStatus === true ){
                $order = $this->_order->loadByIncrementId($orderId);
                if ($request['error_text'] == '') {                       
                    $order->setStatus(NganluongPaid::NGANLUAONG_CODE);                
                    $this->createTransactionPayment($order, $request);
                    $comment = __("Nganluong Transaction Has Been Successful.");
                    $order->setExtOrderId($orderId);
                    $this->addOrderHistory($order, $comment);
                    $order->save();
                    $this->getResponse()->setRedirect($this->dataHelper->getUrl('checkout/onepage/success'));
                }
                else {
                    $comment = __("Nganluong Payment Failed.");
                    $order->setState($order::STATE_CANCELED);
                    $order->setStatus($order::STATE_CANCELED);
                    $order->setExtOrderId($orderId);
                    $this->addOrderHistory($order, $comment);
                    $order->save();
                    $this->getResponse()->setRedirect($this->dataHelper->getUrl('checkout/onepage/failure'));
                }
            }

        }
    }  

    public function createTransaction($order = null, $paymentData = [])
    {
        unset($paymentData['error_code']);        
        $payment = $order->getPayment();
        $payment->setLastTransId($paymentData['transaction_id']);
        $payment->setTransactionId($paymentData['transaction_id']);
        $payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]);        
        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

        $message = __('The authorized amount is %1.', $formatedPrice);
        //get the object of builder class
        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
                            ->setOrder($order)
                            ->setTransactionId($paymentData['transaction_id'])
                            ->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData])
                            ->setFailSafe(true)
                            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save(); 
        return  $transaction->save()->getTransactionId();
    }

    public function createTransactionPayment($order = null, $paymentData = [])
    {
        unset($paymentData['error_text']);        
        $payment = $order->getPayment();
        $payment->setLastTransId($paymentData['payment_id']);
        $payment->setTransactionId($paymentData['payment_id']);
        $payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]);        
        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

        $message = __('The authorized amount is %1.', $formatedPrice);
        //get the object of builder class
        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
                            ->setOrder($order)
                            ->setTransactionId($paymentData['payment_id'])
                            ->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData])
                            ->setFailSafe(true)
                            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save(); 
        return  $transaction->save()->getTransactionId();
    }

    protected function addOrderHistory($order, $comment)
    {
        $history = $this->_orderHistoryFactory->create()
            ->setComment($comment)
            ->setEntityName('order')
            ->setOrder($order);
            if($order->getStatus() == NganluongPaid::NGANLUAONG_CODE)
            {
                $history->setStatus(NganluongPaid::NGANLUAONG_CODE);   
            }
            else
            {
                $history->setStatus($order::STATE_CANCELED);
            }
            $order->save();
            $history->save();
        return true;
    }
    public function verifyPaymentUrl($transaction_info, $order_code, $price, $payment_id, $payment_type, $error_text, $secure_code)
    {
        // Tạo mã xác thực từ chủ web
        $str = '';
        $str .= ' ' . strval($transaction_info);
        $str .= ' ' . strval($order_code);
        $str .= ' ' . strval($price);
        $str .= ' ' . strval($payment_id);
        $str .= ' ' . strval($payment_type);
        $str .= ' ' . strval($error_text);
        $str .= ' ' . strval($this->dataHelper->merchantId());
        $str .= ' ' . strval($this->dataHelper->merchantPass());

        $verify_secure_code = '';
        $verify_secure_code = md5($str);
        
        if ($verify_secure_code === $secure_code) return true;
        else return false;
    } 

    public function transactionDetail($token)
    {
            $checksum=MD5(strval($token).'|'.strval($this->dataHelper->merchantPass())); 
            $params = array(
                'merchant_id'       => $this->dataHelper->merchantId(),                
                'token'             => $token,
                'checksum'          => $checksum
            );            
            $post_field = '';
            foreach ($params as $key => $value){
                if ($post_field != '') $post_field .= '&';
                $post_field .= $key."=".$value;
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$this->dataHelper->getNganluongApiUrl());
            curl_setopt($ch, CURLOPT_ENCODING , 'UTF-8');
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
            $result = curl_exec($ch);          
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
            $error = curl_error($ch);
            if ($result != '' && $status==200){
                return $result;
            }  
            return false;
    }     
}