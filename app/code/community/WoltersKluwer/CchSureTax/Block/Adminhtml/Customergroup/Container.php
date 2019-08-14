<?php
/*
 * SureTax Customer Group Grid container.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Block_Adminhtml_Customergroup_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_objectId = 'customergroup_id';
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_customergroup_container';
        parent::__construct();
        $this->_updateButton('add', 'label', Mage::helper('suretax')->__('Add Group Configuration'));
        $this->_updateButton('add', 'id', 'customergroup_save_button');
        $this->_updateButton('add', 'onclick', 'setLocation(\''.$this->getUrl('*/*/select/').'\')');
    }
    
    public function getHeaderText()
    {
        return Mage::helper('suretax')->__('SureTax Configurations For Customer Group');
    }
}
