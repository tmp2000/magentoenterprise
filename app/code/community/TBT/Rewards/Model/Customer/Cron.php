<?php

/**
 * Customer Cron Model that handles points notification emails
 *
 */
class TBT_Rewards_Model_Customer_Cron extends Varien_Object
{
    const XML_PATH_POINT_SUMMARY_EMAIL_TEMPLATE             = 'rewards/pointSummaryEmails/point_summary_email_template';
    const XML_PATH_POINT_SEND_NO_POINT                      = 'rewards/pointSummaryEmails/send_email_with_no_points';
    const XML_PATH_POINT_SEND_CUSTOMER_GROUP                = 'rewards/pointSummaryEmails/customer_group';
    const XML_PATH_POINT_SEND_EMAILS                        = 'rewards/pointSummaryEmails/allow_points_summary_email';
    const XML_PATH_POINT_SUMMARY_LAST_EXECUTION_TIME        = 'rewards/pointSummaryEmails/last_execution_time';

    /**
     * Customer Cron Model that handles points notification emails
     *
     */
    public function sendPointNotifications()
    {
        if (!Mage::getStoreConfigFlag(self::XML_PATH_POINT_SEND_EMAILS)) {
            return $this;
        }
         
        $now = time();
        $lastExecuted = intval(Mage::getStoreConfig(self::XML_PATH_POINT_SUMMARY_LAST_EXECUTION_TIME));
        if (!empty($lastExecuted) && $now - $lastExecuted < 24 * 60 * 60) {
            return $this;             // Already executed less than 24 hours ago, so skip
            
        } else {
            Mage::getConfig()->saveConfig(self::XML_PATH_POINT_SUMMARY_LAST_EXECUTION_TIME, $now);
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();
        }
        
        //get all customers that need notification
        $customerCollection = Mage::getModel('rewards/customer')
                ->getCollection()
                ->addNameToSelect()
                ->addAttributeToFilter('rewards_points_notification', array (
                    array('eq' => 1),
                    array('null' => true)
                ), 'left');

        $customerCollection->setPageSize(100);
        $pages = $customerCollection->getLastPageNumber();
        $currentPage = 1;
        
        do {
            $customerCollection->setCurPage($currentPage);
            $customerCollection->load();
                                
            foreach ($customerCollection as $customer) {
                $customer = Mage::getModel('rewards/customer')->load($customer->getId());
    
                if($this->isValidSendPointNotifications($customer)) {
                     $this->_sendEmail($customer);
                }
            }
            
            //clear collection and free memory
            $currentPage++;
            $customerCollection->clear();
        } while ($currentPage <= $pages);
             
        return $this;
    }

    /**
     * Send out Point Summary Notification Email
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean send successful?
     */
    private function _sendEmail($customer)
    {
        $template = Mage::getStoreConfig(self::XML_PATH_POINT_SUMMARY_EMAIL_TEMPLATE, $customer->getStoreId());

        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        /* @var $email Mage_Core_Model_Email_Template */
        $email = Mage::getModel('core/email_template');
        $sender = array(
            'name' => strip_tags(Mage::helper('rewards/expiry')->getSenderName($customer->getStoreId())),
            'email' => strip_tags(Mage::helper('rewards/expiry')->getSenderEmail($customer->getStoreId()))
        );
        $email->setDesignConfig(array(
            'area' => 'frontend',
            'store' => $customer->getStoreId())
        );

        $unsubscribeUrl = Mage::getUrl('rewards/customer_notifications/unsubscribe/') . 'customer/' . urlencode(base64_encode($customer->getId()));

        $vars = array(
            'customer_name' => $customer->getName(),
            'customer_email' => $customer->getEmail(),
            'store_name' => $customer->getStore()->getFrontendName(),
            'points_balance' => (string) $customer->getPointsSummary(),
            'pending_points' => (string) $customer->getPendingPointsSummary(),
            'has_pending_points' => $customer->hasPendingPoints(),
            'unsubscribe_url' => $unsubscribeUrl,
            'unsubscribe_all_url' => Mage::helper('rewards/email')->getUnsubscribeUrl($customer)
        );
        $email->sendTransactional($template, $sender, $customer->getEmail(), $customer->getName(), $vars);
        $translate->setTranslateInline(true);

        return $email->getSentSuccess();
    }

    /**
     * Check customer is valid to send point notifications
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean
     */
    public function isValidSendPointNotifications(TBT_Rewards_Model_Customer $customer)
    {
        return ( $this->isValidCustomerGroup($customer)
                && $this->isValidSendNoPoint($customer) );
    }

    /**
     * Check if current customer group is valid to receive points summary notification email
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean
     */
    public function isValidCustomerGroup(TBT_Rewards_Model_Customer $customer)
    {
        $allowGroups = Mage::getStoreConfig(self::XML_PATH_POINT_SEND_CUSTOMER_GROUP, $customer->getStoreId());

        $allowGroupsArr = explode(",", $allowGroups);
        $isCusGrouoAvailable = in_array($customer->getGroupId(), $allowGroupsArr);

        return $isCusGrouoAvailable;
    }

    /**
     * Checks if customer should receive points summary notification email based
     * on config option 'Send Email To Users With No Points'. If both, customer's
     * points summary and pending points are zero and option set to NO, won't
     * receive notification.
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean
     */
    public function isValidSendNoPoint(TBT_Rewards_Model_Customer $customer)
    {
        $noPointStr = Mage::helper ( 'rewards' )->getPointsString (array());// get no points string

        if (!Mage::getStoreConfigFlag(self::XML_PATH_POINT_SEND_NO_POINT, $customer->getStoreId())
            && $customer->getPointsSummary() == $noPointStr
            && $customer->getPendingPointsSummary() == $noPointStr
        ) {
            return false;
        }

        return true;
    }
}
