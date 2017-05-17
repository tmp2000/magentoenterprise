<?php


class TBT_Reports_Model_Service_Transfer_Feed_Item
{
    protected $_helper;
    protected $_customer;
    protected $_transfer;

    protected $_translationPhrase = array();
    protected $_translationParameters = array();
    protected $_feedClasses = array();

    /**
     * Set the transfer object of this block
     * @param TBT_Rewards_Model_Transfer $transfer
     * @return $this
     */
    public function setTransfer($transfer)
    {
        $this->_transfer = $transfer;
        return $this;
    }

    /**
     * @return TBT_Rewards_Model_Transfer
     * @throws Exception
     */
    public function getTransfer()
    {
        $transfer = $this->_transfer;
        if (!$transfer || !$transfer->getId()) {
            throw new Exception("No transfer object set on this block");
        }

        return $transfer;
    }

    /**
     * Get the customer object of the transfer on this block
     * @return Mage_Customer_Model_Customer
     * @throws Exception if no transfer set
     */
    public function getCustomer()
    {
        if (!$this->_customer) {
            $customerId = $this->getTransfer()->getCustomerId();
            $this->_customer = Mage::getModel('customer/customer')->load($customerId);
        }

        return $this->_customer;
    }

    /**
     * Get timestamp for the transfer
     * @return mixed
     * @throws Exception
     */
    public function getTransferTimestamp()
    {
        return $this->getTransfer()->getCreationTs();
    }

    /**
     * Clean all data in this model and reuse it.
     * @return $this
     */
    public function clearInstance()
    {
        $this->_customer = null;
        $this->_transfer = null;

        $this->_feedClasses = array();
        $this->resetMessageTranslation();
        return $this;
    }

    /**
     * Returns an array of class names for this feed after generating feed data
     * @return array
     */
    public function getClasses()
    {
        if (empty($this->_feedClasses)) {
            $this->prepareItemData();
        }

        return $this->_feedClasses;
    }

    /**
     * Will return final translated feed by calling __() after generating feed data
     * @return string
     */
    public function getTranslatedMessage()
    {
        if (empty($this->_translationPhrase)) {
            $this->prepareItemData();
        }

        $phraseToTranslate = implode(" ", $this->_translationPhrase);
        $arguments = array_merge(array($phraseToTranslate), $this->_translationParameters);
        return call_user_func_array('__', $arguments);
    }


