<?php
/**
 * Represents item XML for SureTax web service requests.
 *
 * @category  SureTax
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Model_Ws_SureTaxItem
    extends Mage_Core_Model_Abstract
{
    /**
    *
    * @var string $LineNumber
    * @access public
    */
    public $LineNumber = null;

    /**
    *
    * @var string $InvoiceNumber
    * @access public
    */
    public $InvoiceNumber = null;

    /**
    *
    * @var string $CustomerNumber
    * @access public
    */
    public $CustomerNumber = null;

    /**
    *
    * @var string $LocationCode
    * @access public
    */
    public $LocationCode = null;

    /**
    *
    * @var string $BillToNumber
    * @access public
    */
    public $BillToNumber = null;

    /**
    *
    * @var string $OrigNumber
    * @access public
    */
    public $OrigNumber = null;

    /**
    *
    * @var string $TermNumber
    * @access public
    */
    public $TermNumber = null;

    /**
    *
    * @var string $TransDate
    * @access public
    */
    public $TransDate = null;

    /**
    *
    * @var float $Revenue
    * @access public
    */
    public $Revenue = null;

    /**
    *
    * @var string $TaxIncludedCode
    * @access public
    */
    public $TaxIncludedCode = null;

    /**
    *
    * @var float $Units
    * @access public
    */
    public $Units = null;

    /**
    *
    * @var string $UnitType
    * @access public
    */
    public $UnitType = null;

    /**
    *
    * @var int $Seconds
    * @access public
    */
    public $Seconds = null;

    /**
    *
    * @var string $TaxSitusRule
    * @access public
    */
    public $TaxSitusRule = null;

    /**
    * for current version, all API calls expected to be in USD due to use of
    *  base currency
    *
    * @var    string $CurrencyCode
    * @access public
    */
    public $CurrencyCode = 'USD';

    /**
    * not used
    *
    * @var    string $TaxSitusOverrideCode
    * @access public
    */
    //  public $TaxSitusOverrideCode = null;

    /**
    * SureTax TransTypeCode OR Magento Product Tax Class
    *
    * @var    string $TransTypeCode
    * @access public
    */
    public $TransTypeCode = null;

    /**
    *
    * @var string $SalesTypeCode
    * @access public
    */
    public $SalesTypeCode = null;

    /**
    *
    * @var string $RegulatoryCode
    * @access public
    */
    public $RegulatoryCode = null;

    /**
    *
    * @var String[] $TaxExemptionCodeList
    * @access public
    */
    public $TaxExemptionCodeList = null;

    /**
    * Hold's magento 'SKU' info for products
    *
    * @var    string $UDF
    * @access public
    */
    public $UDF = '';

    /**
    * set system info for system support
    *
    * @var    string $UDF2
    * @access public
    */
    public $UDF2 = '';

    /**
    * not used
    *
    * @var    string $FreightOnBoard
    * @access public
    */
    //  public $FreightOnBoard = null;

    /**
    * do not change.
    *
    * @var    boolean                Always true (1 = default/yes)
    * @access public
    */
    public $ShipFromPOB = 1;

    /**
    * do not change
    *
    * @var    boolean $MailOrder     Always 1.
    * @access public
    */
    public $MailOrder = 1;

    /**
    *
    * @var boolean $CommonCarrier Always 1
    * @access public
    */
    public $CommonCarrier = 1;

    /**
    * don't use for gen sales
    *
    * @var    string $OriginCountryCode
    * @access public
    */
    //  public $OriginCountryCode = null;

    /**
    *
    * @var string $DestCountryCode
    * @access public
    */
    //  public $DestCountryCode = null;

    /**
    *
    * @var float $AuxRevenue     Always 0
    * @access public
    */
    public $AuxRevenue = 0;

    /**
    *
    * @var string $AuxRevenueType    Always 01.
    * @access public
    */
    public $AuxRevenueType = '01';

    /**
    *
    * @var int $BillingDaysInPeriod
    * @access public
    */
    public $BillingDaysInPeriod = '';

    /**
    *
    * @var string $CostCenter
    * @access public
    */
    public $CostCenter = null;

    /**
    *
    * @var string $GLAccount
    * @access public
    */
    public $GLAccount = null;

    /**
    *
    * @var string $MaterialGroup
    * @access public
    */
    //  public $MaterialGroup = null;

    //  /**
    //    *
    //    * @var string $Parameter1
    //    * @access public
    //    */
    //  public $Parameter1 = null;
    //

    /**
    *
    * @var string $ExemptReasonCode
    * @access public
    */
    public $ExemptReasonCode = null;

    /**
    *
    * @var WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $BillingAddress
    * @access public
    */
    public $BillingAddress = null;

    //  /**
    //    *
    //    * @var SureTaxAddress $P2PAddress
    //    * @access public
    //    */
    //  public $P2PAddress = null;

    /**
    *
    * @var WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $ShipToAddress
    * @access public
    */
    public $ShipToAddress = null;

    /**
    *
    * @var WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $ShipFromAddress
    * @access public
    */
    public $ShipFromAddress = null;

    /**
    * @param array                                             $data
    * @param string                                            $data['LineNumber']
    * @param string                                            $data['InvoiceNumber']
    * @param string                                            $data['CustomerNumber']
    * @param string                                            $data['LocationCode']
    * @param string                                            $data['BillToNumber']
    * @param string                                            $data['OrigNumber']
    * @param string                                            $data['TermNumber']
    * @param string                                            $data['TransDate']
    * @param float                                             $data['Revenue']
    * @param string                                            $data['TaxIncludedCode']
    * @param float                                             $data['Units']
    * @param string                                            $data['UnitType']
    * @param string                                            $data['TaxSitusRule']
    * @param string                                            $data['TransTypeCode']
    * @param string                                            $data['SalesTypeCode']
    * @param string                                            $data['RegulatoryCode']
    * @param String[]                                          $data['TaxExemptionCodeList']
    * @param string                                            $data['CostCenter']
    * @param string                                            $data['GLAccount']
    * @param string                                            $data['ExemptReasonCode']
    * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $data['BillingAddress']
    * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $data['ShipToAddress']
    * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $data['ShipFromAddress']
    * @param string                                            $data['UDF']
    * @access public
    */  
    public function __construct(array $data)
    {
        $this->LineNumber           = $data['LineNumber'];
        $this->InvoiceNumber        = $data['InvoiceNumber'];
        $this->CustomerNumber       = $data['CustomerNumber'];
        $this->LocationCode         = $data['LocationCode'];
        $this->BillToNumber         = $data['BillToNumber'];
        $this->OrigNumber           = $data['OrigNumber'];
        $this->TermNumber           = $data['TermNumber'];
        $this->TransDate            = $data['TransDate'];
        $this->Revenue              = $data['Revenue'];
        $this->TaxIncludedCode      = $data['TaxIncludedCode'];
        $this->Units                = $data['Units'];
        $this->UnitType             = $data['UnitType'];
        $this->Seconds              = 0;
        $this->TaxSitusRule         = $data['TaxSitusRule'];
        $this->TransTypeCode        = $data['TransTypeCode'];
        $this->SalesTypeCode        = $data['SalesTypeCode'];
        $this->RegulatoryCode       = $data['RegulatoryCode'];
        $this->TaxExemptionCodeList = $data['TaxExemptionCodeList'];
        $this->CostCenter           = $data['CostCenter'];
        $this->GLAccount            = $data['GLAccount'];
        $this->ExemptReasonCode     = $data['ExemptReasonCode'];
        //$this->BillingAddress     = $data['BillingAddress'];
        //$this->ShipToAddress      = $data['ShipToAddress'];
        //$this->ShipFromAddress    = $data['ShipFromAddress'];      
        $this->UDF                  = $data['UDF']; // holds 'SKU'
        $this->UDF2                 = WoltersKluwer_CchSureTax_Helper_Utility::getVersionString();
    }
    
    public function _construct()
    {
        $this->_init('wolterskluwer_cchsuretax/ws_sureTaxItem');
    }
}
