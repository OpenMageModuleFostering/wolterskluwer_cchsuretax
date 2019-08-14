<?php
/**
 * Represents Address XML element for SureTax web service requests.
 *
 * @category  SureTax
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress
    extends Mage_Core_Model_Abstract
{

    /**
     *
     * @var string $PrimaryAddressLine
     * @access public
     */
    public $PrimaryAddressLine = '';

    /**
     *
     * @var string $SecondaryAddressLine
     * @access public
     */
    public $SecondaryAddressLine = '';

    /**
     *
     * @var string $County
     * @access public
     */
    public $County = null;

    /**
     *
     * @var string $City
     * @access public
     */
    public $City = '';

    /**
     *
     * @var string $State
     * @access public
     */
    public $State = '';

    /**
     *
     * @var string $PostalCode
     * @access public
     */
    public $PostalCode = '';

    /**
     *
     * @var string $Plus4
     * @access public
     */
    public $Plus4 = '';

    /**
     *
     * @var string $Country
     * @access public
     */
    public $Country = '';

    /**
     *
     * @var string $Geocode
     * @access public
     */
    public $Geocode = null;

    /**
     * default is true
     *
     * @var boolean true/false
     */
    public $VerifyAddress = true;

    /**
     *
     * @param string $primaryAddressLine
     * @param string $secondaryAddressLine
     * @param string $county
     * @param string $city
     * @param string $state
     * @param string $postalCode
     * @param string $plus4
     * @param string $country
     * @param string $geocode
     * @param string $verifyAddress
     * @access public
     */
    public function __construct(array $data)
    {       
        $this->PrimaryAddressLine = $this->getValue($data['PrimaryAddressLine']);
        $this->SecondaryAddressLine = $this->getValue($data['SecondaryAddressLine']);
        $this->County = ''; //Not set
        $this->City = $this->getValue($data['City']);
        $this->State = $this->getValue($data['State']);
        $this->PostalCode = $this->getValue($data['PostalCode']);
        $this->Plus4 = $this->getValue($data['Plus4']);
        $this->Country = $this->getValue($data['Country']);
        $this->Geocode = ''; //Not set
        $this->VerifyAddress = (empty($data['VerifyAddress'])) ? true : $data['VerifyAddress'];
    }
    
    public function _construct()
    {
        $this->_init('wolterskluwer_cchsuretax/ws_sureTaxAddress');
    }
    
    protected function getValue($arg)
    {
        // works since empty returns true if the variable is an empty string,
        // false, array(), NULL, “0?, 0, and an unset variable.  we are good
        // with setting '' strings
        return (empty($arg) ? '' : $arg);
    }
}
