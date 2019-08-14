<?php
/*
 * SureTax Customer grid.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customer_Container_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        $this->setUseAjax(true);
        parent::__construct();
        $this->setId('sureTaxCustomerGrid');
        $this->setDefaultSort('entity_id');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = WoltersKluwer_CchSureTax_Model_Resource_Customer_Collection::joinWithCustomer();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id', array(
                'header'    => Mage::helper('suretax')->__('ID'),
                'width'     => '50px',
                'index'     => 'entity_id',
                'type'  => 'number',
            )
        );
        
        $this->addColumn(
            'name', array(
                'header'    => Mage::helper('suretax')->__('Name'),
                'index'     => 'name'
            )
        );
        
        $this->addColumn(
            'email', array(
                'header'    => Mage::helper('suretax')->__('Email'),
                'width'     => '150',
                'index'     => 'email'
            )
        );

        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();

        $this->addColumn(
            'group', array(
                'header'    =>  Mage::helper('suretax')->__('Group'),
                'width'     =>  '100',
                'index'     =>  'group_id',
                'type'      =>  'options',
                'options'   =>  $groups,
            )
        );
        $ar = array($this, '_filterSalesTypeConditionCallback');
        $this->addColumn(
            'sales_type_desc', array(
                'header'    => Mage::helper('customer')->__('Sales Type Code'),
                'width'     => '150',
                'index'     => 'sales_type_code',
                'filter_index' => 'sales_type_code',
                'filter_condition_callback' => $ar
            )
        );
        
        $this->addColumn(
            'exemption_desc', array(
                'header'    => Mage::helper('customer')->__('Exemption Code'),
                'index'     => 'exemption_code',
                'filter_index' => 'exemption_code',
                'filter_condition_callback' => $ar
            )
        );
        
        $this->addColumn(
            'exemption_reason', array(
                'header'    => Mage::helper('customer')->__('Exemption Reason'),
                'width'     => '150',
                'index'     => 'exemption_reason_code',
                'filter_index' => 'exemption_reason_code',
                'filter_condition_callback' => $ar
            )
        );
         
        $this->addColumn(
            'suretaxId', array(
                'header'    => Mage::helper('customer')->__('Sure Tax ID'),
                'width'     => '10',
                'index'     => 'id',
                'column_css_class'=>'no-display',
                'header_css_class'=>'no-display',
            )
        );
        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/edit',
            array('id'=>$row->getEntityId(),'suretaxId'=>$row->getData('id'))
        );
    }
    
    protected function _filterSalesTypeConditionCallback($collection, $column)
    {
        if (!$column->getFilter()->getCondition()) {
            return;
        }
        $columnName = '';
        $columnId = $column->getId();
        switch ($columnId) {
        case 'sales_type_desc' :
            $columnName = 'sales_type_code';
            break;
        case 'exemption_desc' :
            $columnName = 'exemption_code';
            break;
        case 'exemption_reason' :
            $columnName = 'exemption_reason_code';
            break;
        } 
        $collection->filterBasedOnExemptionFields($columnName, $column->getFilter()->getCondition());
    }
}
