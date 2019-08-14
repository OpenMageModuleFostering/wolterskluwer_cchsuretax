<?php
/**
 * SureTax Credit Memo resource collection.
 * 
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Model_Resource_Creditmemo_Collection 
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct() 
    {
        $this->_init(Constants::CREDIT_TBL);
    }
    
    public static function joinWithCreditmemo()
    {
        $resource = Mage::getSingleton('core/resource'); 
        //Join it the Magento's Credit Memo table.
        $collection = Mage::getModel('sales/order_creditmemo')->getCollection();
        
        $collection->getSelect()->join(
            array(
            'order' => $resource->getTableName('sales/order')),
            'main_table.order_id = order.entity_id',
            array('order.increment_id as order_number')
        )->joinLeft(
            array(
            'sure' => $resource->getTableName(Constants::CREDIT_TBL)),
            'main_table.increment_id = sure.increment_id',
            array('if(isnull(sure.stan), "N/A", sure.stan) as stan',
                'if(isnull(sure.trans_id), "N/A", sure.trans_id) as trans_id',
                'if(isnull(sure.client_tracking), "N/A", sure.client_tracking) as client_tracking',
                'if(isnull(sure.tax), "0", sure.tax) as tax',
                'if(isnull(sure.status), "N/A", sure.status) as status',
                'if(isnull(sure.notes), "N/A", sure.notes) as notes',
                '(main_table.tax_amount - sure.tax) as tax_difference')
        );
              
        return $collection;
    }
    
    public function joinCreditMemoWithSelfForCreditmemoIds($creditmemoIds)
    {
        $creditMemoCollection = Mage::getModel('sales/order_creditmemo')
            ->getCollection()
            ->addFieldToFilter('main_table.entity_id', array('in' => $creditmemoIds));
        
        $creditMemoCollection->getSelect()
            ->joinLeft(
                array('address' => $this->getTable('sales/order_address')),
                'main_table.shipping_address_id = address.entity_id',
                array('address_entity_id'=>'entity_id', 'postcode', 'street', 'city', 'country_id')
            )->joinLeft(
                array('wk_credit_memo' => $this->getTable(Constants::CREDIT_TBL)),
                'main_table.increment_id = wk_credit_memo.increment_id',
                array('suretax_id'=>'wk_credit_memo.id', 
                    'wk_increment_id'=>'wk_credit_memo.increment_id', 'stan', 
                    'trans_id', 'tax', 'client_tracking', 'status', 'notes')
            )->joinLeft(
                array('region' => $this->getTable('directory/country_region')),
                'region.region_id = address.region_id',
                array('region'=>'code')
            )->joinLeft(
                array('store' => $this->getTable('core/store')),
                'main_table.store_id = store.store_id',
                array('website_id', 'group_id')
            )->joinLeft(
                array('order' => $this->getTable('sales/order')),
                'main_table.order_id = order.entity_id',
                array('order_created_at'=>'created_at', 'customer_id', 'customer_group_id',
                    'base_shipping_discount_amount', 'order_increment_id'=>'increment_id')
            )->joinLeft(
                array('customer' => $this->getTable(Constants::CUST_TBL)),
                'order.customer_id = customer.suretax_customer_id',
                array('customer_exemption_code'=>'exemption_code', 
                    'customer_exemption_reason_code'=>'exemption_reason_code',
                    'customer_sales_type_code'=>'sales_type_code')
            )->joinLeft(
                array('customer_group' => $this->getTable(Constants::CUST_GRP_TBL)),
                'order.customer_group_id = customer_group.suretax_customer_group_id',
                array('customer_group_exemption_code'=>'exemption_code', 
                    'customer_group_exemption_reason_code'=>'exemption_reason_code',
                    'customer_group_sales_type_code'=>'sales_type_code')
            );
        //        Mage::log((string)$creditMemoCollection->getSelect());
        return $creditMemoCollection;
    }
}
