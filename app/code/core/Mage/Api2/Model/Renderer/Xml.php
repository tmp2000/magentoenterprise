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
 * @package     Mage_Api2
 * @copyright Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license http://www.magento.com/license/enterprise-edition
 */

/**
 * Webservice API2 renderer of XML type model
 *
 * @category   Mage
 * @package    Mage_Api2
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Api2_Model_Renderer_Xml implements Mage_Api2_Model_Renderer_Interface
{
    /**
     * Adapter mime type
     */
    const MIME_TYPE = 'application/xml';

    /**
     * Default name for item of non-associative array
     */
    const ARRAY_NON_ASSOC_ITEM_NAME = 'data_item';

    /**
     * Chars for replacement in the tag name
     *
     * @var array
     */
    protected $_replacementInTagName = array(
        '!' => '', '"' => '', '#' => '', '$' => '', '%' => '', '&' => '', '\'' => '',
        '(' => '', ')' => '', '*' => '', '+' => '', ',' => '', '/' => '', ';' => '',
        '<' => '', '=' => '', '>' => '', '?' => '', '@' => '', '[' => '', '\\' => '',
        ']' => '', '^' => '', '`' => '', '{' => '', '|' => '', '}' => '', '~' => '',
        ' ' => '_', ':' => '_'
    );

    /**
     * Chars for replacement in the tag value
     *
     * @var array
     */
    protected $_replacementInTagValue = array(
        '&' => '&amp;' // replace "&" with HTML entity, because by default not replaced
    );

    /**
     * Protected pattern for check chars in the begin of tag name
     *
     * @var string
     */
    protected $_protectedTagNamePattern = '/^[0-9,.-]/';

    /**
     * Convert Array to XML
     *
     * @param mixed $data
     * @return string
     */
    public function render($data)
    {
        /* @var $writer Mage_Api2_Model_Renderer_Xml_Writer */
        $writer = Mage::getModel('api2/renderer_xml_writer', array(
            'config' => new Zend_Config($this->_prepareData($data, true))
        ));
        return $writer->render();
    }

    /**
     * Prepare convert data
     *
     * @param array|Varien_Object $data
     * @param bool $root
     * @return array
     * @throws Exception
     */
    protected function _prepareData($data, $root = false)
    {
        if (!is_array($data) && !is_object($data)) {
            if ($root) {
                $data = array($data);
            } else {
                throw new Exception('Prepare data must be an object or an array.');
            }
        }
        $data = $data instanceof Varien_Object ? $data->toArray() : (array)$data;
        $isAssoc = !preg_match('/^\d+$/', implode(array_keys($data), ''));

        $preparedData = array();
        foreach ($data as $key => $value) {
            $value = is_array($value) || is_object($value) ? $this->_prepareData($value) : $this->_prepareValue($value);
            if ($isAssoc) {
                $preparedData[$this->_prepareKey($key)] = $value;
            } else {
                $preparedData[self::ARRAY_NON_ASSOC_ITEM_NAME][] = $value;
            }
        }
        return $preparedData;
    }

    /**
     * Prepare value
     *
     * @param string $value
     * @return string
     */
    protected function _prepareValue($value)
    {
        return str_replace(
            array_keys($this->_replacementInTagValue),
            array_values($this->_replacementInTagValue),
            $value
        );
    }

    /**
     * Prepare key and replace unavailable chars
     *
     * @param string $key
     * @return string
     */
    protected function _prepareKey($key)
    {
        $key = str_replace(array_keys($this->_replacementInTagName), array_values($this->_replacementInTagName), $key);
        $key = trim($key, '_');
        if (preg_match($this->_protectedTagNamePattern, $key)) {
            $key = self::ARRAY_NON_ASSOC_ITEM_NAME . '_' . $key;
        }
        return $key;
    }

    /**
     * Get MIME type generated by renderer
     *
     * @return string
     */
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
}
