<?php

namespace Nganluong\Pay\Model\System\Config;

class Lang implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'vi', 'label' => __('Vietnamese')], ['value' => 'en', 'label' => __('English')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    /*public function toArray()
    {
        return [0 => __('Vietnamese'), 1 => __('English')];
    }  */
}