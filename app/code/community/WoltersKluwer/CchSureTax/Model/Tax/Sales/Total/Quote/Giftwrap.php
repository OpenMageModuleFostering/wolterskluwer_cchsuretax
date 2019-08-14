<?php
/**
 * Rewrite collect for Gift wrapping.
 *
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
use WoltersKluwer_CchSureTax_Helper_Utility as Utility;
use WoltersKluwer_CchSureTax_Helper_Config as Config;

class WoltersKluwer_CchSureTax_Model_Tax_Sales_Total_Quote_Giftwrap
    extends Enterprise_GiftWrapping_Model_Total_Quote_Tax_Giftwrapping
{
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $quote = $address->getQuote();
        $isSureTaxEnabled = Config::isSureTaxEnabledForWebsiteStore(
            $quote->getStore()->getWebsiteId(),
            $quote->getStore()->getGroupId()
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

        // set gw taxes for quote from address/s
        $addys = $quote->getAllAddresses();
        $gwTax = 0;
        $gwBaseTax = 0;
        $gwCardTax = 0;
        $gwCardBaseTax = 0;
        $gwItemsTax = 0;
        $gwItemsBaseTax = 0;
        if (!empty($addys)) {
            foreach ($addys as $addy) {
                $gwTax += $addy->getGwTaxAmount();
                $gwBaseTax += $addy->getGwBaseTaxAmount();
                $gwCardTax += $addy->getGwCardTaxAmount();
                $gwCardBaseTax += $addy->getGwCardBaseTaxAmount();
                $gwItemsTax += $addy->getGwItemsTaxAmount();
                $gwItemsBaseTax += $addy->getGwItemsTaxAmount();
            }
        }
        $quote->setGwTaxAmount($gwTax);
        $quote->setGwBaseTaxAmount($gwBaseTax);
        $quote->setGwCardTaxAmount($gwCardTax);
        $quote->setGwCardBaseTaxAmount($gwCardBaseTax);
        $quote->setGwItemsTaxAmount($gwItemsTax);
        $quote->setGwItemsBaseTaxAmount($gwItemsBaseTax);

        return $this;
    }
}
