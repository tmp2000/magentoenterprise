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
 * @category    Mage
 * @package     Mage_Newsletter
 * @copyright Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license http://www.magento.com/license/enterprise-edition
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$subscriberTable = $installer->getTable('newsletter/subscriber');

$select = $installer->getConnection()->select()
    ->from(array('main_table' => $subscriberTable))
    ->join(
        array('customer' => $installer->getTable('customer/entity')),
        'main_table.customer_id = customer.entity_id',
        array('website_id')
    )
    ->where('customer.website_id = 0');

$installer->getConnection()->query(
    $installer->getConnection()->deleteFromSelect($select, 'main_table')
);
