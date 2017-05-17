<?php

/**
 * Product:       Xtento_ProductExport (1.7.6)
 * ID:            dIaxXi5+TGYgezAgBRd+u1PmprrlxVXXQYb4E6yGO2Y=
 * Packaged:      2016-04-05T21:25:32+00:00
 * Last Modified: 2013-02-10T18:58:54+01:00
 * File:          app/code/local/Xtento/ProductExport/Block/Adminhtml/Destination/Edit/Tab/Type/Email.php
 * Copyright:     Copyright (c) 2016 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_ProductExport_Block_Adminhtml_Destination_Edit_Tab_Type_Email
{
    // E-Mail Configuration
    public function getFields($form)
    {
        $model = Mage::registry('product_export_destination');
        // Set default values
        if (!$model->getId()) {
            $model->setEmailAttachFiles(1);
        }

        $fieldset = $form->addFieldset('config_fieldset', array(
            'legend' => Mage::helper('xtento_productexport')->__('E-Mail Export Configuration'),
        ));

        $fieldset->addField('email_sender', 'text', array(
            'label' => Mage::helper('xtento_productexport')->__('E-Mail From Address'),
            'name' => 'email_sender',
            'note' => Mage::helper('xtento_productexport')->__('Enter the email address that should be set as the sender of the email. '),
            'required' => true
        ));
        $fieldset->addField('email_recipient', 'text', array(
            'label' => Mage::helper('xtento_productexport')->__('E-Mail Recipient Address'),
            'name' => 'email_recipient',
            'note' => Mage::helper('xtento_productexport')->__('Enter the email address where exports should be sent to. Separate multiple emails using a comma.'),
            'required' => true
        ));
        $fieldset->addField('email_subject', 'text', array(
            'label' => Mage::helper('xtento_productexport')->__('E-Mail Subject'),
            'name' => 'email_subject',
            'note' => Mage::helper('xtento_productexport')->__('Subject of email. Available variables: %d%, %m%, %y%, %Y%, %h%, %i%, %s%, %exportid%'),
        ));
        $fieldset->addField('email_body', 'textarea', array(
            'label' => Mage::helper('xtento_productexport')->__('E-Mail Text'),
            'name' => 'email_body',
            'note' => Mage::helper('xtento_productexport')->__('Email text (body text). Available variables: %d%, %m%, %y%, %Y%, %h%, %i%, %s%, %exportid%, %content% (%content% contains the data generated by the export)'),
        ));
        $fieldset->addField('email_attach_files', 'select', array(
            'label' => Mage::helper('xtento_productexport')->__('Attach exported files to email'),
            'name' => 'email_attach_files',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note' => Mage::helper('xtento_productexport')->__('Should exported files be attached to the email sent?'),
        ));
    }
}