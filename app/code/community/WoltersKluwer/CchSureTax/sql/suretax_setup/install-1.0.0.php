<?php
/*
 * SureTax installation script.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

$installer = $this;
$installer->startSetup();
$tableName = $installer->getTable('wolterskluwer_cchsuretax/websitestore_config');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $websiteStoreTable = $installer->getConnection()->newTable($installer->getTable('wolterskluwer_cchsuretax/websitestore_config'))
        ->addColumn(
            'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
            'identity' => true,
            ), 'ID'
        )
        ->addColumn(
            'website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'=>true,
            ), 'Website ID'
        )
        ->addColumn(
            'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'=>true,
            ), 'Store ID'
        )
        ->addColumn(
            'is_enable_calc', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'=>true,
            ), 'Is enabled flag'
        )
        ->addColumn(
            'use_business_unit', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'=>true,
            ), 'Flag to use this Business Unit'
        )
        ->addColumn(
            'business_unit', Varien_Db_Ddl_Table::TYPE_TEXT, 20, array(
            'nullable'=>true,
            ), 'Business Unit for this config'
        )
        ->addColumn(
            'use_default_address', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable'=>true,
            ), 'Flag to use Default Address'
        )
        ->addColumn(
            'street_address1', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'=>true,
            ), 'Ship From Street Address 1'
        )
        ->addColumn(
            'street_address2', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'=>true,
            ), 'Ship From Street Address 2'
        )
        ->addColumn(
            'city', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'=>true,
            ), 'Ship From City'
        )
        ->addColumn(
            'country', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'=>true,
            ), 'Ship From Country'
        )
        ->addColumn(
            'state_province', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'=>true,
            ), 'Ship From State'
        )
        ->addColumn(
            'zip_postal', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'=>true,
            ), 'Ship From Zip'
        )
        ->addIndex('IDX_SURETAX_WEBSITESTORE_CONF_WEBSITE', array('website_id'))
        ->addIndex('IDX_SURETAX_WEBSITESTORE_CONF_STORE', array('store_id'))
        ->addIndex('IDX_SURETAX_WEBSITESTORE_BUSINESSUNIT', array('business_unit'))
        ->setComment('Magento Suretax Website/Store Config Settings');

    $installer->getConnection()->createTable($websiteStoreTable);
}
if ($installer->getConnection()->isTableExists($tableName) == true) {
    $installer->getConnection()->addForeignKey(
        'FK_SURETAX_WEBSITESTORE_CONF_WEBSITE',
        'wolterskluwer_cchsuretax_websitestore_config',
        'website_id',
        'core_website',
        'website_id',
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE,
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
    );
    $installer->getConnection()->addForeignKey(
        'FK_SURETAX_WEBSITESTORE_CONF_STORE',
        'wolterskluwer_cchsuretax_websitestore_config',
        'store_id',
        'core_store_group',
        'group_id',
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE,
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
    );
}

$tableName = $installer->getTable('wolterskluwer_cchsuretax/invoice');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $suretaxInvoiceTable = $installer->getConnection()->newTable($installer->getTable('wolterskluwer_cchsuretax/invoice'))
        ->addColumn(
            'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
            'identity' => true,
            ), 'ID'
        )
        ->addColumn(
            'increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            ), 'Invoice Increment Id'
        )
        ->addColumn(
            'trans_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'tax', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'client_tracking', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'notes', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable' => true,
            )
        )
        ->addIndex('IDX_SURETAX_INVOICE_INCREMENT_ID', array('increment_id'))
        ->addIndex('IDX_SURETAX_INVOICE_TRANS_ID', array('trans_id'))
        ->addIndex('IDX_SURETAX_INVOICE_CLIENT_TRACKING', array('client_tracking'))
        ->setComment('Magento Suretax Invoice Table');

    $installer->getConnection()->createTable($suretaxInvoiceTable);
}

$tableName = $installer->getTable('wolterskluwer_cchsuretax/creditmemo');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $suretaxCreditmemoTable = $installer->getConnection()->newTable($installer->getTable('wolterskluwer_cchsuretax/creditmemo'))
        ->addColumn(
            'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
            'identity' => true,
            ), 'ID'
        )
        ->addColumn(
            'increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            ), 'Credit Memo Increment Id'
        )
        ->addColumn(
            'stan', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            ), 'Credit Memo STAN we send to SureTax'
        )
        ->addColumn(
            'trans_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'tax', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'client_tracking', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
            'nullable' => true,
            )
        )
        ->addColumn(
            'notes', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable' => true,
            )
        )
        ->addIndex('IDX_SURETAX_CREDITMEMO_INCREMENT_ID', array('increment_id'))
        ->addIndex('IDX_SURETAX_CREDITMEMO_TRANS_ID', array('trans_id'))
        ->addIndex('IDX_SURETAX_CREDITMEMO_CLIENT_TRACKING', array('client_tracking'))
        ->setComment('Magento Suretax Credit Memo Table');

    $installer->getConnection()->createTable($suretaxCreditmemoTable);
}

$tableName = $installer->getTable('wolterskluwer_cchsuretax/customergroup');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $customergroupinfoTable = $installer->getConnection()->newTable($installer->getTable('wolterskluwer_cchsuretax/customergroup'))
        ->addColumn(
            'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
            'identity' => true,
            ), 'SURE TAX CUSTOMER GROUP ID'
        )
        ->addColumn(
            'exemption_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'length' => 255,
            'nullable' => true,
            ), 'Exemption Code'
        )
        ->addColumn(
            'exemption_reason_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => true,
            'length' => 255,
            ), 'Exemption Reason Code'
        )
        ->addColumn(
            'sales_type_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => true,
            'length' => 255,
            ), 'Sales Type Code'
        )
        ->addColumn(
            'suretax_customer_group_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'unsigned' => true,
            ), 'Customer group ID'
        )
        ->addIndex('IDX_SURETAX_CUSTOMER_GRP', array('suretax_customer_group_id'))
        ->setComment('Sure tax customer group');

    $installer->getConnection()->createTable($customergroupinfoTable);

    $installer->getConnection()->addForeignKey(
        'FK_SURETAX_CUSTOMER_GRP',
        'wolterskluwer_cchsuretax_customergroup',
        'suretax_customer_group_id',
        'customer_group',
        'customer_group_id',
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE,
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
    );
}

$tableName = $installer->getTable('wolterskluwer_cchsuretax/customer');
if ($installer->getConnection()->isTableExists($tableName) != true) {
    $customerinfoTable = $installer->getConnection()->newTable($installer->getTable('wolterskluwer_cchsuretax/customer'))
        ->addColumn(
            'id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
            'identity' => true,
            ), 'SURE TAX CUSTOMER ID'
        )
        ->addColumn(
            'exemption_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'length' => 255,
            'nullable' => true,
            ), 'Exemption Code'
        )
        ->addColumn(
            'exemption_reason_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => true,
            'length' => 255,
            ), 'Exemption Reason Code'
        )
        ->addColumn(
            'sales_type_code', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable' => true,
            'length' => 255,
            ), 'Sales Type Code'
        )
        ->addColumn(
            'suretax_customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
            'unsigned' => true,
            ), 'Customer ID'
        )
        ->addIndex('IDX_SURETAX_CUSTOMER', array('suretax_customer_id'))
        ->setComment('Sure tax customer');

    $installer->getConnection()->createTable($customerinfoTable);

    $installer->getConnection()->addForeignKey(
        'FK_SURETAX_CUSTOMER',
        'wolterskluwer_cchsuretax_customer',
        'suretax_customer_id',
        'customer_entity',
        'entity_id',
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE,
        Varien_Db_Adapter_Interface::FK_ACTION_CASCADE
    );
}
$installer->endSetup();