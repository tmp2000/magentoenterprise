<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition End User License Agreement
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magento.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Enterprise
 * @package     Enterprise_Rma
 * @copyright Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license http://www.magento.com/license/enterprise-edition
 */

class Enterprise_Rma_Block_Adminhtml_Rma_Edit_Tab_General_Shipping_Methods extends Mage_Core_Block_Template
{
    public function _construct()
    {
        parent::_construct();
        $this->setShippingMethods(Mage::registry('current_rma')->getShippingMethods());
    }

    public function getShippingPrice($price)
    {
        return Mage::registry('current_rma')
            ->getStore()
            ->convertPrice(
                Mage::helper('tax')->getShippingPrice(
                    $price
                ),
                true,
                false
            )
        ;
    }

    public function jsonData($method)
    {
        $data = array();
        $data['CarrierTitle']   = $method->getCarrierTitle();
        $data['MethodTitle']    = $method->getMethodTitle();
        $data['Price']          = $this->getShippingPrice($method->getPrice());
        $data['PriceOriginal']  = $method->getPrice();
        $data['Code']           = $method->getCode();

        return Mage::helper('core')->jsonEncode($data);
    }
}
