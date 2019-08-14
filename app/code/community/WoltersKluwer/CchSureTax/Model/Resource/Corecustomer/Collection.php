<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class WoltersKluwer_CchSureTax_Model_Resource_Corecustomer_Collection extends 
Mage_Customer_Model_Resource_Customer_Collection
{
    public function filterBasedOnExemptionFields($fieldName,$condition)
    {
        $conditionWithNewFields = $this->getConnection()
            ->prepareSqlCondition($fieldName, $condition);
        $this->getSelect()->where($conditionWithNewFields);
    }
}