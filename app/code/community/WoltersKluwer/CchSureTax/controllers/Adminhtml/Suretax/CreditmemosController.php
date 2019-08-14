<?php
/**
 * SureTax Credit Memo grid controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;
use WoltersKluwer_CchSureTax_Helper_Config as Config;

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_CreditmemosController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/creditmemos');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('suretax/suretax_transactions/suretax_creditmemos');
    }

    /**
     * Export SureTax Credit Memos grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'SureTax_Creditmemos.csv';
        $content    = $this->getLayout()->createBlock('suretax/adminhtml_creditmemos_container_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
        Utility::logMessage('Exported Credit Memos CSV', Zend_Log::INFO);
    }

    /**
     * Export SureTax Credit Memos grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'SureTax_Creditmemos.xml';
        $content    = $this->getLayout()->createBlock('suretax/adminhtml_creditmemos_container_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
        Utility::logMessage('Exported Credit Memos XML', Zend_Log::INFO);
    }

    /**
     * Batch Finalize all applicable credit memos.
     */
    public function batchFinalizeAction()
    {

        Utility::logMessage('|---Batch Finalize Credit Memos Start---|', Zend_Log::NOTICE);

        $creditmemoIds = $this->getRequest()->getPost('creditmemo_ids', array());
        $timeStart = microtime(true);

        $successIds = array();
        $noticeIds = array();
        $suretaxConfigDisabledForIds = array();
        $errorIds = array();
        $outOfUSAAndCAN = array();
        $storeIsDeletedInvoiceIds = array();
        
        $creditMemoCollection = Mage::getModel(Constants::CREDIT_TBL)
            ->getCollection()
            ->joinCreditMemoWithSelfForCreditmemoIds($creditmemoIds);
        
        if (count($creditmemoIds) > Constants::MAXIMUM_NUMBER_OF_ORDERS_TO_PROCESS) {
            Mage::getSingleton('adminhtml/session')->addNotice(
                "Please select only ".Constants::MAXIMUM_NUMBER_OF_ORDERS_TO_PROCESS.
                " credit memos or fewer to Batch Finalize."
            );
        } else {
            /* @var $client WoltersKluwer_CchSureTax_Helper_WebService*/
            $client = Mage::helper('suretax/webService');
            $requestArray = array();
            $wsConfigArray = Config::getWebsiteStoreConfig();
            foreach ($creditMemoCollection as $creditMemo) {
                $creditmemoId = $creditMemo->getEntityId();
                Utility::logMessage('Credit Memo Id : '. $creditmemoId, Zend_Log::NOTICE);

                $incrementId = $creditMemo->getIncrementId();

                Utility::logMessage('IncrementId : '.$incrementId, Zend_Log::NOTICE);

                $shipToAddressArray = Utility::getAddress(
                    $creditMemo->getStreet(),
                    $creditMemo->getCity(),
                    $creditMemo->getPostcode(),
                    $creditMemo->getRegion(),
                    $creditMemo->getCountryId()
                );

                if (!Utility::isSureTaxSupportedCountry(
                    $shipToAddressArray['Country']
                )) {
                    Utility::logMessage(
                        'Skipping. SureTax does not support Country '. $shipToAddressArray['Country'] .
                        ' for credit memo '. $creditmemoId,
                        Zend_Log::WARN
                    );

                    array_push($outOfUSAAndCAN, $incrementId);
                    continue;
                }
                $suretaxCreditmemoId = $creditMemo->getSuretaxId();
                Utility::logMessage(
                    'Suretax order ID : ' . $suretaxCreditmemoId,
                    Zend_Log::NOTICE
                );
                Utility::logMessage(
                    'Status : ' . $creditMemo->getStatus(),
                    Zend_Log::NOTICE
                );
                if (null !== $creditMemo->getStatus()
                    && !in_array(
                        $creditMemo->getStatus(),
                        Constants::$BATCH_STATUS_ALLOW
                    )
                ) {
                    array_push($noticeIds, $incrementId);
                    continue;
                }
                $websiteId = $creditMemo->getWebsiteId();
                $storeId = $creditMemo->getGroupId();
                
                if ($websiteId === null || $storeId === null) {
                    array_push($storeIsDeletedInvoiceIds, $incrementId);
                    continue;
                }
                // This new method called form batch will not call db everytime.
                $enableCalc = Config::isSureTaxEnabledForWebsiteStoreConfig(
                    $websiteId,
                    $storeId,
                    $wsConfigArray
                );
                if (!$enableCalc) {
                    array_push($suretaxConfigDisabledForIds, $incrementId);
                    continue;
                }
                $wsConfigFilteredArray = Utility::filterWebsiteStoreArray($wsConfigArray, $websiteId, $storeId);
                array_push(
                    $requestArray,
                    $client->createSureTaxRequestFromBatchCreditmemo(
                        $creditMemo,
                        $shipToAddressArray,
                        $wsConfigFilteredArray
                    )
                );
                unset($creditMemo);
            }
            if (!empty($requestArray)) {
                $dataReturned = $client->sendBatchFinalizeToSureTax($requestArray, "Credit Memo");
                foreach ($creditMemoCollection as $creditMemo) {
                    $incrementId = $creditMemo->getIncrementId();
                    $orderId = $creditMemo->getOrderIncrementId();
                    $suretaxCreditmemoId = $creditMemo->getSuretaxId();
                    
                    //This if condition checks if the array key contains credit memo number
                    //as the array index set is clientTraking which contains credit memo number
                    //but not always just credit memo number. It may contain other strings.
                    if (!isset($dataReturned[$incrementId])) {
                        $arrayKeys = array_keys($dataReturned);
                        $matchedKey = Utility::checkIfIdExistsInArray($arrayKeys, $incrementId);
                        if ($matchedKey !== 'false') {
                            $dataReturnForCreditMemo = $dataReturned[$matchedKey];
                        } else {
                            continue;
                        }
                    } else {
                        $dataReturnForCreditMemo = $dataReturned[$orderId];
                    }
                    $transId = $dataReturnForCreditMemo['trans_id'];
                    $clientTracking = $dataReturnForCreditMemo['client_tracking'];
                    $status = $dataReturnForCreditMemo['status'];
                    $notes = $dataReturnForCreditMemo['notes'];
                    $creditMemoTax = $creditMemo->getBaseTaxAmount();
                    $sureTaxCalculatedTax = $dataReturnForCreditMemo['total_tax'];
                    $sureTaxCalculated = -1 * round($sureTaxCalculatedTax, 4);
                    $taxDifference = round(($creditMemoTax - $sureTaxCalculated), 2);

                    if ($status == Constants::FINALIZED_STATUS) {
                        if ($taxDifference > 0) {
                            //customer is paid more in taxes.
                            //so make a note
                            $status = Constants::FINALIZE_PAYMENT_REQUIRED;
                        } elseif ($taxDifference < 0) {
                            //customer is paid less, so adjust at suretax for the remaining
                            $adjustmentData = $client->sendCreditMemoTaxAdjustmentToSureTax(
                                $creditMemo,
                                $shipToAddressArray,
                                -1*$taxDifference
                            );
                            $status = $adjustmentData['status'];
                        }
                    }
                    $suretaxCreditmemo = Mage::getModel(Constants::CREDIT_TBL);
                    $data = array(
                        'id'=>$suretaxCreditmemoId,
                        'increment_id' => $creditMemo->getIncrementId(),
                        'stan' => $dataReturnForCreditMemo['stan'],
                        'trans_id' => $transId,
                        'client_tracking' => $clientTracking,
                        'status' => $status,
                        'notes' => $notes,
                        'tax'=> -1 * $dataReturnForCreditMemo['total_tax']
                    );

                    $suretaxCreditmemo->setData($data);
                    $suretaxCreditmemo->save();
                    if ($dataReturnForCreditMemo['success'] === true) {
                        array_push($successIds, $incrementId);
                        $totalTax = $dataReturnForCreditMemo['total_tax'];
                        $creditMemo->setTaxAmount($creditMemo->getStore()->convertPrice($totalTax));
                        $creditMemo->setBaseTaxAmount($totalTax);
                        $creditMemo->setId($creditmemoId);
                    } else {
                        array_push($errorIds, $incrementId);
                    }
                    unset($suretaxCreditmemo);
                }
            }
        }

        if (count($successIds)) {
            Utility::addSuccessAdmin(
                $successIds,
                " credit memo(s) have been successfully finalized : "
            );
        }
        if (count($suretaxConfigDisabledForIds)) {
            Utility::addNoticeAdmin(
                $suretaxConfigDisabledForIds,
                " credit memo(s) cannot be finalized because SureTax calculation is disabled : "
            );
        }
        if (count($noticeIds)) {
            Utility::addNoticeAdmin(
                $noticeIds,
                " credit memo(s) cannot be finalized because they have already been processed in SureTax : "
            );
        }
        if (count($errorIds)) {
            Utility::addErrorAdmin(
                $errorIds,
                " credit memo(s) cannot be finalized due to an error from SureTax : "
            );
        }

        if (count($outOfUSAAndCAN)) {
            Utility::addErrorAdmin(
                $outOfUSAAndCAN,
                " credit memos(s) cannot be finalized because SureTax does not support its ship-to-address country : "
            );
        }
        if (count($storeIsDeletedInvoiceIds)) {
            Utility::addErrorAdmin(
                $storeIsDeletedInvoiceIds,
                " invoice(s) cannot be finalized because the underlying store or website was deleted : "
            );
        }

        Utility::doProfile($timeStart, 'Credit Memos Batch Finalize ');
        Utility::logMessage('|---Batch Finalize Credit Memos End---|', Zend_Log::NOTICE);
        $this->_redirect('*/*/');
    }
}
