<?php
/**
 * SureTax Invoices grid controller.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;
use WoltersKluwer_CchSureTax_Helper_Config as Config;

class WoltersKluwer_CchSureTax_Adminhtml_Suretax_InvoicesController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('suretax/invoices');
        return $this->renderLayout();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('suretax/suretax_transactions/suretax_invoices');
    }

    /**
     * Export SureTax Invoice grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName = 'SureTax_Invoices.csv';
        $content = $this->getLayout()->createBlock('suretax/adminhtml_invoices_container_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
        Utility::logMessage('Exported Invoices CSV', Zend_Log::INFO);
    }

    /**
     * Export SureTax Invoice grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName = 'SureTax_Invoices.xml';
        $content = $this->getLayout()->createBlock('suretax/adminhtml_invoices_container_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($fileName, $content);
        Utility::logMessage('Exported Invoices XML', Zend_Log::INFO);
    }

    public function batchFinalizeAction()
    {

        Utility::logMessage('|---Batch Finalize Invoices Start---|', Zend_Log::DEBUG);
        $timeStart = microtime(true);
        $invoiceIds = $this->getRequest()->getPost('invoice_Ids', array());

        $successSureTaxInvoiceIds = array();
        $noticeSureTaxInvoiceIds = array();
        $sureTaxConfigDisabledForIds = array();
        $errorSureTaxInvoiceIds = array();
        $outOfUSAndCANInvoiceIds = array();
        $storeIsDeletedInvoiceIds = array();
        
        $invCollection = Mage::getModel(Constants::INVOICE_TBL)->getCollection()
            ->joinInvoiceWithSelfForInvoiceIds($invoiceIds);

        if ($invoiceIds !== null) {
            if (count($invoiceIds) > Constants::MAXIMUM_NUMBER_OF_INVOICES_TO_PROCESS) {
                Mage::getSingleton('adminhtml/session')->addNotice(
                    "Please select only " .
                        Constants::MAXIMUM_NUMBER_OF_INVOICES_TO_PROCESS .
                    " invoices or fewer to Batch Finalize."
                );
            } else {
                /* @var $client WoltersKluwer_CchSureTax_Helper_WebService*/
                $client = Mage::helper('suretax/webService');
                $requestArray = array();
                $wsConfigArray = Config::getWebsiteStoreConfig();
                foreach ($invCollection as $invoice) {
                    Utility::logMessage('Invoice Id : '. $invoice->getEntityId(), Zend_Log::DEBUG);
                    
                    $incrementId = $invoice->getIncrementId();
                    
                    Utility::logMessage('IncrementId : '.$incrementId, Zend_Log::DEBUG);

                    // if not supported country, then do default magento tax calc
                    $shipToAddressArray = Utility::getAddress(
                        $invoice->getStreet(),
                        $invoice->getCity(),
                        $invoice->getPostcode(),
                        $invoice->getRegion(),
                        $invoice->getCountryId()
                    );
                        //check street2

                    if (Utility::isSureTaxSupportedCountry($shipToAddressArray['Country']) === false) {
                        Utility::logMessage(
                            'Skipping. SureTax does not support this country ' .
                            $shipToAddressArray['Country'] . '.  For Invoice Id : ' .
                            $invoice->getEntityId(), Zend_Log::WARN
                        );
                        
                        array_push($outOfUSAndCANInvoiceIds, $incrementId);
                        continue;
                    }

                    $suretaxInvoiceId = $invoice->getSuretaxId();

                    Utility::logMessage(
                        'SureTax invoice ID : ' .
                        $suretaxInvoiceId, Zend_Log::DEBUG
                    );
                    
                    Utility::logMessage(
                        'Status : ' .
                        $invoice->getStatus(), Zend_Log::DEBUG
                    );
                    // If status is not null (No suretax_order associated with this order)
                    //  and is not Finalize_Fail then we do not process them.

                    if (null !== $invoice->getStatus()
                        && ($invoice->getStatus() == Constants::FINALIZED_STATUS
                        || $invoice->getStatus() == Constants::FINALIZE_ADJUSTED
                        || $invoice->getStatus() == Constants::ADJUSTMENT_FAIL
                        || $invoice->getStatus() == Constants::FINALIZE_PAYMENT_REQUIRED)
                    ) {
                        array_push($noticeSureTaxInvoiceIds, $incrementId);
                        continue;
                    }
                    $websiteId = $invoice->getWebsiteId();
                    $storeId = $invoice->getGroupId();
                    
                    if ($websiteId === null || $storeId === null) {
                        array_push($storeIsDeletedInvoiceIds, $incrementId);
                        continue;
                    }
                    // If based on website and store sure tax calc is turned off then do not send to sure tax.
                    // This new method called form batch will not call db everytime.

                    $enableCalc = Config::isSureTaxEnabledForWebsiteStoreConfig(
                        $websiteId,
                        $storeId,
                        $wsConfigArray
                    );

                    if (!$enableCalc) {
                        array_push($sureTaxConfigDisabledForIds, $incrementId);
                        continue;
                    }
                    $wsConfigFilteredArray = Utility::filterWebsiteStoreArray($wsConfigArray, $websiteId, $storeId);
                    array_push(
                        $requestArray, 
                        $client->createSureTaxRequestFromBatchInvoice(
                            $invoice,
                            $shipToAddressArray,
                            $wsConfigFilteredArray
                        )
                    );
                    
                    unset($invoice);
                }
                if (!empty($requestArray)) {
                    $dataReturned = $client->sendBatchFinalizeToSureTax($requestArray, "Invoice");
                    foreach ($invCollection as $invoice) {
                        $incrementId = $invoice->getIncrementId();
                        $suretaxInvoiceId = $invoice->getSuretaxId();

                        //This if condition checks if the array key contains invoice number
                        //as the array index set is clientTraking which contains invoice number
                        //but not always just invoice number. It may contain other strings.
                        if (!isset($dataReturned[$incrementId])) {
                            $arrayKeys = array_keys($dataReturned);
                            $matchedKey = Utility::checkIfIdExistsInArray($arrayKeys, $incrementId);
                            if ($matchedKey !== 'false') {
                                $dataReturnForInvoice = $dataReturned[$matchedKey];
                            } else {
                                continue;
                            }
                        } else {
                            $dataReturnForInvoice = $dataReturned[$incrementId];
                        }
                        $taxDifference = round(
                            $invoice->getBaseTaxAmount() - 
                            round($dataReturnForInvoice['total_tax'], 4), 2
                        );

                        $status = $dataReturnForInvoice['status'];

                        if ($status === Constants::FINALIZED_STATUS) {
                            if ($taxDifference > 0) {
                                // that means tax been over collected from customer,
                                // so we need to send for adjustment with over collected amount.
                                $adjustmentResponse = $client->sendTaxAdjustmentToSureTax(
                                    $invoice,
                                    $shipToAddressArray,
                                    $taxDifference
                                );
                                $status = $adjustmentResponse['status'];
                            } elseif ($taxDifference < 0) {
                                // this means you under collected from customer,
                                // so you need to pay this much amount more.
                                // Sure tax already has this amount, so no need send an adjustment
                                $status = Constants::FINALIZE_PAYMENT_REQUIRED;
                            }
                        }
                        $suretaxInvoice = Mage::getModel(Constants::INVOICE_TBL);
                        $data = array(
                            'increment_id' => $incrementId,
                            'id'=>$suretaxInvoiceId,
                            'trans_id' => $dataReturnForInvoice['trans_id'],
                            'tax' => $dataReturnForInvoice['total_tax'],
                            'client_tracking' => $dataReturnForInvoice['client_tracking'],
                            'status' => $status,
                            'notes' => $dataReturnForInvoice['notes'],
                        );
                        $suretaxInvoice->setData($data);
                        $suretaxInvoice->save();
                        if ($dataReturnForInvoice['success'] === true) {
                            array_push($successSureTaxInvoiceIds, $incrementId);
                        } else {
                            array_push($errorSureTaxInvoiceIds, $incrementId);

                        }
                        unset($suretaxInvoice);
                    }
                }
            }
        }
        if (count($successSureTaxInvoiceIds)) {
            Utility::addSuccessAdmin(
                $successSureTaxInvoiceIds,
                " invoice(s) have been successfully finalized : "
            );
        }
        if (count($sureTaxConfigDisabledForIds)) {
            Utility::addNoticeAdmin(
                $sureTaxConfigDisabledForIds,
                " invoice(s) cannot be finalized because SureTax calculation is disabled : "
            );
        }
        if (count($noticeSureTaxInvoiceIds)) {
            Utility::addNoticeAdmin(
                $noticeSureTaxInvoiceIds,
                " invoice(s) cannot be finalized because they have already been processed in SureTax : "
            );
        }
        if (count($errorSureTaxInvoiceIds)) {
            Utility::addErrorAdmin(
                $errorSureTaxInvoiceIds,
                " invoice(s) cannot be finalized due to an error from SureTax : "
            );
        }
        if (count($outOfUSAndCANInvoiceIds)) {
            Utility::addErrorAdmin(
                $outOfUSAndCANInvoiceIds,
                " invoice(s) cannot be finalized because SureTax does not support its ship-to-address country : "
            );
        }
        if (count($storeIsDeletedInvoiceIds)) {
            Utility::addErrorAdmin(
                $storeIsDeletedInvoiceIds,
                " invoice(s) cannot be finalized because the underlying store or website was deleted : "
            );
        }
        
        Utility::doProfile($timeStart, 'Invoice Batch Finalize ');
        Utility::logMessage(
            '|---Batch Finalize Invoices End---|',
            Zend_Log::NOTICE
        );

        $this->_redirect('*/*/');
    }
}
