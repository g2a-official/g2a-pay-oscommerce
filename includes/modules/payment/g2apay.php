<?php

$g2apay_base = dirname(__FILE__) . '/g2apay';

require_once $g2apay_base . '/config.php';
require_once $g2apay_base . '/functions.php';
require_once $g2apay_base . '/session.php';
require_once $g2apay_base . '/client.php';
require_once $g2apay_base . '/order.php';
require_once $g2apay_base . '/item.php';

/**
 * G2A Pay payment method.
 *
 * @property g2apay_config $_config
 */
class g2apay
{
    public $code, $title, $public_title, $description, $sort_order, $enabled, $order_status,
        $_config;

    public function g2apay()
    {
        global $order;

        $this->_config = new g2apay_config();

        $this->api_version = '2';
        $this->signature   = 'g2apay|g2apay|2.0|2.0';

        $this->code         = 'g2apay';
        $this->title        = MODULE_PAYMENT_G2APAY_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_G2APAY_TEXT_PUBLIC_TITLE;
        $this->description  = MODULE_PAYMENT_G2APAY_TEXT_DESCRIPTION;
        $this->sort_order   = defined('MODULE_PAYMENT_G2APAY_SORT_ORDER') ? (int) MODULE_PAYMENT_G2APAY_SORT_ORDER : 0;
        $this->enabled      = defined('MODULE_PAYMENT_G2APAY_STATUS') && (strcasecmp(MODULE_PAYMENT_G2APAY_STATUS, 'True') === 0);
        $this->order_status = 0;

        if ($this->_config->is_sandbox()) {
            $this->title .= ' [Sandbox]';
            $this->public_title .= ' (' . $this->code . '; Sandbox)';
        }

        if (!function_exists('curl_init')) {
            $this->description = '<div class="secWarning">' . MODULE_PAYMENT_G2APAY_ERROR_ADMIN_CURL . '</div>' . $this->description;
            $this->enabled     = false;
        }

        if ($this->enabled === true) {
            if (!(tep_not_null($this->_config->get_api_hash()) && tep_not_null($this->_config->get_api_secret()))) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_G2APAY_ERROR_ADMIN_API_CREDENTIALS . '</div>' . $this->description;
                $this->enabled     = false;
            }

            if (!tep_validate_email($this->_config->get_merchant_email())) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_G2APAY_ERROR_ADMIN_MERCHANT_EMAIL . '</div>' . $this->description;
                $this->enabled     = false;
            }

