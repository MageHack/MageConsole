<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * PayPal Instant Payment Notification processor model
 */
class Mage_Paypal_Model_Ipn
{
    /**
     * Default log filename
     *
     * @var string
     */
    const DEFAULT_LOG_FILE = 'paypal_unknown_ipn.log';

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /*
     * Recurring profile instance
     *
     * @var Mage_Sales_Model_Recurring_Profile
     */
    protected $_recurringProfile = null;

    /**
     *
     * @var Mage_Paypal_Model_Config
     */
    protected $_config = null;

    /**
     * PayPal info instance
     *
     * @var Mage_Paypal_Model_Info
     */
    protected $_info = null;

    /**
     * IPN request data
     * @var array
     */
    protected $_request = array();

    /**
     * Collected debug information
     *
     * @var array
     */
    protected $_debugData = array();

    /**
     * IPN request data getter
     *
     * @param string $key
     * @return array|string
     */
    public function getRequestData($key = null)
    {
        if (null === $key) {
            return $this->_request;
        }
        return isset($this->_request[$key]) ? $this->_request[$key] : null;
    }

    /**
     * Get ipn data, send verification to PayPal, run corresponding handler
     *
     * @param array $request
     * @param Zend_Http_Client_Adapter_Interface $httpAdapter
     * @throws Exception
     */
    public function processIpnRequest(array $request, Zend_Http_Client_Adapter_Interface $httpAdapter = null)
    {
        $this->_request   = $request;
        $this->_debugData = array('ipn' => $request);
        ksort($this->_debugData['ipn']);

        try {
            if (isset($this->_request['txn_type']) && 'recurring_payment' == $this->_request['txn_type']) {
                $this->_getRecurringProfile();
                if ($httpAdapter) {
                    $this->_postBack($httpAdapter);
                }
                $this->_processRecurringProfile();
            } else {
                $this->_getOrder();
                if ($httpAdapter) {
                    $this->_postBack($httpAdapter);
                }
                $this->_processOrder();
            }
        } catch (Exception $e) {
            $this->_debugData['exception'] = $e->getMessage();
            $this->_debug();
            throw $e;
        }
        $this->_debug();
    }

