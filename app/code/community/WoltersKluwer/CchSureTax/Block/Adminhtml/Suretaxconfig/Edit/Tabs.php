<?php
/*
 * SureTax Tabs in the Global configuration.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_SuretaxConfig_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    /**
     * Initialize Tabs
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('wk_suretax_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('suretax')->__('Global'));
    }

    protected function _prepareLayout()
    {
        $this->addTab(
            'basic_section', array(
            'label'     => Mage::helper('suretax')->__('Basic'),
            'title'     => Mage::helper('suretax')->__('Basic Configuration'),
            'content'   => $this->getLayout()->createBlock('suretax/adminhtml_suretaxconfig_edit_tab_basic')->toHtml(),
            'active'    => true
            )
        );



        return parent::_prepareLayout();
    }
}
