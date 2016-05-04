<?php

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/client.php';
require_once dirname(__FILE__) . '/functions.php';

class g2apay_refund
{
    public $_config, $_error;

    public function g2apay_refund()
    {
        $this->_config = new g2apay_config();
    }

    /**
     * @param g2apay_order $order
     * @param $amount
     * @return false
     */
    public function online($order, $amount)
    {
        $transaction = $order->get_transaction();
        if (tep_not_null($transaction)) {
            $url            = $this->_config->get_rest_url("transactions/{$transaction}");
            $client         = new g2apay_client($url);
            $client->method = 'PUT';

            $data = [
                'action' => 'refund',
                'amount' => g2apay_round($amount),
                'hash'   => $this->generate_hash($order, $amount),
            ];

            $hash = $this->_config->get_api_hash() . ';' . $this->_config->get_authorization_hash();
            $client->add_header('Authorization', $hash);

            $response = $client->send_request($data);

            if (is_array($response) && isset($response['status']) && strcasecmp($response['status'], 'ok') === 0) {
                $success = $this->store_refund($order, $amount);

                if (!$success) {
                    $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_ONLINE_NOT_STORED;

                    return false;
                }

                return true;
            }

            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_ONLINE_FAILED;

            return false;
        }

        $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_MISSING_TRANSACTION;

        return false;
    }

    /**
     * @param g2apay_order $order
     * @param $amount
     * @return false
     */
    public function offline($order, $amount)
    {
        $success = $this->store_refund($order, $amount);

        if (!$success) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_OFFLINE_FAILED;

            return false;
        }

        return true;
    }

    /**
     * @param g2apay_order $order
     * @param $amount
     * @return bool
     */
    public function store_refund($order, $amount)
    {
        global $currencies;

        $now = date('Y-m-d H:i:s');

        $data = [
            'orders_id'  => $order->get_id(),
            'title'      => sprintf(MODULE_PAYMENT_G2APAY_TEXT_REFUND_ORDER_TOTAL, $now),
            'value'      => $amount,
            'text'       => $currencies->format($amount, false, $order->get_currency()),
            'class'      => 'ot_refund',
            'sort_order' => $this->get_next_order_total_sort_order($order->get_id()),
        ];

        tep_db_perform(TABLE_ORDERS_TOTAL, $data);

        return true;
    }

    /**
     * @param g2apay_order $order
     * @param $amount
     * @return string
     */
    public function generate_hash($order, $amount)
    {
        $string = $order->get_transaction() . $order->get_id() . $order->get_amount() . g2apay_round($amount) . $this->_config->get_api_secret();

        return g2apay_hash($string);
    }

    public function get_error()
    {
        return $this->_error;
    }

    public function get_next_order_total_sort_order($order_id)
    {
        $sql   = 'select max(sort_order) as sort_order from ' . TABLE_ORDERS_TOTAL . ' where orders_id=' . (int) $order_id;
        $query = tep_db_query($sql);

        return tep_db_result($query, 0, 'sort_order') + 1;
    }

    /**
     * @param g2apay_order $pay_order
     * @return float
     */
    public function get_already_refunded_amount($pay_order)
    {
        $sql   = 'select sum(value) as amount from ' . TABLE_ORDERS_TOTAL . ' where orders_id=' . (int) $pay_order->get_id() . ' and class="ot_refund"';
        $query = tep_db_query($sql);

        return g2apay_round(tep_db_result($query, 0, 'amount'));
    }

    /**
     * @param g2apay_order $pay_order
     * @param $amount
     * @return bool
     */
    public function can_refund_amount($pay_order, $amount)
    {
        if (empty($amount)) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_REQUIRED_AMOUNT;

            return false;
        }

        if (!is_numeric($amount)) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_INVALID_AMOUNT;

            return false;
        }

        $amount = g2apay_round($amount);

        if ($amount <= 0) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_INVALID_AMOUNT;

            return false;
        }

        $already_refunded = $this->get_already_refunded_amount($pay_order);

        $maximum_allowed_amount = g2apay_round($pay_order->get_amount() - $already_refunded);

        if ($amount > $maximum_allowed_amount) {
            $this->_error = sprintf(MODULE_PAYMENT_G2APAY_ERROR_REFUND_TOO_BIG_AMOUNT, $amount,
                $maximum_allowed_amount);

            return false;
        }

        return true;
    }

    public function can_refund($pay_order)
    {
        // check minimal refund amount
        return $this->can_refund_amount($pay_order, 0.01);
    }

    /**
     * @param g2apay_order $pay_order
     * @return bool
     */
    public function can_refund_online($pay_order)
    {
        $transaction = $pay_order->get_transaction();

        if (empty($transaction)) {
            $this->_error = MODULE_PAYMENT_G2APAY_ERROR_REFUND_MISSING_TRANSACTION;

            return false;
        }

        // @todo: check allowed statuses

        return true;
    }
}
