<?php
/**
 * Magmodules.eu - http://www.magmodules.eu - info@magmodules.eu
 * =============================================================
 * NOTICE OF LICENSE [Single domain license]
 * This source file is subject to the EULA that is
 * available through the world-wide-web at:
 * http://www.magmodules.eu/license-agreement/
 * =============================================================
 * @category    Magmodules
 * @package     Magmodules_Snippets
 * @author      Magmodules <info@magmodules.eu>
 * @copyright   Copyright (c) 2016 (http://www.magmodules.eu)
 * @license     http://www.magmodules.eu/license-agreement/  
 * =============================================================
 */
 
class Magmodules_Snippets_Block_Product_Json extends Mage_Core_Block_Template {
	
    protected function _construct() 
    {
        parent::_construct();	      			
		if($this->getSnippetsEnabled()) {
			$type = Mage::getStoreConfig('snippets/products/type');	
			if($type == 'json') {
				$storeId = Mage::app()->getStore()->getStoreId();
				//if(is_object(Mage::registry('current_product'))) {
					//$this->addData(array(
					//	'cache_lifetime' => 7200,
					//	'cache_tags' => array(Mage_Catalog_Model_Category::CACHE_TAG, Magmodules_Snippets_Model_Snippets::CACHE_TAG),
				//		'cache_key' => $storeId . 'snippets-json-p-' . Mage::registry('current_product')->getId(),        
					//));
				//}
				$this->setTemplate('magmodules/snippets/catalog/product/json.phtml');	    			
			}
		}
    }
    
    public function getJsonProductSnippets() 
    {
        return $this->helper('snippets')->getJsonProductSnippets();
    }	

    public function getSnippetsEnabled() 
    {
        return $this->helper('snippets')->getSnippetsEnabled('product');
    }	
		
}