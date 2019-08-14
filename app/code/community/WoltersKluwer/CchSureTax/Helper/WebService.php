<?php
/**
 * Access SureTax web services (API)
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

use WoltersKluwer_CchSureTax_Helper_Env as Env;
use WoltersKluwer_CchSureTax_Helper_Constants as Constants;
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;
use WoltersKluwer_CchSureTax_Helper_Config as Config;

/**
 * This class makes all the web service requests necessary to get taxes
 */
class WoltersKluwer_CchSureTax_Helper_WebService
    extends Mage_Core_Helper_Abstract
    implements WoltersKluwer_CchSureTax_Helper_WebServiceInterface
{
    /**
     * Jurisdiction array to fill the order tax table.
     */
    protected $_jurisdictionArray = array();

    /**
     * For use to get discount with tax. - should be base amount.
     */
    protected $_totalDiscountWithTax = array();
    
    /**
     * To store all tax class name ID => Class Name.
     */
    protected $_taxClassNameArray = null;

    /**
     *     $address is array of following format:
     *      'PrimaryAddressLine'=>$addressLine1,
     *      'SecondaryAddressLine'=>$addressLine2,
     *      'City'=>$city,
     *      'State'=>$stateCode,
     *      'PostalCode'=>$zip,
     *      'Plus4'=>$plus4,
     *      'Country'=>$country
     *      'VerifyAddress'=>"");
     *
     * @param  array $addressAsArray
     * @return WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress
     */
    public function convertToSureTaxAddress($addressAsArray)
    {
        $retAddress = Mage::getModel(
            'wolterskluwer_cchsuretax/ws_sureTaxAddress',
            $addressAsArray
        );

        return $retAddress;
    }

    /**
     * @param WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $address
     * @return WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress
     */
    public function copySureTaxAddress($address)
    {
        if (!isset($address) || $address == null) {
            return null;
        }

        $retAddress = Mage::getModel(
            'wolterskluwer_cchsuretax/ws_sureTaxAddress',
            array (
                'PrimaryAddressLine'    => $address->PrimaryAddressLine,
                'SecondaryAddressLine'  => $address->SecondaryAddressLine,
                'County'                => $address->County,
                'City'                  => $address->City,
                'State'                 => $address->State,
                'PostalCode'            => $address->PostalCode,
                'Plus4'                 => $address->Plus4,
                'Country'               => $address->Country,
                'Geocode'               => $address->Geocode,
                'VerifyAddress'         => $address->VerifyAddress
            )
        );

        return $retAddress;
    }

    /**
     * STAN is unique ID for SureTax
     *
     * @param  string $theId magento's order Id
     * @return string
     */
    public function generateUniqueId($theId)
    {
        return $theId . Env::ENV;
    }

    /**
     *
     * @param string $theId magento's creditmemo Id
     * @return string
     */
    public function generateUniqueIdForCreditMemo($theId)
    {
        return 'CR_' . $theId . Env::ENV;
    }

    public function createSureTaxRequestFromQuoteAddress(
        Mage_Sales_Model_Quote_Address $address,
        $estimatedAddress = null
    ) {
        try {
            $config = Config::get();
            $store = $address->getQuote()->getStore();
            $businessUnit = $this->getBusinessUnit(
                $config,
                $store->getWebsiteId(),
                $store->getGroupId()
            );

            $returnFileCode = 'Q';  // for Query
            $sTAN = '';             // never set this, we only have an address

            $currentClientTracking = 'Quote';

            // get ship to ship from
            $shipToAddress = null;
            if ($estimatedAddress != null) {
                $shipToAddress = $this->convertToSureTaxAddress($estimatedAddress);
            } else {
                $shipToAddress = $this->convertToSureTaxAddress(
                    Utility::getShipToAddress($address)
                );
            }

            // don't need billing address for quote, we might want to send a
            // blank address
            $billingAddress = null;
            $totalRevenue = 0;

            $shipFromAddress =  $this->convertToSureTaxAddress(
                Utility::getShipFromAddress(
                    $store->getWebsiteId(),
                    $store->getGroupId()
                )
            );

            $trxDate = date_format(new DateTime(), 'Y-m-d H:i:s');

            /*Use Quote Items on single address transactions. For Multiple
            Addresses, use addressItems */
            $isMultiShipping = $address->getQuote()->getIsMultiShipping();
            if ($isMultiShipping) {
                $itemsCollection = $address->getItemsCollection();
            } else {
                $itemsCollection = $address->getQuote()->getItemsCollection();
            }

            // if this is from 'ESTIMATE' from the website don't verify ship to
            $shipToAddress->VerifyAddress = false;
            $quote = $address->getQuote();
            $customerId = $address->getCustomerId();
            $customerFields = $this->getCustomerFields(
                $customerId,
                $quote->getCustomerGroupId()
            );
            
            $i = Constants::LINE_NUMBER_START_INDEX;
            foreach ($itemsCollection as $item) {
                $lineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $trxDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $lineItem;
                $totalRevenue += $lineItem->Revenue;

                // If gift wrap supported and we have it for this line item, add to suretax line items
                $lineItemGift = $this->getLineItem(
                    Constants::generateGiftWrapLineNumber($i),
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $trxDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($lineItemGift !== null) {
                    $lineItemArray[] = $lineItemGift;
                    $totalRevenue += $lineItemGift->Revenue;
                }

                $i++;
            }

            /* Try to see if there's an order level gift option, gift wrap is
            from $address for quotes, otherwise it accumulates gw and gw card
            prices */
            $orderLevelGift = $this->getLineItem(
                Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                $address,
                $billingAddress,
                $shipToAddress,
                $shipFromAddress,
                $trxDate,
                0,
                true,
                $config->getProviderType(),
                false,
                $config,
                $customerFields[Constants::SALES_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                $customerId
            );
            if ($orderLevelGift !== null) {
                $lineItemArray[] = $orderLevelGift;
                $totalRevenue += $orderLevelGift->Revenue;
            }

            $orderLevelGiftCard = $this->getLineItem(
                Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                $address,
                $billingAddress,
                $shipToAddress,
                $shipFromAddress,
                $trxDate,
                0,
                true,
                $config->getProviderType(),
                false,
                $config,
                $customerFields[Constants::SALES_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                $customerId
            );
            if ($orderLevelGiftCard !== null) {
                $lineItemArray[] = $orderLevelGiftCard;
                $totalRevenue += $orderLevelGiftCard->Revenue;
            }

            //Base Shipping Discount amount is a positive number.
            $shippingAfterDiscount = $address->getBaseShippingAmount() -
                $address->getBaseShippingDiscountAmount();

            if ($shippingAfterDiscount) {
                $shippingLineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $trxDate,
                    $shippingAfterDiscount,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $shippingLineItem;
                $totalRevenue += $shippingLineItem->Revenue;
            }

            // Create the SureTaxRequest

            /* @var $requestData WoltersKluwer_CchSureTax_Model_Ws_SureTaxRequest*/
            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date('Y'),
                    'DataMonth'         => date('m'),
                    'CmplDataYear'      => date('Y'),
                    'CmplDataMonth'     => date('m'),
                    'TotalRevenue'      => $totalRevenue,
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'ReturnFileCode'    => $returnFileCode,
                    'STAN'              => $sTAN,
                    'MasterTransId'     => '0',
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'ItemList'          => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Quote::Exception at createSureTaxRequestFromQuoteAddress'
            );
            throw $exe;
        }
    }

    public function makeSureTaxRequestFromQuoteAddress($requestData)
    {
        try {
            $config = Config::get();
            /* @var $client WoltersKluwer_CchSureTax_Model_Ws_SureTaxClient*/
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $soapCalcRequestResult = $client->callSoapRequest($requestData);
            return $soapCalcRequestResult;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Quote::Exception with makeSureTaxRequestFromQuoteAddress'
            );
            throw $exe;
        }
    }

    public function createSureTaxRequestFromInvoice($invoice, $shipToAddressArray)
    {
        try {
            $config = Config::get();
            $order = $invoice->getOrder();

            $returnFileCode = '0';
            $uniqueId = $this->generateUniqueId($invoice->getIncrementId());
            $sTAN = $uniqueId;
            $currentClientTracking = $uniqueId;
            $store = $order->getStore();
            $businessUnit = $this->getBusinessUnit(
                $config,
                $store->getWebsiteId(),
                $store->getGroupId()
            );

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = null;

            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddress(
                    $store->getWebsiteId(),
                    $store->getGroupId()
                )
            );

            $totalRevenue = 0;

            $ordCreationDate = $order->getCreatedAt();
            $invCreationDate = $invoice->getCreatedAt();
            $customerId = $order->getCustomerId();
            $customerFields = $this->getCustomerFields(
                $customerId,
                $order->getCustomerGroupId()
            );

            $i = Constants::LINE_NUMBER_START_INDEX;
            $allItems = $invoice->getAllItems();

            foreach ($allItems as $item) {
                $lineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                $lineItemArray[] = $lineItem;
                $totalRevenue += $lineItem->Revenue;

                // if gift wrap supported and we have it for this line item,
                // add to suretax line items
                $lineItemGift = $this->getLineItem(
                    Constants::generateGiftWrapLineNumber($i),
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                if ($lineItemGift !== null) {
                    $lineItemArray[] = $lineItemGift;
                    $totalRevenue += $lineItemGift->Revenue;
                }
                $i++;
            }

            // try to see if there's an order level gift option
            if (null !== $invoice->getGwBasePrice() &&  $invoice->getGwBasePrice() > 0) {
                //This condition is only to check if its 1st or only invoice and set
                //Order level GW and Print card to line items
                $orderLevelGift = $this->getLineItem(
                    Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                    $order,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    true,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                if ($orderLevelGift !== null) {
                    $lineItemArray[] = $orderLevelGift;
                    $totalRevenue += $orderLevelGift->Revenue;
                }
            }
            if (null !== $invoice->getGwCardBasePrice() &&  $invoice->getGwCardBasePrice() > 0) {
                $invoiceLevelGiftCard = $this->getLineItem(
                    Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                    $order,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    true,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                if ($invoiceLevelGiftCard !== null) {
                    $lineItemArray[] = $invoiceLevelGiftCard;
                    $totalRevenue += $invoiceLevelGiftCard->Revenue;
                }
            }

            $shippingAfterDiscount = $invoice->getBaseShippingAmount();

            if (isset($shippingAfterDiscount) && $shippingAfterDiscount > 0) {
                $shippingAfterDiscount -= $order->getBaseShippingDiscountAmount();

                $shippingLineItem = $this->getLineItem(
                    Constants::DEFAULT_SHIPPING_LINE_NUMBER,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    $shippingAfterDiscount,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                $lineItemArray[] = $shippingLineItem;
                $totalRevenue += $shippingLineItem->Revenue;
            }

            // create a SureTaxRequest object

            /* @var $requestData WoltersKluwer_CchSureTax_Model_Ws_SureTaxRequest*/
            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($ordCreationDate)['year'],
                    'DataMonth'         => date_parse($ordCreationDate)['month'],
                    'CmplDataYear'      => date_parse($invCreationDate)['year'],
                    'CmplDataMonth'     => date_parse($invCreationDate)['month'],
                    'TotalRevenue'      => $totalRevenue,
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'ReturnFileCode'    => $returnFileCode,
                    'STAN'              => $sTAN,
                    'MasterTransId'     => '0',
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'ItemList'          => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Invoice::Exception with createSureTaxRequestFromInvoice'
            );

            throw $exe;
        }
    }
    
    public function createSureTaxRequestFromBatchInvoice(
        $invoice,
        $shipToAddressArray,
        $wsConfigArray
    ) {
        try {
            $config = Config::get();
            $returnFileCode = '0';
            $uniqueId = $this->generateUniqueId($invoice->getIncrementId());
            $sTAN = $uniqueId;
            $currentClientTracking = $uniqueId;
            $websiteId = $invoice->getWebsiteId();
            $storeId = $invoice->getGroupId();
            $businessUnit = Utility::getBusinessUnit(
                $config,
                $websiteId,
                $storeId,
                $wsConfigArray
            );

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = null;

            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddressFromConfig(
                    $invoice->getWebsiteId(),
                    $invoice->getGroupId(),
                    $wsConfigArray
                )
            );

            $totalRevenue = 0;

            $ordCreationDate = $invoice->getOrderCreatedAt();
            $invCreationDate = $invoice->getCreatedAt();
            $customerId = $invoice->getCustomerId();
            $customerFields = $this->getCustomerFieldsFromInvoiceOrCreditMemo($invoice);

            $i = Constants::LINE_NUMBER_START_INDEX;
            $allItems = $invoice->getAllItems();

            foreach ($allItems as $item) {
                $lineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                $lineItemArray[] = $lineItem;
                $totalRevenue += $lineItem->Revenue;

                // if gift wrap supported and we have it for this line item,
                // add to suretax line items
                $lineItemGift = $this->getLineItem(
                    Constants::generateGiftWrapLineNumber($i),
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                if ($lineItemGift !== null) {
                    $lineItemArray[] = $lineItemGift;
                    $totalRevenue += $lineItemGift->Revenue;
                }
                $i++;
            }

            // try to see if there's an order level gift option
            if (null !== $invoice->getGwBasePrice() &&  $invoice->getGwBasePrice() > 0) {
                //This condition is only to check if its 1st or only invoice and set
                //Order level GW and Print card to line items
                $orderLevelGift = $this->getLineItem(
                    Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                    $invoice,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                if ($orderLevelGift !== null) {
                    $lineItemArray[] = $orderLevelGift;
                    $totalRevenue += $orderLevelGift->Revenue;
                }
            }

            if (null !== $invoice->getGwCardBasePrice() &&  $invoice->getGwCardBasePrice() > 0) {
                $invoiceLevelGiftCard = $this->getLineItem(
                    Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                    $invoice,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                if ($invoiceLevelGiftCard !== null) {
                    $lineItemArray[] = $invoiceLevelGiftCard;
                    $totalRevenue += $invoiceLevelGiftCard->Revenue;
                }
            }

            $shippingAfterDiscount = $invoice->getBaseShippingAmount();

            if (isset($shippingAfterDiscount) && $shippingAfterDiscount > 0) {
                $shippingAfterDiscount -= $invoice->getBaseShippingDiscountAmount();

                $shippingLineItem = $this->getLineItem(
                    Constants::DEFAULT_SHIPPING_LINE_NUMBER,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    $shippingAfterDiscount, 
                    false,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                $lineItemArray[] = $shippingLineItem;
                $totalRevenue += $shippingLineItem->Revenue;
            }

            // create a SureTaxRequest object

            /* @var $requestData WoltersKluwer_CchSureTax_Model_Ws_SureTaxRequest*/
            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($ordCreationDate)['year'],
                    'DataMonth'         => date_parse($ordCreationDate)['month'],
                    'CmplDataYear'      => date_parse($invCreationDate)['year'],
                    'CmplDataMonth'     => date_parse($invCreationDate)['month'],
                    'TotalRevenue'      => $totalRevenue,
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'ReturnFileCode'    => $returnFileCode,
                    'STAN'              => $sTAN,
                    'MasterTransId'     => '0',
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'ItemList'          => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Invoice::Exception with createSureTaxRequestFromInvoice'
            );

            throw $exe;
        }
    }

    public function createSureTaxRequestFromOrder(
        Mage_Sales_Model_Order $order,
        $isFinalize,
        $shipToAddressArray
    ) {

        try {
            $config = Config::get();

            $store = $order->getStore();
            $uniqueId = $this->generateUniqueId($order->getIncrementId());
            $returnFileCode = 'Q';  // for Query
            $sTAN = '';

            $currentClientTracking = $uniqueId;
            if ($isFinalize == true) {
                $returnFileCode = '0';
                $sTAN = $uniqueId;
            }

            $businessUnit = $this->getBusinessUnit(
                $config,
                $store->getWebsiteId(),
                $store->getGroupId()
            );

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = $this->convertToSureTaxAddress(
                Utility::getShipToAddress(
                    Mage::getModel('sales/order_address')
                        ->load($order->getBillingAddress()->getId())
                )
            );

            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddress(
                    $store->getWebsiteId(),
                    $store->getGroupId()
                )
            );

            $totalRevenue = 0;
            $trxDate = $order->getCreatedAt();
            $customerId = $order->getCustomerId();
            $customerFields = $this->getCustomerFields(
                $customerId,
                $order->getCustomerGroupId()
            );

            $i = Constants::LINE_NUMBER_START_INDEX;
            foreach ($order->getAllItems() as $itemId => $item) {
                $lineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $trxDate,
                    0,
                    true,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );

                $lineItemArray[] = $lineItem;
                $totalRevenue += $lineItem->Revenue;

                // if gift wrap supported and we have it for this line item,
                // add to suretax line items
                $lineItemGift = $this->getLineItem(
                    Constants::generateGiftWrapLineNumber($i),
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $trxDate,
                    0,
                    true,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($lineItemGift !== null) {
                    $lineItemArray[] = $lineItemGift;
                    $totalRevenue += $lineItemGift->Revenue;
                }
                $i++;
            }

            // try to see if there's an order level gift option
            $orderLevelGift = $this->getLineItem(
                Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                $order,
                $billingAddress,
                $shipToAddress,
                $shipFromAddress,
                $trxDate,
                0,
                true,
                $config->getProviderType(),
                false,
                $config,
                $customerFields[Constants::SALES_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                $customerId
            );
            if ($orderLevelGift !== null) {
                $lineItemArray[] = $orderLevelGift;
                $totalRevenue += $orderLevelGift->Revenue;
            }

            $orderLevelGiftCard = $this->getLineItem(
                Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                $order,
                $billingAddress,
                $shipToAddress,
                $shipFromAddress,
                $trxDate,
                0,
                true,
                $config->getProviderType(),
                false,
                $config,
                $customerFields[Constants::SALES_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                $customerId
            );
            if ($orderLevelGiftCard !== null) {
                $lineItemArray[] = $orderLevelGiftCard;
                $totalRevenue += $orderLevelGiftCard->Revenue;
            }

            $shippingAfterDiscount = $order->getBaseShippingAmount() -
                $order->getBaseShippingDiscountAmount();

            if ($shippingAfterDiscount) {
                $shippingLineItem = $this->getLineItem(
                    Constants::DEFAULT_SHIPPING_LINE_NUMBER,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $trxDate,
                    $shippingAfterDiscount,
                    true,
                    $config->getProviderType(),
                    false,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $shippingLineItem;
                $totalRevenue += $shippingLineItem->Revenue;
            }

            // create a SureTaxRequest object

            /* @var $requestData WoltersKluwer_CchSureTax_Model_Ws_SureTaxRequest*/
            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($trxDate)['year'],
                    'DataMonth'         => date_parse($trxDate)['month'],
                    'CmplDataYear'      => date_parse($trxDate)['year'],
                    'CmplDataMonth'     => date_parse($trxDate)['month'],
                    'TotalRevenue'      => $totalRevenue,
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'ReturnFileCode'    => $returnFileCode,
                    'STAN'              => $sTAN,
                    'MasterTransId'     => '0',
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'ItemList'          => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Order createSureTaxRequestFromOrder '
            );
            throw $exe;
        }
    }

    public function makeSureTaxRequestFromOrder($requestData)
    {
        try {
            $config = Config::get();
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $soapCalcRequestResult = $client->callSoapRequest($requestData);
            return $soapCalcRequestResult;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Order::Exception with makeSureTaxRequestFromOrder'
            );
            throw $exe;
        }
    }

    public function makeSureTaxRequest($requestData)
    {
        try {
            $config = Config::get();
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $soapCalcRequestResult = $client->callSoapRequest($requestData);
            return $soapCalcRequestResult;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Invoice:  Exception with makeSureTaxRequest'
            );
            throw $exe;
        }
    }
    
    public function makeSureTaxBatchRequest($requestDataArray)
    {
        try {
            $config = Config::get();
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $soapBatchCalcRequestResult = $client->callSoapRequestBatch($requestDataArray);
            return $soapBatchCalcRequestResult;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Invoice:  Exception with makeSureTaxBatchRequest'
            );
            throw $exe;
        }
    }

    /**
     * Get Customer Fields in array form.
     *
     * @param  int $customerId
     * @param  int $customerGroupId
     * @return array
     */
    protected function getCustomerFields($customerId, $customerGroupId)
    {
        $customer = Mage::getModel(Constants::CUST_TBL)
            ->load($customerId, 'suretax_customer_id');
        $salesTypeCodeDesc = Constants::DEFAULT_SALES_TYPE_CODE;
        $exemptionCodeDesc = Constants::DEFAULT_EXEMPTION_CODE;
        $exemptionReasonDesc = Constants::DEFAULT_EXEMPTION_REASON_CODE;
        $theId = $customer->getId();
        if (empty($theId)) {
            $customerGroup = Mage::getModel(Constants::CUST_GRP_TBL)
                ->load($customerGroupId, 'suretax_customer_group_id');
            $theGroupId = $customerGroup->getId();
            if (!empty($theGroupId)) {
                $salesTypeCodeDesc = $customerGroup->getSalesTypeCode();
                $exemptionCodeDesc = $customerGroup->getExemptionCode();
                $exemptionReasonDesc = $customerGroup->getExemptionReasonCode();
            }
        } else {
            $salesTypeCodeDesc = $customer->getSalesTypeCode();
            $exemptionCodeDesc = $customer->getExemptionCode();
            $exemptionReasonDesc = $customer->getExemptionReasonCode();
        }
        try {
            $salesTypeCode
                = Constants::$SALES_TYPE_CODES_VALUES[$salesTypeCodeDesc];
            $exemptionCode
                = Constants::$EXEMPTION_TYPE_CODES_VALUES[$exemptionCodeDesc];
            $exemptionReasonCode
                = Constants::$EXEMPTION_REASON_CODES_VALUES[$exemptionReasonDesc];
        } catch (Exception $e) {
            Utility::logCatch($e, 'WebService: getCustomerFields');
        }
        return array(
            Constants::SALES_TYPE_CODE_KEY => $salesTypeCode,
            Constants::EXEMPTION_TYPE_CODE_KEY => $exemptionCode,
            Constants::EXEMPTION_REASON_CODE_KEY => $exemptionReasonCode
        );
    }
    
    /**
     * Get Customer Fields in array form.
     *
     * @param  object $invoice
     * @param  int    $customerGroupId
     * @return array
     */
    protected function getCustomerFieldsFromInvoiceOrCreditMemo($object)
    {
        $salesTypeCodeDesc = Constants::DEFAULT_SALES_TYPE_CODE;
        $exemptionCodeDesc = Constants::DEFAULT_EXEMPTION_CODE;
        $exemptionReasonDesc = Constants::DEFAULT_EXEMPTION_REASON_CODE;
        if (empty($object->getCustomerExemptionCode())) {
            if (!empty($object->getCustomerGroupExemptionCode())) {
                $salesTypeCodeDesc = $object->getCustomerGroupSalesTypeCode();
                $exemptionCodeDesc = $object->getCustomerGroupExemptionCode();
                $exemptionReasonDesc = $object->getCustomerGroupExemptionReasonCode();
            }
        } else {
            $salesTypeCodeDesc = $object->getCustomerSalesTypeCode();
            $exemptionCodeDesc = $object->getCustomerExemptionCode();
            $exemptionReasonDesc = $object->getCustomerExemptionReasonCode();
        }
        try {
            $salesTypeCode
                = Constants::$SALES_TYPE_CODES_VALUES[$salesTypeCodeDesc];
            $exemptionCode
                = Constants::$EXEMPTION_TYPE_CODES_VALUES[$exemptionCodeDesc];
            $exemptionReasonCode
                = Constants::$EXEMPTION_REASON_CODES_VALUES[$exemptionReasonDesc];
        } catch (Exception $e) {
            Utility::logCatch($e, 'WebService: getCustomerFieldsFromInvoiceOrCreditMemo');
        }
        return array(
            Constants::SALES_TYPE_CODE_KEY => $salesTypeCode,
            Constants::EXEMPTION_TYPE_CODE_KEY => $exemptionCode,
            Constants::EXEMPTION_REASON_CODE_KEY => $exemptionReasonCode
        );
    }

    public function sendPing()
    {
        try {
            $config = Config::get();
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $client->callHealthMonitor();
        } catch (Exception $exe) {
            Utility::logCatch($exe, 'Exception with sendPing');
        }
    }

    public function makeCancelRequest($order)
    {
        try {
            $config = Config::get();
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $theId = $this->generateUniqueId($order->getIncrementId());

            $clientTracking = 'Cancel ' . $order->getIncrementId();
            $soapCancelRequestResult = $client->callCancelSoapRequestWithSTAN($theId, $clientTracking);

            return $soapCancelRequestResult;
        } catch (Exception $ere) {
            Utility::logCatch($ere, 'Cancel: Exception with makeCancelRequest');
            throw $ere;
        }
    }

    public function createSureTaxRequestFromCreditmemo(
        Mage_Sales_Model_Order_Creditmemo $creditMemo,
        $shipToAddressArray
    ) {
    
        try {
            $config = Config::get();
            $order = $creditMemo->getOrder();
            $store = $order->getStore();
            $ordCreationDate = $order->getCreatedAt();
            $creditmemoCreationDate = $creditMemo->getCreatedAt();

            $masterTransId = '0';
            $returnFileCode = 0;

            $businessUnit = $this->getBusinessUnit(
                $config,
                $store->getWebsiteId(),
                $store->getGroupId()
            );

            $uniqueId = $this->generateUniqueIdForCreditMemo(
                $creditMemo->getIncrementId()
            );

            $currentClientTracking = $this->generateUniqueId(
                $creditMemo->getOrderIncrementId()
                . 'CT'
                . $creditMemo->getIncrementId()
            );
            $defaultProviderType = $config->getProviderType();

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = null;
            $this->convertToSureTaxAddress(
                Utility::getShipToAddress(
                    Mage::getModel('sales/order_address')
                    ->load($order->getBillingAddress()->getId())
                )
            );

            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddress(
                    $store->getWebsiteId(),
                    $store->getGroupId()
                )
            );

            $totalRevenue = 0;
            $customerId = $order->getCustomerId();
            $customerFields = $this->getCustomerFields(
                $customerId,
                $order->getCustomerGroupId()
            );

            $i = Constants::LINE_NUMBER_START_INDEX;
            $allItems = $creditMemo->getAllItems();

            foreach ($allItems as $item) {
                $lineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $defaultProviderType,
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $lineItem;
                $totalRevenue += $lineItem->Revenue;

                $lineItemGift = $this->getLineItem(
                    Constants::generateGiftWrapLineNumber($i),
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($lineItemGift !== null) {
                    $lineItemArray[] = $lineItemGift;
                    $totalRevenue += $lineItemGift->Revenue;
                }
                $i++;
            }

            if (null !== $creditMemo->getGwBasePrice() && $creditMemo->getGwBasePrice() > 0) {
                $orderLevelGift = $this->getLineItem(
                    Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                    $order,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    true,
                    $config->getProviderType(),
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($orderLevelGift !== null) {
                    $lineItemArray[] = $orderLevelGift;
                    $totalRevenue += $orderLevelGift->Revenue;
                }
            }
            if (null !== $creditMemo->getGwCardBasePrice() && $creditMemo->getGwCardBasePrice() > 0) {
                $orderLevelGiftCard = $this->getLineItem(
                    Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                    $order,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    true,
                    $config->getProviderType(),
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($orderLevelGiftCard != null) {
                    $lineItemArray[] = $orderLevelGiftCard;
                    $totalRevenue += $orderLevelGiftCard->Revenue;
                }
            }

            $shippingAmount = $creditMemo->getBaseShippingAmount();

            if (isset($shippingAmount) && $shippingAmount > 0) {
                $shippingLineItem = $this->getLineItem(
                    Constants::DEFAULT_SHIPPING_LINE_NUMBER,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    $shippingAmount,
                    false,
                    $defaultProviderType,
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $shippingLineItem;
                $totalRevenue += $shippingLineItem->Revenue;
            }

            $stan = $uniqueId;
            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($ordCreationDate)['year'],
                    'DataMonth'         => date_parse($ordCreationDate)['month'],
                    'CmplDataYear'      => date_parse($creditmemoCreationDate)['year'],
                    'CmplDataMonth'     => date_parse($creditmemoCreationDate)['month'],
                    'TotalRevenue'      => $totalRevenue,
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'ReturnFileCode'    => $returnFileCode,
                    'STAN'              => $stan,
                    'MasterTransId'     => $masterTransId,
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'ItemList'          => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $ere) {
            Utility::logCatch(
                $ere,
                'CreditMemo: Exception with createSureTaxRequestFromCreditmemo'
            );
            throw $ere;
        }
    }
    
    public function createSureTaxRequestFromBatchCreditmemo(
        Mage_Sales_Model_Order_Creditmemo $creditMemo,
        $shipToAddressArray,
        $wsConfigArray
    ) {

        try {
            $config = Config::get();

            $ordCreationDate = $creditMemo->getOrderCreatedAt();
            $creditmemoCreationDate = $creditMemo->getCreatedAt();
            
            $websiteId = $creditMemo->getWebsiteId();
            $storeId = $creditMemo->getGroupId();

            $masterTransId = '0';
            $returnFileCode = 0;

            $businessUnit = $this->getBusinessUnit(
                $config,
                $websiteId,
                $storeId,
                $wsConfigArray
            );

            $uniqueId = $this->generateUniqueIdForCreditMemo(
                $creditMemo->getIncrementId()
            );

            $currentClientTracking = $this->generateUniqueId(
                $creditMemo->getOrderIncrementId()
                .'CT'
                .$creditMemo->getIncrementId()
            );
            $defaultProviderType = $config->getProviderType();

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = null;
            
            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddressFromConfig(
                    $websiteId,
                    $storeId,
                    $wsConfigArray
                )
            );

            $totalRevenue = 0;
            $customerId = $creditMemo->getCustomerId();
            $customerFields = $this->getCustomerFieldsFromInvoiceOrCreditMemo(
                $creditMemo
            );

            $i = Constants::LINE_NUMBER_START_INDEX;
            $allItems = $creditMemo->getAllItems();

            foreach ($allItems as $item) {
                $lineItem = $this->getLineItem(
                    $i,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $defaultProviderType,
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $lineItem;
                $totalRevenue += $lineItem->Revenue;

                $lineItemGift = $this->getLineItem(
                    Constants::generateGiftWrapLineNumber($i),
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($lineItemGift != null) {
                    $lineItemArray[] = $lineItemGift;
                    $totalRevenue += $lineItemGift->Revenue;
                }
                $i++;
            }

            if (null !== $creditMemo->getGwBasePrice() && $creditMemo->getGwBasePrice() > 0) {
                $orderLevelGift = $this->getLineItem(
                    Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL,
                    $creditMemo,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($orderLevelGift !== null) {
                    $lineItemArray[] = $orderLevelGift;
                    $totalRevenue += $orderLevelGift->Revenue;
                }
            }
            if (null !== $creditMemo->getGwCardBasePrice() && $creditMemo->getGwCardBasePrice() > 0) {
                $orderLevelGiftCard = $this->getLineItem(
                    Constants::DEFAULT_GW_CARD_LINE_NUMBER,
                    $creditMemo,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    0,
                    false,
                    $config->getProviderType(),
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                if ($orderLevelGiftCard !== null) {
                    $lineItemArray[] = $orderLevelGiftCard;
                    $totalRevenue += $orderLevelGiftCard->Revenue;
                }
            }

            $shippingAmount = $creditMemo->getBaseShippingAmount();

            if (isset($shippingAmount) && $shippingAmount > 0) {
                $shippingLineItem = $this->getLineItem(
                    Constants::DEFAULT_SHIPPING_LINE_NUMBER,
                    $item,
                    $billingAddress,
                    $shipToAddress,
                    $shipFromAddress,
                    $ordCreationDate,
                    $shippingAmount,
                    false,
                    $defaultProviderType,
                    true,
                    $config,
                    $customerFields[Constants::SALES_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                    $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                    $customerId
                );
                $lineItemArray[] = $shippingLineItem;
                $totalRevenue += $shippingLineItem->Revenue;
            }

            $stan = $uniqueId;
            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($ordCreationDate)['year'],
                    'DataMonth'         => date_parse($ordCreationDate)['month'],
                    'CmplDataYear'      => date_parse($creditmemoCreationDate)['year'],
                    'CmplDataMonth'     => date_parse($creditmemoCreationDate)['month'],
                    'TotalRevenue'      => $totalRevenue,
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'ReturnFileCode'    => $returnFileCode,
                    'STAN'              => $stan,
                    'MasterTransId'     => $masterTransId,
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'ItemList'          => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $ere) {
            Utility::logCatch(
                $ere,
                'CreditMemo: Exception with createSureTaxRequestFromCreditmemo'
            );
            throw $ere;
        }
    }

    public function makeCreditRequest($requestData)
    {
        try {
            $config = Config::get();
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );

            $soapReturnRequestResult = $client->callSoapRequest($requestData);
            return $soapReturnRequestResult;
        } catch (Exception $ere) {
            Utility::logCatch($ere, 'CreditMemo: Exception with makeCreditRequest');
            throw $ere;
        }
    }

    public function getTotalTaxFromResponse($response)
    {
        return $response->SoapRequestResult->TotalTax;
    }

    /**
     * Create Line item array that needs to be attached to the request
     *
     * @param  int                                              $i
     * @param  Mage_Sales_Model_Quote_Item|
     *         Mage_Sales_Model_Order_Invoice_Item|
     *         Mage_Sales_Model_Order_Item|
     *         Mage_Sales_Model_Order_Creditmemo_Item           $item
     * @param  WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $billingAddress
     * @param  WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $shipToAddress
     * @param  WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $shipFromAddress
     * @param  string                                           $trxDate
     * @param  float                                            $shippingCharges
     * @param  boolean                                          $isOrderObject
     * @param  string                                           $providerType
     * @param  boolean                                          $isCredit
     * @param  WoltersKluwer_CchSureTax_Helper_Config           $config
     * @param  string                                           $salesTypeCode
     * @param  string                                           $exemptionCode
     * @param  string                                           $exemptionReason
     * @param  int                                              $customerId
     * @return null|
     *          WoltersKluwer_CchSureTax_Model_Ws_SureTaxItem SureTaxItem
     *         OR null if no line item created (e.g. gift wrap not supported)
     *         SureTaxItem or null if no line item created (e.g. gift wrap not
     *         supported)
     */
    public function getLineItem($i, $item, $billingAddress, $shipToAddress,
        $shipFromAddress, $trxDate, $shippingCharges, $isOrderObject,
        $providerType, $isCredit, $config, $salesTypeCode, $exemptionCode,
        $exemptionReason, $customerId
    ) {
        $isGift = Utility::startsWith(
            $i,
            Constants::DEFAULT_GW_LINE_NUMBER_PREPEND
        );
        $isGiftOrder = (
            strcmp(Constants::DEFAULT_GW_LINE_NUMBER_ORDER_LEVEL, $i) == 0
        );
        
        $itemToCheck = $item;
        if ($item instanceof Mage_Sales_Model_Order_Invoice_Item
            || $item instanceof Mage_Sales_Model_Order_Creditmemo_Item
        ) {
            $itemToCheck = $item->getOrderItem();
        }
        
        // is gift supported (ee vs ce)?
        if (($isGift || $isGiftOrder) && !Utility::isGiftWrap($itemToCheck)) {
            return null;
        }

        // gift card is only at quote/order level
        $isGiftWrapCard = (
            strcmp(Constants::DEFAULT_GW_CARD_LINE_NUMBER, $i) == 0
        );

        if ($isGiftWrapCard && !Utility::isGiftWrapCard($itemToCheck)) {
            return null;
        }

        $qty = 1;
        $lineNumber = $i;
        //get it from a method by passing SKU and get tax class of the SKU
        $transTypeCode = Constants::DEFAULT_TRANS_TYPE_CODE;
        $sku = '';

        if ($isGift) {
            $qty = $isOrderObject ? $item->getQtyOrdered() : $item->getQty();
            $transTypeCode = $config->getDefaulGiftWrapClass();
            $rowTotal = $itemToCheck->getGwBasePrice() * $qty;
            $sku = Constants::DEFAULT_GW_SKU_NAME;
        } elseif ($isGiftOrder) {
            $qty = 1;
            $transTypeCode = $config->getDefaulGiftWrapClass();
            $rowTotal = $item->getGwBasePrice();
            $sku = Constants::DEFAULT_GW_SKU_NAME;
        } elseif ($isGiftWrapCard) {
            $qty = 1;
            $transTypeCode = $config->getDefaulGiftWrapClass();
            $rowTotal = $item->getGwCardBasePrice();
            $sku = Constants::DEFAULT_GW_CARD_SKU_NAME;
        } elseif ($shippingCharges != 0) {
            $rowTotal = $shippingCharges;
            $transTypeCode = $config->getDefaultShippingClass();
            $lineNumber = Constants::DEFAULT_SHIPPING_LINE_NUMBER;
            $sku = Constants::DEFAULT_SHIPPING_SKU_NAME;
        } else {
            $rowTotal = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
            $qty = $isOrderObject ? $item->getQtyOrdered() : $item->getQty();
            
            try {
                $sku = $item->getSku();
                $transTypeCode = $this->getTransTypeCode($itemToCheck);
            } catch (Exception $ex) {
                Utility::logCatch($ex, 'WebService get line items');
            }
        }
        $rowTotal = $isCredit ? ($rowTotal * -1) : $rowTotal;

        $retItem = Mage::getModel(
            'wolterskluwer_cchsuretax/ws_sureTaxItem',
            array (
                'LineNumber' => $lineNumber,
                'InvoiceNumber' => '',
                'CustomerNumber' => (($customerId != null) ? $customerId : ''),
                'LocationCode' => '',
                'BillToNumber' => '',
                'OrigNumber' => '',
                'TermNumber' => '',
                'TransDate' => $trxDate,
                'Revenue' => $rowTotal,
                'TaxIncludedCode' => '0',
                'Units' => empty($qty) ? '1' : $qty,
                'UnitType' => $config->getUnitType(),
                'TaxSitusRule' => $config->getTaxSitusRule(),
                'TransTypeCode' => $transTypeCode,
                'SalesTypeCode' => $salesTypeCode,
                'RegulatoryCode' => $providerType,
                'TaxExemptionCodeList' => array($exemptionCode),
                'CostCenter' => '',
                'GLAccount' => '',
                'ExemptReasonCode' => $exemptionReason,
                'BillingAddress' => ((isset($billingAddress) && $billingAddress != null)
                ? $this->copySureTaxAddress($billingAddress) : ''),
                'ShipToAddress' => ((isset($shipToAddress) && $shipToAddress != null)
                ? $this->copySureTaxAddress($shipToAddress) : ''),
                'ShipFromAddress' => ((isset($shipFromAddress) && $shipFromAddress != null)
                ? $this->copySureTaxAddress($shipFromAddress) : ''),
                'UDF' => $sku
            )
        );

        return $retItem;
    }

    /**
     * This method is called for finalizing the order from Observer.php
     *
     * @param WoltersKluwer_CchSureTax_Helper_Config $config
     * @param int                                    $websiteId
     * @param int                                    $storeId
     *
     * @return string
     */
    public function getBusinessUnit($config, $websiteId, $storeId)
    {
        $defaultBusinessUnit = $config->getBusinessUnit();

        $row = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadWebsiteConfig($websiteId);
        if ($row->getUseBusinessUnit() !== null) {
            $websiteFlag = $row->getUseBusinessUnit();
            $websiteBusinessUnit = $row->getBusinessUnit();
        } else {
            $websiteFlag = 1;
            $websiteBusinessUnit = '';
        }

        $row = Mage::getModel(Constants::WS_CONFIG_TBL)
            ->getCollection()
            ->loadStoreConfig($websiteId, $storeId);
        if ($row->getUseBusinessUnit() !== null) {
            $storeFlag = $row->getUseBusinessUnit();
            $storeBusinessUnit = $row->getBusinessUnit();
        } else {
            $storeFlag = 1;
            $storeBusinessUnit = '';
        }

        if ($storeFlag == 0) {
            return isset($storeBusinessUnit) ? $storeBusinessUnit : '';
        } elseif ($websiteFlag == 0) {
            return isset($websiteBusinessUnit) ? $websiteBusinessUnit : '';
        } else {
            return isset($defaultBusinessUnit) ? $defaultBusinessUnit : '';
        }
    }

    /**
     *
     * @param  Mage_Sales_Model_Quote_Item|
     *         Mage_Sales_Model_Order_Item                 $item
     *
     * @return string The trans type code to use.
     */
    public function getTransTypeCode($item)
    {
        $taxClassId = $item->getProduct()->getTaxClassId();
        $taxClassName = $this->_getTaxClassName($taxClassId);
        return $taxClassName ? $taxClassName : Constants::DEFAULT_TRANS_TYPE_CODE;
    }
    
    /**
     *
     * @param  int $id
     *
     * @return string The tax class name
     */
    protected function _getTaxClassName($id)
    {
        if ($this->_taxClassNameArray === null) {
            $temp = Mage::getModel('tax/class_source_product')->toOptionArray();
            $this->_taxClassNameArray = array();
            foreach ($temp as $a) {
                $this->_taxClassNameArray[$a['value']] = $a['label'];
            }        
        }
        return $this->_taxClassNameArray[$id];
    }
     
    public function sendFinalizeInvoiceToSureTax($invoice, $shipToAddressArray)
    {
        $totalTax = 0.0;
        try {
            $requestData = $this->createSureTaxRequestFromInvoice($invoice, $shipToAddressArray);
            $soapCalcRequestResult = $this->makeSureTaxRequest($requestData);

            $isSuccessful = $soapCalcRequestResult->SoapRequestResult->Successful;
            $responseCode = $soapCalcRequestResult->SoapRequestResult->ResponseCode;
            $clientTracking = $soapCalcRequestResult->SoapRequestResult->ClientTracking;
            $transId = $soapCalcRequestResult->SoapRequestResult->TransId;

            if ($isSuccessful == 'Y'
                && (strcmp($responseCode, Constants::API_RESPONSE_CODE_SUCCESS) == 0)
            ) {
                $totalTax = $soapCalcRequestResult->SoapRequestResult->TotalTax;
                Utility::logMessage(
                    'Invoice ' . $invoice->getIncrementId() .
                    ' was successfully posted to SureTax', Zend_Log::DEBUG
                );

                $status = 'Finalized';
                $notes = "Successfully Posted";

                $jurisdictionArray = Utility::
                getJurisdictionLevelTaxesFromSureTaxResponse($soapCalcRequestResult);
                $this->setJurisdictionArray($jurisdictionArray);
                $success = true;
            } else {
                $headerMessage = $soapCalcRequestResult->SoapRequestResult->HeaderMessage;
                $responseCode = $soapCalcRequestResult->SoapRequestResult->ResponseCode;

                $status = 'Finalize_Fail';
                $notes = $isSuccessful . '-' . $responseCode . ' : ' . $headerMessage;
                $success = false;

                Utility::logMessage(
                    'API FAILURE for Invoice ' .
                    $invoice->getIncrementId() .
                    '. Header: ' . $headerMessage,
                    Zend_Log::ERR
                );
            }
        } catch (Exception $ere) {
            $status = 'Finalize_Fail';
            $notes = $ere->getMessage();
            $success = false;

            Utility::logCatch(
                $ere,
                'Exception for Invoice ' . $invoice->getIncrementId()
            );
        }

        $data = array(
            'client_tracking' => isset($clientTracking) ? $clientTracking : 'N/A',
            'trans_id' => $transId,
            'status' => $status,
            'notes' => $notes,
            'total_tax' => $totalTax,
            'success' => $success
        );

        return $data;
    }
    
    public function sendBatchFinalizeToSureTax($requestDataArray, $requestType)
    {
        $totalTax = 0.0;
        $returnDataArray = array();
        try {
            $soapRequestBatchResult = $this->makeSureTaxBatchRequest($requestDataArray);
            $responses = $soapRequestBatchResult->SoapRequestBatchResult->Response;
            foreach ($responses as $response) {
                $isSuccessful = $response->Successful;
                $responseCode = $response->ResponseCode;
                $clientTracking = $response->ClientTracking;
                if ($requestType === 'Invoice') {
                    $clientTracking = isset($clientTracking) ? $clientTracking : 'N/A';
                } else {
                    $clientTracking = isset($clientTracking) ? $clientTracking : '';
                }
                $transId = $response->TransId;
                $stan = isset($response->STAN) ? $response->STAN : '';

                if ($isSuccessful == 'Y'
                    && (strcmp($responseCode, Constants::API_RESPONSE_CODE_SUCCESS) == 0)
                ) {
                    $totalTax = $response->TotalTax;
                    Utility::logMessage(
                        $requestType . $clientTracking .
                        ' was successfully posted to SureTax', Zend_Log::DEBUG
                    );

                    $status = 'Finalized';
                    $notes = "Successfully Posted";
                    $success = true;
                } else {
                    $headerMessage = $response->HeaderMessage;
                    $responseCode = $response->ResponseCode;
                    $status = 'Finalize_Fail';
                    $notes = $isSuccessful . '-' . $responseCode . ' : ' . $headerMessage;
                    $success = false;

                    Utility::logMessage(
                        'API FAILURE for ' . $requestType .
                        $clientTracking .
                        '. Header: ' . $headerMessage,
                        Zend_Log::ERR
                    );
                }
                $data = array(
                    'client_tracking' => isset($clientTracking) ? $clientTracking : 'N/A',
                    'trans_id' => $transId,
                    'status' => $status,
                    'notes' => $notes,
                    'total_tax' => $totalTax,
                    'success' => $success,
                    'stan' => $stan
                );
                $returnDataArray[$clientTracking] = $data;
            }
        } catch (Exception $ex) {
            Utility::logCatch($ex, $ex->getMessage());
        }
        return $returnDataArray;
    }

    public function sendOrderToSureTax($order, $shipToAddressArray)
    {
        $totalTax = 0.0;
        try {
            $soapRequest = $this->createSureTaxRequestFromOrder(
                $order,
                false,
                $shipToAddressArray
            );
            $soapCalcRequestResult = $this->makeSureTaxRequestFromOrder($soapRequest);

            $isSuccessful = $soapCalcRequestResult->SoapRequestResult->Successful;
            $clientTracking = $soapCalcRequestResult->SoapRequestResult->ClientTracking;
            $transId = $soapCalcRequestResult->SoapRequestResult->TransId;
            $responseCode = $soapCalcRequestResult->SoapRequestResult->ResponseCode;

            if ($isSuccessful == 'Y'
                && (strcmp($responseCode, Constants::API_RESPONSE_CODE_SUCCESS) == 0)
            ) {
                $totalTax = $soapCalcRequestResult->SoapRequestResult->TotalTax;

                $status = 'Finalized';
                $notes = "Successfully Posted";

                $this->saveOrderTaxesToMagento($order, $soapCalcRequestResult, $soapRequest);

                $jurisdictionArray = Utility
                    ::getJurisdictionLevelTaxesTotaledFromResponse($soapCalcRequestResult);
                $this->setJurisdictionArray($jurisdictionArray);

                $success = true;
            } else {
                $headerMessage = $soapCalcRequestResult->SoapRequestResult->HeaderMessage;
                $responseCode = $soapCalcRequestResult->SoapRequestResult->ResponseCode;

                $status = 'Finalize_Fail';
                $notes = $isSuccessful . '-' . $responseCode . ' : ' . $headerMessage;
                $success = false;

                Utility::logMessage(
                    'Finalize API FAILURE for Order ' .
                    $order->getIncrementId() .
                    " Header: $headerMessage",
                    Zend_Log::ERR
                );
            }
        } catch (Exception $ere) {
            $status = 'Finalize_Fail';
            $notes = $ere->getMessage();
            $success = false;

            Utility::logCatch($ere, 'Issue calling Suretax API for Order');
        }
        $data = array(
            'client_tracking' => $clientTracking,
            'trans_id' => $transId,
            'status' => $status,
            'notes' => $notes,
            'total_tax' => $totalTax,
            'success' => $success
        );
        return $data;
    }

    /**
     * @return array
     */
    public function getJurisdictionArray()
    {
        return $this->_jurisdictionArray;
    }

    /**
     * @param array
     */
    public function setJurisdictionArray($jurisdictionArray)
    {
        $this->_jurisdictionArray = $jurisdictionArray;
    }

    public function sendCancelToSureTax($order)
    {
        try {
            $soapCancelRequestResult = $this->makeCancelRequest($order);
            $isSuccessful = $soapCancelRequestResult->CancelSoapRequestWithSTANResult->Successful;
            $responseCode = $soapCancelRequestResult->SoapRequestResult->ResponseCode;

            if ($isSuccessful == 'Y' && (strcmp(
                $responseCode,
                Constants::API_RESPONSE_CODE_SUCCESS
            ) == 0)) {
                $status = "Canceled";
                $notes = "Successfully Canceled";
                $success = true;
            } else {
                $headerMessage = $soapCancelRequestResult->CancelSoapRequestWithSTANResult->HeaderMessage;
                $responseCode = $soapCancelRequestResult->CancelSoapRequestWithSTANResult->ResponseCode;

                $status = "Cancel_Fail";
                $notes = $isSuccessful . '-' . $responseCode . ' : ' . $headerMessage;
                $success = false;

                Utility::logMessage(
                    'Cancel Failure for Order ' . $order->getIncrementId(),
                    Zend_Log::ERR
                );
            }
        } catch (Exception $ere) {
            $status = 'Cancel_Fail';
            $notes = $ere->getMessage();
            $success = false;

            Utility::logCatch($ere, 'Cancel for Order ' . $order->getIncrementId());
        }
        $data = array(
            'status' => $status,
            'notes' => $notes,
            'success' => $success
        );
        return $data;
    }

    public function sendCreditMemoToSureTax($creditmemo, $shipToAddressArray)
    {
        $order = $creditmemo->getOrder();

        try {
            $soapRequest = $this->createSureTaxRequestFromCreditmemo(
                $creditmemo,
                $shipToAddressArray
            );
            $soapReturnResult = $this->makeCreditRequest($soapRequest);

            $isSuccessful = $soapReturnResult->SoapRequestResult->Successful;
            $transId = $soapReturnResult->SoapRequestResult->TransId;
            $clientTracking = (isset($soapReturnResult->SoapRequestResult->ClientTracking))
                ? $soapReturnResult->SoapRequestResult->ClientTracking : '';
            $stan = (isset($soapReturnResult->SoapRequestResult->STAN))
                ? $soapReturnResult->SoapRequestResult->STAN : '';
            $responseCode = $soapReturnResult->SoapRequestResult->ResponseCode;

            if ($isSuccessful == 'Y' && (strcmp(
                $responseCode,
                Constants::API_RESPONSE_CODE_SUCCESS
            ) == 0)) {
                $totalTax = $soapReturnResult->SoapRequestResult->TotalTax;

                $status = "Finalized";
                $notes = 'Successfully Posted';
                $success = true;
            } else {
                $headerMessage = $soapReturnResult->SoapRequestResult->HeaderMessage;
                $responseCode = $soapReturnResult->SoapRequestResult->ResponseCode;

                $status = "Finalize_Fail";
                $notes = $isSuccessful . '-' . $responseCode . ' : ' . $headerMessage;
                $success = false;

                Utility::logMessage(
                    'Credit Memo API result in Failure for Order ' .
                    $order->getIncrementId() . 'Header : ' . $headerMessage,
                    Zend_Log::ERR
                );
            }
        } catch (Exception $ere) {
            $status = 'Finalize_Fail';
            $success = false;
            $notes = $ere->getMessage();
            Utility::logCatch($ere, 'API Return for Order ' . $order->getIncrementId());
        }

        $data = array(
            'client_tracking' => $clientTracking,
            'notes' => $notes,
            'status' => $status,
            'trans_id' => $transId,
            'total_tax' => isset($totalTax) ? $totalTax : 0,
            'stan' => isset($stan) ? $stan : '',
            'success' => $success
        );
        return $data;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param type                   $soapResult
     * @param type                   $soapRequest
     * @return $this
     */
    public function saveOrderTaxesToMagento($order, $soapResult, $soapRequest)
    {
        $totalTax = $soapResult->SoapRequestResult->TotalTax;
        $totalAmount = 0;
        $totalLineTax = 0;
        $store = $order->getStore();

        //Get the LineItem level taxes
        $lineItemTaxArray = Utility
            ::getLineItemLevelTaxByResponseWithRequest($soapRequest, $soapResult);

        if (!isset($lineItemTaxArray) || $lineItemTaxArray == null) {
            // we have no taxes
            $this->saveZeroTaxesForOrder($order);
            return;
        }

        $items = $order->getAllItems();
        $i = Constants::LINE_NUMBER_START_INDEX;
        foreach ($items as $itemId => $item) {
            if ($lineItemTaxArray[$i]) {
                $revenue = $lineItemTaxArray[$i]['Revenue'];
                $totalAmount += isset($revenue) ? $revenue : 0;
                $this->saveTaxesForOrderItem($item, $lineItemTaxArray[$i]);
                $lineTax = $lineItemTaxArray[$i]['LineItemTax'];
                $totalLineTax += isset($lineTax) ? $lineTax : 0;
            }
            $i++;
        }

        //Order has their discount set as negative numbers
        $totalDiscount = $order->getBaseDiscountAmount() * -1;
        $totalDiscountTax = array_sum($this->_totalDiscountWithTax);

        $order->setTaxAmount($store->convertPrice($totalTax));
        $order->setBaseTaxAmount($totalTax);
        //save line item level taxes at item level in db

        $preShippingTotal = $totalAmount;

        $hasShippingLine = array_key_exists(
            Constants::DEFAULT_SHIPPING_LINE_NUMBER,
            $lineItemTaxArray
        );

        if ($hasShippingLine) {
            $lineItemNumber = $lineItemTaxArray
                [Constants::DEFAULT_SHIPPING_LINE_NUMBER]
                ['LineItemNumber'];
            if (isset($lineItemNumber) && strcmp(
                $lineItemNumber,
                Constants::DEFAULT_SHIPPING_LINE_NUMBER == 0
            )) {
                $this->saveShippingTaxesForOrder(
                    $order,
                    $lineItemTaxArray[Constants::DEFAULT_SHIPPING_LINE_NUMBER]
                );
            }
        }

        // SUBTOTAL
        // The cost of just the products without shipping, taxes, or discounts.
        // SUBTOTAL INCLUDING TAX
        // This amount of tax that is paid on just the products.
        $subtotalInclTax = $preShippingTotal + $totalLineTax +
            $totalDiscount + $totalDiscountTax;
        $order->setSubtotalInclTax($store->convertPrice($subtotalInclTax));
        $order->setBaseSubtotalInclTax($subtotalInclTax);
    }

    /**
     * @param Mage_Sales_Model_Order_Item $item
     * @param array                       $itemArray
     */
    public function saveTaxesForOrderItem($item, $itemArray)
    {
        $store = $item->getOrder()->getStore();
        $rev = $itemArray['Revenue'];
        $taxT = $itemArray['LineItemTax'];
        $item->setTaxAmount($store->convertPrice($taxT));
        $item->setBaseTaxAmount($taxT);
        $quantity = $item->getQtyOrdered();

        // sales_flat_quote_item
        // set: tax_percent, price_incl_tax, base_price_incl_tax,
        // row_total_incl_tax, base_row_total_incl_tax
        $taxPercent = (($rev == 0) ? 0.0 : (($taxT / $rev) * 100.0));
        $taxRate = (($rev == 0) ? 0 : ($taxT / $rev));
        $item->setData('tax_percent', $taxPercent);
        $rowTotal = $rev + $taxT;
        $priceInclTax = ($quantity == 0) ? 0 : $rowTotal / $quantity;

        // item has their discount set as positive floats.
        $discAmount = $item->getBaseDiscountAmount();

        // Since calculation is done AFTER discount, we need to add the discount
        // (and its taxes) back to the price and row totals to see what the
        // result would be before discount.
        $temp = ($quantity == 0)
            ? 0 : (($discAmount + ($discAmount * $taxRate)) / $quantity);
        $priceInclTaxBeforeDiscount = $priceInclTax + $temp;
        $rowTotalBeforeDiscount = $rowTotal + $discAmount +
            ($discAmount * $taxRate);
        $this->_totalDiscountWithTax[$item->getId()] = $discAmount * $taxRate;

        // base -> default store currency
        // grand -> currency store currency
        // price is for single unit
        $item->setPriceInclTax($store->convertPrice($priceInclTaxBeforeDiscount));
        $item->setBasePriceInclTax($priceInclTaxBeforeDiscount);

        // row totals are unit price * quantity
        $rowTot = $rev + $discAmount;
        $item->setRowTotal($store->convertPrice($rowTot));
        $item->setBaseRowTotal($rowTot);

        $item->setRowTotalInclTax($store->convertPrice($rowTotalBeforeDiscount));
        $item->setBaseRowTotalInclTax($rowTotalBeforeDiscount);
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param $soapResult
     * @param array                          $soapRequest
     * @return $this
     */
    public function saveInvoiceTaxesToMagento($invoice, $soapResult, $soapRequest)
    {
        $store = $invoice->getStore();
        $totalTax = $soapResult->SoapRequestResult->TotalTax;

        $totalAmount = 0;
        $totalLineTax = 0;

        //Get the LineItem level taxes
        $lineItemTaxArray = Utility
            ::getLineItemLevelTaxByResponseWithRequest($soapRequest, $soapResult);
        if (!isset($lineItemTaxArray) || $lineItemTaxArray == null) {
            // we have no taxes
            return $this;
        }

        $items = $invoice->getAllItems();
        $i = Constants::LINE_NUMBER_START_INDEX;
        foreach ($items as $itemId => $item) {
            if ($lineItemTaxArray[$i]) {
                $revenue = $lineItemTaxArray[$i]['Revenue'];
                $totalAmount += isset($revenue) ? $revenue : 0;
                $this->saveTaxesForInvoiceItem($item, $lineItemTaxArray[$i]);
                $lineTax = $lineItemTaxArray[$i]['LineItemTax'];
                $totalLineTax += isset($lineTax) ? $lineTax : 0;
            }
            $i++;
        }

        $totalDiscount = $invoice->getBaseDiscountAmount() * -1;
        $totalDiscountTax = array_sum($this->_totalDiscountWithTax);

        $invoice->setTaxAmount($store->convertPrice($totalTax));
        $invoice->setBaseTaxAmount($totalTax);

        $preShippingTotal = $totalAmount;

        $hasShippingLine = array_key_exists(
            Constants::DEFAULT_SHIPPING_LINE_NUMBER,
            $lineItemTaxArray
        );

        if ($hasShippingLine) {
            $lineItemNumber = $lineItemTaxArray
                [Constants::DEFAULT_SHIPPING_LINE_NUMBER]
                ['LineItemNumber'];
            if (isset($lineItemNumber) && strcmp(
                $lineItemNumber,
                Constants::DEFAULT_SHIPPING_LINE_NUMBER == 0
            )) {
                $this->saveShippingTaxesForInvoice(
                    $invoice,
                    $lineItemTaxArray[Constants::DEFAULT_SHIPPING_LINE_NUMBER]
                );
            }
        }
        $subtotalincltax = $preShippingTotal + $totalLineTax +
            $totalDiscount + $totalDiscountTax;

        $invoice->setData('subtotal_incl_tax', $store->convertPrice($subtotalincltax));
        $invoice->setData('base_subtotal_incl_tax', $subtotalincltax);
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice_Item $item
     * @param array                               $itemArray
     */
    public function saveTaxesForInvoiceItem($item, $itemArray)
    {
        $store = $item->getInvoice()->getStore();
        $rev = $itemArray['Revenue'];
        $taxT = $itemArray['LineItemTax'];
        $item->setTaxAmount($store->convertPrice($taxT));
        $item->setBaseTaxAmount($taxT);
        $quantity = $item->getQty();

        $taxPercent = (($rev == 0) ? 0.0 : (($taxT / $rev) * 100.0));
        $taxRate = (($rev == 0) ? 0.0 : ($taxT / $rev));
        $item->setData('tax_percent', $taxPercent);
        $rowTotal = $rev + $taxT;
        $priceInclTax = ($quantity == 0) ? 0 : $rowTotal / $quantity;

        //item has their discount set as positive floats.
        $discAmount = $item->getBaseDiscountAmount();

        // Since calculation is done AFTER discount, we need to add the discount
        // (and its taxes) back to the price and row totals to see what the
        // result would be before discount.
        $temp = ($quantity == 0)
            ? 0 : (($discAmount + ($discAmount * $taxRate)) / $quantity);
        $priceInclTaxBeforeDiscount = $priceInclTax + $temp;
        $rowTotalBeforeDiscount = $rowTotal + $discAmount +
            ($discAmount * $taxRate);
        $this->_totalDiscountWithTax[$item->getId()] = $discAmount * $taxRate;

        // price is for single unit
        $item->setPriceInclTax($store->convertPrice($priceInclTaxBeforeDiscount));
        $item->setBasePriceInclTax($priceInclTaxBeforeDiscount);

        // row totals are unit price * quantity
        $rowT = $rev + $discAmount;
        $item->setRowTotal($store->convertPrice($rowT));
        $item->setBaseRowTotal($rowT);

        $item->setRowTotalInclTax($store->convertPrice($rowTotalBeforeDiscount));
        $item->setRowTotal($rowTotalBeforeDiscount);
    }

    public function saveCreditMemoTaxesToMagento($creditMemo, $soapResult, $soapRequest)
    {
        
        $store = $creditMemo->getStore();
        $totalTax = $soapResult->SoapRequestResult->TotalTax;
        $totalTax *= -1;
        $creditMemo->setTaxAmount($store->convertPrice($totalTax));
        $creditMemo->setBaseTaxAmount($totalTax);
        $totalAmount = 0;

        //Get the LineItem level taxes
        $lineItemTaxArray = Utility::
            getLineItemLevelTaxByResponseWithRequest($soapRequest, $soapResult);
        if (!isset($lineItemTaxArray) || $lineItemTaxArray == null) {
            // we have no taxes
            return $this;
        }

        $items = $creditMemo->getAllItems();
        $i = Constants::LINE_NUMBER_START_INDEX;
        foreach ($items as $itemId => $item) {
            $rev = $lineItemTaxArray[$i]['Revenue'];
            if (isset($rev)) {
                $rev *= -1;
                $totalAmount += $rev;
            }
            $this->saveTaxesForCreditMemoItem($item, $lineItemTaxArray[$i]);
            $i++;
        }

        $rev = $lineItemTaxArray[$i]['Revenue'];
        $rev *= -1;
        $preShippingTotal = $totalAmount;
        $shipT = 0.0;
        if (isset($rev)) {
            $totalAmount += $rev;
        }

        $lineItemNumber = $lineItemTaxArray[$i]['LineItemNumber'];
        if (isset($lineItemNumber) && strcmp(
            $lineItemNumber,
            Constants::DEFAULT_SHIPPING_LINE_NUMBER == 0
        )) {
            $shipT = $this->saveShippingTaxesForCreditMemo(
                $creditMemo,
                $lineItemTaxArray[$i]
            );
        }

        // SUBTOTAL
        // The cost of just the products without shipping, taxes, or discounts.
        // SUBTOTAL INCLUDING TAX
        // This amount of tax that is paid on just the products.
        $taxWithoutShip = $totalTax - $shipT;
        $preShippingTotal += $taxWithoutShip;
        $totalAmount += $totalTax;
        $creditMemo->setSubtotalInclTax($store->convertPrice($preShippingTotal));
        $creditMemo->setBaseSubtotalInclTax($preShippingTotal);

        $creditMemo->setGrandTotal($store->convertPrice($totalAmount));
        $creditMemo->setBaseGrandTotal($totalAmount);
    }

    public function saveTaxesForCreditMemoItem($item, $itemArray)
    {
        $store = $item->getCreditmemo()->getStore();
        $lineItemNumber = $itemArray['LineItemNumber'];
        // should not get here, but shipping doesn't get saved here
        if (strcmp($lineItemNumber, Constants::DEFAULT_SHIPPING_LINE_NUMBER) === 0) {
            return;
        }

        $rev = $itemArray['Revenue'] * -1;

        $taxT = $itemArray['LineItemTax'] * -1;

        $item->setTaxAmount($store->convertPrice($taxT));
        $item->setBaseTaxAmount($taxT);
        $quantity = $item->getQty();

        // sales_flat_quote_item
        // set: tax_percent, price_incl_tax, base_price_incl_tax,
        // row_total_incl_tax, base_row_total_incl_tax
        // Credit memo doesn't have tax percent.
        $rowTotal = $rev + $taxT;

        $priceInclTax = ($quantity == 0) ? 0 : $rowTotal / $quantity;

        // base -> default store currency
        // grand -> currency store currency
        // price is for single unit
        $item->setPriceInclTax($store->convertPrice($priceInclTax));
        $item->setBasePriceInclTax($priceInclTax);

        // row totals are unit price * quantity
        $item->setBaseRowTotal($rev);
        $item->setRowTotal($store->convertPrice($rev));

        $item->setRowTotalInclTax($store->convertPrice($rowTotal));
        $item->setBaseRowTotalInclTax($rowTotal);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array                  $itemArray
     * @return mixed
     */
    public function saveShippingTaxesForOrder($order, $itemArray)
    {
        $store = $order->getStore();
        $shippingTax = $itemArray['LineItemTax'];
        $order->setShippingTaxAmount($store->convertPrice($shippingTax));
        $order->setBaseShippingTaxAmount($shippingTax);
        //needs to be calculated by magento. Not set.
        return $shippingTax;
    }

    public function saveShippingTaxesForInvoice($invoice, $itemArray)
    {
        $store = $invoice->getStore();
        $shippingAmount = $itemArray['RevenueBase'];
        $shippingTax = $itemArray['LineItemTax'];
        $shippingInclTax = $shippingAmount + $shippingTax;
        $invoice->setShippingTaxAmount($store->convertPrice($shippingTax));
        $invoice->setBaseShippingTaxAmount($shippingTax);
        $invoice->setShippingInclTax($store->convertPrice($shippingInclTax));
        $invoice->setBaseShippingInclTax($shippingInclTax);
        return $shippingTax;
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param array                             $itemArray
     * @return mixed
     */
    public function saveShippingTaxesForCreditMemo($creditMemo, $itemArray)
    {
        $store = $creditMemo->getStore();
        $rev = $itemArray['Revenue'] * -1;
        $taxT = $itemArray['LineItemTax'] * -1;
        $total = $rev + $taxT;
        $creditMemo->setShippingInclTax($store->convertPrice($total));
        $creditMemo->setBaseShippingInclTax($total);
        $creditMemo->setShippingTaxAmount($store->convertPrice($taxT));
        $creditMemo->setBaseShippingTaxAmount($taxT);

        return $taxT;
    }

    /**
     * @param WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $billingAddress
     * @param WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $shipToAddress
     * @param WoltersKluwer_CchSureTax_Model_Ws_SureTaxAddress $shipFromAddress
     * @param string                                           $trxDate
     * @param string                                           $providerType
     * @param WoltersKluwer_CchSureTax_Helper_Config           $config
     * @param string                                           $salesTypeCode
     * @param string                                           $exemptionCode
     * @param string                                           $exemptionReason
     * @param float                                            $taxForAdjustment
     *
     * @return WoltersKluwer_CchSureTax_Model_Ws_TaxAdjustmentItem
     */
    public function getTaxAdjustmentItem($billingAddress, $shipToAddress,
        $shipFromAddress, $trxDate, $providerType, $config, $salesTypeCode,
        $exemptionCode, $exemptionReason, $taxForAdjustment
    ) {

        $lineNumber = 0;
        $transTypeCode = Constants::DEFAULT_TRANS_TYPE_CODE;
        $sku = '';
        $qty = 0;

        /* @var $taxAdjustmentItem WoltersKluwer_CchSureTax_Model_Ws_TaxAdjustmentItem*/
        $taxAdjustmentItem = Mage::getModel(
            'wolterskluwer_cchsuretax/ws_taxAdjustmentItem',
            array (
                'LineNumber' => $lineNumber,
                'InvoiceNumber' => '',
                'CustomerNumber' => '',
                'OrigNumber' => '',
                'TermNumber' => '',
                'BillToNumber' => '',
                'TransDate' => $trxDate,
                'Revenue' => 0.00,
                'Units' => $qty,
                'UnitType' => $config->getUnitType(),
                'Seconds' => '0',
                'ShipFromPOB' => '1',
                'MailOrder' => '1',
                'CommonCarrier' => '1',
                'BillingDaysInPeriod' => '',
                'TaxIncludedCode' => '0',
                'TaxSitusRule' => $config->getTaxSitusRule(),
                'TransTypeCode' => $transTypeCode,
                'SalesTypeCode' => $salesTypeCode,
                'RegulatoryCode' => $providerType,
                'BillingAddress' => ((isset($billingAddress) && $billingAddress != null)
                    ? $this->copySureTaxAddress($billingAddress) : ''),
                'ShipToAddress' => ((isset($shipToAddress) && $shipToAddress != null)
                    ? $this->copySureTaxAddress($shipToAddress) : ''),
                'ShipFromAddress' => ((isset($shipFromAddress) && $shipFromAddress != null)
                    ? $this->copySureTaxAddress($shipFromAddress) : ''),
                'LocationCode' => '',
                'ExemptReasonCode' => $exemptionReason,
                'TaxExemptionCodeList' => array($exemptionCode),
                'CostCenter' => '',
                'GLAccount' => '',
                'UDF' => $sku,
                'UDF2' => '',
                'Tax' => $taxForAdjustment
            )
        );
        return $taxAdjustmentItem;
    }

    /**
     * @param WoltersKluwer_CchSureTax_Model_Ws_TaxAdjustmentRequest $requestData
     *
     * @return type
     *
     * @throws Exception
     */
    public function makeTaxAdjustmentRequest($requestData)
    {
        try {
            $config = Config::get();
            /* @var $client WoltersKluwer_CchSureTax_Model_Ws_SureTaxClient*/
            $client = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_sureTaxClient',
                array('Config' => $config)
            );
            $soapCalcRequestResult = $client->callTaxAdjustmentRequest($requestData);
            return $soapCalcRequestResult;
        } catch (Exception $exe) {
            Utility::logCatch($exe, 'Exception with makeTaxAdjustmentRequest : ');
            throw $exe;
        }
    }

    public function createSureTaxAdjustRequestFromInvoice($invoice, $shipToAddressArray, $taxForAdjustment)
    {
        try {
            $config = Config::get();
            $order = $invoice->getOrder();
            $ordCreationDate = $order->getCreatedAt();
            $invCreationDate = $invoice->getCreatedAt();
            $store = $order->getStore();
            $customerFields = $this->getCustomerFields(
                $order->getCustomerId(),
                $order->getCustomerGroupId()
            );

            $uniqueId = $this->generateUniqueId($invoice->getIncrementId());
            $STAN = 'ADJ-' . $uniqueId;

            $currentClientTracking = 'ADJ-' . $uniqueId;

            $businessUnit = $this->getBusinessUnit(
                $config,
                $store->getWebsiteId(),
                $store->getGroupId()
            );

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = null;

            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddress(
                    $store->getWebsiteId(),
                    $store->getGroupId()
                )
            );

            $lineItemArray[] = $this->getTaxAdjustmentItem(
                $billingAddress, $shipToAddress,
                $shipFromAddress, $ordCreationDate,
                $config->getProviderType(), $config,
                $customerFields[Constants::SALES_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                $taxForAdjustment
            );

            // create TaxAdjustmentRequest

            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_taxAdjustmentRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($ordCreationDate)['year'],
                    'DataMonth'         => date_parse($ordCreationDate)['month'],
                    'CmplDataYear'      => date_parse($invCreationDate)['year'],
                    'CmplDataMonth'     => date_parse($invCreationDate)['month'],
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'STAN'              => $STAN,
                    'MasterTransId'     => '0',
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'TaxAdjustmentItemList' => $lineItemArray
                )
            );

            return $requestData;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'Invoice: Exception with createSureTaxAdjustRequestFromInvoice'
            );
            throw $exe;
        }
    }

    public function sendTaxAdjustmentToSureTax($invoice, $shipToAddressArray, $taxAdjustment)
    {
        try {
            $requestData = $this->createSureTaxAdjustRequestFromInvoice(
                $invoice,
                $shipToAddressArray,
                $taxAdjustment
            );
            
            $soapTaxAdjustmentRequestResult = $this
                ->makeTaxAdjustmentRequest($requestData);
            $isSuccessful = $soapTaxAdjustmentRequestResult
                ->SoapTaxAdjustmentRequestResult->Successful;
            $responseCode = $soapTaxAdjustmentRequestResult
                ->SoapTaxAdjustmentRequestResult->ResponseCode;

            if ($isSuccessful == 'Y' && (strcmp(
                $responseCode,
                Constants::API_RESPONSE_CODE_SUCCESS
            ) == 0)) {
                Utility::logMessage(
                    'Invoice ' . $invoice->getIncrementId() .
                    ' was successfully adjusted at SureTax', Zend_Log::DEBUG
                );
                $success = true;
                $status = Constants::FINALIZE_ADJUSTED;
            } else {
                $success = false;
                $status = Constants::ADJUSTMENT_FAIL;
            }
        } catch (Exception $ere) {
            $status = Constants::ADJUSTMENT_FAIL;
            $success = false;
            Utility::logCatch(
                $ere,
                'Issue calling SureTax Adjustment API for Invoice ' .
                $invoice->getIncrementId()
            );
        }

        $data = array(
            'status' => $status,
            'success' => $success
        );

        return $data;
    }

    public function createSureTaxAdjustRequestFromCreditMemo(
        $creditMemo,
        $shipToAddressArray,
        $taxForAdjustment
    ) {

        try {
            $config = Config::get();
            $order = $creditMemo->getOrder();
            $store = $creditMemo->getStore();
            $ordCreationDate = $order->getCreatedAt();
            $creditmemoCreationDate = $creditMemo->getCreatedAt();
            
            $customerFields = $this->getCustomerFields(
                $order->getCustomerId(),
                $order->getCustomerGroupId()
            );

            $uniqueId = $this->generateUniqueId($creditMemo->getIncrementId());
            $STAN = 'CM-ADJ-' . $uniqueId;

            $currentClientTracking = 'CM-ADJ-' . $uniqueId;

            $businessUnit = $this->getBusinessUnit(
                $config,
                $store->getWebsiteId(),
                $store->getGroupId()
            );

            $shipToAddress = $this->convertToSureTaxAddress($shipToAddressArray);

            $billingAddress = $this->convertToSureTaxAddress(
                Utility::getShipToAddress(
                    Mage::getModel('sales/order_address')
                        ->load($creditMemo->getOrder()->getBillingAddress()->getId())
                )
            );

            $shipFromAddress = $this->convertToSureTaxAddress(
                Utility::getShipFromAddress(
                    $store->getWebsiteId(),
                    $store->getGroupId()
                )
            );

            $lineItemArray[] = $this->getTaxAdjustmentItem(
                $billingAddress,
                $shipToAddress,
                $shipFromAddress,
                $ordCreationDate,
                $config->getProviderType(),
                $config,
                $customerFields[Constants::SALES_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_TYPE_CODE_KEY],
                $customerFields[Constants::EXEMPTION_REASON_CODE_KEY],
                $taxForAdjustment
            );

            // create TaxAdjustmentRequest

            $requestData = Mage::getModel(
                'wolterskluwer_cchsuretax/ws_taxAdjustmentRequest',
                array (
                    'ClientNumber'      => $config->getClientNumber(),
                    'BusinessUnit'      => $businessUnit,
                    'ValidationKey'     => $config->getValidationKey(),
                    'DataYear'          => date_parse($ordCreationDate)['year'],
                    'DataMonth'         => date_parse($ordCreationDate)['month'],
                    'CmplDataYear'      => date_parse($creditmemoCreationDate)['year'],
                    'CmplDataMonth'     => date_parse($creditmemoCreationDate)['month'],
                    'ClientTracking'    => $currentClientTracking,
                    'ResponseType'      => $config->getResponseType(),
                    'ResponseGroup'     => $config->getResponseGroup(),
                    'STAN'              => $STAN,
                    'MasterTransId'     => '0',
                    'ShipToAddress'     => $shipToAddress,
                    'ShipFromAddress'   => $shipFromAddress,
                    'TaxAdjustmentItemList' => $lineItemArray
                )
            );
            return $requestData;
        } catch (Exception $exe) {
            Utility::logCatch(
                $exe,
                'CreditMemo: createTaxAdjustmentRequestFromCreditMemo'
            );
            throw $exe;
        }
    }

    public function sendCreditMemoTaxAdjustmentToSureTax($creditMemo,
        $shipToAddressArray, $taxAdjustment
    ) {
        try {
            $requestData = $this->createSureTaxAdjustRequestFromCreditMemo(
                $creditMemo,
                $shipToAddressArray,
                $taxAdjustment
            );

            $soapTaxAdjustmentRequestResult = $this
                ->makeTaxAdjustmentRequest($requestData);
            $isSuccessful = $soapTaxAdjustmentRequestResult
                ->SoapTaxAdjustmentRequestResult->Successful;
            $responseCode = $soapTaxAdjustmentRequestResult
                ->SoapTaxAdjustmentRequestResult->ResponseCode;

            if ($isSuccessful == 'Y' && (strcmp(
                $responseCode,
                Constants::API_RESPONSE_CODE_SUCCESS
            ) == 0)) {
                Utility::logMessage(
                    'Credit Memo ' . $creditMemo->getIncrementId() .
                    ' was successfully adjusted at SureTax', Zend_Log::DEBUG
                );
                $success = true;
                $status = Constants::FINALIZE_ADJUSTED;
            } else {
                $success = false;
                $status = Constants::ADJUSTMENT_FAIL;
            }
        } catch (Exception $ere) {
            $success = false;
            $status = Constants::ADJUSTMENT_FAIL;

            Utility::logCatch(
                $ere,
                'Adjustment Fail for CreditMemo ' .
                $creditMemo->getIncrementId()
            );
        }

        $data = array(
            'status' => $status,
            'success' => $success
        );

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    protected function saveZeroTaxesForOrder($order)
    {
        $order->setSubtotalInclTax($order->getSubtotal());
        $order->setBaseSubtotalInclTax($order->getBaseSubtotal());
        $order->setBaseTaxAmount(0);
        $order->setTaxAmount(0);
    }
}
