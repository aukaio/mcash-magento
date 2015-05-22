<?php

class Trollweb_Mcash_Model_Payment_Mcash extends Mage_Payment_Model_Method_Abstract
{

    protected $_code = 'mcash';
    protected $_formBlockType = 'mcash/form';
    protected $_infoBlockType = 'payment/info';

    /**
     * Payment Method features
     * @var bool
     */
    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = false;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_isInitializeNeeded          = false;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = true;
    /**
     * TODO: whether a captured transaction may be voided by this gateway
     * This may happen when amount is captured, but not settled
     * @var bool
     */
    protected $_canCancelInvoice        = false;

    const MCASH_TOKEN       = 'mcash_token';
    const MCASH_SHORTLINK   = 'mcash_shortlink';



    public function validate()
    {
        parent::validate();
        $payment = $this->getInfoInstance();

        if (!$payment->getAdditionalInformation(self::MCASH_TOKEN)) {
            Mage::throwException(Mage::helper('mcash')->__('You must scan the QR code before you can continue'));
        }

        return $this;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $api = Mage::getModel('mcash/api');
        $text = Mage::helper('mcash')->__('Order #%s',$payment->getOrder()->getIncrementId())."\n".$this->getProductLines($payment->getOrder());

        $transactionId = $api->paymentRequestAuthorize($payment->getAdditionalInformation(self::MCASH_TOKEN),$amount,$payment->getOrder()->getId(),$text);
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(false);

        if (!$transactionId) {
            Mage::throwException(Mage::helper('mcash')->__('Communication with mCASH failed. Please try again or choose another payment method'));
        }

        $isOk = false;
        $giveuptime = time()+120;

        while (time() < $giveuptime) {
            $result = $api->paymentRequestOutcome($transactionId);
            $status = $result['status'];
            if ($status == 'pending') {
                // Sleep for 1 second
                usleep(1000000);
                continue;
            }

            if ($status == 'auth') {
                $isOk = true;
            }
            break;
        }

        if (!$isOk) {
            Mage::log($result);
            Mage::throwException(Mage::helper('mcash')->__('Payment failed'));
        }

    }

    public function capture(Varien_Object $payment, $amount)
    {
        $helper = Mage::helper("mcash");
        $invoice = Mage::registry('current_invoice');

        // Grab mcash transaction id from parent transaction
        $transactionId = $payment->getParentTransactionId();
        //$transactionId = $payment->getTransactionId();
        //$transactionId = str_replace("-capture", "", $transactionId);

        if ($helper->isPartial($invoice)) {
            // Create a "random" capture id with the transaction id as a prefix
            $captureId = uniqid($transactionId . "-");
            $payment->setTransactionId($captureId);

            $this->capturePartial($transactionId, $amount, $captureId);
        } else {
            $this->captureFull($transactionId);
        }
    }

    private function captureFull($transactionId) {
        $api = Mage::getModel('mcash/api');

        if (!$api->paymentRequestCaptureFull($transactionId)) {
            Mage::throwException(Mage::helper('mcash')->__('Capture failed'));
        }

        $isOk = false;
        $giveuptime = time() + 30;

        while (time() < $giveuptime) {
            $result = $api->paymentRequestOutcome($transactionId);
            $status = $result['status'];


            if ($status == 'auth') {
                // Sleep for 1 second
                usleep(1000000);
                continue;
            }

            if ($status == 'ok') {
                $isOk = true;
            }
            break;
        }

        if (!$isOk) {
            Mage::log($result);
            Mage::throwException(Mage::helper('mcash')->__('Capture failed, status is not ok'));
        }
    }

    private function capturePartial($transactionId, $amount, $captureId) {
        $api = Mage::getModel('mcash/api');
        if (!$api->paymentRequestCapturePartial($transactionId, $amount, $captureId)) {
            Mage::throwException(Mage::helper('mcash')->__('Partial capture failed'));
        }

        $isOk = false;
        $giveuptime = time() + 30;

        while (time() < $giveuptime) {
            $result = $api->paymentRequestOutcome($transactionId);
            $captures = $result['captures'];

            // Assume the captures is ok if we find our
            // capture id in the list of captures
            foreach ($captures as $capture) {
                if ($capture["id"] === $captureId) {
                    $isOk = true;
                    break;
                }
            }

            // Sleep for 1 second
            usleep(1000000);
        }

        if (!$isOk) {
            Mage::log($result);
            Mage::throwException(Mage::helper('mcash')->__('Partial capture failed, could not find capture id in outcome'));
        }
    }

    public function getProductLines($order) {
        $text = "";
        foreach ($order->getAllItems() as $product) {
            if (!$product->getParentItemId()) {
              $text .= (int)$product->getQtyOrdered().' x '.$product->getName()."\n";
            }
        }
        return $text;
    }

}
