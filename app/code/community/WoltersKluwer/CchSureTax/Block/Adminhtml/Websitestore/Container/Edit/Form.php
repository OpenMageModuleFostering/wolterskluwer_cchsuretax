<?php
/*
 * SureTax Website/Store configuration form.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Websitestore_Container_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $config = Mage::registry('config');
        $websiteId = $config['websiteId'];
        $storeId = $config['storeId'];

        if ($storeId == 0) {
            $name = 'Website : '.$config['name'];
            $isWebsite = true;

            $row = Mage::getModel(WoltersKluwer_CchSureTax_Helper_Constants::WS_CONFIG_TBL)
                ->getCollection()
                ->loadWebsiteConfig($websiteId);

            $scope = '<td class="scope-label">[WEBSITE]</td>';

            WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                'Form loaded for Website : ' .
                $config['name'],
                Zend_Log::DEBUG
            );
        } else {
            $name = 'Store : '.$config['name'];
            $isWebsite = false;

            $row = Mage::getModel(WoltersKluwer_CchSureTax_Helper_Constants::WS_CONFIG_TBL)
                ->getCollection()
                ->loadStoreConfig($websiteId, $storeId);

            $scope = '<td class="scope-label">[STORE GROUP]</td>';

            WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                'Form loaded for Store : ' .
                $config['name'],
                Zend_Log::DEBUG
            );
        }

        $enableFlag = $row->getIsEnableCalc();
        $useDefaultBusinessUnit = $row->getUseBusinessUnit();
        $businessUnit = $row->getBusinessUnit();
        $useDefaultAddressFlag = $row->getUseDefaultAddress();
        $streetAddress1 = $row->getStreetAddress1();
        $streetAddress2 = $row->getStreetAddress2();
        $city = $row->getCity();
        $country = $row->getCountry();
        $stateProv = $row->getStateProvince();
        $zipPostal = $row->getZipPostal();

        $form = new Varien_Data_Form(
            array(
            'id'=>'edit_form',
            'action'=>$this->getData('action'),
            'method'=>'post'
            )
        );

        $fieldset = $form->addFieldset(
            'base_fieldset', array(
            'legend'=>Mage::helper('suretax')->__($name),
            'class'=>'fieldset'
            )
        );

        $fieldset->addField(
            'website_id', 'hidden', array(
            'name' => 'website_id',
            'value' => $websiteId
            )
        );

        $fieldset->addField(
            'store_id', 'hidden', array(
            'name' => 'store_id',
            'value' => $storeId
            )
        );

        $labelKey = $isWebsite ?
                WoltersKluwer_CchSureTax_Helper_Constants::USE_GLOBAL_SETTINGS :
                WoltersKluwer_CchSureTax_Helper_Constants::USE_WEBSITE_SETTINGS;
        $label = $isWebsite ? 'Use Global Settings' : 'Use Website Settings';

        $fieldset->addField(
            'is_enable_calc', 'select', array(
            'label'     => Mage::helper('suretax')->__('Enable SureTax Calculation'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'is_enable_calc',
            'onclick'   => "",
            'onchange'  => "",
            'value'     => isset($enableFlag) ? $enableFlag : $labelKey,
            'values'    => array($labelKey=>$label, WoltersKluwer_CchSureTax_Helper_Constants::NO=>'No',
                                                    WoltersKluwer_CchSureTax_Helper_Constants::YES => 'Yes'),
            'disabled'  => false,
            'readonly'  => false,
            'tabindex'  => 0,
            'after_element_html' => $scope
            )
        );

        $notes = $isWebsite ? $this->__('Business Unit is pulled from the Global Settings') :
                $this->__('Business Unit is pulled from the Website Settings');

        $fieldset->addField(
            'use_business_unit', 'select', array(
            'name'   => 'use_business_unit',
            'label'  => Mage::helper('suretax')->__('Use Default Business Unit'),
            'title'  => Mage::helper('suretax')->__('Use Default Business Unit'),
            'required' => true,
            'maxlength' => WoltersKluwer_CchSureTax_Helper_Constants::BUSINESS_UNIT_MAX_LENGTH,
            'value'  => isset($useDefaultBusinessUnit) ? $useDefaultBusinessUnit : 1,
            'values' => array('0'=>'No','1' => 'Yes'),
            'note'   => '<small>'.$notes.'</small>'
            )
        );

        $fieldset->addField(
            'business_unit', 'text', array(
            'name'  => 'business_unit',
            'label' => Mage::helper('suretax')->__('Business Unit'),
            'title' => Mage::helper('suretax')->__('Business Unit'),
            'required' => false,
            'value' => isset($businessUnit) ? $businessUnit : '',
            'note'  => $this->__('<small>Enter the SureTax business unit for transactions</small>')
            )
        );

        $fieldset2 = $form->addFieldset(
            'shipping_fieldset', array(
            'legend'=>Mage::helper('suretax')->__('Ship From Address'),
            'class'=>'fieldset'
            )
        );

        $notes = $isWebsite ? $this->__('Ship From Address is pulled from System->Configuration->Shipping Settings->Origin') :
                $this->__('Ship From Address is pulled from the Website Settings');

        $fieldset2->addField(
            'use_default_address',
            'select',
            array(
                'name'   => 'use_default_address',
                'label'  => Mage::helper('suretax')->__('Use Default Ship From Address'),
                'title'  => Mage::helper('suretax')->__('Use Default Ship From Address'),
                'value'  => isset($useDefaultAddressFlag) ? $useDefaultAddressFlag : 1,
                'values' => array('0'=>'No','1' => 'Yes'),
                'required' => true,
                'note'   => '<small>'.$notes.'</small>'
            )
        );

        $fieldset2->addField(
            'ship_from_address1',
            'text',
            array(
                'name' => 'ship_from_address1',
                'label' => Mage::helper('suretax')->__('Street Address'),
                'title' => Mage::helper('suretax')->__('Street Address'),
                'value' => isset($streetAddress1) ? $streetAddress1 : '',
                'required' => true
            )
        );

        $fieldset2->addField(
            'ship_from_address2',
            'text',
            array(
                'name' => 'ship_from_address2',
                'value' => isset($streetAddress2) ? $streetAddress2 : '',
                'required' => false
            )
        );

        $fieldset2->addField(
            'ship_from_city',
            'text',
            array(
                'name' => 'ship_from_city',
                'label' => Mage::helper('suretax')->__('City'),
                'title' => Mage::helper('suretax')->__('City'),
                'value' => isset($city) ? $city : '',
                'required' => true
            )
        );

        $countryCollection = Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();

        $fieldset2->addField(
            'ship_from_country',
            'select',
            array(
                'name' => 'ship_from_country',
                'class' => 'required-entry',
                'label' => Mage::helper('suretax')->__('Country'),
                'title' => Mage::helper('suretax')->__('Country'),
                'values' => $countryCollection,
                'value' => isset($country) ? $country : '0',
                'onchange' => 'getstate(this)',
                'required' => true
            )
        );

        $stateCollection = Mage::getModel('directory/country')->load('US')->getRegions()->toOptionArray();

        $fieldset2->addField(
            'ship_from_state',
            'select',
            array(
                'name' => 'ship_from_state',
                'label' => Mage::helper('suretax')->__('State/Province'),
                'title' => Mage::helper('suretax')->__('State/Province'),
                'values' => $stateCollection,
                'value' => isset($stateProv) ? $stateProv : '',
                'required' => true
            )
        );

        $fieldset2->addField(
            'ship_from_zip',
            'text',
            array(
                'name' => 'ship_from_zip',
                'label' => Mage::helper('suretax')->__('Zip/Postal Code'),
                'title' => Mage::helper('suretax')->__('Zip/Postal Code'),
                'value' => isset($zipPostal) ? $zipPostal : '',
                'required' => true
            )
        );

            $form->setUseContainer(true);
            $this->setForm($form);

            return parent::_prepareForm();
    }

    //Allows the Ship From Address Fieldset be dependent on the "Use default ship from address" opening and closing.
    protected function _toHtml()
    {
        $dependencyBlock = $this->getLayout()
            ->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap('use_default_address', 'use_default_address')
            ->addFieldMap('ship_from_address1', 'ship_from_address1')
            ->addFieldMap('ship_from_address2', 'ship_from_address2')
            ->addFieldMap('ship_from_city', 'ship_from_city')
            ->addFieldMap('ship_from_country', 'ship_from_country')
            ->addFieldMap('ship_from_state', 'ship_from_state')
            ->addFieldMap('ship_from_zip', 'ship_from_zip')
            ->addFieldMap('use_business_unit', 'use_business_unit')
            ->addFieldMap('business_unit', 'business_unit')

            ->addFieldDependence('ship_from_address1', 'use_default_address', '0')
            ->addFieldDependence('ship_from_address2', 'use_default_address', '0')
            ->addFieldDependence('ship_from_city', 'use_default_address', '0')
            ->addFieldDependence('ship_from_country', 'use_default_address', '0')
            ->addFieldDependence('ship_from_state', 'use_default_address', '0')
            ->addFieldDependence('ship_from_zip', 'use_default_address', '0')
            ->addFieldDependence('business_unit', 'use_business_unit', '0');

        return parent::_toHtml() . $dependencyBlock->toHtml();
    }
}
