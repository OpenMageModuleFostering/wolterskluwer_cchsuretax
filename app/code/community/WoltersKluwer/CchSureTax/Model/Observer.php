<?php
/**
 * SureTax Observer for all event catching.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;
use WoltersKluwer_CchSureTax_Helper_Config as Config;

class WoltersKluwer_CchSureTax_Model_Observer
{
    const FLAG_SHOW_CONFIG = 'showConfig';
    const FLAG_SHOW_CONFIG_FORMAT = 'showConfigFormat';

    /**
     * Event that converts the address obj to order setting the applied taxes
     * to null so it's not duplicated in our dbs
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesEventConvertQuoteAddressToOrder($observer)
    {
        //This method is kept empty so that Suretax can save taxes that they got
        //back from tax engine. This way magento does not override our tax saving logic
    }

    /**
     * This method is fired for the event sales_order_save_after - config.xml
     *
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function afterOrderSaved($observer)
    {
        /* @var $order Mage_Sales_Model_Order*/
        $order = $observer->getEvent()->getOrder();

        Utility::logMessage(
            '|--After Order ' . $order->getIncrementId() . ' Saved--|',
            Zend_Log::INFO
        );

        Utility::logMessage(
            'Order StatusLabel : ' . $order->getStatusLabel(),
            Zend_Log::INFO
        );

        // if not supported country, then do default magento tax calc
        $shipToAddressArray = Utility::
            getShipToAddress(
                Mage::getModel('sales/order_address')
                ->load($order->getShippingAddress()->getId())
            );

        if (!Utility::isSureTaxSupportedCountry($shipToAddressArray['Country'])) {
            return $this;
        }
        $store = $order->getStore();
        $sureTaxConfig = Config::get();
        $isDebug = $sureTaxConfig->isDebug();
        $enableCalc = Config::isSureTaxEnabledForWebsiteStore(
            $store->getWebsiteId(),
            $store->getGroupId()
        );
        if (!$enableCalc) {
            return $this;
        }

        //following if condition is to make sure that it gets called only once
        if ((!$order->getStatusLabel() || $order->getStatusLabel() == 'Pending')
            && !Mage::registry('prevent_observer_final'.$order->getIncrementId())
        ) {
            Utility::log(
                'Posting Order ' . $order->getIncrementId() . ' to SureTax',
                Zend_Log::INFO,
                $isDebug
            );

            /* @var $client WoltersKluwer_CchSureTax_Helper_WebService */
            $client = Mage::helper('suretax/webService');
            $client->sendOrderToSureTax($order, $shipToAddressArray);

            /**
             * This following block saves the order and tax info in the
             * sales_order_tax table and sales_order_tax_item which gets the
             * item ids from sales_flat_order_item for tax display purposes which can be
             * configured under Sales->Configuration->Tax
             */

            if (Utility::isEnabledTaxDisplay()) {
                $jurisdictionArray = $client->getJurisdictionArray();
                $i = 0;
                foreach ($jurisdictionArray as $jRate) {
                    $taxAmount = $jRate['TaxAmount'];
                    $taxAmountStore = $store->convertPrice($taxAmount);
                    $taxRate = round(
                        $jRate['TaxRate'],
                        Constants::PRECISION_DIGITS_FOR_DISPLAY,
                        PHP_ROUND_HALF_UP
                    );
                    $title = Utility::generateJurisdictionTitle(
                        $jRate['TaxTypeDesc'],
                        $jRate['TaxAuthorityName']
                    );

                    $data = array(
                        'order_id'         => $order->getId(),
                        'code'             => $title,
                        'title'            => $title,
                        'percent'          => $taxRate,
                        'amount'           => $taxAmountStore,
                        'priority'         => 0,
                        'position'         => $i,
                        'base_amount'      => $taxAmount,
                        'process'          => $i,
                        'base_real_amount' => $taxAmount,
                        'hidden'           => 0,
                    );
                    $i++;

                    $salesOrderTax = Mage::getModel('tax/sales_order_tax')->setData($data)->save();
                    //each jurisdiction tax should be saved for each line
                    //item in the order
                    foreach ($order->getAllItems() as $item) {
                        $data = array(
                            'tax_id'      => $salesOrderTax->getTaxId(),
                            'item_id'     => $item->getId(),
                            'tax_percent' => $taxRate,
                        );
                        Mage::getModel('tax/sales_order_tax_item')->setData($data)->save();
                    }
                }
            }

            Mage::register('prevent_observer_final' . $order->getIncrementId(), true);
        }
    }

    /**
     * Event driven : called after Invoice creation and save.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function afterInvoiceSaved($observer)
    {
        /* @var $invoice Mage_Sales_Model_Order_Invoice*/
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $store = $order->getStore();
        Utility::logMessage(
            '|--After Invoice ' . $invoice->getIncrementId() . ' Saved--|',
            Zend_Log::INFO
        );

        $shipToAddressArray = Utility::
            getShipToAddress(
                Mage::getModel('sales/order_address')
                ->load($order->getShippingAddress()->getId())
            );

        if (!Utility::isSureTaxSupportedCountry($shipToAddressArray['Country'])) {
            return $this;
        }

        $sureTaxConfig = Config::get();
        $isDebug = $sureTaxConfig->isDebug();

        $enableCalc = Config::isSureTaxEnabledForWebsiteStore(
            $store->getWebsiteId(),
            $store->getGroupId()
        );
        if (!$enableCalc) {
            return $this;
        }

        $suretaxInvoiceModel = Mage::getModel(Constants::INVOICE_TBL);
        $suretaxInvoice = $suretaxInvoiceModel->load($invoice->getIncrementId(), 'increment_id');

        if (($suretaxInvoice->getStatus() === null)) {
            Utility::log(
                'Finalizing Invoice '.$invoice->getIncrementId().' to SureTax',
                Zend_Log::INFO,
                $isDebug
            );

            /* @var $client WoltersKluwer_CchSureTax_Helper_WebService*/
            $client = Mage::helper('suretax/webService');
            $dataReturned = $client->sendFinalizeInvoiceToSureTax($invoice, $shipToAddressArray);

            $taxDifference = round($invoice->getBaseTaxAmount() - round($dataReturned['total_tax'], 4), 2);

            $status = $dataReturned['status'];
            if ($status == Constants::FINALIZED_STATUS) {
                if ($taxDifference > 0) {
                    //that means tax been over collected from customer,
                    //so we need to send for adjustment with over collected amount.
                    $adjustmentResponse = $client->sendTaxAdjustmentToSureTax(
                        $invoice,
                        $shipToAddressArray,
                        $taxDifference
                    );
                    $status = $adjustmentResponse['status'];
                } elseif ($taxDifference < 0) {
                    //this means you under collected from customer, so you need to pay this much amount more.
                    //Sure tax already has this amount, so no need send an adjustment
                    $status = Constants::FINALIZE_PAYMENT_REQUIRED;
                }
            }

            $data = array(
                'increment_id'    => $invoice->getIncrementId(),
                'trans_id'        => $dataReturned['trans_id'],
                'client_tracking' => $dataReturned['client_tracking'],
                'status'          => $status,
                'notes'           => $dataReturned['notes'],
                'tax'             => $dataReturned['total_tax']
            );
            $suretaxInvoiceModel->setData($data);
            $suretaxInvoiceModel->save();
        }
    }

    /**
     * Event driven : called after a Credit memo creation.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return self
     */
    public function afterCreditMemoRefund($observer)
    {
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo*/
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();

        Utility::logMessage(
            '|--After Credit Memo ' . $creditmemo->getIncrementId() . ' Refund--|',
            Zend_Log::INFO
        );

        $shipToAddressArray = Utility::
            getShipToAddress(
                Mage::getModel('sales/order_address')
                ->load($order->getShippingAddress()->getId())
            );

        if (!Utility::isSureTaxSupportedCountry($shipToAddressArray['Country'])) {
            return $this;
        }

        $enableCalc = Config::isSureTaxEnabledForWebsiteStore(
            $order->getStore()->getWebsiteId(),
            $order->getStore()->getGroupId()
        );
        if (!$enableCalc) {
            return $this;
        }

        $suretaxCreditmemoModel = Mage::getModel(Constants::CREDIT_TBL)
            ->load($creditmemo->getIncrementId(), 'increment_id');

        if (!Mage::registry('prevent_observer')) {
            Utility::logMessage(
                'Order ID to be credited : ' . $order->getIncrementId() .
                ' | Order Status Label : ' . $order->getStatusLabel(),
                Zend_Log::NOTICE
            );

            if (!$suretaxCreditmemoModel->getTransId()) {
                /* @var $client WoltersKluwer_CchSureTax_Helper_WebService*/
                $client = Mage::helper('suretax/webService');
                $dataReturned = $client->sendCreditMemoToSureTax($creditmemo, $shipToAddressArray);
                $suretaxReturnedTax = $dataReturned['total_tax'];
                $suretaxReturnedTaxAmount = -1 * round($suretaxReturnedTax, 4);
                $creditMemoTax = $creditmemo->getBaseTaxAmount();

                $taxDifference = round(($creditMemoTax - $suretaxReturnedTaxAmount), 2);
                $status = $dataReturned['status'];
                Utility::logMessage(
                    'Total Tax = ' . $dataReturned['total_tax'] .
                    ' Tax Difference = ' . $taxDifference .
                    ' Status = ' . $status,
                    Zend_Log::DEBUG
                );

                if ($status == Constants::FINALIZED_STATUS) {
                    if ($taxDifference > 0) {
                        //this means vendor paid more money to customer as tax in refund than actual refund tax.
                        //so it is just to know for vendor
                        $status = Constants::FINALIZE_PAYMENT_REQUIRED;
                    } elseif ($taxDifference < 0) {
                        //this means vendor has been paid less money as tax, so we need to submit adjustment
                        $adjustDataReturned = $client->sendCreditMemoTaxAdjustmentToSureTax(
                            $creditmemo,
                            $shipToAddressArray,
                            $taxDifference * -1
                        );
                        $status = $adjustDataReturned['status'];
                    }
                }
                Utility::logMessage('STAN : ' . $dataReturned['stan'], Zend_Log::DEBUG);
                $data = array(
                    'increment_id'    => $creditmemo->getIncrementId(),
                    'trans_id'        => $dataReturned['trans_id'],
                    'stan'            => $dataReturned['stan'],
                    'client_tracking' => $dataReturned['client_tracking'],
                    'status'          => $status,
                    'tax'             => $dataReturned['total_tax'] * -1,
                    'notes'           => $dataReturned['notes']
                );
                $suretaxCreditmemoModel->setData($data);
                $suretaxCreditmemoModel->save();
            }
            Mage::register('prevent_observer', true);
        }
    }
}
