<?php
/*
 * SureTax Help Screen.
 *
 * Detailing all the needed information to configure SureTax for tax calculation.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Help_Container extends Mage_Adminhtml_Block_Widget_Container
{
    public function _toHtml() 
    {
        return $this->getHelpHtml() . parent::_toHtml();
    }
    
    public function getHelpHtml()
    {
        
        $helpHtml = <<< INFO_HTML
    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4 class="icon-head head-account">Quick Start</h4>
        </div>
        <div class="fieldset">
            <ul>
                <li class="messages">The global information of the CCH SureTax configuration contains information that is applicable across the Magento installation.</li>
                <br/>
                <li>To configure global settings, select <strong>SureTax > Global</strong> on the Admin page. 
                    When you save the configuration, the connection to CCH SureTax is tested using the URL, client number, and validation key entered. 
                    All configuration values are saved, but if an error occurs, you are notified.</li>
<br/>
                <li>•	Enable SureTax Calculation. Activate the extension for the entire Magento installation by selecting Yes.</li>
<br/>
                <li>•	Client Number. Your CCH SureTax client number, which you get from your CCH SureTax representative. Validated when the configuration settings are saved.</li>
<br/>
                <li>•	SureTax Webservice URL. Most people will only need the <a href="https://api.taxrating.net/Services/V07/SureTax.asmx">Production URL</a>. If you have a development account, you can use <a href="https://testapi.taxrating.net/Services/V07/SureTax.asmx">Demo URL</a> for testing. Validated when the configuration settings are saved.</li>
<br/>
                <li>•	Validation Key. Your SureTax company validation key, which you get from your CCH SureTax representative. Validated when the configuration settings are saved.</li>
<br/>
                <li>•	Global Business Unit. The business unit that will be sent to CCH SureTax for all transactions from the 
                    Magento installation unless a specific business unit is specified for a website or store. The maximum length of business unit is 20 characters. 
                    Reporting within CCH SureTax allows filtering by business unit.</li>
<br/>
                <li>•	Provider Type. The type of business that will be sent to CCH SureTax for all transactions.</li>
<br/>
                <li>•	Tax Class for Shipping. The Magento product tax class that is mapped in CCH SureTax to the appropriate shipping SKU.  This product tax class is used as the transaction type code for all shipping and handling costs.</li>
<br/>
				<li>•	Tax Class for Gift Options. The Magento product tax class that is mapped in CCH SureTax to the appropriate gift wrapping SKU. This product tax class is used as the transaction type code for all gift wrapping and printed card costs.</li>
<br/>
                <li>•	Enable Debug Logging. Activate the debugging logging for the entire Magento installation by selecting Yes. The log file is named suretax.log and is located in the var/log directory of the Magento file structure.</li>

            </ul>
        </div>
    </div>

    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4 class="icon-head head-account">Ship From Setting</h4>
        </div>
        <div class="fieldset">
            <ul>
                <li>
                    The ship from address is sent to CCH SureTax for each transaction. The ship from address can be specified for individual stores, websites, or the entire Magento installation. 
                    When sending a sales order to CCH SureTax, the address is first obtained from the configuration for the store.  If the store does not specify an address, 
                    then the address for the website is obtained. If the website does not specify an address, then the address of the installation is sent to CCH SureTax.
                </li> <br/>
                <li>•	To configure the ship from address for a website or store, select <strong>SureTax > Website/Store.</strong>  Select the store or website to configure.  
                    Enter an address or choose to use the default ship from address.  
                    When configuring a store, this is the ship from address specified for the website.  
                    When configuring a website, this is the default ship from address for the Magento installation.</li><br/>
                <li>•	To configure the default ship from address for the Magento installation, select <strong>System > Configuration > Sales/Shipping Settings</strong> on the Admin page. 
                    Open the Origin accordion and fill in all fields for the address. </li>
            </ul>
        </div>
    </div>

    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4 class="icon-head head-account">Magento Product Tax Class</h4>
        </div>
        <div class="fieldset">
            <ul>
                <li>
                    •	The product tax class in Magento is sent to CCH SureTax as the transaction type indicator. These values determine the taxability of products.
                </li><br/>
                <li>
                    •	Within the Magento Admin, select <strong>Sales > Tax > Product Tax Classes</strong>. Verify that each product tax class in the list has a SKU/Product mapping in CCH SureTax.
                </li>
            </ul>
        </div>
    </div>


    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4 class="icon-head head-account">Customer / Customer Group Setting</h4>
        </div>
        <div class="fieldset">
            <ul>
                <li>
                    There are settings for the CCH SureTax extension that are customer specific. These settings can be specified for a group of customers or for individual customers. 
                    By default, an individual customer is configured the same as the group to which the customer belongs. In this case, the settings of the customer group will be sent to CCH SureTax. 
                    If a customer is configured differently than the group to which the customer belongs, then the settings of the customer will be sent to CCH SureTax.
                </li><br/>
                <li>The following fields are configured for customers and customer groups:</li>
                <li>•	<strong>Sales Type Code</strong>. The type of customer that is purchasing each item. </li>
                <li>•	<strong>Exemption Code</strong>. Specify whether the customer qualifies as tax-exempt. </li>
                <li>•	<strong>Exemption Reason</strong>. The reason that the customer qualifies as tax-exempt.</li>
				<br/>
                <li>To view existing SureTax configuration settings for customer groups, select <strong>SureTax > Customer Groups</strong> from the Admin page.  
					Select a customer group in list to edit the existing SureTax Information fields.  Or click on the Add Group Configuration button and 
					select a customer group to create SureTax Information fields for the customer group.  Click Save Group Configuration button.</li>
                <br/>
                <li>To view existing SureTax configuration settings for customers, select <strong>SureTax > Customers</strong> from the Admin page.  Any values specified for a customer are sent to
                    CCH SureTax instead of the values specified for the customer group.  Select a customer in list to edit the SureTax Information fields.
					Or click on the Add Customer Configuration button and select a customer to create SureTax Information fields for the customer.
					Click Save Customer Configuration button.</li>
            </ul>
        </div>
    </div>

<div class="entry-edit">
        <div class="entry-edit-head">
            <h4 class="icon-head head-account">Finalizing Transactions</h4>
        </div>
        <div class="fieldset">
            <ul>
                <li>
                    If the CCH SureTax extension is enabled, taxes are recorded in CCH SureTax when an invoice or credit memo is created.  
                    If there is a problem connecting to CCH SureTax at the time of creation, a group of invoices or credit memos that have not been processed by 
                    CCH SureTax can be sent for processing from the SureTax transaction report.
                </li>
                <br/>
                <li>
                    Within the Magento Admin, select <strong>SureTax > Transaction Report > Invoices</strong> to view the invoices and information about their status in CCH SureTax.
                    <ul>
                        <li>•	Check any invoices you wish to send to CCH SureTax that do not display a CCH SureTax identifier.</li>
                        <li>•	Click on the Actions drop-down menu and select Batch Finalize.</li>
                        <li>•	Click Submit.</li>
                    </ul>
                </li>
                <br/>
                <li>
                    Within the Magento Admin, select <strong>SureTax > Transaction Report > Credit Memos</strong> to view the credit memos and information about their status in CCH SureTax.
                    <ul>
                        <li>•	Check any credit memos you wish to send to CCH SureTax that do not display a CCH SureTax identifier.</li>
                        <li>•	Click on the Actions drop-down menu and select Batch Finalize.</li>
                        <li>•	Click Submit.</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <div class="entry-edit">
        <div class="entry-edit-head">
            <h4 class="icon-head head-account">Logging Settings</h4>
        </div>
        <div class="fieldset">
            <ul>
                <li>
                    The CCH SureTax extension provides two levels of logging.  Configuration of logging is located in the SureTax > Global menu.  The log file is named suretax.log and is located in the var/log directory of the Magento file structure.
                </li>
                <li>
                    •	If the debug logging is enabled, all debug and info level messages will be logged along with API request response.
                </li>
                <li>
                    •	If the debug logging is not enabled, errors, warnings, and exceptions will be logged.
                </li>
            </ul>
        </div>
    </div>
INFO_HTML;
        return $helpHtml;
    }
}