    /**
     * Post back to PayPal to check whether this request is a valid one
     *
     * @param Zend_Http_Client_Adapter_Interface $httpAdapter
     */
    protected function _postBack(Zend_Http_Client_Adapter_Interface $httpAdapter)
    {
            $sReq = '';
            foreach ($this->_request as $k => $v) {
                $sReq .= '&'.$k.'='.urlencode($v);
            }
            $sReq .= "&cmd=_notify-validate";
            $sReq = substr($sReq, 1);
            $this->_debugData['postback'] = $sReq;
            $this->_debugData['postback_to'] = $this->_config->getPaypalUrl();

            $httpAdapter->setConfig(array('verifypeer' => $this->_config->verifyPeer));
            $httpAdapter->write(Zend_Http_Client::POST, $this->_config->getPaypalUrl(), '1.1', array(), $sReq);
            try {
                $response = $httpAdapter->read();
            } catch (Exception $e) {
                $this->_debugData['http_error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                throw $e;
            }
            $this->_debugData['postback_result'] = $response;

            $response = preg_split('/^\r?$/m', $response, 2);
            $response = trim($response[1]);
            if ($response != 'VERIFIED') {
                throw new Exception('PayPal IPN postback failure. See ' . self::DEFAULT_LOG_FILE . ' for details.');
            }
            unset($this->_debugData['postback'], $this->_debugData['postback_result']);
    }

    /**
     * Load and validate order, instantiate proper configuration
     *
     *
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    protected function _getOrder()
    {
        if (empty($this->_order)) {
            // get proper order
            $id = $this->_request['invoice'];
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($id);
            if (!$this->_order->getId()) {
                $this->_debugData['exception'] = sprintf('Wrong order ID: "%s".', $id);
                $this->_debug();
                Mage::app()->getResponse()
                    ->setHeader('HTTP/1.1','503 Service Unavailable')
                    ->sendResponse();
                exit;
            }
            // re-initialize config with the method code and store id
            $methodCode = $this->_order->getPayment()->getMethod();
            $this->_config = Mage::getModel('paypal/config', array($methodCode, $this->_order->getStoreId()));
            if (!$this->_config->isMethodActive($methodCode) || !$this->_config->isMethodAvailable()) {
                throw new Exception(sprintf('Method "%s" is not available.', $methodCode));
            }

            $this->_verifyOrder();
        }
        return $this->_order;
    }

    /**
     * Load recurring profile
     *
     * @return Mage_Sales_Model_Recurring_Profile
     * @throws Exception
     */
    protected function _getRecurringProfile()
    {
        if (empty($this->_recurringProfile)) {
            // get proper recurring profile
            $internalReferenceId = $this->_request['rp_invoice_id'];
            $this->_recurringProfile = Mage::getModel('sales/recurring_profile')
                ->loadByInternalReferenceId($internalReferenceId);
            if (!$this->_recurringProfile->getId()) {
                throw new Exception(
                    sprintf('Wrong recurring profile INTERNAL_REFERENCE_ID: "%s".', $internalReferenceId)
                );
            }
            // re-initialize config with the method code and store id
            $methodCode = $this->_recurringProfile->getMethodCode();
            $this->_config = Mage::getModel(
                'paypal/config', array($methodCode, $this->_recurringProfile->getStoreId())
            );
            if (!$this->_config->isMethodActive($methodCode) || !$this->_config->isMethodAvailable()) {
                throw new Exception(sprintf('Method "%s" is not available.', $methodCode));
            }
        }
        return $this->_recurringProfile;
    }

    /**
     * Validate incoming request data, as PayPal recommends
     *
     * @throws Exception
     * @link https://cms.paypal.com/cgi-bin/marketingweb?cmd=_render-content&content_ID=developer/e_howto_admin_IPNIntro
     */
    protected function _verifyOrder()
    {
        // verify merchant email intended to receive notification
        $merchantEmail = $this->_config->businessAccount;
        if ($merchantEmail) {
            $receiverEmail = $this->getRequestData('business');
            if (!$receiverEmail) {
                $receiverEmail = $this->getRequestData('receiver_email');
            }
            if (strtolower($merchantEmail) != strtolower($receiverEmail)) {
                throw new Exception(
                    sprintf(
                        'Requested %s and configured %s merchant emails do not match.', $receiverEmail, $merchantEmail
                    )
                );
            }
        }
    }

    /**
     * IPN workflow implementation
     * Everything should be added to order comments. In positive processing cases customer will get email notifications.
     * Admin will be notified on errors.
     */
    protected function _processOrder()
    {
        $this->_order = null;
        $this->_getOrder();

        $this->_info = Mage::getSingleton('paypal/info');
        try {
            // handle payment_status
            $paymentStatus = $this->_filterPaymentStatus($this->_request['payment_status']);

            switch ($paymentStatus) {
                // paid
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:
                    $this->_registerPaymentCapture();
                    break;

                // the holded payment was denied on paypal side
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_DENIED:
                    $this->_registerPaymentDenial();
                    break;

                // customer attempted to pay via bank account, but failed
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_FAILED:
                    // cancel order
                    $this->_registerPaymentFailure();
                    break;

                // refund forced by PayPal
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_REVERSED: // break is intentionally omitted
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_UNREVERSED: // or returned back :)
                    $this->_registerPaymentReversal();
                    break;

                // refund by merchant on PayPal side
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_REFUNDED:
                    $this->_registerPaymentRefund();
                    break;

                // payment was obtained, but money were not captured yet
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING:
                    $this->_registerPaymentPending();
                    break;

                // MassPayments success
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_PROCESSED:
                    $this->_registerMasspaymentsSuccess();
                    break;

                // authorization expire/void
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_EXPIRED: // break is intentionally omitted
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_VOIDED:
                    $this->_registerPaymentVoid();
                    break;

                default:
                    throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
            }
        } catch (Mage_Core_Exception $e) {
            $comment = $this->_createIpnComment(Mage::helper('paypal')->__('Note: %s', $e->getMessage()), true);
            $comment->save();
            throw $e;
        }
    }

    /**
     * Process notification from recurring profile payments
     */
    protected function _processRecurringProfile()
    {
        $this->_recurringProfile = null;
        $this->_getRecurringProfile();

        try {
            // handle payment_status
            $paymentStatus = $this->_filterPaymentStatus($this->_request['payment_status']);

            switch ($paymentStatus) {
                // paid
                case Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED:
                    $this->_registerRecurringProfilePaymentCapture();
                    break;

                default:
                    throw new Exception("Cannot handle payment status '{$paymentStatus}'.");
            }
        } catch (Mage_Core_Exception $e) {
// TODO: add to payment profile comments
//            $comment = $this->_createIpnComment(Mage::helper('paypal')->__('Note: %s', $e->getMessage()), true);
//            $comment->save();
            throw $e;
        }
    }

    /**
     * Register recurring payment notification, create and process order
     */
    protected function _registerRecurringProfilePaymentCapture()
    {
        $price = $this->getRequestData('mc_gross') - $this->getRequestData('tax') -  $this->getRequestData('shipping');
        $productItemInfo = new Varien_Object;
        $type = trim($this->getRequestData('period_type'));
        if ($type == 'Trial') {
            $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_TRIAL);
        } elseif ($type == 'Regular') {
            $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_REGULAR);
        }
        $productItemInfo->setTaxAmount($this->getRequestData('tax'));
        $productItemInfo->setShippingAmount($this->getRequestData('shipping'));
        $productItemInfo->setPrice($price);

