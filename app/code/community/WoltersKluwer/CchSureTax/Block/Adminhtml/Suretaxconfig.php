<?php
/*
 * SureTax configuration Grid Container.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

//Is this even used? Remove this class before release.
class WoltersKluwer_CchSureTax_Block_Adminhtml_Suretaxconfig extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_suretaxconfig';
        $this->_headerText = Mage::helper('suretax')->__('Info');
        $this->_addButtonLabel = Mage::helper('suretax')->__('Add New Info');
        parent::__construct();
    }
}
