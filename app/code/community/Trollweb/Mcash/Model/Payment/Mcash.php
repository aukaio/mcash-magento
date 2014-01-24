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


    public function capture(Varien_Object $payment, $amount)
    {
        $api = Mage::getModel('mcash/api');
        $text = Mage::helper('mcash')->__('Order #%s',$payment->getOrder()->getIncrementId())."\n".$this->getProductLines($payment->getOrder());

        $isOk = false;

        if ($this->getConfigPaymentAction() == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE) {
            $transactionId = $api->paymentRequest($payment->getAdditionalInformation(self::MCASH_TOKEN),$amount,$payment->getOrder()->getId(),$text);
            $payment->setTransactionId($transactionId);

            if (!$transactionId) {
                Mage::throwException(Mage::helper('mcash')->__('Communication with mCash failed. Please try again or choose another payment method'));
            }

            $giveuptime = time()+120;

            while (time() < $giveuptime) {
                $result = $api->paymentRequestOutcome($transactionId);
                $status = $result['status'];

                if ($status == 'pending') {
                    usleep(1000);
                    continue;
                }

                // TODO: We probably want to handle ok and auth differently
                if ($status == 'ok' || $status == 'auth') {
                    $isOk = true;
                }
                break;
            }
        }

        if (!$isOk) {
            Mage::log($result);
            Mage::throwException(Mage::helper('mcash')->__('Payment failed'));
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
