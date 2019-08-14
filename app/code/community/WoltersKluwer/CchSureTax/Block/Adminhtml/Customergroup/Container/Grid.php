<?php
/*
 * SureTax Customer Group Grid.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customergroup_Container_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $collection = WoltersKluwer_CchSureTax_Model_Resource_Customergroup_Collection::joinWithCustomerGroup();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'time', array(
                'header' => Mage::helper('suretax')->__('ID'),
                'width' => '50',
                'align'  => 'right',
                'index'  => 'customer_group_id',
            )
        );

        $this->addColumn(
            'type', array(
                'header' => Mage::helper('suretax')->__('Group Name'),
                'index'  => 'customer_group_code',
            )
        );

        $this->addColumn(
            'class_name', array(
                'header' => Mage::helper('suretax')->__('Tax Class'),
                'index'  => 'class_name',
            )
        );

        $this->addColumn(
            'sales_type_desc', array(
                'header'    => Mage::helper('customer')->__('Sales Type Code'),
                'width'     => '150',
                'index'     => 'sales_type_code',
                'filter_index'=> 'sales_type_code'
            )
        );

        $this->addColumn(
            'exemption_desc', array(
                'header'    => Mage::helper('customer')->__('Exemption Code'),
                'width'     => '150',
                'index'     => 'exemption_code',
                'filter_index' => 'exemption_code'
            )
        );

        $this->addColumn(
            'exemption_reason', array(
                'header'    => Mage::helper('customer')->__('Exemption Reason'),
                'width'     => '150',
                'index'     => 'exemption_reason_code',
                'filter_index' => 'exemption_reason_code'
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
            array('id'=>$row->getCustomerGroupId(),'suretaxId'=>$row->getData('id'))
        );
    }
}
