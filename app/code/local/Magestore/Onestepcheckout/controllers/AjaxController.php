<?php

class Magestore_Onestepcheckout_AjaxController extends Mage_Core_Controller_Front_Action {

    public function add_giftwrapAction() {
        $remove = $this->getRequest()->getPost('remove', false);
        $session = Mage::getSingleton('checkout/session');
        if (!$remove) {
            $session->setData('onestepcheckout_giftwrap', 1);
        } else {
            $session->unsetData('onestepcheckout_giftwrap');
            $session->unsetData('onestepcheckout_giftwrap_amount');
        }
        $this->_addOnestepcheckoutHandle(true);
        $result = $this->_getBlockResults($result, true);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function forgotPasswordAction() {
        $email = $this->getRequest()->getPost('email', false);

        if (!Zend_Validate::is($email, 'EmailAddress')) {
            $result = array('success' => false);
        } else {
            $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
            if ($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);
                    $customer->sendPasswordReminderEmail();
                    $result = array('success' => true);
                } catch (Exception $e) {
                    $result = array('success' => false, 'error' => $e->getMessage());
                }
            } else {
                $result = array('success' => false, 'error' => 'notfound');
            }
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    public function loginAction() {
        $username = $this->getRequest()->getPost('onestepcheckout_username', false);
        $password = $this->getRequest()->getPost('onestepcheckout_password', false);
        $session = Mage::getSingleton('customer/session');
        $result = array('success' => false);
        if ($username && $password) {
            try {
                $session->login($username, $password);
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }
            if (!isset($result['error'])) {
                $result['success'] = true;
            }
        } else {
            $result['error'] = $this->__('Please enter a username and password.');
        }
        /* Start: Modified by Daniel -01042015- Reload data after login */
        if (isset($result['error']))
            $session->setData('login_result_error', $result['error']);
        $this->getResponse()->setBody(Zend_Json::encode($result));
        /* End: Modified by Daniel -01042015- Reload data after login */
    }

    /* Create new account on checkout page - Leo 08042015 */

    public function createAccAction() {
        $session = Mage::getSingleton('customer/session');
        $firstName = $this->getRequest()->getPost('onestepcheckout_firstname', false);
        $lastName = $this->getRequest()->getPost('onestepcheckout_lastname', false);
        $pass = $this->getRequest()->getPost('onestepcheckout_register_password', false);
        $passConfirm = $this->getRequest()->getPost('onestepcheckout_confirmation_password', false);
        $email = $this->getRequest()->getPost('onestepcheckout_register_username', false);

        $customer = Mage::getModel('customer/customer')
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setEmail($email)
                ->setPassword($pass)
                ->setConfirmation($passConfirm);

        try {
            $customer->save();
            Mage::dispatchEvent('customer_register_success', array('customer' => $customer));
            $result = array('success' => true);
            $session->setCustomerAsLoggedIn($customer);
        } catch (Exception $e) {
            $result = array('success' => false, 'error' => $e->getMessage());
        }
        if (isset($result['error']))
            $session->setData('register_result_error', $result['error']);
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
    /* Create new account on checkout page - Leo 08042015 */
    
    private function _getOnestepcheckoutHandle($action, $style = null, $layout = null) {
        if (!$style) {
            $storeId = Mage::app()->getStore()->getStoreId();
            $style = Mage::getStoreConfig('onestepcheckout/general/page_style', $storeId);
            $layout = Mage::getStoreConfig('onestepcheckout/general/page_layout', $storeId);
        }
        return $action . '_' . $style . '_' . $layout;
    }

    private function _addOnestepcheckoutHandle($hasShipping = false, $otherHandle = null) {
        $storeId = Mage::app()->getStore()->getStoreId();
        $style = Mage::getStoreConfig('onestepcheckout/general/page_style', $storeId);
        $layout = Mage::getStoreConfig('onestepcheckout/general/page_layout', $storeId);
        $update = $this->getLayout()->getUpdate();
        if ($hasShipping)
            $update->addHandle($this->_getOnestepcheckoutHandle('onestepcheckout_shipping_payment_review', $style, $layout));
        else
            $update->addHandle($this->_getOnestepcheckoutHandle('onestepcheckout_payment_review', $style, $layout));
        if ($otherHandle)
            foreach ($otherHandle as $handle) {
                $update->addHandle($this->_getOnestepcheckoutHandle($handle, $style, $layout));
            }
        $this->loadLayoutUpdates();
        $this->generateLayoutXml();
        $this->generateLayoutBlocks();
        $this->_isLayoutLoaded = true;
    }
    
    private function _getBlockResults($result = null, $isShipping = false){
        if(!$result) $result = array();
        $payment_method_html = $this->getLayout()->getBlock('onestepcheckout_payment_method')->toHtml();
        $review_total_html = $this->getLayout()->getBlock('review_info')->toHtml();
        $result['payment_method'] = $payment_method_html;
        $result['review'] = $review_total_html;
        if($isShipping){
            $shipping_method_html = $this->getLayout()->getBlock('onestepcheckout_shipping_method')->toHtml();
            $result['shipping_method'] = $shipping_method_html;
        }
        return $result;
    }
}
