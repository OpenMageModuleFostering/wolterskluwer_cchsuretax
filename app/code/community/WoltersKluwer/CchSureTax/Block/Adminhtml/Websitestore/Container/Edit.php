<?php
/*
 * SureTax Website/store configuration edit container.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Websitestore_Container_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'edit';
        $this->_blockGroup = 'suretax';
        $this->_controller = 'adminhtml_websitestore_container';
        parent::__construct();
        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        return Mage::helper('suretax')->__('SureTax Website/Store Configuration');
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/suretax_websitestore/save');
    }
}
