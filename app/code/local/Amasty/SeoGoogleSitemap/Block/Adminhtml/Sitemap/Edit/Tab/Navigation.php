<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_SeoGoogleSitemap
 */


class Amasty_SeoGoogleSitemap_Block_Adminhtml_Sitemap_Edit_Tab_Navigation extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $this->helper = Mage::helper('amseogooglesitemap');

        $model = Mage::registry('am_sitemap_profile');

        $this->_navigationFields();

        $this->getForm()->setValues($model->getData());

        return parent::_prepareForm();
    }

    private function _navigationFields()
    {
        $fieldset = $this->getForm()->addFieldset('amseogooglesitemap_form_content_navigation', array('legend' => Mage::helper('amseogooglesitemap')->__('Improved Navigation Pages')));
        $fieldset->addField('navigation', 'select', array(
            'label'     => $this->helper->__('Include Improved Navigation Pages'),
            'name'      => 'navigation',
            'title'     => $this->helper->__('Include Improved Navigation Pages'),
            'options'   => $this->helper->getYesNo(),
            'note'      => 'See <a href="//amasty.com/improved-layered-navigation.html">Improved Layered Navigation</a> module.     
                Please fill canonical urls of  navigation pages manually. '
        ));

        $fieldset->addField('navigation_priority', 'text',
            array(
                'label'     => Mage::helper('amseogooglesitemap')->__('Priority'),
                'name'      => 'navigation_priority',
                'note'     => Mage::helper('amseogooglesitemap')->__('0.01-0.99'),
            )
        );

        $fieldset->addField('navigation_frequency', 'select', array(
            'label'     => $this->helper->__('Frequency'),
            'name'      => 'navigation_frequency',
            'title'     => $this->helper->__('Frequency'),
            'options'   => $this->helper->getFrequency()
        ));

    }
}
