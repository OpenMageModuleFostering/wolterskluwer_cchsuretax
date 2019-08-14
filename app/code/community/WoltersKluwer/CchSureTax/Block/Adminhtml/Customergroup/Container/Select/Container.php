<?php
/*
 * SureTax Customer Group Grid container.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Customergroup_Container_Select_Container
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'customer_id';
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_customergroup_container_select_container';

        $this->_removeButton('add');
        $this->_addBackButton();
        $this->_updateButton('back', 'onclick', 'setLocation(\''.$this->getUrl('*/suretax_customergroup/index/').'\')');
    }

    public function getHeaderText()
    {
        return Mage::helper('suretax')->__('Select Customer Group For Configuration');
    }
}
