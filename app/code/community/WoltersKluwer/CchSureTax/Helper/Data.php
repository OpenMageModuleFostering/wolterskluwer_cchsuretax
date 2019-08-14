<?php
/*
 * Required or extension will not install
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
class WoltersKluwer_CchSureTax_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns customer exemption info
     * customer exemption can inherit from customer group if
     * there is no exemption for the customer, but the group
     * has exemptions.
     *
     * @param  int $customerId
     * @param  int $suretaxCustomerId
     * @return array with following:
     *       'salesTypeCode' => $salesTypeCode,
     *       'exemptionCode' => $exemptionCode,
     *       'exemptionReason' => $exemptionReason,
     *       'groupName' => $groupName,
     *       'group' => $group,
     *       'email' => $email,
     *       'name' => $name
     */
    public static function getCustomerExemptionInfo($customerId = 0, $suretaxCustomerId = null)
    {
        $salesTypeCode = Constants::DEFAULT_SALES_TYPE_CODE;
        $exemptionCode = Constants::DEFAULT_EXEMPTION_CODE;
        $exemptionReason = Constants::DEFAULT_EXEMPTION_REASON_CODE;
       
        // load customer and customer group above where customer belongs to.
        $customerItem = Mage::getModel(Constants::CUST_TBL)
            ->getCollection()
            ->loadCustomerForExemption($customerId);
        
        $name = $customerItem->getName();
        $email = $customerItem->getEmail();
        $group = $customerItem->getGroupId();
        $groupName = $customerItem->getCustomerGroupCode();      

        // if there's a customer Id
        if ($suretaxCustomerId !== null) {
            $suretaxCustomer = Mage::getModel(Constants::CUST_TBL)
                ->load($suretaxCustomerId);
            $salesTypeCode = $suretaxCustomer->getSalesTypeCode();
            $exemptionCode = $suretaxCustomer->getExemptionCode();
            $exemptionReason = $suretaxCustomer->getExemptionReasonCode();
        } else {
            $suretaxCustomerGroup = Mage::getModel(Constants::CUST_GRP_TBL)
                ->load($group, 'suretax_customer_group_id');
            if ($suretaxCustomerGroup !== null) {
                $salesTypeCode = $suretaxCustomerGroup->getSalesTypeCode();
                $exemptionCode = $suretaxCustomerGroup->getExemptionCode();
                $exemptionReason = $suretaxCustomerGroup->getExemptionReasonCode();
            }
        }

        return array(
            'salesTypeCode' => $salesTypeCode,
            'exemptionCode' => $exemptionCode,
            'exemptionReason' => $exemptionReason,
            'groupName' => $groupName,
            'group' => $group,
            'email' => $email,
            'name' => $name
        );
    }
}
