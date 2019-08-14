<?php
/*
 * SureTax Customer Grid.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customer_Container_Select_Container_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_customer_container';
        $this->setId('customer_exceptions_create_customer_grid');
        $this->setUseAjax(true);
        parent::__construct();
        $this->setDefaultSort('entity_id');
        $this->setEmptyText(Mage::helper('suretax')->__('No records found'));
    }

    protected function _prepareCollection()
    {
        $suretaxCustomerIds = Mage::getResourceModel('wolterskluwer_cchsuretax/customer_collection')
                ->addFieldToSelect('suretax_customer_id')
                ->load();
        $customerIds = array();

        foreach ($suretaxCustomerIds as $suretaxCustomer) {
            array_push($customerIds, $suretaxCustomer->getSuretaxCustomerId());
        }

        if (count($customerIds) > 0) {
            $collection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('group_id')
                ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
                ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
                ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->joinAttribute('billing_regione', 'customer_address/region', 'default_billing', null, 'left')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
                ->addAttributeToFilter('entity_id', array('nin' => $customerIds));
        } else {
            $collection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email')
                ->addAttributeToSelect('created_at')
                ->addAttributeToSelect('group_id')
                ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
                ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
                ->joinAttribute('biling_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->joinAttribute('billing_regione', 'customer_address/region', 'default_billing', null, 'left')
                ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left');
        }
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id', array(
            'header'    =>Mage::helper('suretax')->__('ID'),
            'width'     =>'50px',
            'index'     =>'entity_id',
            'align'     => 'right',
            )
        );

        $this->addColumn(
            'name', array(
            'header'    =>Mage::helper('suretax')->__('Name'),
            'index'     =>'name'
            )
        );

        $this->addColumn(
            'email', array(
            'header'    =>Mage::helper('suretax')->__('Email'),
            'width'     =>'150px',
            'index'     =>'email'
            )
        );

        $this->addColumn(
            'Telephone', array(
            'header'    =>Mage::helper('suretax')->__('Telephone'),
            'width'     =>'100px',
            'index'     =>'billing_telephone'
            )
        );

        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=>0))
            ->load()
            ->toOptionHash();

        $this->addColumn(
            'group', array(
            'header' => Mage::helper('suretax')->__('Group'),
            'width' => '100',
            'index' => 'group_id',
            'type' => 'options',
            'options'=> $groups,
            )
        );

        $this->addColumn(
            'billing_postcode', array(
            'header'    =>Mage::helper('suretax')->__('ZIP/Post Code'),
            'width'     =>'120px',
            'index'     =>'billing_postcode',
            )
        );

        $this->addColumn(
            'billing_country_id', array(
            'header'    =>Mage::helper('suretax')->__('Country'),
            'width'     =>'100px',
            'type'      =>'country',
            'index'     =>'billing_country_id',
            )
        );

        $this->addColumn(
            'billing_regione', array(
            'header'    =>Mage::helper('suretax')->__('State/Province'),
            'width'     =>'100px',
            'index'     =>'billing_regione',
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
            array('id'=>$row->getEntityId())
        );
    }
}
