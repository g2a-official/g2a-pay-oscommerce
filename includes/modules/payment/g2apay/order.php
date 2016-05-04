<?php

class g2apay_order
{
    public $_info, $_products, $_totals, $_transaction;

    public function load_by_id($order_id)
    {
        $query    = tep_db_query('select o.orders_id from ' . TABLE_ORDERS . ' o, ' . TABLE_ORDERS_STATUS . " s where o.orders_id = '" . (int) $order_id . "' and o.orders_status = s.orders_status_id");
        $num_rows = tep_db_num_rows($query);

        if ($num_rows > 0) {
            $this->load_info($order_id);
            $this->load_products($order_id);
            $this->load_totals($order_id);
            $this->load_transaction($order_id);

            return true;
        }

        return false;
    }

    public function g2apay_order()
    {
    }

    public function get_id()
    {
        return $this->_info['orders_id'];
    }

    public function get_amount()
    {
        $amount = 0;

        foreach ($this->_totals as $total) {
            if ('ot_total' == $total['class']) {
                $amount = g2apay_round($this->apply_currency($total['value']));
            }
        }

        return g2apay_round($amount);
    }

    public function get_currency()
    {
        return $this->_info['currency'];
    }

    public function get_customer_id()
    {
        return $this->_info['customers_id'];
    }

    public function get_customer_email()
    {
        return $this->_info['customers_email_address'];
    }

    public function get_transaction()
    {
        if (tep_not_null($this->_transaction)) {
            return $this->_transaction['transaction'];
        }

        return;
    }

    public function get_items()
    {
        $items = [];
        foreach ($this->_products as $product) {
            $info = [
                'id'    => $product['products_id'],
                'price' => $this->apply_currency($product['final_price']),
                'name'  => $product['products_name'],
                'qty'   => $product['products_quantity'],
                'type'  => 'product',
            ];
            $items[] = new g2apay_item($info);
        }

        $excluded_totals = ['ot_total', 'ot_subtotal'];

        foreach ($this->_totals as $total) {
            if (!in_array($total['class'], $excluded_totals)) {
                $info = [
                    'id'    => $total['orders_total_id'],
                    'price' => $this->apply_currency($total['value']),
                    'name'  => $total['title'],
                    'qty'   => 1,
                    'type'  => $total['class'],
                ];
                $items[] = new g2apay_item($info);
            }
        }

        return $items;
    }

    public function load_info($order_id)
    {
        $sql         = 'select orders_id, customers_id, customers_email_address, currency, currency_value from ' . TABLE_ORDERS . ' where orders_id = ' . (int) $order_id;
        $query       = tep_db_query($sql);
        $this->_info = tep_db_fetch_array($query);
    }

    public function load_products($order_id)
    {
        $sql             = 'select products_id, products_name, final_price, products_quantity from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = ' . (int) $order_id;
        $query           = tep_db_query($sql);
        $this->_products = [];
        while ($product = tep_db_fetch_array($query)) {
            $this->_products[] = $product;
        }
    }

    public function load_totals($order_id)
    {
        $sql           = 'select orders_total_id, title, value, class from ' . TABLE_ORDERS_TOTAL . ' where orders_id = ' . (int) $order_id;
        $query         = tep_db_query($sql);
        $this->_totals = [];
        while ($total = tep_db_fetch_array($query)) {
            $this->_totals[] = $total;
        }
    }

    public function load_transaction($order_id)
    {
        $sql                = 'select g2apay_transactions_id, `transaction`, `status`, data from g2apay_transactions where orders_id = ' . (int) $order_id;
        $query              = tep_db_query($sql);
        $this->_transaction = tep_db_fetch_array($query);
    }

    public function apply_currency($amount)
    {
        return $amount * $this->_info['currency_value'];
    }

    public function update_status($status, $comment = '')
    {
        $sql_data_array = [
            'orders_status' => $status,
            'last_modified' => 'now()',
        ];
        tep_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = ' . (int) $this->get_id());

        $sql_data_array = [
            'orders_id'         => (int) $this->get_id(),
            'orders_status_id'  => $status,
            'date_added'        => 'now()',
            'customer_notified' => '0',
            'comments'          => $comment,
        ];

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    }

    public function update_transaction($transaction, $status, $data = [])
    {
        $data = [
            'orders_id'   => $this->get_id(),
            'transaction' => $transaction,
            'status'      => $status,
            'data'        => serialize($data),
        ];

        if (tep_not_null($this->_transaction)) {
            $current_id         = $this->_transaction['g2apay_transactions_id'];
            $data['updated_at'] = 'now()';
            tep_db_perform('g2apay_transactions', $data, 'update', 'g2apay_transactions_id=' . (int) $current_id);
        } else {
            tep_db_perform('g2apay_transactions', $data);
        }
    }
}
