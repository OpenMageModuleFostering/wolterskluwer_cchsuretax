<?php
/*
 * SureTax grid for which we have proper filtering.
 *
 * Grid displaying all of Magento's credit memo joined with SureTax credit memo
 * to accurately display which credit memo has been finalized in SureTax.
 *
 * @category     WoltersKluwer
 * @package      WoltersKluwer_CchSureTax
 * @copyright    Copyright (c) 2016, CCH Incorporated. All rights reserved
 */
                     
class WoltersKluwer_CchSureTax_Block_Adminhtml_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    
    protected function _setFilterValues($data)
    {
        foreach ($this->getColumns() as $columnId => $column) {
            if (isset($data[$columnId])
                && (!empty($data[$columnId]) || strlen($data[$columnId]) > 0)
                && $column->getFilter()
            ) {
                $column->getFilter()->setValue($data[$columnId]);

                if ($this->isNA($data[$columnId]) 
                    && ($columnId === 'client_tracking'  
                    || $columnId === 'notes' 
                    || $columnId === 'status' 
                    || $columnId === 'stan' 
                    || $columnId === 'trans_id')
                ) {
                    
                    $this->_addColumnFilterToCollectionWithNA($column);        
                    
                } else { 
                    $this->_addColumnFilterToCollection($column);                   
                }
                
                WoltersKluwer_CchSureTax_Helper_Utility::logMessage(
                    'SureTax Grid filter select : ' .
                    (string)$this->getCollection()->getSelect(), Zend_Log::DEBUG
                );
            }
        }
        return $this;
    }
    
    protected function _addColumnFilterToCollectionWithNA($column)
    {
        if ($this->getCollection()) {
            $field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();
            if ($column->getFilterConditionCallback()) {
                call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);
            } else {
                $cond = $column->getFilter()->getCondition();
                if ($field && isset($cond)) {
                    $this->getCollection()->addFieldToFilter(
                        $field,
                        array(array('null' => true), $cond)
                    );  
                }
            }
        }
        return $this;
    }
    
    /**
     * For use when filtering in the Suretax invoice grid or credit memo grid.
     * 
     * @param  string $value
     * @return boolean
     */
    public function isNA($value)
    {
        if (is_array($value)) {
            return false;
        }
        if (strcasecmp($value, 'n') == 0 
            || strcasecmp($value, '/') == 0 
            || strcasecmp($value, 'a') == 0 
            || strcasecmp($value, 'n/') == 0 
            || strcasecmp($value, '/a') == 0 
            || strcasecmp($value, 'n/a') == 0
        ) {           
            return true;
        }
        return false;
    }   
}
