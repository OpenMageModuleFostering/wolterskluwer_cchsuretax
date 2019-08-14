<?php
/*
 * SureTax Customer Group resource collection.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Resource_Customergroup_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    public function _construct()
    {
        $this->_init(WoltersKluwer_CchSureTax_Helper_Constants::CUST_GRP_TBL);
    }

    public static function joinWithCustomerGroup()
    {
        $resource = Mage::getSingleton('core/resource');

        $collection = Mage::getResourceModel('customer/group_collection')
            ->addTaxClass();

        $collection->getSelect()->join(
            array(
            'suretax_group_info' => $resource->getTableName(WoltersKluwer_CchSureTax_Helper_Constants::CUST_GRP_TBL)),
            'main_table.customer_group_id = suretax_group_info.suretax_customer_group_id'
        );

        return $collection;
    }
}
