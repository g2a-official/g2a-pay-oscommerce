<?php

require_once dirname(__FILE__) . '/config.php';

class g2apay_log
{
    public $_config;

    public function g2apay_log()
    {
        $this->_config = new g2apay_config();
    }

    public function error($message, $data = [])
    {
        if ($this->_config->enable_log()) {
            $subject = MODULE_PAYMENT_G2APAY_LOG_TITLE;
            $body    = MODULE_PAYMENT_G2APAY_LOG_MESSAGE . ":\n" . $message . "\n\n";
            $body .= MODULE_PAYMENT_G2APAY_LOG_TIME . ":\n" . date('Y-m-d H:i:s') . "\n\n";
            $body .= MODULE_PAYMENT_G2APAY_LOG_IP . ":\n" . tep_get_ip_address() . "\n\n";
            $body .= MODULE_PAYMENT_G2APAY_LOG_DATA . ":\n" . print_r($data, true) . "\n\n";
            $email = $this->_config->get_log_email();

            tep_mail('', $email, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
    }
}
