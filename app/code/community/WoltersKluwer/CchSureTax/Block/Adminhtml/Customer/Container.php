<?php
/*
 * SureTax Customer Grid container.
 *  
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customer_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_objectId = 'customer_id';
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_customer_container';
        parent::__construct();
        
        $this->_updateButton('add', 'label', Mage::helper('suretax')->__('Add Customer Configuration'));
        $this->_updateButton('add', 'id', 'customer_save_button');
        $this->_updateButton('add', 'onclick', 'setLocation(\''.$this->getUrl('*/*/selectCustomer/').'\')');
    }
    
    public function getHeaderText()
    {
        return Mage::helper('suretax')->__('SureTax Configurations For Customer');
    }
}
