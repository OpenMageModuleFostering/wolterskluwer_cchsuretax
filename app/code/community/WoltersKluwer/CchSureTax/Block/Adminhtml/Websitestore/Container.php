<?php
/*
 * SureTax Website/store configuration Grid Container.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Websitestore_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_websitestore_container';
        $this->_headerText = Mage::helper('suretax')->__('SureTax Website/Store');
        
        parent::__construct();
        $this->_removeButton('add');
    }
}
