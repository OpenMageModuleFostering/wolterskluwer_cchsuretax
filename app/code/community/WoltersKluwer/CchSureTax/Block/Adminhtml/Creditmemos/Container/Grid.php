<?php
/**
 * SureTax Credit memo grid
 *
 * Grid displaying all of Magento's credit memo joined with SureTax credit memo
 * to accurately display which credit memo has been finalized in SureTax.
 * 
 * @category  WoltersKluwer
 * @package   WoltersKluwer_CchSureTax
 * @copyright Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
                     
class WoltersKluwer_CchSureTax_Block_Adminhtml_Creditmemos_Container_Grid
    extends WoltersKluwer_CchSureTax_Block_Adminhtml_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('suretaxCreditMemosGrid');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    //Load the collection of SureTax Order.
    protected function _prepareCollection()
    {             
        $collection = WoltersKluwer_CchSureTax_Model_Resource_Creditmemo_Collection::joinWithCreditmemo();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.entity_id');
        $this->getMassactionBlock()->setFormFieldName('creditmemo_ids');
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
            'header'    => Mage::helper('suretax')->__('Credit Memo #'),
            'width'     => '100px',
            'index'     => 'increment_id',
            'filter_index' => 'main_table.increment_id',
            'type'      => 'text'
            )
        );
        
        $this->addColumn(
            'created_at', array(
            'header'    => Mage::helper('suretax')->__('Created At'),
            'width'     => '150px',
            'index'     => 'created_at',
            'filter_index' => 'main_table.created_at',
            'type'      => 'datetime'
            )
        );
        
        $this->addColumn(
            'order_number', array(
            'header'    => Mage::helper('suretax')->__('Order #'),
            'width'     => '100px',
            'index'     => 'order_number',
            'filter_index' => 'order.increment_id',
            'type'      => 'text'
            )
        );
        
        $this->addColumn(
            'tax_amount', array(
            'header'    => Mage::helper('suretax')->__('Magento Tax Refunded'),
            'index'     => 'tax_amount',
            'filter_index' => 'main_table.tax_amount',
            'type'      => 'currency',
            'currency'  => 'order_currency_code'
            )
        );
                      
        $this->addColumn(
            'tax', array(
            'header'    => Mage::helper('suretax')->__('SureTax Refunded Tax'),
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
            'filter' => false,
            'type'      => 'currency',
            'currency'  => 'order_currency_code'
            )
        );
        
        $this->addColumn(
            'trans_id', array(
            'header'    => Mage::helper('suretax')->__('SureTax Trans ID'),
            'width'     => '100px',
            'index'     => 'trans_id',
            'filter_index' => 'sure.trans_id',
            'type'      => 'text'
            )
        );
              
        $this->addColumn(
            'client_tracking', array(
            'header'    => Mage::helper('suretax')->__('Client Tracking'),
            'index'     => 'client_tracking',
            'filter_index' => 'sure.client_tracking',
            'type'      => 'text'
            )
        );

        $this->addColumn(
            'status', array(
            'header'    => Mage::helper('suretax')->__('SureTax Status'),
            'index'     => 'status',
            'filter_index' => 'sure.status',
            'type'      => 'options',
            'options'   => WoltersKluwer_CchSureTax_Helper_Constants::$SURETAX_STATUS
            )
        );
        
        $this->addColumn(
            'notes', array(
            'header'    => Mage::helper('suretax')->__('Notes'),
            'width'     => '200px',
            'index'     => 'notes',
            'filter_index' => 'sure.notes',
            'type'      => 'text'
            )
        );
        
        $this->addColumn(
            'action',
            array(
                'header'    => Mage::helper('sales')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url'     => array('base'=>'*/sales_creditmemo/view'),
                        'field'   => 'creditmemo_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true
            )
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('suretax')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('suretax')->__('Excel XML'));
        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/order/creditmemo')) {
            return false;
        }

        return $this->getUrl(
            '*/sales_creditmemo/view',
            array(
                'creditmemo_id'=> $row->getId(),
            )
        );
    }
}
