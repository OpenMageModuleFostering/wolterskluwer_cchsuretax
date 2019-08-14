<?php
/*
 * Utility and commonly used methods
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;

class WoltersKluwer_CchSureTax_Helper_Utility extends Mage_Core_Helper_Abstract
{

    // Call by isEnterprise() static method, don't use this directly
    public static $version;
    protected static $_isEnterprise;
    /**
     * log levels:
     * e.g. Zend_Log::DEBUG
     * const EMERG   = 0;  // Emergency: system is unusable
     * const ALERT   = 1;  // Alert: action must be taken immediately
     * const CRIT    = 2;  // Critical: critical conditions
     * const ERR     = 3;  // Error: error conditions
     * const WARN    = 4;  // Warning: warning conditions
     * const NOTICE  = 5;  // Notice: normal but significant condition
     * const INFO    = 6;  // Informational: informational messages
     * const DEBUG   = 7;  // Debug: debug messages
     *
     * @param string  $logMessage
     * @param int     $logLevel   Magento's log levels see above
     * @param boolean $isDebug    true or false - if true, then log every level.
     *                            if false, log level 0 - 5 if false, log level
     *                            0 - 5
     *                            if false, log level 0 - 5
     */
    public static function log($logMessage, $logLevel, $isDebug)
    {
        if ($isDebug == true || $logLevel < Zend_Log::INFO) {
            Mage::log($logMessage, $logLevel, Constants::LOG_FILENAME);
        }
    }

     /**
     * @param string $logMessage
     * @param int    $logLevel   Magento's log levels see above
     */
    public static function logMessage($logMessage, $logLevel)
    {
        $isDebug = false;
        if (isset($logLevel) && $logLevel < Zend_Log::INFO) {
            $isDebug = true;
        }
        // don't do this level is high enough already
        if ($isDebug === false) {
            $sureTaxConfig = WoltersKluwer_CchSureTax_Helper_Config::get();
            $isDebug = $sureTaxConfig->isSuretaxEnabled();
        }

        if ($isDebug == true) {
            Mage::log($logMessage, $logLevel, Constants::LOG_FILENAME);
        }
    }

    /**
     * Validate the Global configuration using SoapRequest with sample data.
     *
     * @param  array $suretaxConfigData
     * @return null|string warning messages if invalid. Null if valid
     */
    public static function validateConfiguration($suretaxConfigData)
    {
        try {
            $clientNumber = trim($suretaxConfigData['clientnumber']);
            $url = trim($suretaxConfigData['suretaxurl']);
            $validationKey = trim($suretaxConfigData['validationkey']);
            $businessUnit = trim($suretaxConfigData['defaultbusinessunit']);

            $client = new SoapClient(
                $url.'?WSDL',
                array(
                        'trace' => 1,
                        'connection_timeout' => Constants::SOAP_CALL_TIMEOUT,
                        'exceptions' => true,
                        'cache_wsdl' => WSDL_CACHE_NONE,
                        'features' => SOAP_SINGLE_ELEMENT_ARRAYS
                )
            );

            $address = array(
                'PrimaryAddressLine'=>'',
                'SecondaryAddressLine'=>'',
                'City'=>'Torrance',
                'State'=>'CA',
                'PostalCode'=>'90502',
                'Country'=>'US',
                'Plus4'=>"",
                'VerifyAddress'=>'false'
            );

            $item =
                array(
                    'LineNumber'=>'1',
                    'InvoiceNumber'=>'1',
                    'CustomerNumber'=>'',
                    'OrigNumber'=>'',
                    'TermNumber'=>'',
                    'BillToNumber'=>'',
                    'TransDate'=>date('Y-m-d').'T00:00:00',
                    'Revenue'=>1,
                    'AuxRevenue'=>0,
                    'Units'=>1,
                    'UnitType'=>'00',
                    'Seconds'=>'0',
                    'ShipFromPOB'=>1,
                    'MailOrder'=>1,
                    'CommonCarrier'=>1,
                    'TaxIncludedCode'=>'0',
                    'BillingDaysInPeriod'=>'',
                    'TaxSitusRule'=>'22',
                    'TransTypeCode'=>  Constants::DEFAULT_TRANS_TYPE_CODE,
                    'SalesTypeCode'=>'R',
                    'RegulatoryCode'=>'99',
                    'ShipToAddress'=>$address,
                    'ShipFromAddress'=>$address
                );

            $soapCalcRequest=
            array('request'
                =>array(
                    'ClientNumber'=> $clientNumber,
                    'BusinessUnit'=>isset($businessUnit) ? $businessUnit : '',
                    'ValidationKey'=>$validationKey,
                    'DataYear'=>date('Y'),
                    'DataMonth'=>date('m'),
                    'TotalRevenue'=>1,
                    'ReturnFileCode'=>'Q',
                    'ClientTracking'=>Constants::VALIDATE_CONFIG_INFO,
                    'STAN'=>'',
                    'MasterTransId'=>'',
                    'ResponseType'=>'D5',
                    'ResponseGroup'=>'03',
                    'ItemList'=>array($item)
                )
            );

            $soapResult = $client->SoapRequest($soapCalcRequest);
            $isSuccessful = $soapResult->SoapRequestResult->Successful;

            if ($isSuccessful == 'Y') {
                return null;
            } else {
                self::log(
                    'Configuration Validation Issue : Invalid : ' .
                    $soapResult->SoapRequestResult->HeaderMessage,
                    Zend_Log::ERR,
                    true
                );
                return 'Invalid : ' . $soapResult->SoapRequestResult->HeaderMessage;
            }
        } catch (Exception $ere) {

            $isPhpConfigured = self::isPhpConfigured();
            if ($isPhpConfigured !== true) {
                self::log($isPhpConfigured, Zend_Log::ERR, true);
                return $isPhpConfigured;
            } else {
                self::log(
                    'Configuration Validation Error : ' . $ere->getMessage(),
                    Zend_Log::ERR,
                    true
                );
            }

            return Constants::MESSAGE_SURETAX_NOT_CONFIGURED;
        }
    }

    /**
     *
     * @param mixed  $timeStart      must be microtime(true)
     * @param string $messagePrepend string describing what time elapsed is for
     */
    public static function doProfile($timeStart, $messagePrepend)
    {
        self::log(
            "Time elapsed (seconds) - $messagePrepend:" .
            (microtime(true) - $timeStart), Zend_Log::INFO, true
        );
    }

    /**
     * @param Exception $ex
     * @param string    $prependString
     */
    public static function logCatch($ex, $prependString)
    {
        // always log exceptions
        self::log(
            $prependString . '.  LogCatch: ' .
            $ex->getMessage() . '.  TRACE: ' .
            $ex->getTraceAsString(), Zend_Log::ERR, true
        );
    }

    /**
     *
     * @return array GLOBAL configuration in array form.
     */
    public static function getSureTaxConfigData()
    {
        $suretaxConfigData = Mage::getStoreConfig(Constants::SURETAX_CONFIG_PATH);
        $value = Mage::helper('core')->decrypt($suretaxConfigData['validationkey']);
        $suretaxConfigData['validationkey'] = $value;
        return $suretaxConfigData;
    }


    /**
     * Returns default ship from or website/store specific ship from
     *
     * @param int $websiteId
     * @param int $storeId
     *
     * @return array string holding ship from address info
     */
    public static function getShipFromAddress($websiteId, $storeId)
    {
        $webRow = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadWebsiteConfig($websiteId);
        if (!($webRow->getUseDefaultAddress() === null)) {
            $websiteFlag = $webRow->getUseDefaultAddress();
        } else {
            $websiteFlag = 1;
        }

        $storeRow = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadStoreConfig($websiteId, $storeId);
        if (!($storeRow->getUseDefaultAddress() === null)) {
            $storeFlag = $storeRow->getUseDefaultAddress();
        } else {
            $storeFlag = 1;
        }

        if ($storeFlag == 0) {
            $country = $storeRow->getCountry();
            $shipFromState = $storeRow->getStateProvince();
            $shipFromPostcode = $storeRow->getZipPostal();
            $shipFromAddressLine1 = $storeRow->getStreetAddress1();
            $shipFromAddressLine2 = $storeRow->getStreetAddress2();
            $shipFromCity = $storeRow->getCity();

        } else if ($websiteFlag == 0) {
            $country = $webRow->getCountry();
            $shipFromState = $webRow->getStateProvince();
            $shipFromPostcode = $webRow->getZipPostal();
            $shipFromAddressLine1 = $webRow->getStreetAddress1();
            $shipFromAddressLine2 = $webRow->getStreetAddress2();
            $shipFromCity = $webRow->getCity();

        } else {
            // get default ship-from-address from system
            $regionId = Mage::getStoreConfig('shipping/origin/region_id');
            // should be 2 digit iso code
            $country = Mage::getStoreConfig('shipping/origin/country_id');
            $shipFromState = self::getStateCodeFromRegionId($regionId);
            $shipFromPostcode = Mage::getStoreConfig('shipping/origin/postcode');
            $shipFromAddressLine1 = Mage::getStoreConfig('shipping/origin/street_line1');
            $shipFromAddressLine2 = Mage::getStoreConfig('shipping/origin/street_line2');
            $shipFromCity = Mage::getStoreConfig('shipping/origin/city');
        }

        // ship from is always scrubbed
        $verifyAddress = true;
        if (isset($country) && strcasecmp($country, 'US') !== 0) {
            $verifyAddress = false;
        }

        $shipFromAddress = array(
            'PrimaryAddressLine'=>$shipFromAddressLine1,
            'SecondaryAddressLine'=>$shipFromAddressLine2,
            'City'=>$shipFromCity,
            'State'=>$shipFromState,
            'PostalCode'=>$shipFromPostcode,
            'Plus4'=>"",
            'Country'=>$country,
            'VerifyAddress'=>$verifyAddress
        );

        return $shipFromAddress;
    }
    
    /**
     * Returns default ship from or website/store specific ship from
     *
     * @param int   $websiteId
     * @param int   $storeId
     * @param array $wsConfigArray 
     * 
     * @return array string holding ship from address info
     */
    public static function getShipFromAddressFromConfig($websiteId, $storeId, $wsConfigArray)
    {
        $websiteFlag = $wsConfigArray[$websiteId.':0'][Constants::USE_DEFAULT_ADDRESS];
        $storeFlag = $wsConfigArray[$websiteId.':'.$storeId][Constants::USE_DEFAULT_ADDRESS];

        if ($storeFlag == 0) {
            $country = $wsConfigArray[$websiteId.':'.$storeId][Constants::COUNTRY];
            $shipFromState = $wsConfigArray[$websiteId.':'.$storeId][Constants::STATE_PROVINCE];
            $shipFromPostcode = $wsConfigArray[$websiteId.':'.$storeId][Constants::ZIP_POSTAL];
            $shipFromAddressLine1 = $wsConfigArray[$websiteId.':'.$storeId][Constants::STREET_ADDRESS_1];
            $shipFromAddressLine2 = $wsConfigArray[$websiteId.':'.$storeId][Constants::STREET_ADDRESS_2];
            $shipFromCity = $wsConfigArray[$websiteId.':'.$storeId][Constants::CITY];

        } else if ($websiteFlag == 0) {
            $country = $wsConfigArray[$websiteId.':0'][Constants::COUNTRY];
            $shipFromState = $wsConfigArray[$websiteId.':0'][Constants::STATE_PROVINCE];
            $shipFromPostcode = $wsConfigArray[$websiteId.':0'][Constants::ZIP_POSTAL];
            $shipFromAddressLine1 = $wsConfigArray[$websiteId.':0'][Constants::STREET_ADDRESS_1];
            $shipFromAddressLine2 = $wsConfigArray[$websiteId.':0'][Constants::STREET_ADDRESS_2];
            $shipFromCity = $wsConfigArray[$websiteId.':0'][Constants::CITY];

        } else {
            // get default ship-from-address from system
            $regionId = Mage::getStoreConfig('shipping/origin/region_id');
            // should be 2 digit iso code
            $country = Mage::getStoreConfig('shipping/origin/country_id');
            $shipFromState = self::getStateCodeFromRegionId($regionId);
            $shipFromPostcode = Mage::getStoreConfig('shipping/origin/postcode');
            $shipFromAddressLine1 = Mage::getStoreConfig('shipping/origin/street_line1');
            $shipFromAddressLine2 = Mage::getStoreConfig('shipping/origin/street_line2');
            $shipFromCity = Mage::getStoreConfig('shipping/origin/city');
        }

        // ship from is always scrubbed
        $verifyAddress = true;
        if (isset($country) && strcasecmp($country, 'US') !== 0) {
            $verifyAddress = false;
        }

        $shipFromAddress = array(
            'PrimaryAddressLine'=>$shipFromAddressLine1,
            'SecondaryAddressLine'=>$shipFromAddressLine2,
            'City'=>$shipFromCity,
            'State'=>$shipFromState,
            'PostalCode'=>$shipFromPostcode,
            'Plus4'=>"",
            'Country'=>$country,
            'VerifyAddress'=>$verifyAddress
        );

        return $shipFromAddress;
    }

    /**
     * @param string $regionId
     * @return string
     */
    public static function getStateCodeFromRegionId($regionId)
    {
        $shipFromState = "";
        if ($regionId) {
            $region = Mage::getModel('directory/region')->load($regionId);
            $shipFromState = $region->getCode();
        }
        return $shipFromState;
    }

    /**
     * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest $request
     * @param type                                              $response
     *
     * @return array
     */
    public static function getLineItemLevelTaxByResponseWithRequest($request, $response)
    {
        $groups = isset($response->SoapRequestResult->GroupList->Group);
        // if not set, we have no tax.
        if (!$groups) {
            return null;
        }
        $groups = $response->SoapRequestResult->GroupList->Group;

        $items = $request->getItemList();

        $lineItemTaxArray = array();
        if (count($groups) > 0) {
            foreach ($groups as $group) {
                $jurisdictionArray = $group->TaxList->Tax;
                $taxAmount = 0;
                $revBase = 0;
                $rev = 0;
                foreach ($jurisdictionArray as $jurisdiction) {
                    $taxAmount = $taxAmount + $jurisdiction->TaxAmount;

                    if ($revBase == 0) {
                        $revBase = $jurisdiction->RevenueBase;
                    }

                    if ($rev == 0) {
                        $rev = $jurisdiction->Revenue;
                    }
                }
                $lineItemNumber = $group->LineNumber;
                $stateCode = $group->StateCode;
                $lineItemTaxArray[$lineItemNumber] = array(
                        'StateCode'=>$stateCode,
                        'RevenueBase'=>$revBase,
                        'Revenue'=>$rev,
                        'LineItemNumber'=>$lineItemNumber,
                        'LineItemTax'=>$taxAmount
                );
            }
        } else {

            foreach ($items as $item) {
                $rev = $item->Revenue;
                $lineItemTaxArray[$item->LineNumber] = array(
                    'RevenueBase'=>$rev,
                    'Revenue'=>$rev,
                    'LineItemNumber'=>$item->LineNumber,
                    'LineItemTax'=>0
                );
            }
        }

        return $lineItemTaxArray;
    }

    /**
     * for makeSureTaxRequestFromOrder() this returns an error
     * if Mage_Sales_Model_Quote_Address is used in method
     * declaration.
     *
     * @param  Mage_Sales_Model_Quote_Address $address
     * @return array with address data
     */
    public static function getShipToAddress($address)
    {
        $addressLine1 = "";
        if (array_key_exists(0, $address->getStreet(0))) {
            $addressLine1 = $address->getStreet(0)[0];
        }
        $addressLine2 = "";
        if (array_key_exists(1, $address->getStreet(0))) {
            $addressLine2 = $address->getStreet(0)[1];
        }
        $city = $address->getCity();
        $stateCode = $address->getRegionCode();

        $zip = $address->getPostcode();

        $country = $address->getCountryModel()->getIso2Code();

        // if we only have state + zip, then don't verify address.
        $tStateCode = trim($stateCode);
        $tZip = trim($zip);
        $verifyAddress =
                (
                    (trim($addressLine1) == false)
                    && (trim($city) == false)
                    && (trim($addressLine2) == false)
                    && ($tStateCode != false)
                    && ($tZip != false && strlen($tZip) == 5)
                ) == true ? false : true;
        if (isset($country) && strcasecmp($country, 'US') !== 0) {
            $verifyAddress = false;
        }

        $estimatedAddress = array(
            'PrimaryAddressLine'=>$addressLine1,
            'SecondaryAddressLine'=>$addressLine2,
            'City'=>$city,
            'State'=>$stateCode,
            'PostalCode'=>$zip,
            'Plus4'=>"",
            'Country'=>$country,
            'VerifyAddress'=>$verifyAddress);

        return $estimatedAddress;
    }

    /**
     * @param string $street
     * @param string $city
     * @param string $zip
     * @param string $stateCode
     * @param string $country
     * 
     * @return array $estimatedAddress
     */
    public static function getAddress($street, $city, $zip, $stateCode, $country)
    {
        $addressLine1 = "";
        $streetArray = array();
        if (!empty($street)) {
            $streetArray = explode("\n", $street);
        }
        if (array_key_exists(0, $streetArray)) {
            $addressLine1 = $streetArray[0];
        }
        $addressLine2 = "";
        if (array_key_exists(1, $streetArray)) {
            $addressLine2 = $streetArray[1];
        }
        // if we only have state + zip, then don't verify address.
        $tStateCode = trim($stateCode);
        $tZip = trim($zip);
        $verifyAddress =
            (
                (trim($addressLine1) == false)
                && (trim($city) == false)
                && (trim($addressLine2) == false)
                && ($tStateCode != false)
                && ($tZip != false && strlen($tZip) == 5)
            ) == true ? false : true;
        if (isset($country) && strcasecmp($country, 'US') !== 0) {
            $verifyAddress = false;
        }

        $estimatedAddress = array(
            'PrimaryAddressLine'=>$addressLine1,
            'SecondaryAddressLine'=>$addressLine2,
            'City'=>$city,
            'State'=>$stateCode,
            'PostalCode'=>$zip,
            'Plus4'=>"",
            'Country'=>$country,
            'VerifyAddress'=>$verifyAddress);

        return $estimatedAddress;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return array
     */
    public static function getCustomerBillingAddress($address)
    {
        $billingAddressQuote = $address->getQuote()->getBillingAddress();
        $addressLine1 = "";
        if (array_key_exists(0, $billingAddressQuote->getStreet(0))) {
            $addressLine1 = $billingAddressQuote->getStreet(0)[0];
        }
        $addressLine2 = "";
        if (array_key_exists(1, $billingAddressQuote->getStreet(0))) {
            $addressLine2 = $billingAddressQuote->getStreet(0)[1];
        }
        $city = $billingAddressQuote->getCity();
        $stateCode = $billingAddressQuote->getRegionCode();
        $zip = $billingAddressQuote->getPostcode();
        $country = $address->getCountryModel()->getIso2Code();
        $tStateCode = trim($stateCode);
        $tZip = trim($zip);
        $verifyAddress =
            (
                (trim($addressLine1) == false)
                && (trim($city) == false)
                && (trim($addressLine2) == false)
                && ($tStateCode != false)
                && ($tZip != false && strlen($tZip) == 5)
            ) == true ? false : true;
        if (isset($country) && strcasecmp($country, 'US') !== 0) {
            $verifyAddress = false;
        }

        $billingAddress = array(
            'PrimaryAddressLine'=>$addressLine1,
            'SecondaryAddressLine'=>$addressLine2,
            'City'=>$city,
            'State'=>$stateCode,
            'PostalCode'=>$zip,
            'Plus4'=>"",
            'Country'=>$country,
            'VerifyAddress'=>$verifyAddress);

        return $billingAddress;
    }

    /**
     *
     * @param int $websiteId
     * @param int $storeId
     *
     * @return int
     */
    public static function isSureTaxEnabled($websiteId, $storeId)
    {
        $sureTaxConfig = WoltersKluwer_CchSureTax_Helper_Config::get();
        $defaultFlag = $sureTaxConfig->isSuretaxEnabled();

        $row = Mage::getModel('wolterskluwer_cchsuretax/websitestore_config')
            ->getCollection()
            ->loadWebsiteConfig($websiteId);
        $websiteFlag = $row->getIsEnableCalc();

        $row = Mage::getModel('wolterskluwer_cchsuretax/websitestore_config')
            ->getCollection()
            ->loadStoreConfig($websiteId, $storeId);
        $storeFlag = $row->getIsEnableCalc();

        if ($storeFlag !=Constants::USE_WEBSITE_SETTINGS
        ) {
            return $storeFlag;
        } else if ($websiteFlag !=Constants::USE_GLOBAL_SETTINGS
        ) {
            return $websiteFlag;
        } else {
            return $defaultFlag;
        }
    }

    /**
     * Check to see if SoapClient is configured.
     *
     * @returns boolean | string
     */
    public static function isPhpConfigured()
    {
        if (!class_exists('SoapClient')) {
            return Constants::MESSAGE_PHP_NOT_CONFIGURED_SOAP;
        }
        if (!extension_loaded('openssl')) {
            return Constants::MESSAGE_PHP_NOT_CONFIGURED_SSL;
        }
        return true;
    }

    /**
     * SureTax supports USA and CANADA
     *
     * @param string $country 2 digit ISO uppercase + trimmed country code
     *
     * @returns boolean         true if supported country, else false
     */
    public static function isSureTaxSupportedCountry($country = null)
    {
        if ($country && array_key_exists(
            $country,
            Constants::$MAP_COUNTRIES_SUPPORTED
        )) {
            return true;
        }
        return false;
    }

    /**
     * Print out all incrementIds in the array separated by commas.
     *
     * @param array $incrementIds
     *
     * @return string
     */
    public static function displayIncrementIds($incrementIds)
    {
        return implode(", ", $incrementIds);
    }

    /**
     * @param type $response
     *
     * @return array
     */
    public static function getJurisdictionLevelTaxesFromSureTaxResponse($response)
    {
        $jurisdictionArray = array();

        if (!isset($response->SoapRequestResult->GroupList->Group) ) {
            return $jurisdictionArray;
        }

        $groups = $response->SoapRequestResult->GroupList->Group;

        foreach ($groups as $group) {
            $lineNumber = $group->LineNumber;
            $taxArray = $group->TaxList->Tax;
            $taxRate = 0.00;
            foreach ($taxArray as $jurisdiction) {
                $taxTypeCode = $jurisdiction->TaxTypeCode;
                $taxTypeDesc = $jurisdiction->TaxTypeDesc;

                if (is_numeric($lineNumber)) {
                    $taxRate = $jurisdiction->TaxRate;
                } else {
                    $taxRate = $taxRate + $jurisdiction->TaxRate;
                }

                $taxAuthorityName = $jurisdiction->TaxAuthorityName;
                $theKey = self::generateJurisdictionTitle(
                    $taxTypeDesc,
                    $taxAuthorityName,
                    $taxTypeCode
                );
                if (!array_key_exists($theKey, $jurisdictionArray)) {
                    $jurisdictionArray[$theKey] = $taxRate * 100;
                }
            }
        }
        return $jurisdictionArray;
    }
    
    /**
     * @param string $arrayKeys
     * @param string $id
     *
     * @return string
     */
    public static function checkIfIdExistsInArray($arrayKeys, $id)
    {
        foreach ($arrayKeys as $value) {
            if (strpos($value, $id) !== false) {
                return $value;
            } else {
                continue;
            }
        }
        return 'false';
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return string
     */
    public static function startsWith($haystack, $needle)
    {
        return 0 === strpos($haystack, $needle);
    }

    /**
     *
     * @param Mage_Sales_Model_Order | Mage_Sales_Model_Order_Creditmemo |
     * Mage_Sales_Model_Quote_Address $object
     * @return boolean
     */
    public static function isGiftWrap($object)
    {
        if (self::isEnterprise() && $object->getGwBasePrice()) {
            return true;
        }
        return false;
    }

    /**
     * Quote, Address, Order, and Credit memos
     * not at line item level
     *
     * @param  type $object
     * @return boolean
     */
    public static function isGiftWrapCard($object)
    {
        if (self::isEnterprise() && $object->getGwCardBasePrice()) {
            return true;
        }
        return false;
    }

    /**
     * Assumes only 1 unique jurisdction (TaxAuth and Taxtype combo) per line
     *
     * Totals taxes for each jurisdiction level
     *
     * @param  $response     SureTax SOAPRequest method's API response
     * @return array
     *  index are from generateJurisdictionKey() method
     *  each index holds:
     *  TaxTypeCode
     *  TaxTypeDesc
     *  TaxAmount
     *  TaxRate
     *  TaxAuthorityName
     *  RevenueBase
     *  Revenue
     */
    public static function getJurisdictionLevelTaxesTotaledFromResponse($response)
    {
        $jurisdictionArray = array();
        if (!isset($response->SoapRequestResult->GroupList->Group) ) {
            return $jurisdictionArray;
        }

        $groups = $response->SoapRequestResult->GroupList->Group;

        foreach ($groups as $group) {
            $lineNumber = $group->LineNumber;
            $taxArray = $group->TaxList->Tax;

            foreach ($taxArray as $jurisdiction) {
                $taxTypeCode = $jurisdiction->TaxTypeCode;
                $taxAuthId = $jurisdiction->TaxAuthorityID;
                $taxTypeDesc = $jurisdiction->TaxTypeDesc;
                $taxAmount = $jurisdiction->TaxAmount;
                $taxAuthorityName = $jurisdiction->TaxAuthorityName;
                $revenue = $jurisdiction->Revenue;
                $taxRate = $jurisdiction->TaxRate;

                $calcRate = round(
                    ($taxAmount / $revenue),
                    Constants::PRECISION_DIGITS,
                    PHP_ROUND_HALF_UP
                );
                if ($calcRate != $taxRate ) {
                    $taxRate = $taxAmount / $revenue;
                }
                $taxRate *= 100;
                $theKey = self::generateJurisdictionKey($taxAuthId, $taxTypeCode);
                if (array_key_exists($theKey, $jurisdictionArray)) {
                    $currentJ = $jurisdictionArray[$theKey];
                    $revenue += $currentJ['Revenue'];
                    $taxAmount += $currentJ['TaxAmount'];
                    if ($taxRate != $currentJ['TaxRate']) {
                        $taxRate = (round(
                            ($taxAmount / $revenue),
                            Constants::PRECISION_DIGITS,
                            PHP_ROUND_HALF_UP
                        )) * 100;
                    }
                    $jurisdictionArray[$theKey]['TaxAmount'] = $taxAmount;
                    $jurisdictionArray[$theKey]['TaxRate'] = $taxRate;
                    $jurisdictionArray[$theKey]['Revenue'] = $revenue;
                } else {
                    $jurisdictionArray[$theKey] = array(
                        'LineNumber'=>$lineNumber,
                        'TaxTypeCode'=>$taxTypeCode,
                        'TaxTypeDesc'=>$taxTypeDesc,
                        'TaxAmount'=>$taxAmount,
                        'TaxRate'=>$taxRate,
                        'TaxAuthorityID'=>$taxAuthId,
                        'TaxAuthorityName'=>$taxAuthorityName,
                        'Revenue'=>$revenue
                    );
                }
            }
        }
        return $jurisdictionArray;
    }

    public static function generateJurisdictionKey($taxAuthId, $taxTypeCode)
    {
        return $taxAuthId . ':' . $taxTypeCode;
    }

    /**
     *
     * @param $taxTypeDesc
     * @param $taxAuthorityName
     * @param $taxTypeCode
     *
     * above are from SOAP response
     *  TaxTypeCode
     *  TaxTypeDesc
     *  TaxAuthorityName
     *
     * @return string
     */
    public static function generateJurisdictionTitle($taxTypeDesc = '',
        $taxAuthorityName = '', $taxTypeCode = ''
    ) {
    

        $taxTypeAppend = ', ' . $taxTypeDesc;
        if (!$taxTypeDesc) {
            $taxTypeAppend = ' (TaxType: ' . $taxTypeCode . ')';
        }
        return self::getShortTaxAuthNameForTitle($taxAuthorityName) .
            $taxTypeAppend;
    }

    /**
     * tax authority names are typically in this format:
     * <NAME>, <something> Of
     * knowing the above, we can shorten this by using the 1st token using ','
     *
     * @param  $taxAuthName
     * @return string
     */
    public static function getShortTaxAuthNameForTitle($taxAuthName = null)
    {
        $retval = strtok($taxAuthName, ",");
        return (($retval == null) ? 'Unknown' : ($retval));
    }

    /**
     * @param array                 $jurisdictionsArray array
     *                              generated by
     *                              self::getJurisdictionLevelTaxesTotaledFromResponse()
     * @param Mage_Core_Model_Store $store
     * @return applied taxes array used to display taxes details
     *         (e.g. jurisdiction level)
     */
    public static function generateAppliedTaxesArray($jurisdictionsArray, $store)
    {
        $appliedTaxesArray = null;
        if (self::isEnabledTaxDisplay()) {
            foreach ($jurisdictionsArray as $jRate) {
                // 1 jursdiction tax
                $taxAmount = $jRate['TaxAmount'];
                $taxAmountStore = $store->convertPrice($taxAmount);
                $taxRate = round(
                    $jRate['TaxRate'],
                    Constants::PRECISION_DIGITS_FOR_DISPLAY,
                    PHP_ROUND_HALF_UP
                );
                $title = self::generateJurisdictionTitle(
                    $jRate['TaxTypeDesc'],
                    $jRate['TaxAuthorityName'],
                    $jRate['TaxTypeCode']
                );

                $oneRate = array(
                    'code' => $title,
                    'title' => $title,
                    'percent' => $taxRate,
                    'position' => '0',
                    'priority' => '0',
                    'amount'=> $taxAmountStore,
                    'base_amount' => $taxAmount,
                    'rule_id' => '',
                    'hidden' => 0
                );

                $appliedTaxesArray[] =  array(
                  'amount' => $taxAmountStore,
                  'id' => '',
                  'process' => '',
                  'percent' => $taxRate,
                  'base_amount' => $taxAmount,
                  'base_real_amount' => $taxAmount,
                  'rates' =>array( $oneRate) );
            }
        }
        return $appliedTaxesArray;
    }

    public static function isEnabledTaxDisplay()
    {
        return Mage::getStoreConfigFlag('tax/sales_display/full_summary');
        //        if (Mage::getStoreConfig('tax/sales_display/full_summary')) {
        //            return true;
        //        } else {
        //            return false;
        //        }
    }

    public static function isEnterprise()
    {
        if (!isset(self::$_isEnterprise)) {
            self::$_isEnterprise = Mage::helper('core')
                ->isModuleEnabled('Enterprise_Enterprise');
            // only for CE 1.7+
        }
        return self::$_isEnterprise;
    }

    public static function getVersionString()
    {
        if (!isset(self::$version)) {
            $eeS = (self::isEnterprise() ? 'EE ' : 'CE ');
            self::$version = 'Magento ' . $eeS .
                Mage::getVersion() .
                ' Extension v ' .
                ((string)Mage::getConfig()
                    ->getNode()->modules->WoltersKluwer_CchSureTax->version);
        }
        return self::$version;
    }

    // Admin utils
    public static function addSuccessAdmin($theNum, $appendMsg)
    {
        Mage::getSingleton('adminhtml/session')->addSuccess(
            count($theNum) . $appendMsg .
            self::displayIncrementIds($theNum)
        );

    }

    public static function addNoticeAdmin($theNum, $appendMsg)
    {
        Mage::getSingleton('adminhtml/session')->addNotice(
            count($theNum) . $appendMsg .
            self::displayIncrementIds($theNum)
        );

    }

    public static function addErrorAdmin($theNum, $appendMsg)
    {
        Mage::getSingleton('adminhtml/session')->addError(
            count($theNum) . $appendMsg .
            self::displayIncrementIds($theNum)
        );

    }

    public static function processAdminMessagesForOrder($successSuretaxOrderIds,
        $successCanceledSuretaxOrderIds, $suretaxConfigDisabledForIds,
        $noticeSuretaxOrderIds, $noticeCanceledSuretaxOrderIds,
        $errorSuretaxOrderIds, $errorCanceledSuretaxOrderIds,
        $outofUSAndCANOrderIds
    ) {
    

        if (count($successSuretaxOrderIds)) {
            self::addSuccessAdmin(
                $successSuretaxOrderIds,
                ' order(s) have been successfully finalized : '
            );
        }
        if (count($successCanceledSuretaxOrderIds)) {
            self::addSuccessAdmin(
                $successCanceledSuretaxOrderIds,
                ' order(s) have been successfully canceled : '
            );
        }
        if (count($suretaxConfigDisabledForIds)) {
            self::addNoticeAdmin(
                $suretaxConfigDisabledForIds,
                " order(s) cannot be finalized because SureTax calculation is disabled : "
            );
        }
        if (count($noticeSuretaxOrderIds)) {
            self::addNoticeAdmin(
                $noticeSuretaxOrderIds,
                " order(s) are already finalized : "
            );
        }
        if (count($noticeCanceledSuretaxOrderIds)) {
            self::addNoticeAdmin(
                $noticeCanceledSuretaxOrderIds,
                " order(s) are already canceled : "
            );
        }
        if (count($errorSuretaxOrderIds)) {
            self::addErrorAdmin(
                $errorSuretaxOrderIds,
                " order(s) cannot be finalized due to an error from SureTax : "
            );
        }
        if (count($errorCanceledSuretaxOrderIds)) {
            self::addErrorAdmin(
                $errorCanceledSuretaxOrderIds,
                " order(s) cannot be canceled due to an error from SureTax : "
            );
        }
        if (count($outofUSAndCANOrderIds)) {
            self::addErrorAdmin(
                $outofUSAndCANOrderIds,
                " order(s) cannot be finalized as SureTax does not support its ship-to-address country : "
            );
        }
    }
    
    /**
     * 
     * @param array $wsConfigArray
     * @param int   $websiteId
     * @param i     $storeId
     * @return array
     */
    public static function filterWebsiteStoreArray($wsConfigArray, $websiteId, $storeId)
    {
        $wsConfigFilteredArray[$websiteId.':0'] = $wsConfigArray[$websiteId.':0'];
        $wsConfigFilteredArray[$websiteId.':'.$storeId] = $wsConfigArray[$websiteId.':'.$storeId];
        return $wsConfigFilteredArray;
    }
    /**
     * 
     * @param WoltersKluwer_CchSureTax_Helper_Config $config
     * @param int                                    $websiteId
     * @param int                                    $storeId
     * @param array                                  $wsConfigArray
     * @return int
     */
    public static function getBusinessUnit($config, $websiteId, $storeId, $wsConfigArray)
    {
        $defaultBusinessUnit = $config->getBusinessUnit();
        $websiteFlag = $wsConfigArray[$websiteId.':0'][Constants::USE_BUSINESS_UNIT];
        $websiteBusinessUnit = $wsConfigArray[$websiteId.':0'][Constants::BUSINESS_UNIT];
        $storeFlag = $wsConfigArray[$websiteId.':'.$storeId][Constants::USE_BUSINESS_UNIT];
        $storeBusinessUnit = $wsConfigArray[$websiteId.':'.$storeId][Constants::BUSINESS_UNIT];        
        
        if ($storeFlag == 0) {
            return isset($storeBusinessUnit) ? $storeBusinessUnit : '';
        } else if ($websiteFlag == 0) {
            return isset($websiteBusinessUnit) ? $websiteBusinessUnit : '';
        } else {
            return isset($defaultBusinessUnit) ? $defaultBusinessUnit : '';
        }
    }
    
    /**
     * 
     * @param int   $storeId
     * @param array $wsConfigArray
     * @return string
     */
    public static function getWebsiteIdFromStoreId($storeId, $wsConfigArray)
    {
        foreach ($wsConfigArray as $key => $value) {
            $keySplit = explode(":", $key);
            if (in_array($storeId, $keySplit)) {
                return $keySplit[0];
            }
        }
    }
}
