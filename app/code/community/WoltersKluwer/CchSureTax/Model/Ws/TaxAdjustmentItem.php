<?php
/*
 * Represents tax adjustment item XML for SureTax web service requests.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Model_Ws_TaxAdjustmentItem
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
   * @var string $BillingPeriodStartDate
   * @access public
   */
    public $BillingPeriodStartDate = null;

    /**
   *
   * @var string $BillingPeriodEndDate
   * @access public
   */
    public $BillingPeriodEndDate = null;

    /**
   *
   * @var float $Revenue
   * @access public
   */
    public $Revenue = null;

    /**
   *
   * @var float $Tax
   * @access public
   */
    public $Tax = null;

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
   * @var string $TaxSitusRule
   * @access public
   */
    public $TaxSitusRule = null;

    /**
   *
   * @var string $TaxSitusOverrideCode
   * @access public
   */
    public $TaxSitusOverrideCode = null;

    /**
   *
   * @var string $TransTypeCode
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
   *
   * @var string $UDF
   * @access public
   */
    public $UDF = null;

    /**
   *
   * @var string $UDF2
   * @access public
   */
    public $UDF2 = null;

    /**
   *
   * @var string $FreightOnBoard
   * @access public
   */
    public $FreightOnBoard = null;

    /**
   *
   * @var boolean $ShipFromPOB
   * @access public
   */
    public $ShipFromPOB = null;

    /**
   *
   * @var boolean $MailOrder
   * @access public
   */
    public $MailOrder = null;

    /**
   *
   * @var boolean $CommonCarrier
   * @access public
   */
    public $CommonCarrier = null;

    /**
   *
   * @var string $OriginCountryCode
   * @access public
   */
    public $OriginCountryCode = null;

    /**
   *
   * @var string $DestCountryCode
   * @access public
   */
    public $DestCountryCode = null;

    /**
   *
   * @var int $BillingDaysInPeriod
   * @access public
   */
    public $BillingDaysInPeriod = null;

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
    public $MaterialGroup = null;

    /**
   *
   * @var string $Parameter1
   * @access public
   */
    public $Parameter1 = null;

    /**
   *
   * @var string $Parameter2
   * @access public
   */
    public $Parameter2 = null;

    /**
   *
   * @var string $Parameter3
   * @access public
   */
    public $Parameter3 = null;

    /**
   *
   * @var string $Parameter4
   * @access public
   */
    public $Parameter4 = null;

    /**
   *
   * @var string $Parameter5
   * @access public
   */
    public $Parameter5 = null;

    /**
   *
   * @var string $Parameter6
   * @access public
   */
    public $Parameter6 = null;

    /**
   *
   * @var string $Parameter7
   * @access public
   */
    public $Parameter7 = null;

    /**
   *
   * @var string $Parameter8
   * @access public
   */
    public $Parameter8 = null;

    /**
   *
   * @var string $Parameter9
   * @access public
   */
    public $Parameter9 = null;

    /**
   *
   * @var string $Parameter10
   * @access public
   */
    public $Parameter10 = null;

    /**
   *
   * @var string $RuleOverride
   * @access public
   */
    public $RuleOverride = null;

    /**
   *
   * @var string $CurrencyCode
   * @access public
   */
    public $CurrencyCode = 'USD';

    /**
   *
   * @var string $ExemptReasonCode
   * @access public
   */
    public $ExemptReasonCode = null;

    /**
   *
   * @var Address $BillingAddress
   * @access public
   */
    public $BillingAddress = null;

    /**
   *
   * @var Address $P2PAddress
   * @access public
   */
    public $P2PAddress = null;

    /**
   *
   * @var Address $ShipToAddress
   * @access public
   */
    public $ShipToAddress = null;

    /**
   *
   * @var Address $ShipFromAddress
   * @access public
   */
    public $ShipFromAddress = null;

    /**
   *
   * @var Address $OrderPlacementAddress
   * @access public
   */
    public $OrderPlacementAddress = null;

    /**
   *
   * @var Address $OrderApprovalAddress
   * @access public
   */
    public $OrderApprovalAddress = null;

    /**
   *
   * @var TaxAdjustmentTax[] $TaxAdjustmentTaxList
   * @access public
   */
    public $TaxAdjustmentTaxList = null;

    /**
   *
   * @param string             $lineNumber
   * @param string             $invoiceNumber
   * @param string             $customerNumber
   * @param string             $locationCode
   * @param string             $billToNumber
   * @param string             $origNumber
   * @param string             $termNumber
   * @param string             $transDate
   * @param string             $BillingPeriodStartDate
   * @param string             $BillingPeriodEndDate
   * @param float              $Revenue
   * @param float              $tax
   * @param float              $units
   * @param string             $unitType
   * @param string             $taxSitusRule
   * @param string             $TaxSitusOverrideCode
   * @param string             $transTypeCode
   * @param string             $salesTypeCode
   * @param string             $regulatoryCode
   * @param String[]           $taxExemptionCodeList
   * @param string             $uDF
   * @param string             $uDF2
   * @param string             $FreightOnBoard
   * @param boolean            $ShipFromPOB
   * @param boolean            $MailOrder
   * @param boolean            $CommonCarrier
   * @param string             $OriginCountryCode
   * @param string             $DestCountryCode
   * @param int                $BillingDaysInPeriod
   * @param string             $costCenter
   * @param string             $gLAccount
   * @param string             $MaterialGroup
   * @param string             $Parameter1
   * @param string             $Parameter2
   * @param string             $Parameter3
   * @param string             $Parameter4
   * @param string             $Parameter5
   * @param string             $Parameter6
   * @param string             $Parameter7
   * @param string             $Parameter8
   * @param string             $Parameter9
   * @param string             $Parameter10
   * @param string             $RuleOverride
   * @param string             $currencyCode
   * @param string             $exemptReasonCode
   * @param Address            $BillingAddress
   * @param Address            $P2PAddress
   * @param Address            $ShipToAddress
   * @param Address            $ShipFromAddress
   * @param Address            $OrderPlacementAddress
   * @param Address            $OrderApprovalAddress
   * @param TaxAdjustmentTax[] $TaxAdjustmentTaxList
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
        $this->Tax                  = $data['Tax'];
        $this->Units                = $data['Units'];
        $this->UnitType             = $data['UnitType'];
        $this->TaxSitusRule         = $data['TaxSitusRule'];
        $this->TransTypeCode        = $data['TransTypeCode'];
        $this->SalesTypeCode        = $data['SalesTypeCode'];
        $this->RegulatoryCode       = $data['RegulatoryCode'];
        $this->TaxExemptionCodeList = $data['TaxExemptionCodeList'];
        $this->CostCenter           = $data['CostCenter'];
        $this->UDF                  = $data['UDF'];
        $this->UDF2                 = $data['UDF2'];
        $this->ExemptReasonCode     = $data['ExemptReasonCode'];
        $this->GLAccount            = $data['GLAccount'];
        $this->Revenue              = 0;
        $this->ShipFromPOB          = false;
        $this->MailOrder            = false;
        $this->CommonCarrier        = false;
        $this->BillingDaysInPeriod  = 0;
    }
    
    public function _construct()
    {
        $this->_init('wolterskluwer_cchsuretax/ws_taxAdjustmentItem');
    }
}