        $order = $this->_recurringProfile->createOrder($productItemInfo);

        $payment = $order->getPayment();
        $payment->setTransactionId($this->getRequestData('txn_id'))
            ->setPreparedMessage($this->_createIpnComment(''))
            ->setIsTransactionClosed(0);
        $order->save();
        $this->_recurringProfile->addOrderRelation($order->getId());
        $payment->registerCaptureNotification($this->getRequestData('mc_gross'));
        $order->save();

        // notify customer
        if ($invoice = $payment->getCreatedInvoice()) {
            $message = Mage::helper('paypal')->__('Notified customer about invoice #%s.', $invoice->getIncrementId());
            $comment = $order->sendNewOrderEmail()->addStatusHistoryComment($message)
                ->setIsCustomerNotified(true)
                ->save();
        }
    }

    /**
     * Process completed payment (either full or partial)
     */
    protected function _registerPaymentCapture()
    {
        if ($this->getRequestData('transaction_entity') == 'auth') {
            return;
        }
        $this->_importPaymentInformation();
        $payment = $this->_order->getPayment();
        $payment->setTransactionId($this->getRequestData('txn_id'))
            ->setPreparedMessage($this->_createIpnComment(''))
            ->setParentTransactionId($this->getRequestData('parent_txn_id'))
            ->setShouldCloseParentTransaction('Completed' === $this->getRequestData('auth_status'))
            ->setIsTransactionClosed(0)
            ->registerCaptureNotification($this->getRequestData('mc_gross'));
        $this->_order->save();

        // notify customer
        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$this->_order->getEmailSent()) {
            $this->_order->sendNewOrderEmail()->addStatusHistoryComment(
                Mage::helper('paypal')->__('Notified customer about invoice #%s.', $invoice->getIncrementId())
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
    }

    /**
     * Process denied payment notification
     */
    protected function _registerPaymentDenial()
    {
        $this->_importPaymentInformation();
        $this->_order->getPayment()
            ->setTransactionId($this->getRequestData('txn_id'))
            ->setNotificationResult(true)
            ->setIsTransactionClosed(true)
            ->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
        $this->_order->save();
    }

    /**
     * Treat failed payment as order cancellation
     */
    protected function _registerPaymentFailure()
    {
        $this->_importPaymentInformation();
        $this->_order
            ->registerCancellation($this->_createIpnComment(''), false)
            ->save();
    }

    /**
     * Process a refund or a chargeback
     */
    protected function _registerPaymentRefund()
    {
        $this->_importPaymentInformation();
        $reason = $this->getRequestData('reason_code');
        $isRefundFinal = !$this->_info->isReversalDisputable($reason);
        $payment = $this->_order->getPayment()
            ->setPreparedMessage($this->_createIpnComment($this->_info->explainReasonCode($reason)))
            ->setTransactionId($this->getRequestData('txn_id'))
            ->setParentTransactionId($this->getRequestData('parent_txn_id'))
            ->setIsTransactionClosed($isRefundFinal)
            ->registerRefundNotification(-1 * $this->getRequestData('mc_gross'));
        $this->_order->save();

        // TODO: there is no way to close a capture right now

        if ($creditmemo = $payment->getCreatedCreditmemo()) {
            $creditmemo->sendEmail();
            $comment = $this->_order->addStatusHistoryComment(
                    Mage::helper('paypal')->__('Notified customer about creditmemo #%s.', $creditmemo->getIncrementId())
                )
                ->setIsCustomerNotified(true)
                ->save();
        }
    }

    /**
     * Process payment reversal notification
     */
    protected function _registerPaymentReversal()
    {
        /**
         * PayPal may send such payment status when triggered IPR denial
         * Note that this check is done on the old payment info object, before importing new payment information
         */
        if ($this->_info->isPaymentReviewRequired($this->_order->getPayment())) {
            $this->_registerPaymentDenial();
            return;
        }

        if ('chargeback_reimbursement' == $this->getRequestData('reason_code')) {
            // TODO: chargebacks reversals are not implemented
            return;
        }

        // treat as a usual charegeback
        $this->_registerPaymentRefund();
    }

    /**
     * Process payment pending notification
     *
     * @throws Exception
     */
    public function _registerPaymentPending()
    {
        $reason = $this->getRequestData('pending_reason');
        if ('authorization' === $reason) {
            $this->_registerPaymentAuthorization();
            return;
        }
        if ('order' === $reason) {
            throw new Exception('The "order" authorizations are not implemented.');
        }

        // case when was placed using PayPal standard
        if (Mage_Sales_Model_Order::STATE_PENDING_PAYMENT == $this->_order->getState()) {
            $this->_registerPaymentCapture();
            return;
        }

        $this->_importPaymentInformation();

        $this->_order->getPayment()
            ->setPreparedMessage($this->_createIpnComment($this->_info->explainPendingReason($reason)))
            ->setTransactionId($this->getRequestData('txn_id'))
            ->setIsTransactionClosed(0)
            ->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_UPDATE, false);
        $this->_order->save();
    }

    /**
     * Register authorized payment
     */
    protected function _registerPaymentAuthorization()
    {
        $this->_importPaymentInformation();

        $this->_order->getPayment()
            ->setPreparedMessage($this->_createIpnComment(''))
            ->setTransactionId($this->getRequestData('txn_id'))
            ->setParentTransactionId($this->getRequestData('parent_txn_id'))
            ->setIsTransactionClosed(0)
            ->registerAuthorizationNotification($this->getRequestData('mc_gross'));
        if (!$this->_order->getEmailSent()) {
            $this->_order->sendNewOrderEmail();
        }
        $this->_order->save();
    }

    /**
     * Process voided authorization
     */
    protected function _registerPaymentVoid()
    {
        $this->_importPaymentInformation();

        $parentTxnId = $this->getRequestData('transaction_entity') == 'auth'
            ? $this->getRequestData('txn_id') : $this->getRequestData('parent_txn_id');

        $this->_order->getPayment()
            ->setPreparedMessage($this->_createIpnComment(''))
            ->setParentTransactionId($parentTxnId)
            ->registerVoidNotification();

        $this->_order->save();
    }

    /**
     * TODO
     * The status "Processed" is used when all Masspayments are successful
     */
    protected function _registerMasspaymentsSuccess()
    {
        $comment = $this->_createIpnComment('', true);
        $comment->save();
    }

    /**
     * Generate an "IPN" comment with additional explanation.
     * Returns the generated comment or order status history object
     *
     * @param string $comment
     * @param bool $addToHistory
     * @return string|Mage_Sales_Model_Order_Status_History
     */
    protected function _createIpnComment($comment = '', $addToHistory = false)
    {
        $paymentStatus = $this->getRequestData('payment_status');
        $message = Mage::helper('paypal')->__('IPN "%s".', $paymentStatus);
        if ($comment) {
            $message .= ' ' . $comment;
        }
        if ($addToHistory) {
            $message = $this->_order->addStatusHistoryComment($message);
            $message->setIsCustomerNotified(null);
        }
        return $message;
    }

    /**
     * Map payment information from IPN to payment object
     * Returns true if there were changes in information
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    protected function _importPaymentInformation()
    {
        $payment = $this->_order->getPayment();
        $was = $payment->getAdditionalInformation();

        // collect basic information
        $from = array();
        foreach (array(
            Mage_Paypal_Model_Info::PAYER_ID,
            'payer_email' => Mage_Paypal_Model_Info::PAYER_EMAIL,
            Mage_Paypal_Model_Info::PAYER_STATUS,
            Mage_Paypal_Model_Info::ADDRESS_STATUS,
            Mage_Paypal_Model_Info::PROTECTION_EL,
            Mage_Paypal_Model_Info::PAYMENT_STATUS,
            Mage_Paypal_Model_Info::PENDING_REASON,
        ) as $privateKey => $publicKey) {
            if (is_int($privateKey)) {
                $privateKey = $publicKey;
            }
            $value = $this->getRequestData($privateKey);
            if ($value) {
                $from[$publicKey] = $value;
            }
        }
        if (isset($from['payment_status'])) {
            $from['payment_status'] = $this->_filterPaymentStatus($this->getRequestData('payment_status'));
        }

        // collect fraud filters
        $fraudFilters = array();
        for ($i = 1; $value = $this->getRequestData("fraud_management_pending_filters_{$i}"); $i++) {
            $fraudFilters[] = $value;
        }
        if ($fraudFilters) {
            $from[Mage_Paypal_Model_Info::FRAUD_FILTERS] = $fraudFilters;
        }

        $this->_info->importToPayment($from, $payment);

        /**
         * Detect pending payment, frauds
         * TODO: implement logic in one place
         * @see Mage_Paypal_Model_Pro::importPaymentInfo()
         */
        if ($this->_info->isPaymentReviewRequired($payment)) {
            $payment->setIsTransactionPending(true);
            if ($fraudFilters) {
                $payment->setIsFraudDetected(true);
            }
        }
        if ($this->_info->isPaymentSuccessful($payment)) {
            $payment->setIsTransactionApproved(true);
        } elseif ($this->_info->isPaymentFailed($payment)) {
            $payment->setIsTransactionDenied(true);
        }

        return $was != $payment->getAdditionalInformation();
    }

    /**
     * Filter payment status from NVP into paypal/info format
     *
     * @param string $ipnPaymentStatus
     * @return string
     */
    protected function _filterPaymentStatus($ipnPaymentStatus)
    {
        switch ($ipnPaymentStatus) {
            case 'Created': // break is intentionally omitted
            case 'Completed': return Mage_Paypal_Model_Info::PAYMENTSTATUS_COMPLETED;
            case 'Denied':    return Mage_Paypal_Model_Info::PAYMENTSTATUS_DENIED;
            case 'Expired':   return Mage_Paypal_Model_Info::PAYMENTSTATUS_EXPIRED;
            case 'Failed':    return Mage_Paypal_Model_Info::PAYMENTSTATUS_FAILED;
            case 'Pending':   return Mage_Paypal_Model_Info::PAYMENTSTATUS_PENDING;
            case 'Refunded':  return Mage_Paypal_Model_Info::PAYMENTSTATUS_REFUNDED;
            case 'Reversed':  return Mage_Paypal_Model_Info::PAYMENTSTATUS_REVERSED;
            case 'Canceled_Reversal': return Mage_Paypal_Model_Info::PAYMENTSTATUS_UNREVERSED;
            case 'Processed': return Mage_Paypal_Model_Info::PAYMENTSTATUS_PROCESSED;
            case 'Voided':    return Mage_Paypal_Model_Info::PAYMENTSTATUS_VOIDED;
        }
        return '';
// documented in NVP, but not documented in IPN:
//Mage_Paypal_Model_Info::PAYMENTSTATUS_NONE
//Mage_Paypal_Model_Info::PAYMENTSTATUS_INPROGRESS
//Mage_Paypal_Model_Info::PAYMENTSTATUS_REFUNDEDPART
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug()
    {
        if ($this->_config && $this->_config->debug) {
            $file = $this->_config->getMethodCode() ? "payment_{$this->_config->getMethodCode()}.log"
                : self::DEFAULT_LOG_FILE;
            Mage::getModel('core/log_adapter', $file)->log($this->_debugData);
        }
    }
}
