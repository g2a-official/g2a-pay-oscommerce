<?php

require DIR_WS_CLASSES . 'payment.php';
require DIR_WS_CLASSES . 'order_total.php';

$g2apay_base = dirname(__FILE__);
require_once $g2apay_base . '/config.php';
require_once $g2apay_base . '/order.php';
require_once $g2apay_base . '/functions.php';

class g2apay_ipn
{
    public $_config, $_error;

    public function g2apay_ipn()
    {
        $this->_config = new g2apay_config();
    }

    public function validate_data($data)
    {
        if (!is_array($data) || 0 === sizeof($data)) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_IPN_EMPTY_DATA;

            return false;
        }

        return true;
    }

    public function validate_secret($secret)
    {
        if ($this->_config->has_ipn_secret() && $this->_config->get_ipn_secret() !== $secret) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_IPN_INVALID_SECRET;

            return false;
        }

        return true;
    }

    public function process_data($data)
    {
        $init_success = $this->init_payment_method();

        if (false === $init_success) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_IPN_NOT_ACTIVE;

            return false;
        }

        $data = $this->filter_data((array) $data);

        $order_id  = $data['userOrderId'];
        $pay_order = new g2apay_order();

        if (!$pay_order->load_by_id($order_id)) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_IPN_NOT_FOUND;

            return false;
        }

        if (false === $this->validate_hash($pay_order, $data['transactionId'], $data['hash'])) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_IPN_INVALID_HASH;

            return false;
        }

        if (false === $this->validate_order_data($pay_order, $data)) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_IPN_INVALID_DATA;

            return false;
        }

        return $this->update_order($pay_order, $data);
    }

    /**
     * @param g2apay_order $order
     * @param array $data
     * @return bool
     */
    public function update_order($order, $data)
    {
        $status         = strtolower((string) $data['status']);
        $transaction_id = $data['transactionId'];
        $info           = [];

        switch ($status) {
            case 'complete':
                $comment = sprintf(MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_COMPLETED, $transaction_id);
                $order->update_status(MODULE_PAYMENT_G2APAY_COMPLETED_STATUS, $comment);
            break;

            case 'pending':
                $comment = sprintf(MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_PENDING, $transaction_id);
                $order->update_status(MODULE_PAYMENT_G2APAY_PENDING_STATUS, $comment);
            break;

            case 'rejected':
                $comment = sprintf(MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_REJECTED, $transaction_id);
                $order->update_status(MODULE_PAYMENT_G2APAY_CANCELED_STATUS, $comment);
            break;

            case 'canceled':
                $comment = sprintf(MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_CANCELLED, $transaction_id);
                $order->update_status(MODULE_PAYMENT_G2APAY_CANCELED_STATUS, $comment);
            break;

            case 'refunded':
            case 'partial_refunded':
                $total          = 'refunded' == $status;
                $info['amount'] = $data['refundedAmount'];
                $comment        = sprintf($total ? MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_REFUNDED : MODULE_PAYMENT_G2APAY_TEXT_IPN_STATUS_PARTIALLY_REFUNDED, $info['amount'], $transaction_id);
                $this->update_status($order, MODULE_PAYMENT_G2APAY_REFUNDED_STATUS, $comment);
            break;
        }

        $order->update_transaction($transaction_id, $status, $info);

        return true;
    }

    /**
     * @param g2apay_order $order
     * @param int $status
     * @param string $comment
     */
    public function update_status($order, $status, $comment = '')
    {
        $order->update_status($status, $comment);
    }

    /**
     * @param g2apay_order $order
     * @param $transaction_id
     * @param $hash
     * @return bool
     */
    public function validate_hash($order, $transaction_id, $hash)
    {
        $validHash = $this->generate_hash($order, $transaction_id);

        return $validHash === $hash;
    }

    /**
     * @param g2apay_order $order
     * @param $transaction_id
     * @return string
     */
    public function generate_hash($order, $transaction_id)
    {
        return g2apay_hash($transaction_id . $order->get_id() . $order->get_amount() . $this->_config->get_api_secret());
    }

    /**
     * @param g2apay_order $order
     * @param array $data
     * @return mixed
     */
    public function validate_order_data($order, $data)
    {
        $status = strtolower((string) $data['status']);

        return ($order->get_amount() == $data['amount'])
        && ($order->get_currency() == $data['currency'])
        && in_array($status, ['complete', 'partial_refunded', 'refunded', 'rejected', 'canceled']);
    }

    public function get_error()
    {
        return $this->_error;
    }

    public function filter_data($data)
    {
        $template = [
            'transactionId'   => null,
            'userOrderId'     => null,
            'amount'          => 0,
            'currency'        => null,
            'status'          => null,
            'orderCreatedAt'  => null,
            'orderCompleteAt' => null,
            'refundedAmount'  => null,
            'hash'            => null,
        ];

        foreach ($template as $key => $value) {
            if (array_key_exists($key, $data)) {
                $template[$key] = $data[$key];
            }
        }

        return $template;
    }

    public function init_payment_method()
    {
        global $g2apay;
        $payment_modules = new payment('g2apay');

        return is_object($g2apay) && get_class($g2apay) == 'g2apay' && $g2apay->enabled;
    }
}
