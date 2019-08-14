<?php
/**
 * SureTax Website/Store Configuration resource model.
 * 
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Resource_Websitestore_Config extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init(WoltersKluwer_CchSureTax_Helper_Constants::WS_CONFIG_TBL, 'id');
    }
}
