<?php
/**
 * Holds all constants for central access.
 * Include messages.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Helper_Constants extends Mage_Core_Helper_Abstract
{
    // names
    const LOG_FILENAME = 'suretax.log';

    const SOAP_CALL_TIMEOUT = 60;

    // data constants
    // 990101 is General Sales Trans Type Code
    const DEFAULT_TRANS_TYPE_CODE = '990101';
    const DEFAULT_TRANS_TYPE_CODE_SHIPPING = '990101';
    const DEFAULT_TRANS_TYPE_CODE_GIFT_WRAP = '990101';
    const PRECISION_DIGITS_FOR_DISPLAY = 4;
    const PRECISION_DIGITS = 8;

    // for UDF field
    const DEFAULT_SHIPPING_SKU_NAME = 'Shipping';
    const DEFAULT_GW_SKU_NAME = 'Gift Wrap';
    const DEFAULT_GW_CARD_SKU_NAME = 'Gift Wrap Card';
    const DEFAULT_SHIPPING_LINE_NUMBER = 'Shipping';
    const DEFAULT_GW_LINE_NUMBER_PREPEND = "GWRAP";
    const DEFAULT_GW_CARD_LINE_NUMBER = "GWCARD";
    const DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL = "GIFTORDER";
    const DEFAULT_SITUS = '22';
    const DEFAULT_SITUS_DESTINATION = '23';
    const DEFAULT_SITUS_ORIGIN = '24';

    //Tax Summary Names
    const SHIPPING_TAX = 'Shipping & Handling Tax';
    const GW_ORDER_TAX = 'Gift Wrap Order Tax';
    const GW_ITEMS_TAX = 'Gift Wrap Items Tax';
    const GW_CARD_TAX = 'Gift Card Tax';

    public static $TAX_SUMMARY_ARRAY = array(
        'Shipping & Handling Tax',
        'Gift Wrap Order Tax',
        'Gift Wrap Items Tax',
        'Gift Card Tax'
    );

    // API related
    // line numbers start with 1, 2, ...
    const LINE_NUMBER_START_INDEX = 1;
    const API_RESPONSE_CODE_SUCCESS = '9999';

    // ws constants
    const VALIDATE_CONFIG_INFO = 'Validate Configuration';

    // messages
    const MESSAGE_INVALID_SHIPPING_CLASS = 'SureTax: Shipping class is invalid or not set, shipping class reset to default (General Sales).  Shipping class value: ';
    const MESSAGE_INVALID_GIFT_WRAP_CLASS = 'SureTax: Gift Wrap class is invalid or not set, Gift Wrap class reset to default (General Sales).  Gift Wrap class value: ';
    const MESSAGE_CONFIG_MISSING_ARGUMENTS = 'SureTax: Config Missing Arguments: client number, validation key, or endpoint URL.';
    const MESSAGE_PHP_NOT_CONFIGURED_SOAP = 'SureTax Error: Your PHP configuration (php.ini) must have the following PHP extension enabled: soap (php_soap.dll).  See http://www.php.net/ for details.';
    const MESSAGE_PHP_NOT_CONFIGURED_SSL = 'SureTax Error: Your PHP configuration must have SSL enabled.  Typically, this is done by enabling openssl extension (php_openssel.ddl) in your PHP configuration file (php.ini).  See http://www.php.net/ for details.';
    const MESSAGE_CANT_SAVE_CREDIT = 'SureTax Error: Cannot save credit memo.';
    const MESSAGE_SURETAX_NOT_CONFIGURED = 'SureTax Error: Unable to validate configuration: Please check SureTax URL, Client Number, and Validation Key';
    // tables
    const GEN_CONFIG_TBL = 'wolterskluwer_cchsuretax/info';
    const WS_CONFIG_TBL = 'wolterskluwer_cchsuretax/websitestore_config';
    const ORDER_TBL = 'wolterskluwer_cchsuretax/order';
    const INVOICE_TBL = 'wolterskluwer_cchsuretax/invoice';
    const CREDIT_TBL = 'wolterskluwer_cchsuretax/creditmemo';

    const CUST_TBL = 'wolterskluwer_cchsuretax/customer';
    const CUST_GRP_TBL = 'wolterskluwer_cchsuretax/customergroup';
    
    // considered constant
    // Batch Finalize Allowed Status
    public static $BATCH_STATUS_ALLOW = array(
        'Finalize_Fail', 'Cancel_Fail'
    );

    const USE_WEBSITE_SETTINGS = -2;
    const USE_GLOBAL_SETTINGS = -1;
    const NO = 0;
    const YES = 1;
    
    const ENABLE_CALC = 'is_enable_calc';
    const WEBSITE_ID = 'website_id';
    const STORE_ID = 'store_id';
    const USE_BUSINESS_UNIT = 'use_business_unit';
    const BUSINESS_UNIT = 'business_unit';
    const USE_DEFAULT_ADDRESS = 'use_default_address';
    const STREET_ADDRESS_1 = 'street_address1';
    const STREET_ADDRESS_2 = 'street_address2';
    const CITY = 'city';
    const COUNTRY = 'country';
    const STATE_PROVINCE = 'state_province';
    const ZIP_POSTAL = 'zip_postal';

    const BUSINESS_UNIT_MAX_LENGTH = 20;

    // considered constant
    public static $SALES_TYPE_CODES = array(
        'Residential customer' => 'Residential customer',
        'Business customer' => 'Business customer',
        'Industrial customer' => 'Industrial customer',
        'Lifeline customer' => 'Lifeline customer'
    );

    // considered constant
    public static $EXEMPTION_TYPE_CODES = array(
        'None' => 'None',
        'All Taxes Exempt – Apply no tax or fees' =>
            'All Taxes Exempt – Apply no tax or fees'
    );

    // considered constant
    public static $EXEMPTION_REASON_CODES = array(
        'None' => 'None',
        'Federal government' => 'Federal government',
        'State or local government' => 'State or local government',
        'Tribal government' => 'Tribal government',
        'Foreign diplomat' => 'Foreign diplomat',
        'Charitable organization' => 'Charitable organization',
        'Religious or educational organization' =>
            'Religious or educational organization',
        'Resale' => 'Resale',
        'Agricultural production' => 'Agricultural production',
        'Industrial production/manufacturing' =>
            'Industrial production/manufacturing',
        'Direct pay permit' => 'Direct pay permit',
        'Direct mail' => 'Direct mail',
        'Other' => 'Other'
    );

    // considered constant
    public static $SALES_TYPE_CODES_VALUES = array(
        'Residential customer' => 'R',
        'Business customer' => 'B',
        'Industrial customer' => 'I',
        'Lifeline customer' => 'L'
    );

    // considered constant
    public static $EXEMPTION_TYPE_CODES_VALUES = array(
        'None' => '',
        'All Taxes Exempt – Apply no tax or fees' => '99'
    );

    // considered constant
    public static $EXEMPTION_REASON_CODES_VALUES = array(
        'None' => '',
        'Federal government' => '01',
        'State or local government' => '02',
        'Tribal government' => '03',
        'Foreign diplomat' => '04',
        'Charitable organization' => '05',
        'Religious or educational organization' => '06',
        'Resale' => '07',
        'Agricultural production' => '08',
        'Industrial production/manufacturing' => '09',
        'Direct pay permit' => '10',
        'Direct mail' => '11',
        'Other' => '12'
    );

    // considered constant
    // holds supported countries in 2 digit ISO code, only supported countries
    // should be in here
    public static $MAP_COUNTRIES_SUPPORTED = array (
        'US' => true,
        'CA' => true,
        'PR' => true,
        'VI' => true,
        'AS' => true,
        'FM' => true,
        'GU' => true,
        'MH' => true,
        'MP' => true,
        'PW' => true,
    );

    const DEFAULT_SALES_TYPE_CODE = 'Residential customer';
    const DEFAULT_EXEMPTION_CODE = 'None';
    const DEFAULT_EXEMPTION_REASON_CODE = 'None';

    const SALES_TYPE_CODE_KEY = 'SalesTypeCode';
    const EXEMPTION_TYPE_CODE_KEY = 'ExemptionCode';
    const EXEMPTION_REASON_CODE_KEY = 'ExemptionReasonCode';

    const MAXIMUM_NUMBER_OF_ORDERS_TO_PROCESS = 20;
    const MAXIMUM_NUMBER_OF_INVOICES_TO_PROCESS = 20;

    const FINALIZED_STATUS = 'Finalized';
    const FINALIZE_FAIL_STATUS = 'Finalize_Fail';
    const FINALIZE_ADJUSTED = 'Finalize_Adjusted';
    const FINALIZE_PAYMENT_REQUIRED = 'Finalize_Payment_Required';
    const ADJUSTMENT_FAIL = 'Adjustment_Fail';
    
    public static $SURETAX_STATUS = array (
        'N/A'=>'N/A',
        self::FINALIZED_STATUS => self::FINALIZED_STATUS,
        self::FINALIZE_FAIL_STATUS => self::FINALIZE_FAIL_STATUS,
        self::FINALIZE_ADJUSTED => self::FINALIZE_ADJUSTED,
        self::FINALIZE_PAYMENT_REQUIRED => self::FINALIZE_PAYMENT_REQUIRED,
        self::ADJUSTMENT_FAIL => self::ADJUSTMENT_FAIL
    );

    const RESP_CODE_CANCEL_GT_60 = '1510';

    public static function generateGiftWrapLineNumber($num)
    {
        return WoltersKluwer_CchSureTax_Helper_Constants::
            DEFAULT_GW_LINE_NUMBER_PREPEND . $num;
    }
    
    // string keys
    const CONF_VALIDATION_KEY = 'validationkey';
    
    const SURETAX_CONFIG_PATH = 'wk/cch/suretax/global/config';
}
