<?php
/**
 * Represents Request XML for SureTax web service requests.
 *
 * @category  SureTax
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Model_Ws_SureTaxRequest
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
   * @var float $TotalRevenue
   * @access public
   */
    public $TotalRevenue = null;

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
   * @var string $ReturnFileCode
   * @access public
   */
    public $ReturnFileCode = null;

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
   * @var WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $BillingAddress
   * @access public
   */
    //  public $BillingAddress = null;

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
   *
   * @var WoltersKluwer_CchSureTax_Helper_Ws_SureTaxItem[] $ItemList
   * @access public
   */
    public $ItemList = null;

    /**
   *
   * @param string                                            $clientNumber
   * @param string                                            $businessUnit
   * @param string                                            $validationKey
   * @param string                                            $dataYear
   * @param string                                            $dataMonth
   * @param string                                            $cmplDataYear
   * @param string                                            $cmplDataMonth
   * @param float                                             $totalRevenue
   * @param string                                            $clientTracking
   * @param string                                            $responseType
   * @param string                                            $responseGroup
   * @param string                                            $returnFileCode
   * @param string                                            $stan
   * @param int                                               $masterTransId
   * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $billingAddress
   * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $shipToAddress
   * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $shipFromAddress
   * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxItem[]  $itemList
   * @access public
   */
    public function __construct(array $data)        
    {
        $this->ClientNumber     = $data['ClientNumber'];
        $this->BusinessUnit     = 
            (!isset($data['BusinessUnit']) || $data['BusinessUnit'] === null)
                ? '' : $data['BusinessUnit'];
        $this->ValidationKey    = $data['ValidationKey'];
        $this->DataYear         = $data['DataYear'];
        $this->DataMonth        = $data['DataMonth'];
        $this->CmplDataYear     = $data['CmplDataYear'];
        $this->CmplDataMonth    = $data['CmplDataMonth'];
        $this->TotalRevenue     = $data['TotalRevenue'];
        $this->ClientTracking   = $data['ClientTracking'];
        $this->ResponseType     = $data['ResponseType'];
        $this->ResponseGroup    = $data['ResponseGroup'];
        $this->ReturnFileCode   = $data['ReturnFileCode'];
        $this->STAN             = $data['STAN'];
        $this->MasterTransId    = $data['MasterTransId'];
        $this->ShipToAddress    = $data['ShipToAddress'];
        $this->ShipFromAddress  = $data['ShipFromAddress'];
        $this->ItemList         = $data['ItemList'];
    }
    
    public function _construct()
    {
        $this->_init('wolterskluwer_cchsuretax/ws_sureTaxRequest');
    }

    public function getItemList()
    {
        return $this->ItemList;
    }
}
