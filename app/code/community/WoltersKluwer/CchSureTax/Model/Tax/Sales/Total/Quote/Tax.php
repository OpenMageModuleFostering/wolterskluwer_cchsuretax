<?php
/**
 * Rewrite collect for quotes.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;
use WoltersKluwer_CchSureTax_Helper_Config as Config;

class WoltersKluwer_CchSureTax_Model_Tax_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
    protected $_totalDiscountWithTax = array();

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();
        $store = $quote->getStore();
        $isSureTaxEnabled = Config::isSureTaxEnabledForWebsiteStore(
            $store->getWebsiteId(),
            $store->getGroupId()
        );

        if ($isSureTaxEnabled == 0) {
            return parent::collect($address);
        }

        $estimatedAddress = Utility::getShipToAddress($address);
        $theCountry = $estimatedAddress['Country'];
        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        $customer = null;
        if ($isLoggedIn) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
        }

        if (!isset($theCountry) && $customer) {
            // might have a logged in customer
            // if logged in, we can try to use their shipping address (if avail)
            $customerAddressId = $customer->getDefaultShipping();
            if ($customerAddressId) {
                $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
                $estimatedAddress = Utility::getShipToAddress($customerAddress);
                $theCountry = $estimatedAddress['Country'];
            }
        }

        // if country isn't set, we return 0 tax
        if (!isset($theCountry) || $theCountry == null) {
            return $this;
        }

        // if not supported country, then do default magento tax calc
        if (!Utility::isSureTaxSupportedCountry($theCountry)) {
            return parent::collect($address);
        }

        // init
        $this->_setAddress($address);
        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        // This logic makes the call only once not twice
        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }

        $subtotal = $address->getBaseSubtotal();

        // check if the address is entered
        if ($subtotal != 0 && array_filter($estimatedAddress)) {
            try {
                /* @var $client WoltersKluwer_CchSureTax_Helper_WebService*/
                $client = Mage::helper('suretax/webService');
                $soapRequest = $client->createSureTaxRequestFromQuoteAddress($address, $estimatedAddress);
                $soapResult = null;
                try {
                    $soapResult = $client->makeSureTaxRequestFromQuoteAddress($soapRequest);
                } catch (Exception $exe) {
                    Utility::log(
                        'CALC ERROR: Quote Exception with sendQuoteAddressToSureTax : '
                        . $exe->getTraceAsString(),
                        Zend_Log::ERR,
                        true
                    );
                    return parent::collect($address);
                }

                if ($soapResult == null) {
                    return parent::collect($address);
                }

                $isSuccessful = $soapResult->SoapRequestResult->Successful;
                $responseCode = $soapResult->SoapRequestResult->ResponseCode;

                if ($isSuccessful == 'Y'
                    && (strcmp($responseCode, Constants::API_RESPONSE_CODE_SUCCESS) == 0)
                ) {
                    $totalTax = $soapResult->SoapRequestResult->TotalTax;
                    $totalGwTax = 0;

                    $lineItemTaxArray = Utility::getLineItemLevelTaxByResponseWithRequest(
                        $soapRequest,
                        $soapResult
                    );

                    if (!isset($lineItemTaxArray) || $lineItemTaxArray == null) {
                        // we have no taxes
                        $this->saveZeroTaxes($address);
                        return $this;
                    }

                    $totalLineTax = 0;
                    $i = Constants::LINE_NUMBER_START_INDEX;
                    foreach ($items as $itemId => $item) {
                        $lineTax = $lineItemTaxArray[$i]['LineItemTax'];
                        $totalLineTax += isset($lineTax) ? $lineTax : 0;

                        // save line item level taxes at item level in db
                        $gwIndex = Constants::generateGiftWrapLineNumber($i);
                        if (array_key_exists($gwIndex, $lineItemTaxArray)) {
                            $gwLineItemArray = $lineItemTaxArray[$gwIndex];
                            $tempTax = $gwLineItemArray['LineItemTax'];
                            if (isset($tempTax)) {
                                $totalGwTax += $tempTax;
                            }
                            $this->saveGiftWrapTaxesForQuoteItem($address, $item, $tempTax, $store);
                        }
                        if ($totalGwTax) {
                            $address->setGwItemsTaxAmount($store->convertPrice($totalGwTax));
                            $address->setGwItemsBaseTaxAmount($totalGwTax);
                        }
                        $this->saveTaxesForQuoteItem($item, $lineItemTaxArray[$i], $store);
                        $i++;
                    }

                    //processing discount
                    $totalDiscountTax = array_sum($this->_totalDiscountWithTax);
                    $subtotalInclTax = $subtotal + $totalLineTax + $totalDiscountTax;
                    $address->setSubtotalInclTax($store->convertPrice($subtotalInclTax));
                    $address->setBaseSubtotalTotalInclTax($subtotalInclTax);
                    $totalTaxSt = $store->convertPrice($totalTax);
                    $address->setTaxAmount($totalTaxSt);
                    $address->setBaseTaxAmount($totalTax);
                    $this->_addAmount($totalTaxSt);
                    $this->_addBaseAmount($totalTax);

                    $shipA = $address->getBaseShippingAmount();
                    // check for shipping
                    $hasShippingLine = array_key_exists(
                        Constants::DEFAULT_SHIPPING_LINE_NUMBER,
                        $lineItemTaxArray
                    );

                    if ($hasShippingLine && isset($shipA) && $shipA != 0) {
                        $shipTax =
                                $lineItemTaxArray
                                [Constants::DEFAULT_SHIPPING_LINE_NUMBER]
                                ['LineItemTax'];
                        if (isset($shipTax) && $shipTax != 0) {
                            // e.g. ShipA = 100, discount = 20
                            // shipUsedCalc = 80, ShipTax is for this amount
                            // shipping_including_tax = full tax ignores discount
                            // $shipInclTaxAdjusted hold shipping + tax and may be adjusted for discounts to match Mage
                            $shipInclTaxAdjusted = $shipA + $shipTax;
                            $shipDis = $address->getBaseShippingDiscountAmount();
                            if ($shipDis) {
                                $shipUsedCalc = $shipA - $shipDis;
                                $shipApparentRate = $shipTax / $shipUsedCalc;
                                $shipInclTaxAdjusted = $shipA + ($shipApparentRate * $shipA);
                            }
                            $address->setShippingInclTax($store->convertPrice($shipInclTaxAdjusted));
                            $address->setBaseShippingInclTax($shipInclTaxAdjusted);
                            // holds actual tax
                            $address->setShippingTaxAmount($store->convertPrice($shipTax));
                            $address->setBaseShippingTaxAmount($shipTax);
                        }
                    }
                    // check gift wrap
                    if (array_key_exists(
                        Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                        $lineItemTaxArray
                    )) {
                        $tempTax = $lineItemTaxArray
                                [Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL]
                                ['LineItemTax'];
                        $this->saveGiftWrapTaxForQuote($address, $tempTax, $store);
                    }
                    if (array_key_exists(
                        Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                        $lineItemTaxArray
                    )) {
                        $tempTaxCard = $lineItemTaxArray
                                [Constants::DEFAULT_GW_CARD_LINE_NUMBER]
                                ['LineItemTax'];
                        $this->saveGiftWrapCardTaxForQuote($address, $tempTaxCard, $store);
                    }

                    $jurisdictionArray =
                        Utility::getJurisdictionLevelTaxesTotaledFromResponse($soapResult);
                    
                    $appliedTaxes = Utility::generateAppliedTaxesArray(
                        $jurisdictionArray,
                        $store
                    );
                    if ($appliedTaxes != null) {
                        $address->setAppliedTaxes($appliedTaxes);
                    }
                    return $this;
                } else {
                    $headerMessage = $soapResult->SoapRequestResult->HeaderMessage;

                    Utility::log(
                        'CALC ERROR: Failure Response for Quote '
                        . $address->getQuoteId()
                        . ' Header : ' . $headerMessage,
                        Zend_Log::ERR,
                        true
                    );
                    return parent::collect($address);
                }
            } catch (Exception $ere) {
                // ERROR HANDLING - CUSTOMER FACING.
                Utility::log(
                    'CALC ERROR: Exception calling Suretax API for Quote ' . $ere->getMessage()
                    . ' Quote ID = ' . $address->getQuoteId(),
                    Zend_Log::ERR,
                    true
                );
                return parent::collect($address);
            }
        }
        return $this;
    }

    /**
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @param type                        $suretaxLineItem
     *            array holding suretax lineitem tax info
     *            e.g. 'StateCode'=>$stateCode,
     *            'Revenue'=>$rev
     *            'RevenueBase'=>$revBase,
     *            'LineItemNumber'=>$linteItemNumber,
     *            'LineItemTax'=>$taxAmount
     * @param Mage_Core_Model_Store       $store
     */
    public function saveTaxesForQuoteItem($item, $suretaxLineItem, $store)
    {
        $rev = $suretaxLineItem['Revenue'];
        $taxT = $suretaxLineItem['LineItemTax'];
        $taxStore = $store->convertPrice($taxT);
        $item->setTaxAmount($taxStore);
        $item->setBaseTaxAmount($taxT);
        $quantity = $item->getQty();
        // sales_flat_quote_item
        // set: tax_percent, price_incl_tax, base_price_incl_tax,
        // row_total_incl_tax, base_row_total_incl_tax
        $taxPercent = (($rev == 0) ? 0 : (($taxT / $rev) * 100.0));
        
        $taxRate = (($rev == 0) ? 0 : ($taxT / $rev));
        $item->setTaxPercent($taxPercent);

        $rowTotal = $rev + $taxT;
        $priceInclTax = ($quantity == 0) ? 0 : $rowTotal / $quantity;

        //Take discount into consideration.
        $discAmount = $item->getBaseDiscountAmount();

        //Since calculation is done AFTER discount, we need to add the discount (and its taxes)
        //back to the price and row totals to see what the result would be before discount.
        $priceInclTaxBeforeDiscount = $priceInclTax + (($discAmount + ($discAmount * $taxRate)) / $quantity);
        $rowTotalBeforeDiscount = $rowTotal + $discAmount + ($discAmount * $taxRate);
        $this->_totalDiscountWithTax[$item->getId()] = $discAmount * $taxRate;

        // base -> default store currency
        // grand -> currency store currency
        // price is for single unit
        $item->setPriceInclTax($store->convertPrice($priceInclTaxBeforeDiscount));
        $item->setBasePriceInclTax($priceInclTaxBeforeDiscount);

        // row totals are unit price * quantity
        $item->setRowTotalInclTax($store->convertPrice($rowTotalBeforeDiscount));
        $item->setBaseRowTotalInclTax($rowTotalBeforeDiscount);
    }

    /**
     *
     * @param Mage_Sales_Model_Quote_Address      $address
     * @param Mage_Sales_Model_Quote_Address_Item $item
     * @param array                               $tax     holds taxes
     * @param Mage_Core_Model_Store               $store   used for currency conversion
     */
    protected function saveGiftWrapTaxesForQuoteItem(&$address, &$item, $tax, $store)
    {
        $qTax = 0;
        if (isset($tax) && $tax) {
            $qTax += $tax;
            $qty = $item->getQty();
            $gwTaxOnBasePrice = $tax / $qty;
            $item->setGwTaxAmount($store->convertPrice($gwTaxOnBasePrice));
            $item->setGwBaseTaxAmount($gwTaxOnBasePrice);
        }

        $qTax += $address->getGwItemsBaseTaxAmount();
        $address->setGwItemsTaxAmount($store->convertPrice($qTax));
        $address->setGwItemsBaseTaxAmount($qTax);
    }

    /**
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param type                           $tax
     * @param Mage_Core_Model_Store          $store
     */
    protected function saveGiftWrapTaxForQuote(&$address, $tax, $store)
    {
        if (isset($tax) && $tax) {
            $address->setGwTaxAmount($store->convertPrice($tax));
            $address->setGwBaseTaxAmount($tax);
        }
    }

    /**
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @param type                           $tax
     * @param Mage_Core_Model_Store          $store
     */
    protected function saveGiftWrapCardTaxForQuote(&$address, $tax, &$store)
    {
        if (isset($tax) && $tax) {
            $taxStore = $store->convertPrice($tax);
            $address->setGwCardTaxAmount($taxStore);
            $address->setGwCardBaseTaxAmount($tax);
            $quote = $address->getQuote();
            $quote->setGwCardTaxAmount($taxStore);
            $quote->setGwCardBaseTaxAmount($tax);
        }
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     */
    protected function saveZeroTaxes(&$address)
    {
        $address->setSubtotalInclTax($address->getSubtotal());
        $address->setBaseSubtotalTotalInclTax($address->getBaseSubtotal());
        $address->setTaxAmount(0);
        $address->setBaseTaxAmount(0);
        $address->setShippingTaxAmount(0);
        $address->setBaseShippingTaxAmount(0);
        $address->setShippingInclTax($address->getShippingAmount());
        $address->setBaseShippingInclTax($address->getBaseShippingAmount());
        // should be Mage_Sales_Model_Quote_Item

        $isMultiShipping  = $address->getQuote()->getIsMultiShipping();
        if ($isMultiShipping) {
            $itemArray = $address->getItemsCollection();
        } else {
            $itemArray = $address->getQuote()->getItemsCollection();
        }
        $store = $address->getQuote()->getStore();
        foreach ($itemArray as $item) {
            $item->setTaxAmount(0);
            $item->setBaseTaxAmount(0);
            // THIS isn't set correctly by magento, so convert from base
            // for some reason item price appears to hold the base value
            // setting price to the store currency results in Magento performing
            // an extra currency conversion when viewing price excluding tax on OnePage
            $item->setPriceInclTax($store->convertPrice($item->getBasePrice()));
            $item->setBasePriceInclTax($item->getBasePrice());
            $item->setRowTotalInclTax($item->getRowTotal());
            $item->setBaseRowTotalInclTax($item->getBaseRowTotal());
            $item->setTaxPercent(0.00);
        }
    }
}
