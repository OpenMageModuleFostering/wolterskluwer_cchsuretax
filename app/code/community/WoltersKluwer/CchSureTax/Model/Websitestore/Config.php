<?php
/**
 * SureTax Website/Store configuration model.
 * 
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Websitestore_Config extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        $this->_init(WoltersKluwer_CchSureTax_Helper_Constants::WS_CONFIG_TBL);
    }      
}
