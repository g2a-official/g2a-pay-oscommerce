<?php

class g2apay_item
{
    public $_data;

    public function g2apay_item($data)
    {
        $this->_data = $data;
    }

    public function get_id()
    {
        return $this->_data['id'];
    }

    public function get_sku()
    {
        return $this->_data['id'];
    }

    public function get_name()
    {
        return $this->_data['name'];
    }

    public function get_amount()
    {
        $price = $this->_data['price'];
        $qty   = $this->get_qty();

        return g2apay_round($price * $qty);
    }

    public function get_qty()
    {
        return $this->_data['qty'];
    }

    public function get_price()
    {
        return $this->_data['price'];
    }

    public function get_type()
    {
        return isset($this->_data['type']) ? $this->_data['type'] : 'product';
    }
}
