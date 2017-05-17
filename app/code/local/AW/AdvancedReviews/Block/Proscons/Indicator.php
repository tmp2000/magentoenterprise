<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento enterprise edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_AdvancedReviews
 * @version    2.3.9
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_AdvancedReviews_Block_Proscons_Indicator extends Mage_Core_Block_Template
{
    protected $_reviewId;
    protected $_prosCollection = array();
    protected $_consCollection = array();

    /**
     * Get common collection with all filters exclude TYPE filter
     */
    protected function _getCommonCollection()
    {
        return Mage::getModel('advancedreviews/proscons')->getCollection()
            ->setStatusFilter()
            ->setReviewFilter($this->_reviewId)
            ->setStoreFilter(Mage::app()->getStore()->getId())
        ;
    }

    public function getProsCollection()
    {
        if (isset($this->_prosCollection[$this->_reviewId])) {
            return $this->_prosCollection[$this->_reviewId];
        } else {
            return $this->_prosCollection[$this->_reviewId] = $this->_getCommonCollection()->setProsFilter();
        }
    }

    public function getConsCollection()
    {
        if (isset($this->_consCollection[$this->_reviewId])) {
            return $this->_consCollection[$this->_reviewId];
        } else {
            return $this->_consCollection[$this->_reviewId] = $this->_getCommonCollection()->setConsFilter();
        }
    }

    public function setReviewId($reviewId)
    {
        $this->_reviewId = $reviewId;
        return $this;
    }

    public function getReviewId()
    {
        return $this->_reviewId;
    }

    public function canShow()
    {
        return Mage::helper('advancedreviews')->confShowProscons()
        && (count($this->getProsCollection()) || count($this->getConsCollection()));
    }

    public function showPros()
    {
        return count($this->getProsCollection());
    }

    public function showCons()
    {
        return count($this->getConsCollection());
    }
}