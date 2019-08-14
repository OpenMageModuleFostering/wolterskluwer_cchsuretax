<?php
/*
 * SureTax Website/Store configuration grid.
 *
 * Grid displaying configuration per Website and store group only.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Block_Adminhtml_Websitestore_Container_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        $this->setUseAjax(true);
        parent::__construct();
        $this->setId('sureTaxWebsiteGrid');
        $this->setDefaultSort('website_id');
        $this->setSaveParametersInSession(true);
    }

    //Load the collection of Website/Stores and their SureTax configuration into the Grid.
    //Create a default configuration for a store or website if it doesn't already have one.
    protected function _prepareCollection()
    {
        $websites = Mage::app()->getWebsites();
        $allgroups = Mage::app()->getGroups();
        $resourceTransaction = Mage::getModel('core/resource_transaction');
               
        $websiteConfigCollection = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadAllWebsiteConfig();
        
        $storeConfigCollection = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadAllStoreConfig();
              
        $websiteIDs = array();
        foreach ($websiteConfigCollection as $config) {
            array_push($websiteIDs, $config->getWebsiteId());        
        }
              
        foreach ($websites as $website) {
            $found = in_array($website->getId(), $websiteIDs);   
                      
            if (!$found) {
                $row = Mage::getModel(Constants::WS_CONFIG_TBL);
                $row->setData(
                    array(
                    'website_id' => $website->getId(),
                    'store_id' => 0,
                    'is_enable_calc' => Constants::USE_GLOBAL_SETTINGS,
                    'use_business_unit' => Constants::YES,
                    'business_unit' => '',
                    'use_default_address' => Constants::YES,
                    'street_address1' => null,
                    'street_address2' => null,
                    'city' => null,
                    'country' => null,
                    'state_province' => null,
                    'zip_postal' => null
                    )
                );
                $resourceTransaction->addObject($row);
                WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                    'Saving Default Website Configuration for website : ' .
                    $website->getName(),
                    Zend_Log::DEBUG
                );               
            }                
        }
        
        $groupIDs = array();
        foreach ($storeConfigCollection as $config) {
            array_push($groupIDs, $config->getStoreId());        
        }
        
        foreach ($allgroups as $group) {
            $found = in_array($group->getId(), $groupIDs);   
            if (!$found) {
                $row = Mage::getModel(Constants::WS_CONFIG_TBL);
                $row->setData(
                    array(
                    'website_id' => $group->getWebsiteId(),
                    'store_id' => $group->getId(),
                    'is_enable_calc' => Constants::USE_WEBSITE_SETTINGS,
                    'use_business_unit' => Constants::YES,
                    'business_unit' => '',
                    'use_default_address' => Constants::YES,
                    'street_address1' => null,
                    'street_address2' => null,
                    'city' => null,
                    'country' => null,
                    'state_province' => null,
                    'zip_postal' => null
                    )
                );
                $resourceTransaction->addObject($row);
                WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                    'Saving Default Store Configuration for Store : ' .
                    $group->getName(),
                    Zend_Log::DEBUG
                );
            }
        }      

        $resourceTransaction->save();
        
        $collection = Mage::getModel(Constants::WS_CONFIG_TBL)->getCollection();

        $collection->joinWebsite()->joinStore();


        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'website_id', array(
            'header'    => Mage::helper('suretax')->__('Website ID'),
            'width'     => '100',
            'index'     => 'website_id',
            'filter_index' => 'main_table.website_id',
            'type'      => 'number'
            )
        );

        $websites = $websites = Mage::app()->getWebsites();
        foreach ($websites as $website) {
            $ws[$website->getName()] = $website->getName();
        }

        $allGroups = Mage::app()->getGroups();
        foreach ($allGroups as $group) {
            $stores[$group->getName()] = $group->getName();
        }
        $this->addColumn(
            'website', array(
            'header'    => Mage::helper('suretax')->__('Website'),
            'width'     => '100',
            'index'     => 'website',
            'filter_index' => 'wb.name',
            'type'      => 'options',
            'options'   => $ws
            )
        );

        $this->addColumn(
            'store_id', array(
            'header'    => Mage::helper('suretax')->__('Store ID'),
            'width'     => '100',
            'index'     => 'store_id',
            'filter_index' => 'main_table.store_id',
            'type'      => 'number'
            )
        );

        $this->addColumn(
            'store', array(
            'header'    => Mage::helper('suretax')->__('Store'),
            'width'     => '100',
            'index'     => 'store',
            'filter_index' => 'grp.name',
            'type'      => 'options',
            'options'   => $stores
            )
        );

        $this->addColumn(
            'is_enable_calc', array(
            'header'    => Mage::helper('suretax')->__('Enable SureTax Calculation'),
            'width'     => '75',
            'index'     => 'is_enable_calc',
            'type'      => 'options',
            'options'   => array(Constants::USE_WEBSITE_SETTINGS => 'Use Website Settings',
                                 Constants::USE_GLOBAL_SETTINGS => 'Use Global Settings',
                                 Constants::NO => 'No',
                                 Constants::YES => 'Yes')
            )
        );
        
        $this->addColumn(
            'use_business_unit', array(
            'header'    => Mage::helper('suretax')->__('Use Default Business Unit'),
            'width'     => '75',
            'index'     => 'use_business_unit',
            'type'      => 'options',
            'options'   => array(Constants::NO => 'No',
                                 Constants::YES => 'Yes')
            )
        );
        
        $this->addColumn(
            'business_unit', array(
            'header'    => Mage::helper('suretax')->__('Business Unit'),
            'width'     => '200',
            'index'     => 'business_unit'
            )
        );

        $this->addColumn(
            'use_default_address', array(
            'header'    => Mage::helper('suretax')->__('Use Default Ship From Address'),
            'width'     => '75',
            'index'     => 'use_default_address',
            'type'      => 'options',
            'options'   => array(Constants::NO => 'No',
                                 Constants::YES => 'Yes'),
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        $name = $row->getStoreId() == 0 ? $row->getWebsite() : $row->getStore();

        return $this->getUrl(
            '*/*/edit', array(
            'websiteId'=>$row->getWebsiteId(),
            'storeId'=>$row->getStoreId(),
            'name'=>$name
            )
        );
    }
}
