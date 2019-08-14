<?php
/**
 * Use to call 'soap' methods, not the 'post' methods for SureTax web service
 * calls
 *
 * uses PHP SoapClient
 *
 * @category   SureTax
 * @package    WoltersKluwer_CchSureTax
 * @copyright  Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
class WoltersKluwer_CchSureTax_Model_Ws_SureTaxClient
    extends Mage_Core_Model_Abstract
{
    protected $_conf;    // SureTaxConfigInterface
    protected $_client;  // SoapClient object

    public function _construct()
    {
        $this->_init('wolterskluwer_cchsuretax/ws_sureTaxClient');
    }

    /**
     * @param WoltersKluwer_CchSureTax_Helper_Config $config
     */
    public function __construct(array $data)
    {
        $this->_conf = $data['Config'];
        $timeStart = microtime(true);
        /**
         * There is a known bug with some versions of Xdebug which can cause
         * SoapClient to not throw an exception but instead cause a fatal error.
         * Surround the SoapClient call with xdebug_disable(); and
         * xdebug_enable();  to work around this problem.
         */
        $this->_client = new SoapClient(
            $this->_conf->getWsdlUrl(),
            array(
                'trace' => 1,
                'exceptions' => true ,
                'cache_wsdl' => $this->_conf->getWsdlCaching(),
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            )
        );

        if ($this->_conf->isProfiling()) {
            WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                $timeStart,
                'SureTaxClient Construct'
            );
        }
    }

    /**
     *
     * @throws Exception
     */
    public function callHealthMonitor()
    {
        try {
            $timeStart = microtime(true);
            $result = $this->_client->HealthMonitor('');
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax CallHealthMonitor'
                );
            }
            $this->doLog();
            return $result;
        } catch(Exception $ere){
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     *
     * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest $requestData
     *
     * @return type
     * @throws Exception
     */
    public function callSoapRequest($requestData)
    {
        try {
            $timeStart = microtime(true);
            $result = $this->_client->SoapRequest(
                array('request' => $requestData)
            );
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax CallSoapRequest'
                );
            }
            $this->doLog();
            return $result;
        } catch (Exception $ere) {
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     *
     * @param WoltersKluwer_CchSureTax_Helper_Ws_TaxAdjustmentRequest $requestData
     * @return type
     * @throws Exception
     */
    public function callTaxAdjustmentRequest($requestData)
    {
        try {
            $timeStart = microtime(true);

             $this->logRequest();
            $result = $this->_client->SoapTaxAdjustmentRequest(
                array('request' => $requestData)
            );
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax CallSoapTaxAdjustmentRequest'
                );
            }
            $this->doLog();
            return $result;
        } catch (Exception $ere) {
            $this->logRequest();
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     * @param array $requestDataArray      array of SureTaxRequest
     *
     * @return type
     * @throws Exception
     */
    public function callSoapRequestBatch($requestDataArray)
    {
        try {
            $timeStart = microtime(true);
            $result = $this->_client->SoapRequestBatch(
                array('requests' => $requestDataArray)
            );
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax callSoapRequestBatch'
                );
            }
            $this->doLog();
            return $result;
        } catch (Exception $ere) {
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     * @param int $theId
     * @param string $clientTracking
     *
     * @return type
     * @throws Exception
     */
    public function callCancelSoapRequest($theId, $clientTracking)
    {
        $theR=
            array('requestCancel'
                =>array(
                    'ClientNumber'=>$this->_conf->getClientNumber(),
                    'ValidationKey'=>$this->_conf->getValidationKey(),
                    'ClientTracking'=>$clientTracking,
                    'TransId'=>$theId
                    )
                );
        try {
            $timeStart = microtime(true);
            $result = $this->_client->CancelSoapRequest($theR);
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax callCancelSoapRequest'
                );
            }
            $this->doLog();
            return $result;
        } catch (Exception $ere){
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     * @param string $masterTransId
     * @param string $clientTracking
     *
     * @return type
     * @throws Exception
     */
    public function callCancelSoapRequestWithMasterTransId($masterTransId,
            $clientTracking)
    {
        $theR=
            array('requestCancelWithMasterTransId'
                =>array(
                    'ClientNumber'=>$this->_conf->getClientNumber(),
                    'ValidationKey'=>$this->_conf->getValidationKey(),
                    'ClientTracking'=>$clientTracking,
                    'MasterTransId'=>$masterTransId
                    )
                );
        try {
            $timeStart = microtime(true);
            $result = $this->_client->CancelSoapRequestWithMasterTransId($theR);
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax callCancelSoapRequestWithMasterTransId'
                );
            }
            $this->doLog();
            return $result;
        }catch(Exception $ere){
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     * @param string $stan
     * @param string $clientTracking
     *
     * @return type
     * @throws Exception
     */
    public function callCancelSoapRequestWithSTAN($stan, $clientTracking)
    {
         $theR=
            array('requestCancelWithSTAN'
                =>array(
                    'ClientNumber'=>$this->_conf->getClientNumber(),
                    'ValidationKey'=>$this->_conf->getValidationKey(),
                    'ClientTracking'=>$clientTracking,
                    'STAN'=>$stan
                    )
                );
        try {
            $timeStart = microtime(true);
            $result = $this->_client->CancelSoapRequestWithSTAN($theR);
            if ($this->_conf->isProfiling()) {
                WoltersKluwer_CchSureTax_Helper_Utility::doProfile(
                    $timeStart,
                    'SureTax callCancelSoapRequestWithSTAN'
                );
            }
            $this->doLog();
            return $result;
        }catch(Exception $ere){
            $this->logCatch($ere);
            throw $ere;
        }
    }

    /**
     * @param Exception $ex
     */
    protected function logCatch($ex)
    {
        WoltersKluwer_CchSureTax_Helper_Utility::log(
            'SureTaxClient LogCatch: ' .
            $ex->getMessage() . '::TRACE::' . $ex->getTraceAsString(),
            Zend_Log::ERR, true
        );
    }

    protected function doLog()
    {
        $this->logRequest();
        $this->logResponse();
    }

    protected function logRequest()
    {
        if ($this->_conf->isDebug() == true) {
            WoltersKluwer_CchSureTax_Helper_Utility::log(
                "SureTax SOAP Request: " . $this->_client->__getLastRequest(),
                Zend_Log::DEBUG, $this->_conf->isDebug()
            );
        }
    }

    protected function logResponse()
    {
        if ($this->_conf->isDebug() == true) {
            WoltersKluwer_CchSureTax_Helper_Utility::log(
                "SureTax SOAP Response: " . $this->_client->__getLastResponse(),
                Zend_Log::DEBUG, $this->_conf->isDebug()
            );
        }
    }
}
