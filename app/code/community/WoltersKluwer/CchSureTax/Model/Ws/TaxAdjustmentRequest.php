<?php
/*
 * Represents Tax Adjustment request XML for SureTax web service requests.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Model_Ws_TaxAdjustmentRequest
    extends Mage_Core_Model_Abstract
{

    /**
    *
    * @var string $ClientNumber
    * @access public
    */
    public $ClientNumber = null;

    /**
    *
    * @var string $BusinessUnit
    * @access public
    */
    public $BusinessUnit = null;

    /**
    *
    * @var string $ValidationKey
    * @access public
    */
    public $ValidationKey = null;

    /**
    *
    * @var string $DataYear
    * @access public
    */
    public $DataYear = null;

    /**
    *
    * @var string $DataMonth
    * @access public
    */
    public $DataMonth = null;

    /**
    *
    * @var string $CmplDataYear
    * @access public
    */
    public $CmplDataYear = null;

    /**
    *
    * @var string $CmplDataMonth
    * @access public
    */
    public $CmplDataMonth = null;

    /**
    *
    * @var string $ClientTracking
    * @access public
    */
    public $ClientTracking = null;

    /**
    *
    * @var string $ResponseType
    * @access public
    */
    public $ResponseType = null;

    /**
    *
    * @var string $ResponseGroup
    * @access public
    */
    public $ResponseGroup = null;

    /**
    *
    * @var string $STAN
    * @access public
    */
    public $STAN = null;

    /**
    *
    * @var int $MasterTransId
    * @access public
    */
    public $MasterTransId = null;

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
    * @var TaxAdjustmentItem[] $TaxAdjustmentItemList
    * @access public
    */
    public $TaxAdjustmentItemList = null;

    /**
    *
    * @param string              $ClientNumber
    * @param string              $BusinessUnit
    * @param string              $ValidationKey
    * @param string              $DataYear
    * @param string              $DataMonth
    * @param string              $CmplDataYear
    * @param string              $CmplDataMonth
    * @param string              $ClientTracking
    * @param string              $ResponseType
    * @param string              $ResponseGroup
    * @param string              $STAN
    * @param int                 $MasterTransId
    * @param Address             $BillingAddress
    * @param Address             $P2PAddress
    * @param Address             $ShipToAddress
    * @param Address             $ShipFromAddress
    * @param Address             $OrderPlacementAddress
    * @param Address             $OrderApprovalAddress
    * @param TaxAdjustmentItem[] $TaxAdjustmentItemList
    * @access public
    */
    public function __construct(array $data)
    {
        $this->ClientNumber             = $data['ClientNumber'];
        $this->BusinessUnit             =
            (!isset($data['BusinessUnit']) || $data['BusinessUnit'] === null)
                ? '' : $data['BusinessUnit'];
        $this->ValidationKey            = $data['ValidationKey'];
        $this->DataYear                 = $data['DataYear'];
        $this->DataMonth                = $data['DataMonth'];
        $this->CmplDataYear             = $data['CmplDataYear'];
        $this->CmplDataMonth            = $data['CmplDataMonth'];
        $this->ClientTracking           = $data['ClientTracking'];
        $this->ResponseType             = $data['ResponseType'];
        $this->ResponseGroup            = $data['ResponseGroup'];
        $this->STAN                     = $data['STAN'];
        $this->MasterTransId            = $data['MasterTransId'];
        $this->ShipToAddress            = $data['ShipToAddress'];
        $this->ShipFromAddress          = $data['ShipFromAddress'];
        $this->TaxAdjustmentItemList    = $data['TaxAdjustmentItemList'];
    }

    public function _construct()
    {
        $this->_init('wolterskluwer_cchsuretax/ws_taxAdjustmentRequest');
    }
}
