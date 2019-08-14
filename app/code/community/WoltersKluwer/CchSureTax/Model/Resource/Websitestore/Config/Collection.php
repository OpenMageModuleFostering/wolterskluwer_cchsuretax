<?php
/**
 * SureTax Website/Store Configuration resource collection.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Resource_Websitestore_Config_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init(WoltersKluwer_CchSureTax_Helper_Constants::WS_CONFIG_TBL);
    }

    /**
     * Join website to get the name
     *
     * @return self
     */
    public function joinWebsite()
    {
        $this->getSelect()->join(
            array('wb' => Mage::getSingleton('core/resource')
                ->getTableName('core/website')),
            'main_table.website_id = wb.website_id',
            array('wb.name as website')
        );
        return $this;
    }

    /**
     * Join store to get the name
     *
     * @return self
     */
    public function joinStore()
    {
        $this->getSelect()->join(
            array('grp' => Mage::getSingleton('core/resource')
                ->getTableName('core/store_group')),
            'main_table.store_id = grp.group_id',
            array('grp.name as store')
        );
        return $this;
    }

    /**
     * Load Website config from the website ID
     *
     * @param  int $websiteId
     * @return WoltersKluwer_CchSureTax_Model_Websitestore_Config
     */
    public function loadWebsiteConfig($websiteId)
    {
        $this->addFieldToFilter('website_id', $websiteId)
            ->addFieldToFilter('store_id', 0);

        $this->getSelect()->limit(1);

        return $this->getFirstItem();
    }

    /**
     * Load all Website config.
     *
     * @return array WoltersKluwer_CchSureTax_Model_Websitestore_Config
     */
    public function loadAllWebsiteConfig()
    {
        $this->addFieldToFilter('store_id', 0);

        return $this->getItems();
    }

    /**
     * Load Store config from the Website and Store ID
     *
     * @param  int $websiteId
     * @param  int $storedId
     * @return WoltersKluwer_CchSureTax_Model_Websitestore_Config
     */
    public function loadStoreConfig($websiteId, $storedId)
    {
        $this->addFieldToFilter('website_id', array('eq' =>$websiteId))
            ->addFieldToFilter('store_id', array('eq' =>$storedId));

        $this->getSelect()->limit(1);

        return $this->getFirstItem();
    }

    /**
     * Load all store config.
     *
     * @return array WoltersKluwer_CchSureTax_Model_Websitestore_Config
     */
    public function loadAllStoreConfig()
    {
        $this->addFieldToFilter('store_id', array('neq' => 0));

        return $this->getItems();
    }
}
