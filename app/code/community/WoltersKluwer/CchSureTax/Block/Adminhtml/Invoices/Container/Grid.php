<?php
/*
 * SureTax Invoices grid
 *
 * Grid displaying all of Magento's invoices joined with SureTax invoice table.
 * Display which invoice had been finalized in SureTax.
 * 
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */

class WoltersKluwer_CchSureTax_Block_Adminhtml_Invoices_Container_Grid extends WoltersKluwer_CchSureTax_Block_Adminhtml_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('suretaxInvoicesGrid');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    //Load the collection of Suretax Invoice.
    protected function _prepareCollection()
    {
        $collection = WoltersKluwer_CchSureTax_Model_Resource_Invoice_Collection::joinInvoiceWithSelf();     
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.entity_id');
        $this->getMassactionBlock()->setFormFieldName('invoice_Ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem(
            'suretax_finalize', array(
            'label'=> Mage::helper('sales')->__('Batch Finalize'),
            'url'  => $this->getUrl('*/*/batchFinalize'),
            )
        );

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'increment_id', array(
            'header'    => Mage::helper('suretax')->__('Invoice #'),
            'width'     => '100',
            'index'     => 'increment_id',
            'filter_index' => 'main_table.increment_id',
            'type'      => 'text'
            )
        );

        $this->addColumn(
            'order_increment_id', array(
            'header'    => Mage::helper('suretax')->__('Order #'),
            'width'     => '100',
            'index'     => 'order_increment_id',
            'filter_index' => 'order.increment_id',
            'type'      => 'text'
            )
        );

        $this->addColumn(
            'created_at', array(
            'header'    => Mage::helper('suretax')->__('Date'),
            'width'     => '100',
            'index'     => 'created_at',
            'filter_index' => 'main_table.created_at',
            'type'      => 'datetime'
            )
        );

        $this->addColumn(
            'tax_amount', array(
            'header'    => Mage::helper('suretax')->__('Magento Collected Tax'),
            'width'     => '100',
            'index'     => 'tax_amount',
            'filter_index' => 'main_table.tax_amount',
            'type'      => 'currency',
            'currency'  => 'order_currency_code'
            )
        );

        $this->addColumn(
            'sure_tax_amount', array(
            'header'    => Mage::helper('suretax')->__('SureTax Calculated Tax'),
            'width'     => '100',
            'index'     => 'tax',
            'filter_index' => 'sure.tax',
            'type'      => 'currency',
            'currency'  => 'order_currency_code'
            )
        );
        
        $this->addColumn(
            'tax_difference', array(
            'header'    => Mage::helper('suretax')->__('Difference'),
            'width'     => '100',
            'index'     => 'tax_difference',
            'filter'    => false,
            'type'      => 'currency',
            'currency'  => 'order_currency_code'
            )
        );

        $this->addColumn(
            'trans_id', array(
            'header'    => Mage::helper('suretax')->__('SureTax Trans ID'),
            'width'     => '100',
            'index'     => 'trans_id',
            'filter_index' => 'sure.trans_id',
            'type'      => 'text'
            )
        );

        $this->addColumn(
            'client_tracking', array(
            'header'    => Mage::helper('suretax')->__('Client Tracking'),
            'width'     => '100',
            'index'     => 'client_tracking',
            'filter_index' => 'sure.client_tracking',
            'type'      => 'text'
            )
        );

        $this->addColumn(
            'status', array(
            'header'    => Mage::helper('suretax')->__('SureTax Status'),
            'width'     => '100',
            'index'     => 'status',
            'filter_index' => 'sure.status',
            'type'      => 'options',
            'options'   => WoltersKluwer_CchSureTax_Helper_Constants::$SURETAX_STATUS
            )
        );
        
        $this->addColumn(
            'notes', array(
            'header'    => Mage::helper('suretax')->__('Notes'),
            'width'     => '200',
            'index'     => 'notes',
            'filter_index' => 'sure.notes',
            'type'      => 'text'
            )
        );

        $this->addColumn(
            'action', array(
            'header'    => Mage::helper('sales')->__('Action'),
            'width'     => '50px',
            'type'      => 'action',
            'getter'     => 'getId',
            'actions'   => array(
                array(
                    'caption' => Mage::helper('sales')->__('View'),
                    'url'     => array('base'=>'*/sales_invoice/view'),
                    'field'   => 'invoice_id',
                    'data-column' => 'action',
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'stores',
            'is_system' => true,
            )
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('suretax')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('suretax')->__('Excel XML'));
        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/order/invoice')) {
            return false;
        }

        return $this->getUrl(
            '*/sales_invoice/view',
            array(
                'invoice_id'=> $row->getId(),
            )
        );
    }
}
