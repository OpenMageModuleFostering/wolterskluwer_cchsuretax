<?php
/**
 * SureTax Help Screen controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_HelpController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/help');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('suretax/suretax_help');
    }
}
