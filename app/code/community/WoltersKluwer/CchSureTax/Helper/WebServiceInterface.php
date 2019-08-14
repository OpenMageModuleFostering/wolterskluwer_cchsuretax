<?php
/*
 * Interface detailing all Webservice method for calling SureTax API with
 * Magento.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

interface WoltersKluwer_CchSureTax_Helper_WebServiceInterface
{

    /**
     * Creates a SureTaxRequest from a quote's address
     *
     * @param Mage_Sales_Model_Quote_Address $address magento's quote address
     *
     * @param  $estimatedAddress
     * @return WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest
     */
    public function createSureTaxRequestFromQuoteAddress(
        Mage_Sales_Model_Quote_Address $address,
        $estimatedAddress
    );

    /**
     * Make the API call for a SureTax quote from the Magento's quote address.
     *
     * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest $requestData
     *
     * @return type
     * @throws Exception
     */
    public function makeSureTaxRequestFromQuoteAddress($requestData);

    /**
     * Creates SureTaxRequest from an invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param array                          $shipToAddressArray
     *
     * @return WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest
     * @throws Exception
     */
    public function createSureTaxRequestFromInvoice($invoice, $shipToAddressArray);

    public function makeSureTaxRequest($requestData);
    /**
     * Called from Observer.
     * Creates a Finalized transaction from a magento invoice.
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param array                          $shipToAddressArray
     *
     * @return array
     */
    public function sendFinalizeInvoiceToSureTax($invoice, $shipToAddressArray);

    /**
     * Creates the SureTax request to finalize the order
     *
     * @param Mage_Sales_Model_Order $order
     * @param boolean                $isFinalize
     * @param array                  $shipToAddressArray
     *
     * @return WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest
     * @throws Exception
     */
    public function createSureTaxRequestFromOrder(Mage_Sales_Model_Order $order,
        $isFinalize, $shipToAddressArray
    );

    /**
     * Make the API call to finalize in SureTax
     *
     * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest $requestData
     *
     * @return type
     * @throws Exception
     */
    public function makeSureTaxRequestFromOrder($requestData);

    /**
     * Called from Observer.
     * Creates a transaction from a magento order. Can be a finalized order transaction.
     *
     * @param Mage_Sales_Model_Order $order
     * @param array                  $shipToAddressArray
     *
     * @return array
     */
    public function sendOrderToSureTax($order, $shipToAddressArray);

    /**
     * Make the API call to cancel in SureTax
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return type
     * @throws Exception
     */
    public function makeCancelRequest($order);

    /**
     * Called from Observer.
     * Cancel the SureTax transaction associated with the given magento order.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function sendCancelToSureTax($order);

    /**
     * Creates the SureTax request for the given credit memo.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param array                             $shipToAddressArray
     *
     * @return WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest
     * @throws Exception
     */
    public function createSureTaxRequestFromCreditmemo(
        Mage_Sales_Model_Order_Creditmemo $creditMemo,
        $shipToAddressArray
    );

    /**
     * Make the API call for a finalized credit memo in SureTax.
     *
     * @param WoltersKluwer_CchSureTax_Helper_Ws_SureTaxRequest $requestData
     *
     * @return type
     */
    public function makeCreditRequest($requestData);

    /**
     * Called from Observer.
     * Creates the SureTax transaction associated with the given magento credit
     * memo.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param array                             $shipToAddressArray
     *
     * @return array
     */
    public function sendCreditMemoToSureTax($creditmemo, $shipToAddressArray);

    /**
     * Create Line item array that needs to be attached to the request
     *
     * @param  int                                               $i
     * @param  Mage_Sales_Model_Quote_Item|
     *         Mage_Sales_Model_Order_Item                       $item
     * @param  WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $billingAddress
     * @param  WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $shipToAddress
     * @param  WoltersKluwer_CchSureTax_Helper_Ws_SureTaxAddress $shipFromAddress
     * @param  string                                            $trxDate
     * @param  float                                             $shippingCharges
     * @param  boolean                                           $isOrderObject
     * @param  string                                            $providerType
     * @param  boolean                                           $isCredit
     * @param  WoltersKluwer_CchSureTax_Helper_Config            $config
     * @param  string                                            $salesTypeCode
     * @param  string                                            $exemptionCode
     * @param  string                                            $exemptionReason
     * @param  int                                               $customerId
     * @return null|WoltersKluwer_CchSureTax_Helper_Ws_SureTaxItem
     *         SureTaxItem or null if no line item created (e.g. gift wrap
     *         not supported)
     */
    public function getLineItem($i, $item, $billingAddress, $shipToAddress,
        $shipFromAddress, $trxDate, $shippingCharges, $isOrderObject,
        $providerType, $isCredit, $config, $salesTypeCode, $exemptionCode,
        $exemptionReason, $customerId
    );

    /**
     *
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param array                          $shipToAddressArray
     * @param float                          $taxForAdjustment
     *
     * @return WoltersKluwer_CchSureTax_Helper_Ws_TaxAdjustmentRequest
     */
    public function createSureTaxAdjustRequestFromInvoice($invoice, $shipToAddressArray, $taxForAdjustment);

    /**
     *
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param array                          $shipToAddressArray
     * @param float                          $taxAdjustment
     *
     * @return array
     */
    public function sendTaxAdjustmentToSureTax($invoice, $shipToAddressArray, $taxAdjustment);

    /**
     *
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param array                             $shipToAddressArray
     * @param float                             $taxForAdjustment
     *
     * @return WoltersKluwer_CchSureTax_Helper_Ws_TaxAdjustmentRequest
     */
    public function createSureTaxAdjustRequestFromCreditMemo($creditMemo, $shipToAddressArray, $taxForAdjustment);

    /**
     *
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param array                             $shipToAddressArray
     * @param float                             $taxAdjustment
     *
     * @return array
     */
    public function sendCreditMemoTaxAdjustmentToSureTax($creditMemo, $shipToAddressArray, $taxAdjustment);

    /**
     * always get totals by what we get in the soapresult
     *
     * @param Mage_Sales_Model_Order $order
     * @param type                   $soapResult
     * @param type                   $soapRequest
     */
    public function saveOrderTaxesToMagento($order, $soapResult, $soapRequest);

    /**
     * always get totals by what we get in the soapresult
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param type                           $soapresult
     * @param array                          $soapRequest
     */
    public function saveInvoiceTaxesToMagento($invoice, $soapResult, $soapRequest);


    /**
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param type                              $soapResult
     * @param array                             $soapRequest
     */
    public function saveCreditMemoTaxesToMagento($creditMemo, $soapResult, $soapRequest);

    /**
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param array                       $itemArray
     */
    public function saveTaxesForOrderItem($item, $itemArray);

    /**
     *
     * @param Mage_Sales_Model_Order_Invoice_Item $item
     * @param array                               $itemArray
     */
    public function saveTaxesForInvoiceItem($item, $itemArray);

    /**
     *
     * @param Mage_Sales_Model_Order_Creditmemo_Item $item
     * @param array                                  $itemArray
     */
    public function saveTaxesForCreditMemoItem($item, $itemArray);

    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param array                  $itemArray
     * @return float   shipping tax amount
     */
    public function saveShippingTaxesForOrder($order, $itemArray);

    /**
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param array                          $itemArray
     * @return float   shipping tax amount
     */
    public function saveShippingTaxesForInvoice($invoice, $itemArray);

    /**
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param array                             $itemArray
     * @return float
     */
    public function saveShippingTaxesForCreditMemo($creditMemo, $itemArray);
}