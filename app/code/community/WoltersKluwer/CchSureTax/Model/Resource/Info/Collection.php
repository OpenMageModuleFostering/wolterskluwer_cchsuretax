<?php
/**
 * SureTax Global Configuration resource collection.
 * 
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Resource_Info_Collection 
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init(WoltersKluwer_CchSureTax_Helper_Constants::GEN_CONFIG_TBL);
    }
}