            if (isset($order) && is_object($order)) {
                $this->update_status();
            }
        }
    }

    public function update_status()
    {
        if (($this->enabled == true) && ((int) MODULE_PAYMENT_G2APAY_ZONE > 0)) {
            $this->enabled = $this->is_order_zone_allowed();
        }
    }

    public function is_order_zone_allowed()
    {
        global $order;
        $check_query = tep_db_query('SELECT zone_id FROM ' . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_PAYMENT_G2APAY_ZONE . "' AND zone_country_id = '" . $order->delivery['country']['id'] . "' ORDER BY zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
            if ($check['zone_id'] < 1 || $check['zone_id'] == $order->delivery['zone_id']) {
                return true;
            }
        }

        return false;
    }

    public function get_error()
    {
        return false;
    }

    public function check()
    {
        if (!isset($this->_check)) {
            $table        = TABLE_CONFIGURATION;
            $key          = $this->quote('MODULE_PAYMENT_G2APAY_STATUS');
            $check_query  = tep_db_query("SELECT configuration_value FROM {$table} WHERE configuration_key = {$key}");
            $this->_check = tep_db_num_rows($check_query);
        }

        return $this->_check;
    }

    public function javascript_validation()
    {
        return false;
    }

    public function selection()
    {
        return [
            'id'     => $this->code,
            'module' => $this->public_title,
        ];
    }

    public function pre_confirmation_check()
    {
    }

    public function confirmation()
    {
        $confirmation = ['title' => tep_image('ext/modules/payment/g2apay/images/checkout.png')];

        return $confirmation;
    }

    public function process_button()
    {
        return false;
    }

    public function before_process()
    {
        global $order;
        if (tep_not_null(MODULE_PAYMENT_G2APAY_PENDING_STATUS)) {
            $order->info['order_status'] = MODULE_PAYMENT_G2APAY_PENDING_STATUS;
        }
    }

    public function after_process()
    {
        global $insert_id, $cart;

        $pay_order = new g2apay_order();
        $pay_order->load_by_id($insert_id);
        $secure_token = g2apay_random_token();
        $data         = $this->get_order_data($pay_order, $secure_token);

        $client = new g2apay_client($this->_config->get_create_quote_url());
        $result = $client->send_request($data);

        if (is_array($result) && isset($result ['status']) && strcasecmp($result['status'], 'ok') === 0) {
            $cart->reset(true);
            $token       = $result['token'];
            $pay_session = new g2apay_session();
            $pay_session->store_order($pay_order, $secure_token);
            tep_redirect($this->_config->get_checkout_gateway_url($token));
        } else {
            tep_redirect(tep_href_link(FILENAME_SHOPPING_CART,
                'error_message=' . MODULE_PAYMENT_G2APAY_ERROR_CREATE_QUOTE, 'SSL'));
        }
    }

    public function keys()
    {
        $params = $this->get_params();

        return array_keys($params);
    }

    public function install($parameter = null)
    {
        $params = $this->get_params();

        if (isset($parameter)) {
            if (isset($params[$parameter])) {
                $params = [$parameter => $params[$parameter]];
            } else {
                $params = [];
            }
        }

        foreach ($params as $key => $data) {
            $sql_data_array = [
                'configuration_title'       => $data['title'],
                'configuration_key'         => $key,
                'configuration_value'       => (isset($data['value']) ? $data['value'] : ''),
                'configuration_description' => $data['desc'],
                'configuration_group_id'    => '6',
                'sort_order'                => '0',
                'date_added'                => 'now()',
            ];

            if (isset($data['set_func'])) {
                $sql_data_array['set_function'] = $data['set_func'];
            }

            if (isset($data['use_func'])) {
                $sql_data_array['use_function'] = $data['use_func'];
            }

            tep_db_perform(TABLE_CONFIGURATION, $sql_data_array);
        }

        $this->create_tables();
    }

    public function remove()
    {
        $table = TABLE_CONFIGURATION;
        $keys  = array_map([$this, 'quote'], $this->keys());
        $in    = implode(', ', $keys);
        tep_db_query("DELETE FROM {$table} WHERE configuration_key IN ({$in})");

        // does not remove g2apay_transactions table so history will be accessible
    }

    public function quote($value)
    {
        $escaped = tep_db_input($value);

        return "'{$escaped}'";
    }

    /**
     * @param g2apay_order $pay_order
     * @param $secure_token
     * @return array
     */
    public function get_order_data($pay_order, $secure_token)
    {
        $hash = g2apay_hash($pay_order->get_id() . $pay_order->get_amount() . $pay_order->get_currency() . $this->_config->get_api_secret());

        $secure_params    = 'order_id=' . $pay_order->get_id() . '&token=' . $secure_token;
        $gateway_base_url = tep_href_link('ext/modules/payment/g2apay/gateway.php', '', 'SSL');
        $gateway_base_url .= (strpos($gateway_base_url, '?') === false ? '?' : '&') . $secure_params;

        $data = [
            'api_hash'    => $this->_config->get_api_hash(),
            'hash'        => $hash,
            'order_id'    => $pay_order->get_id(),
            'amount'      => $pay_order->get_amount(),
            'currency'    => $pay_order->get_currency(),
            'email'       => $pay_order->get_customer_email(),
            'items'       => $this->get_order_items_data($pay_order),
            'url_failure' => $gateway_base_url . '&action=cancel',
            'url_ok'      => $gateway_base_url . '&action=success',
        ];

        return $data;
    }

    /**
     * @param g2apay_order $pay_order
     * @return array
     */
    public function get_order_items_data($pay_order)
    {
        $items_data = [];

        /** @var g2apay_item $item */
        foreach ($pay_order->get_items() as $item) {
            $items_data[] = [
                'id'     => $item->get_id(),
                'sku'    => $item->get_sku(),
                'name'   => $item->get_name(),
                'price'  => $item->get_price(),
                'amount' => $item->get_amount(),
                'qty'    => $item->get_qty(),
                'type'   => $item->get_type(),
                'url'    => 'http://www.oscommerce.dev',
            ];
        }

        return $items_data;
    }

    public function get_params()
    {
        return [
            'MODULE_PAYMENT_G2APAY_STATUS' => [
                'title'    => 'Enable G2A Pay',
                'desc'     => 'Do you want to accept G2A Pay payments?',
                'value'    => 'True',
                'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            'MODULE_PAYMENT_G2APAY_API_HASH' => [
                'title' => 'API Hash',
                'desc'  => 'The hash for G2A Pay API service.',
            ],
            'MODULE_PAYMENT_G2APAY_API_SECRET' => [
                'title' => 'API Secret',
                'desc'  => 'The secret for G2A Pay API service.',
            ],
            'MODULE_PAYMENT_G2APAY_MERCHANT_EMAIL' => [
                'title' => 'Merchant email',
                'desc'  => 'G2A Pay merchant email.',
            ],
            'MODULE_PAYMENT_G2APAY_ENVIRONMENT' => [
                'title'    => 'Environment',
                'desc'     => 'Which environment to use with with current configuration?',
                'value'    => 'Production',
                'set_func' => 'tep_cfg_select_option(array(\'Production\', \'Sandbox\'), ',
            ],
            'MODULE_PAYMENT_G2APAY_COMPLETED_STATUS' => [
                'title' => 'Order status for completed payment',
                'desc'  => 'Set the status of orders marked as paid by G2A Pay IPN',
                'value' => defined('MODULE_PAYMENT_G2APAY_COMPLETED_STATUS')
                    ? MODULE_PAYMENT_G2APAY_COMPLETED_STATUS : $this->fetch_or_create_order_status('Completed [G2A Pay]',
                        1, 1),
                'set_func' => 'tep_cfg_pull_down_order_statuses(',
                'use_func' => 'tep_get_order_status_name',
            ],
            'MODULE_PAYMENT_G2APAY_PENDING_STATUS' => [
                'title' => 'Order status for pending payment',
                'desc'  => 'Set the status of orders marked as pending by G2A Pay IPN',
                'value' => defined('MODULE_PAYMENT_G2APAY_PENDING_STATUS')
                    ? MODULE_PAYMENT_G2APAY_PENDING_STATUS : $this->fetch_or_create_order_status('Pending [G2A Pay]',
                        1, 1),
                'set_func' => 'tep_cfg_pull_down_order_statuses(',
                'use_func' => 'tep_get_order_status_name',
            ],
            'MODULE_PAYMENT_G2APAY_CANCELED_STATUS' => [
                'title' => 'Order status for cancelled or rejected payments',
                'desc'  => 'Set the status of orders rejected or cancelled by G2A Pay IPN',
                'value' => defined('MODULE_PAYMENT_G2APAY_CANCELED_STATUS')
                    ? MODULE_PAYMENT_G2APAY_CANCELED_STATUS : $this->fetch_or_create_order_status('Cancelled [G2A Pay]',
                        1),
                'set_func' => 'tep_cfg_pull_down_order_statuses(',
                'use_func' => 'tep_get_order_status_name',
            ],
            'MODULE_PAYMENT_G2APAY_REFUNDED_STATUS' => [
                'title' => 'Order status for refunded payments',
                'desc'  => 'Set the status of orders refunded or partially refunded by G2A Pay IPN',
                'value' => defined('MODULE_PAYMENT_G2APAY_REFUNDED_STATUS')
                    ? MODULE_PAYMENT_G2APAY_REFUNDED_STATUS : $this->fetch_or_create_order_status('Refunded [G2A Pay]'),
                'set_func' => 'tep_cfg_pull_down_order_statuses(',
                'use_func' => 'tep_get_order_status_name',
            ],
            'MODULE_PAYMENT_G2APAY_IPN_SECRET' => [
                'title' => 'IPN secret',
                'desc'  => 'Additional token that will be required as "secret" GET parameter to access IPN. Leave empty to disable',
            ],
            'MODULE_PAYMENT_G2APAY_VERIFY_SSL' => [
                'title'    => 'Verify SSL certificate',
                'desc'     => 'Should gateway SSL certificate be verified on connection?',
                'value'    => 'True',
                'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            'MODULE_PAYMENT_G2APAY_LOG_EMAIL' => [
                'title' => 'Log email',
                'desc'  => 'Email address to send info about IPN errors. Leave empty to disable.',
            ],
            'MODULE_PAYMENT_G2APAY_SORT_ORDER' => [
                'title' => 'Frontend display sort order.',
                'desc'  => 'Frontend display sort order. Lowest is displayed first.',
                'value' => '0',
            ],
            'MODULE_PAYMENT_G2APAY_ZONE' => [
                'title'    => 'Payment Zone',
                'desc'     => 'Enable method payments only fot this zone. Leave empty to enable for any zone.',
                'desc'     => 'If a zone is selected, only enable this payment method for that zone.',
                'value'    => '0',
                'use_func' => 'tep_get_zone_class_title',
                'set_func' => 'tep_cfg_pull_down_zone_classes(',
            ],
        ];
    }

    public function fetch_or_create_order_status($name, $public = 0, $download = 0)
    {
        $quoted_name = $this->quote($name);
        $sql         = 'select orders_status_id from ' . TABLE_ORDERS_STATUS . " where orders_status_name = $quoted_name limit 1";
        $query       = tep_db_query($sql);

        if (tep_db_num_rows($query) > 0) {
            $status = tep_db_fetch_array($query);

            return $status['orders_status_id'];
        } else {
            $new_status_id = $this->get_new_order_status_id();
            $languages     = tep_get_languages();

            foreach ($languages as $lang) {
                $data = [
                    'orders_status_id'   => $new_status_id,
                    'language_id'        => $lang['id'],
                    'orders_status_name' => $name,
                ];
                tep_db_perform(TABLE_ORDERS_STATUS, $data);
            }

            $flags_query = tep_db_query('describe ' . TABLE_ORDERS_STATUS . ' public_flag');
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query('update ' . TABLE_ORDERS_STATUS . ' set public_flag = ' . ((int) $public) . ' and downloads_flag = ' . ((int) $download) . " where orders_status_id = '" . $new_status_id . "'");
            }

            return $new_status_id;
        }
    }

    public function get_new_order_status_id()
    {
        $sql          = 'SELECT max(orders_status_id) AS status_id FROM ' . TABLE_ORDERS_STATUS;
        $status_query = tep_db_query($sql);
        $status       = tep_db_fetch_array($status_query);

        $new_status_id = $status['status_id'] + 1;

        return $new_status_id;
    }

    public function create_tables()
    {
        if (tep_db_num_rows(tep_db_query("show tables like 'g2apay_transactions'")) == 0) {
            $sql = 'CREATE TABLE IF NOT EXISTS `g2apay_transactions` (
                      `g2apay_transactions_id` INT(11) NOT NULL AUTO_INCREMENT,
                      `orders_id` INT(11) DEFAULT NULL,
                      `transaction` VARCHAR(36) DEFAULT NULL,
                      `status` VARCHAR(32) DEFAULT NULL,
                      `data` VARCHAR(32) DEFAULT NULL,
                      `created_at` DATETIME DEFAULT NOW(),
                      `updated_at` DATETIME DEFAULT NULL,
                      PRIMARY KEY (`g2apay_transactions_id`),
                      UNIQUE KEY `orders_id_UNIQUE` (`orders_id`),
                      UNIQUE KEY `transaction_UNIQUE` (`transaction`)
                    )';

            tep_db_query($sql);
        }
    }
}
