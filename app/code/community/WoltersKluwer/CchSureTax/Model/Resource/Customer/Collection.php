<?php
/*
 * SureTax Customer resource collection.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Model_Resource_Customer_Collection 
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct() 
    {
        $this->_init(Constants::CUST_TBL);
    }
    
    public static function joinWithCustomer() 
    {
        $resource = Mage::getSingleton('core/resource');
        /* @var $collection WoltersKluwer_CchSureTax_Model_Resource_Corecustomer_Collection */
        $collection = Mage::getResourceModel('wolterskluwer_cchsuretax/corecustomer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id');
        
        $collection->getSelect()->join(
            array(
            'sure' => $resource->getTableName(Constants::CUST_TBL)
            ), 'e.entity_id = sure.suretax_customer_id'
        );
        
        return $collection;
        
    }
    
    /**
     * Magento invoice join with Magento order and SureTax invoice table.
     *
     * @param int $customerId
     * 
     * @return WoltersKluwer_CchSureTax_Model_Customer
     */
    public function loadCustomerForExemption($customerId)
    {     
        /* @var $collection WoltersKluwer_CchSureTax_Model_Resource_Corecustomer_Collection */
        $collection = Mage::getResourceModel('wolterskluwer_cchsuretax/corecustomer_collection')
            ->addFieldToFilter('entity_id', $customerId)
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('group_id');

        $collection->getSelect()->join(
            array('group' => $this->getTable('customer/customer_group')),
            'e.group_id = group.customer_group_id',
            array('customer_group_code')
        );
        $collection->getSelect()->limit(1);
        
        return $collection->getFirstItem();
    }
}
