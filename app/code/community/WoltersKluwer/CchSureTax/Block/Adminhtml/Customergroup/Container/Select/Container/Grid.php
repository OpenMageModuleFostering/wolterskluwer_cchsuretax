<?php
/*
 * SureTax Customer Group Grid.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Block_Adminhtml_Customergroup_Container_Select_Container_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_customergroup_container';
        $this->setUseAjax(true);
        parent::__construct();
        $this->setId('group_exceptions_create_customer_grid');
    }

    protected function _prepareCollection()
    {
        $suretaxCustomerGroupIds = Mage::getResourceModel('wolterskluwer_cchsuretax/customergroup_collection')
                ->addFieldToSelect('suretax_customer_group_id')
                ->load();

        $customerIds = array();

        foreach ($suretaxCustomerGroupIds as $suretaxCustomerGroup) {
            array_push($customerIds, $suretaxCustomerGroup->getData('suretax_customer_group_id'));
        }

        $collection = Mage::getResourceModel('customer/group_collection')
            ->addTaxClass();
        
        if (!empty($customerIds)) {
            $collection->addFieldToFilter('customer_group_id', array('nin' => $customerIds));
        } 

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'time', array(
            'header' => Mage::helper('suretax')->__('ID'),
            'width'  =>'100',
            'align'  => 'right',
            'index'  => 'customer_group_id',
            )
        );

        $this->addColumn(
            'type', array(
            'header' => Mage::helper('suretax')->__('Group Name'),
            'index' => 'customer_group_code'
            )
        );

        $this->addColumn(
            'class_name', array(
            'header' => Mage::helper('suretax')->__('Tax Class'),
            'index' => 'class_name'
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowId($row)
    {
        return $row->getId();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            array('id'=>$row->getId())
        );
    }
}