    /**
     * Similar to calling __(), but will maintain original phrase for a final global translation.
     *
     * Will maintain a list of strings (optionally containing %s placeholders)
     * as well as correlating variables for the placeholders, all for one final translation.
     *
     * @see __()
     * @param string $phrase to be concatenated to final translation phrase
     * @param {*} int\string|null $variable to be passed in as next parameter to final translation
     * @return $this
     */
    protected function appendToMessageTranslation()
    {
        $arguments = func_get_args();
        if (!empty($arguments)) {
            foreach ($arguments as $index => $arg) {
                if ($index == 0) {
                    array_push($this->_translationPhrase, $arg);
                } else {
                    if (!empty($arg)) {
                        array_push($this->_translationParameters, $arg);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Will reset the global translation
     * @return $this
     */
    protected function resetMessageTranslation()
    {
        $this->_translationPhrase = array();
        $this->_translationParameters = array();

        return $this;
    }

    /**
     * Will prepare data for this feed item
     * @return string
     */
    protected function prepareItemData()
    {
        $transfer = $this->getTransfer();
        $transferUrl = $this->getUrl('adminhtml/manage_transfer/edit', array('id' => $transfer->getId()));
        $transferLink = $this->generateLink($transferUrl, $this->getFormattedTransferAmount());
        $this->_feedClasses[] = ($transfer->getQuantity() > 0) ? "positive" : "negative";
        switch ($transfer->getStatus()) {
            case TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED:
                $this->_feedClasses[] = "status-approved";
                break;
            case TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED:
                $this->_feedClasses[] = "status-cancelled";
                break;
            case TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED:
                $this->_feedClasses[] = "status-cancelled";
                break;
            case TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT:
                $this->_feedClasses[] = "status-pending-event";
                break;
            case TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL:
                $this->_feedClasses[] = "status-pending-admin";
                break;
            case TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME:
                $this->_feedClasses[] = "status-pending-time";
                break;
        }

        $customerName = $this->generateCustomerLink($transfer->getCustomerId(), array(
            'no_name'   => "A customer with email %s",
            'not_found' => "A customer"
        ));
        $this->appendToMessageTranslation("%s", $customerName);


        if ($transfer->getQuantity() > 0) {
            /*
             *  Points Earning Messages
             */
            switch ($transfer->getReferenceType()) {
                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_ORDER:
                    $orderLink = $this->generateOrderLink($transfer->getReferenceId());
                    $this->appendToMessageTranslation("earned %s for placing an %s.", $transferLink, $orderLink);
                    $this->_feedClasses[] = "action-order";
                    break;

                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_RATING:
                case TBT_Rewards_Model_Review_Reference::REFERENCE_TYPE_ID:
                    $reviewLink = $this->generateLink(
                        $this->getUrl('adminhtml/catalog_product_review/edit', array('id' => $transfer->getReferenceId())),
                        $this->__('review')
                    );
                    $this->appendToMessageTranslation("earned %s for writing a %s.", $transferLink, $reviewLink);
                    $this->_feedClasses[] = "action-review";
                    $this->_feedClasses[] = "action-rating";
                    break;

                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_POLL:
                    $pollLink = $this->generateLink(
                        $this->getUrl('adminhtml/poll/edit', array('id' => $transfer->getReferenceId())),
                        $this->__('poll')
                    );
                    $this->appendToMessageTranslation("earned %s for participating in a %s.", $transferLink, $pollLink);
                    $this->_feedClasses[] = "action-poll";
                    break;

                case TBT_Rewards_Model_Tag_Reference::REFERENCE_TYPE_ID:
                    $tagLink = $this->generateLink(
                        $this->getUrl('adminhtml/tag/edit', array('tag_id' => $transfer->getReferenceId())),
                        $this->__('tag')
                    );
                    $this->appendToMessageTranslation("earned %s for submitting a %s.", $transferLink, $tagLink);
                    $this->_feedClasses[] = "action-tag";
                    break;

                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_SIGNUP:
                    $this->appendToMessageTranslation("earned %s for signing up.", $transferLink);
                    $this->_feedClasses[] = "action-signup";
                    break;

                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_FROM_FRIEND:
                    $this->appendToMessageTranslation("received %s from", $transferLink);
                    $friendLink = $this->generateCustomerLink($transfer->getReferenceId(), array(
                        'default'   => "their friend, %s.",
                        'no_name'   => "a friend with email %s.",
                        'not_found' => "a friend.",
                    ), false);
                    $this->appendToMessageTranslation($friendLink['text'], $friendLink['link']);
                    $this->_feedClasses[] = "action-friend-transfer";
                    break;

                case TBT_Rewards_Model_Newsletter_Subscription_Reference::REFERENCE_TYPE_ID:
                    $newsletterLink = $this->generateLink(
                        $this->getUrl('adminhtml/newsletter_subscriber/index'),
                        $this->__('newsletter')
                    );
                    $this->appendToMessageTranslation("earned %s for subscribing to the %s.", $transferLink, $newsletterLink);
                    $this->_feedClasses[] = "action-newsletter";
                    break;

                case TBT_Milestone_Model_Rule_Condition::POINTS_GENERIC_REFERENCE_TYPE_ID:
                case TBT_Milestone_Model_Rule_Condition_Orders::POINTS_REFERENCE_TYPE_ID:
                case TBT_Milestone_Model_Rule_Condition_Membership::POINTS_REFERENCE_TYPE_ID:
                case TBT_Milestone_Model_Rule_Condition_Inactivity::POINTS_REFERENCE_TYPE_ID:
                case TBT_Milestone_Model_Rule_Condition_Referrals::POINTS_REFERENCE_TYPE_ID:
                case TBT_Milestone_Model_Rule_Condition_Revenue::POINTS_REFERENCE_TYPE_ID:
                case TBT_Milestone_Model_Rule_Condition_Earned::POINTS_REFERENCE_TYPE_ID:
                    $milestoneLog = Mage::getModel('tbtmilestone/rule_log')->load($transfer->getReferenceId());
                    $milestoneDetails = $milestoneLog->getMilestoneDetails();
                    $milestoneLink = $this->__('milestone');
                    if ($milestoneLog && $milestoneLog->getId() && !empty($milestoneDetails['action']['transfer_id'])) {
                        $milestoneLink = $this->generateLink(
                            $this->getUrl('adminhtml/manage_history/view', array('id' => $milestoneLog->getId())),
                            $this->__('milestone')
                        );
                    }
                    $this->appendToMessageTranslation("earned %s for reaching a %s.", $transferLink, $milestoneLink);
                    $this->_feedClasses[] = "action-milestone";
                    $this->_feedClasses[] = "milestone-{$milestoneLog->getConditionType()}";
                    break;

                default:
                    switch ($transfer->getReasonId()) {
                        case TBT_Rewards_Model_Transfer_Reason_AdminAdjustment::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("was granted %s by a store administrator.", $transferLink);
                            $this->_feedClasses[] = "action-admin-transfer";
                            break;

                        case TBT_Rewards_Model_Birthday_Reason::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("was granted %s for their birthday.", $transferLink);
                            $this->_feedClasses[] = "action-birthday";
                            break;

                        case TBT_RewardsReferral_Model_Transfer_Reason_Signup::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("earned %s because", $transferLink);
                            $friendLink = $this->generateCustomerLink($transfer->getReferenceId(), array(
                                'default'   => "their referral, %s, signed up.",
                                'no_name'   => "their referral signed up with the email %s.",
                                'not_found' => "one of their referrals signed up.",
                            ), false);
                            $this->appendToMessageTranslation($friendLink['text'], $friendLink['link']);
                            $this->_feedClasses[] = "action-referral-signup";
                            break;

                        case TBT_RewardsReferral_Model_Transfer_Reason_Firstorder::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("was granted %s because of", $transferLink);
                            $references = $this->getMultiReferenceIds($transfer->getId());
                            $orderLink = $this->generateOrderLink(
                                $references['referral_order_id'],
                                array('link_label' => '<b>first</b> order')
                            );
                            $referralLink = $this->generateCustomerLink(
                                $references['referral_customer_id'], array(
                                    'default'   => "their referral, %s's",
                                    'no_name'   => "their referral, %s's",
                                    'not_found' => "their referral's ",
                                ), false
                            );
                            $this->appendToMessageTranslation($referralLink['text'], $referralLink['link']);
                            $this->appendToMessageTranslation("%s.", $orderLink);
                            $this->_feedClasses[] = "action-referral-first-order";
                            break;

                        case TBT_RewardsReferral_Model_Transfer_Reason_Order::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("was granted %s because ", $transferLink);
                            $references = $this->getMultiReferenceIds($transfer->getId());
                            $orderLink = $this->generateOrderLink($references['referral_order_id']);
                            $referralLink = $this->generateCustomerLink(
                                $references['referral_customer_id'], array(
                                    'default'   => "their referral, %s,",
                                    'no_name'   => "their referral, %s,",
                                    'not_found' => "their referral, ",
                                ), false
                            );
                            $this->appendToMessageTranslation($referralLink['text'], $referralLink['link']);
                            $this->appendToMessageTranslation("placed an %s.", $orderLink);
                            $this->_feedClasses[] = "action-referral-order";
                            break;

                        case TBT_RewardsReferral_Model_Transfer_Reason_Guestorder::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("was granted %s because ", $transferLink);
                            $references = $this->getMultiReferenceIds($transfer->getId());
                            $orderLink = $this->generateOrderLink($references['referral_order_id']);
                            $referralLink = $this->generateCustomerLink(
                                $references['referral_customer_id'], array(
                                    'default'   => "their referral, %s,",
                                    'no_name'   => "their referral, %s,",
                                    'not_found' => "a guest they referred to the store,",
                                ), false
                            );
                            $this->appendToMessageTranslation($referralLink['text'], $referralLink['link']);
                            $this->appendToMessageTranslation("placed an %s.", $orderLink);
                            $this->_feedClasses[] = "action-referral-guest-order";
                            break;

                        case TBT_RewardsSocial2_Model_Reason_FacebookLike::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_FacebookShare::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_GooglePlusOne::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_PinterestPin::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_PurchaseShareFacebook::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_PurchaseShareTwitter::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_ReferralShare::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_TwitterFollow::REASON_TYPE_ID:
                        case TBT_RewardsSocial2_Model_Reason_TwitterTweet::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("was awarded %s for ", $transferLink);
                            $this->_feedClasses[] = "action-social";
                            $socialAction = Mage::getModel('rewardssocial2/action')
                                ->load($transfer->getReferenceId());
                            if ($socialAction && $socialAction->getId()) {
                                $url = $socialAction->getExtra();
                                $link = $this->generateLink($url, $this->__('page'));
                                $this->_feedClasses[] = "social-{$socialAction->getAction()}";
                                switch ($socialAction->getAction()) {
                                    case 'facebook_like':
                                        $this->appendToMessageTranslation("liking a %s on Facebook.", $link);
                                        break;

                                    case 'facebook_share':
                                        $this->appendToMessageTranslation("sharing a %s on Facebook.", $link);
                                        break;

                                    case 'twitter_tweet':
                                        $this->appendToMessageTranslation("tweeting about a %s on Twitter.", $link);
                                        break;

                                    case 'google_plusone':
                                        $this->appendToMessageTranslation("a Google +1 on a %s.", $link);
                                        break;

                                    case 'pinterest_pin':
                                        $this->appendToMessageTranslation("pinning a %s on Pinterest.", $link);
                                        break;

                                    case 'twitter_follow':
                                        $twitterHandle = $socialAction->getExtra() ?:
                                            Mage::helper('rewardssocial2')->getTwitterUsername();
                                        $link = $twitterHandle ?
                                            $this->generateLink("http://twitter.com/{$twitterHandle}", "@{$twitterHandle}") : "";
                                        $this->appendToMessageTranslation("following %s on Twitter.", $link);
                                        break;

                                    case 'facebook_share_purchase':
                                        $network = "Facebook";
                                        // no break

                                    case 'twitter_tweet_purchase':
                                        if (empty($network)) $network = "Twitter";
                                        $extras = json_decode($socialAction->getExtra(), true);
                                        $productId = !empty($extras['product']) ? $extras['product'] : null;
                                        $orderId = !empty($extras['order']) ? $extras['order'] : null;
                                        $orderLink = $this->generateOrderLink($orderId, array(
                                            'link_label' => "purchased",
                                            'not_found' => "purchased",
                                        ), true, true);
                                        $product = Mage::getModel('catalog/product')->load($productId);
                                        if ($product && $product->getId()) {
                                            $productLink = $this->generateLink($product->getProductUrl(), $this->__('product'));
                                        } else {
                                            $productLink = "product";
                                        }
                                        $this->appendToMessageTranslation("sharing a %s %s on {$network}.", $orderLink, $productLink);
                                        break;

                                    case 'facebook_share_referral':
                                        $this->appendToMessageTranslation("sharing their referral link on Facebook.");
                                        break;

                                    case 'twitter_tweet_referral':
                                        $this->appendToMessageTranslation("sharing their referral link on Twitter.");
                                        break;

                                    default:
                                        $this->appendToMessageTranslation("completing a social action.");
                                        break;

                                }
                            }
                            break;

                        default:
                            $this->appendToMessageTranslation("gained %s.", $transferLink);
                    }
            }

        } else {
            /*
             *  Points Spending Messages
             */
            switch ($transfer->getReferenceType()) {
                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_ORDER:
                    $orderLink = $this->generateOrderLink($transfer->getReferenceId());
                    $this->appendToMessageTranslation("spent %s on an %s.", $transferLink, $orderLink);
                    $this->_feedClasses[] = "action-order";
                    break;

                case TBT_Rewards_Model_Transfer_Reference::REFERENCE_TO_FRIEND:
                    $this->appendToMessageTranslation("transferred %s to", $transferLink);
                    $friendLink = $this->generateCustomerLink($transfer->getReferenceId(), array(
                        'default'   => "their friend, %s.",
                        'no_name'   => "a friend with email %s.",
                        'not_found' => "a friend.",
                    ), false);
                    $this->appendToMessageTranslation($friendLink['text'], $friendLink['link']);
                    $this->_feedClasses[] = "action-friend-transfer";
                    break;

                default:
                    $this->resetMessageTranslation();
                    $customerName = $this->generateCustomerLink($transfer->getCustomerId(), array(
                        'no_name'   => "a customer with email %s",
                        'not_found' => "a customer"
                    ));

                    switch ($transfer->getReasonId()) {
                        case TBT_Rewards_Model_Transfer_Reason_AdminAdjustment::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("An administrator deducted %s from %s.", $transferLink, $customerName);
                            $this->_feedClasses[] = "action-admin-transfer";
                            break;

                        case TBT_Rewards_Model_Transfer_Reason_SystemAdjustment::REASON_TYPE_ID:
                        case TBT_Rewards_Model_Transfer_Reason_PointsExpiry::REASON_TYPE_ID:
                            $this->appendToMessageTranslation("All %s belonging to %s have now expired.", $transferLink, $customerName);
                            $this->_feedClasses[] = "action-expiry";
                            break;

                        case TBT_Rewards_Model_Transfer_Reason_SystemRevoked::REASON_TYPE_ID:
                        default:
                            $auxiliaryVerb = ((int) $transfer->getQuantity() == 1)? "was" : "were";
                            $this->appendToMessageTranslation("%s {$auxiliaryVerb} deducted from %s's balance.", $transferLink, $customerName);
                            break;
                    }
            }
        }

        return $this;
    }



    /**
     * Will produce a produce a string with points currency caption, label and absolute transfer amount
     * @return string (eg. "200 Gold Points")
     * @throws Exception
     */
    protected function getFormattedTransferAmount()
    {
        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        $qty = abs($this->getTransfer()->getQuantity());

        return Mage::helper('rewards')->getPointsString(array ($currencyId => $qty));
    }


    /**
     * Will generate a text along with admin link for customer account of specified customer
     *
     * @param int|Mage_Customer_Model_Customer $customer
     * @param array $labels (optional), consists of 3 strings with following (optional) indexes,
     *  'not_found':    text to render as plain-text, if customer not found (default: "a customer")
     *  'no_name':      text with %s placeholder for email address,
     *                      if customer has no name (default: "customer with email %s")
     *  'default':      text with %s placeholder for customer name,
     *                      if customer has a name (default: "%s")
     * @param $translate (default: true). Will translate text before returning a single string.
     * @return string|array translated string or array containing two strings with following indexes,
     *  'text'          text with "%s" placeholder, appropriate for translations
     *  'link'          html string containing anchor tag and link to customer account
     */
    protected function generateCustomerLink($customer, $labels = array(), $translate = true)
    {
        if (empty($labels['not_found']))    { $labels['not_found'] = "a customer"; }
        if (empty($labels['no_name']))      { $labels['no_name'] = "customer with email %s"; }
        if (empty($labels['default']))      { $labels['default'] = "%s"; }

        if (is_int($customer) || is_string($customer)) {
            $customerId = $customer;
            $customer = Mage::getModel('customer/customer')->load($customerId);
        }

        $customerId = $customer->getId();
        if ($customer && $customerId) {
            $customerName = ucwords(trim($customer->getName()));
            $customerEmail = $customer->getEmail();
            $customerUrl = $this->getUrl('adminhtml/customer/edit', array('id' => $customer->getId()));
            $output = array(
                'text' => empty($customerName)? $labels['no_name'] : $labels['default'],
                'link' => $this->generateLink($customerUrl, (empty($customerName)? $customerEmail : $customerName))
            );
        }
        $output = !empty($output)? $output : array('text'  => $labels['not_found'], 'link'  => '');

        if ($translate) {
            return $this->__($output['text'], $output['link']);

        } else {
            return $output;
        }
    }

    /**
     * Will generate a text along with admin link to view specified order
     * @param int|string|Varien_Object $order. OrderId or object to extract id from.
     * @param array $labels (optional), consists of 3 strings with following (optional) indexes,
     *  'link_label':   text to render as link label (default: "order")
     *  'not_found':    text to render as plain-text if orderId is invalid (default: "order")
     *  'default':      text with %s placeholder for generated link to go in (default: "%s")
     * @param bool $translate (default: true). Will translate text before returning a single string.
     * @param bool $useIncrementNumber (default: false). Will assume first parameter is an order increment number.
     * @return string|array translated string or array containing two strings with following indexes,
     *  'text'          text with "%s" placeholder, appropriate for translations
     *  'link'          html string containing anchor tag and link to customer account
     */
    protected function generateOrderLink($order, $labels = array(), $translate = true, $useIncrementNumber = false)
    {
        if (empty($labels['link_label']))   { $labels['link_label'] = "order"; }
        if (empty($labels['not_found']))    { $labels['not_found'] = "order"; }
        if (empty($labels['default']))      { $labels['default'] = "%s"; }


        if ($useIncrementNumber) {
            $incrementId = $order;
            $order = Mage::getModel('sales/order')->load($incrementId, 'increment_id');
        }

        $orderId = ($order instanceof Varien_Object) ? $order->getId() : $order;
        if ($orderId) {
            $url = $this->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId));
            $label = $translate? $this->__($labels['link_label']) : $labels['link_label'];
            $output = array(
                'text'  => $labels['default'],
                'link'  => $this->generateLink($url, $label)
            );
        } else {
            $output = array(
                'text'  => $labels['not_found'],
                'link'  => ''
            );
        }

        if ($translate) {
            return $this->__($output['text'], $output['link']);

        } else {
            return $output;
        }
    }

    /**
     * Will produce an HTML anchor tag to open $url in new window/tab
     * @param $url
     * @param $label
     * @param boolean $noWrap (optional. default: true)
     * @return string
     */
    protected function generateLink($url, $label, $noWrap = true)
    {
        $nonBreakingLabel = str_replace(' ', '&nbsp;', $label);
        return "<a href=\"{$url}\" target=\"_blank\">"
                    .($noWrap? $nonBreakingLabel: $label)
                ."</a>";
    }

    /**
     * Given a transfer which has multiple references, will return a named array of all recognized references
     * @param int|string $transferId of transfer to look up
     * @return array of ids for all recognized references of the original transfer.
     *  Supported indexes are,
     *      'referral_customer_id': ID of referral customer responsible for the transfer
     *      'referral_order_id':    ID of referral order responsible for the transfer
     */
    protected function getMultiReferenceIds($transferId)
    {
        $referenceIds = array();
        $allReferences = $this->getTransferFeedService()->getTransferReferences($transferId);
        foreach ($allReferences as $reference) {
            switch ($reference->getReferenceType()) {
                case TBT_RewardsReferral_Model_Transfer_Reference_Referral::REFERENCE_TYPE_ID:
                    $referenceIds['referral_customer_id'] = $reference->getReferenceId();
                    break;
                case TBT_RewardsReferral_Model_Transfer_Reference_Referral_Order::REFERENCE_TYPE_ID:
                    $referenceIds['referral_order_id'] = $reference->getReferenceId();
                    break;
            }
        }

        return $referenceIds;
    }

    /**
     * Get Admin Url
     * @see Mage_Adminhtml_Helper_Data::getUrl
     * @param string $route
     * @param array $params
     * @return mixed
     */
    protected function getUrl($route='', $params=array())
    {
        return Mage::helper("adminhtml")->getUrl($route, $params);
    }

    /**
     * Proxy to the helper's translator
     * @return string
     */
    protected function __()
    {
        $arguments = func_get_args();
        return call_user_func_array(array($this->getHelper(), "__"), $arguments);
    }

    /**
     * @return TBT_Reports_Model_Service_Transfer_Feed
     */
    protected function getTransferFeedService()
    {
        return Mage::getModel('tbtreports/service_transfer_feed');
    }

    /**
     * Returns same single instance of the tbtreports helper
     * @return TBT_Reports_Helper_Data
     */
    protected function getHelper()
    {
        if (empty($this->_helper)) {
            $this->_helper = Mage::helper('tbtreports');
        }

        return $this->_helper;
    }
}