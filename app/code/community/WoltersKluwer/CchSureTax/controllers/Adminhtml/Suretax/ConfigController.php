<?php
/**
 * SureTax Global Configuration controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_ConfigController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/suretaxconfig');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('suretax/suretax_config');
    }
    
    /**
     * Saving the configuration that is entered in admin
     */
    public function saveAction()
    {
        if ($formData = $this->getRequest()->getPost()) {
            try {
                unset($formData['key']);
                unset($formData['form_key']);
                $suretaxPath = WoltersKluwer_CchSureTax_Helper_Constants::SURETAX_CONFIG_PATH;
                $this->validate($formData);
                foreach ($formData as $key => $value) {
                    $valueData = trim($value);
                    if ($key === 'validationkey') {
                        $valueData = Mage::helper('core')->encrypt(trim($value));
                    }
                    Mage::getModel('core/config')->saveConfig($suretaxPath . '/' . $key, $valueData);
                }
                Mage::app()->getCacheInstance()->cleanType('config');
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__("Your SureTax Global configuration has been saved.")
                );
            } catch (Exception $ex) {
                Utility::logMessage(
                    'Exception when saving global configuration: ' .
                    $ex->getMessage(), Zend_Log::ERR
                );
                Mage::getSingleton('adminhtml/session')->addErrors(
                    $this->__(
                        'Your SureTax Global configuration did not save successfully. ' .
                        'Please check the logs.'
                    )
                );
            }
        }
              
        $this->_redirect('*/*/index');
    }
    
    /**
     * For validating the Global configuration form data.
     *
     * @param array $formData the global config form data array.
     */
    protected function validate($formData)
    {
        $validateResult = Utility::validateConfiguration($formData);
        if (isset($validateResult)) {
            Mage::getSingleton('adminhtml/session')->addWarning(
                $this->__($validateResult)
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->__("Your SureTax Configuration validated successfully.")
            );
        }
    }
}
