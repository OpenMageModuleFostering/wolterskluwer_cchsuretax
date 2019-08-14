<?php
/*
 * SureTax Invoices grid container.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Invoices_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_objectId = 'invoices_id';
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_invoices_container';

        parent::__construct();
        $this->_removeButton('add');
    }

    public function getHeaderText()
    {
        return Mage::helper('suretax')->__('SureTax Invoices');
    }
}
