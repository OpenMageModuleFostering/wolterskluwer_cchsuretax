<?php
/*
 * Complete configuration used for all SureTax call processing. 
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;

class WoltersKluwer_CchSureTax_Helper_Config extends Mage_Core_Helper_Abstract
{
    protected static $_handle = null;

    const REQUEST_TIMEOUT = 600;
    const UNIT_TYPE = "00";               // 00 default
    const AUX_REVENUE_TYPE = "01";        // 01 - freight (only value)

    const WSDL_CACHING = 'WSDL_CACHE_MEMORY';

    protected $_defaultShippingClass = Constants::DEFAULT_TRANS_TYPE_CODE_SHIPPING;
    protected $_defaultGiftWrapClass = Constants::DEFAULT_TRANS_TYPE_CODE_GIFT_WRAP;
    protected $_isDebug = false;
    // set this to DEBUG
    protected $_isProfiling = true;
    // global setting
    protected $_isSuretaxEnabled = 0;

    // holds endpoint URL
    protected $_endpointUrl = '';

    // no need for wsdl url setter, it's auto set by endpoint url setter
    protected $_wsdlUrl = '';
    protected $_clientNumber = '';
    protected $_businessUnit = '';
    protected $_validationKey = '';
    /**
     * Use 03, don't expect to change this
     * 03 Tax grouped by State + Customer Number + Invoice Number (Default)
     */
    protected $_responseGroup = "03";
    // detailed 4 decimal places
    protected $_responseType = "D4";
    // 70 retail, 99 default
    protected $_regulatoryCode = "70";
    // 23, 24 can be valid for general sales (Origin vs Destination)
    protected $_taxSitusRule = '22';
    protected $_providerType = '99';

    /**
     * must call init before using this
     *
     * @return self
     */
    public static function get()
    {
        $timeStart = microtime(true);

        if (isset(self::$_handle) == false || self::$_handle === null) {
            /* @var $_handle WoltersKluwer_CchSureTax_Helper_Config*/
            self::$_handle = Mage::helper('suretax/config');
            if (self::$_handle->isProfiling()) {
                Utility::doProfile($timeStart, 'Config Init ');
            }
        }

        return self::$_handle;
    }

    public function __construct()
    {
        try {
            $suretaxConfigData = Utility::getSureTaxConfigData();
            $endpointUrl = $suretaxConfigData['suretaxurl'];
            // wsdl url auto set by this
            $this->setEndpointUrl($endpointUrl);

            $clientNumber = $suretaxConfigData['clientnumber'];
            $this->setClientNumber($clientNumber);

            $validationKey = $suretaxConfigData['validationkey'];
            $this->setValidationKey($validationKey);

            $this->setProviderType($suretaxConfigData['providertype']);

            //default is NONE
            $prodClassId = array_key_exists(
                'shipping_tax_class',
                $suretaxConfigData
            ) ? $suretaxConfigData['shipping_tax_class'] : 0;
            $defShipObj = Mage::getModel('tax/class')->load($prodClassId);
            $defShip = $defShipObj->getClassName();
            $this->setDefaultShippingClass($defShip);

            //default is NONE
            $prodClassIdGift = array_key_exists('gift_wrap_tax_class', $suretaxConfigData)
                    ? $suretaxConfigData['gift_wrap_tax_class'] : 0;
            $defGiftWrapObj = Mage::getModel('tax/class')->load($prodClassIdGift);
            $defGiftWrap = $defGiftWrapObj->getClassName();
            $this->setDefaulGiftWrapClass($defGiftWrap);

            $isD = array_key_exists('is_logging_enabled', $suretaxConfigData)
                ? $suretaxConfigData['is_logging_enabled'] == '1' : true;
            $this->setDebug($isD);

            // default values, but can have site/store specific values
            $this->setBusinessUnit($suretaxConfigData['defaultbusinessunit']);

            $this->setSuretaxEnabled($suretaxConfigData['is_suretax_enabled']);

            // min requirements for web service calls
            if ($clientNumber == null
                || $validationKey == null
                || $endpointUrl == null
            ) {
                throw new InvalidArgumentException(
                    Constants::MESSAGE_CONFIG_MISSING_ARGUMENTS
                );
            }
        } catch (Exception $exe) {
            Utility::logCatch($exe, 'Issue with initializing Config');
        }
    }

    /**
     *
     * @param int $websiteId
     * @param int $storeId
     *
     * @return int
     */
    public static function isSureTaxEnabledForWebsiteStore($websiteId, $storeId)
    {
        $sureTaxConfig = self::get();
        $defaultFlag = $sureTaxConfig->isSuretaxEnabled();

        $row = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadWebsiteConfig($websiteId);
        if (!($row->getIsEnableCalc() === null)) {
            $websiteFlag = $row->getIsEnableCalc();
        } else {
            $websiteFlag = $defaultFlag;
        }

        $row = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadStoreConfig($websiteId, $storeId);
        if (!($row->getIsEnableCalc() === null)) {
            $storeFlag = $row->getIsEnableCalc();
        } else {
            $storeFlag = $websiteFlag;
        }

        if ($storeFlag != Constants::USE_WEBSITE_SETTINGS) {
            return $storeFlag;
        } elseif ($websiteFlag != Constants::USE_GLOBAL_SETTINGS) {
            return $websiteFlag;
        } else {
            return $defaultFlag;
        }
    }
    
    /**
     *
     * @return array Array of all the website/store configuration customers have set up.
     */
    public static function getWebsiteStoreConfig()
    {
        $wsConfigCollection = Mage::getModel(Constants::WS_CONFIG_TBL)->getCollection();
        $wsConfigArray = array();
        foreach ($wsConfigCollection as $wsConfig) {
            $arrayIndex = $wsConfig->getData('website_id').':'.$wsConfig->getData('store_id');
            $wsConfigArray[$arrayIndex] = $wsConfig->__toArray();
        }
        return $wsConfigArray;
    }
    
    /**
     * @param int   $websiteId     websiteId
     * @param int   $storeId       store id
     * @param array $wsConfigArray entire ws config array
     * @return int
     */
    public static function isSureTaxEnabledForWebsiteStoreConfig($websiteId, $storeId, $wsConfigArray)
    {
        $sureTaxConfig = self::get();
        $defaultFlag = $sureTaxConfig->isSuretaxEnabled();
        $websiteFlag = $wsConfigArray[$websiteId.':0'][Constants::ENABLE_CALC];
        $storeFlag = $wsConfigArray[$websiteId.':'.$storeId][Constants::ENABLE_CALC];
        if ($storeFlag != Constants::USE_WEBSITE_SETTINGS) {
            return $storeFlag;
        } elseif ($websiteFlag != Constants::USE_GLOBAL_SETTINGS) {
            return $websiteFlag;
        } else {
            return $defaultFlag;
        }
    }
    
    /**
     * @param int   $storeId       store id
     * @param array $wsConfigArray entire ws config array
     * @return int
     */
    public static function isSureTaxEnabledFromStore($storeId, $wsConfigArray)
    {
        $sureTaxConfig = self::get();
        $defaultFlag = $sureTaxConfig->isSuretaxEnabled();
        $websiteFlag = 0;
        $storeFlag = 0;
        foreach ($wsConfigArray as $key => $value) {
            $keySplit = explode(":", $key);
            if (in_array($storeId, $keySplit)) {
                $websiteId = $keySplit[0];
                $storeFlag = $value[Constants::ENABLE_CALC];
                $websiteFlag = $wsConfigArray[$websiteId.':0'][Constants::ENABLE_CALC];
            }
        }
        if ($storeFlag != Constants::USE_WEBSITE_SETTINGS) {
            return $storeFlag;
        } elseif ($websiteFlag != Constants::USE_GLOBAL_SETTINGS) {
            return $websiteFlag;
        } else {
            return $defaultFlag;
        }
    }

    public function isSuretaxEnabled()
    {
        return $this->_isSuretaxEnabled;
    }

    public function isDebug()
    {
        return $this->_isDebug;
    }

    public function isProfiling()
    {
        return $this->_isProfiling;
    }

    public function getEndpointUrl()
    {
        return $this->_endpointUrl;
    }

    public function getWsdlUrl()
    {
        return $this->_wsdlUrl;
    }

    public function getClientNumber()
    {
        return $this->_clientNumber;
    }

    public function getBusinessUnit()
    {
        return $this->_businessUnit;
    }

    public function getValidationKey()
    {
        return $this->_validationKey;
    }

    public function getRegulatoryCode()
    {
        return $this->_regulatoryCode;
    }

    public function getResponseGroup()
    {
        return $this->_responseGroup;
    }

    public function getResponseType()
    {
        return $this->_responseType;
    }

    public function getUnitType()
    {
        return self::UNIT_TYPE;
    }

    public function getAuxRevenueType()
    {
        return self::AUX_REVENUE_TYPE;
    }

    public function getWsdlCaching()
    {
        return self::WSDL_CACHING;
    }

    public function getDefaultShippingClass()
    {
        return $this->_defaultShippingClass;
    }

    public function getDefaulGiftWrapClass()
    {
        return $this->_defaultGiftWrapClass;
    }

    public function setDefaulGiftWrapClass($defaultGiftWrapClass)
    {
        if ($defaultGiftWrapClass != null) {
            $this->_defaultGiftWrapClass = $defaultGiftWrapClass;
        } else {
            Utility::log(
                Constants::MESSAGE_INVALID_GIFT_WRAP_CLASS .
                $defaultGiftWrapClass,
                Zend_Log::INFO,
                $this->isDebug()
            );
            $this->_defaultGiftWrapClass = Constants::DEFAULT_TRANS_TYPE_CODE_GIFT_WRAP;
        }
    }

    public function setDefaultShippingClass($defaultShippingClass)
    {
        if ($defaultShippingClass != null) {
            $this->_defaultShippingClass = $defaultShippingClass;
        } else {
            Utility::log(
                $this->__(
                    Constants::MESSAGE_INVALID_SHIPPING_CLASS .
                    $defaultShippingClass
                ),
                Zend_Log::INFO,
                $this->isDebug()
            );
            $this->_defaultShippingClass = Constants
                ::DEFAULT_TRANS_TYPE_CODE_SHIPPING;
        }
    }

    public function setSuretaxEnabled($isSuretaxEnabled)
    {
        $this->_isSuretaxEnabled = $isSuretaxEnabled;
    }
    public function setDebug($trueOrFalse)
    {
        $this->_isDebug = $trueOrFalse;
    }

    public function setEndpointUrl($theUrlString)
    {
        $this->_endpointUrl = $theUrlString;
        $this->_wsdlUrl = $this->_endpointUrl . '?wsdl';
    }

    public function setClientNumber($clientNumber)
    {
        $this->_clientNumber = $clientNumber;
    }

    public function setBusinessUnit($businessUnit)
    {
        $this->_businessUnit = $businessUnit;
    }

    public function setValidationKey($validationKey)
    {
        $this->_validationKey = $validationKey;
    }

    public function setResponseGroup($responseGroup)
    {
        $this->_responseGroup = $responseGroup;
    }

    public function setResponseType($responseType)
    {
        $this->_responseType = $responseType;
    }

    public function setRegulatoryCode($regulatoryCode)
    {
        $this->_regulatoryCode = $regulatoryCode;
    }

    public function getRequestTimeout()
    {
        return self::REQUEST_TIMEOUT;
    }

    public function setProfiling($PRO)
    {
        $this->_isProfiling = $PRO;
    }

    public function getTaxSitusRule()
    {
        return $this->_taxSitusRule;
    }

    public function setTaxSitusRule($taxSitusRule)
    {
        $this->_taxSitusRule = $taxSitusRule;
    }

    public function getProviderType()
    {
        return $this->_providerType;
    }

    public function setProviderType($providerType)
    {
        $this->_providerType = $providerType;
    }
}
