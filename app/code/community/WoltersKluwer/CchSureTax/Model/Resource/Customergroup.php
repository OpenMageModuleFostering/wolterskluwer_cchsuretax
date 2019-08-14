<?php
/*
 * SureTax Customer Group resource model.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Resource_Customergroup extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init(WoltersKluwer_CchSureTax_Helper_Constants::CUST_GRP_TBL, 'id');
    }
}
