<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Autocomplete extends MageHack_MageConsole_Model_Abstract
{
    
    /**
     * Entity mapping
     *
     * @var  array
     */
    protected $_entities = array(
        'customer'      => 'customer',
        'address'       => 'customer_address',
        'category'      => 'catalog_category',
        'product'       => 'catalog_product',
        'order'         => 'order',
        'invoice'       => 'invoice',
        'creditmemo'    => 'creditmemo',
        'shipment'      => 'shipment',
    );

    /**
     * Model mapping
     *
     * @var  array
     */
    protected $_models = array(
        'customer'          => 'customer/customer',
        'customer_address'  => 'customer/address',
        'catalog_category'  => 'catalog/category',
        'catalog_product'   => 'catalog/product',
        'order'             => 'sales/order',
        'invoice'           => 'sales/order_invoice',
        'creditmemo'        => 'sales/order_creditmemo',
        'shipment'          => 'sales/order_shipment',
    );

    /**
     * Get request model
     *
     * @return  MageHack_MageConsole_Model_Request
     */
    public function _getRequestModel()
    {
        return Mage::getModel('mageconsole/request');
    }
    
    /**
     * Auto complete entity
     *
     * @param   string  $entityPart
     * @return  string
     */
    protected function _completeEntity($entityPart)
    {
        $message        = '';
        $entities       = array_keys($this->_getRequestModel()->getEntityMapping());
        $autocomplete   = array();
        
        foreach ($entities as $entity) {
            if (preg_match('/^' . $entityPart .'.+/', $entity)) {
                $autocomplete[] = $entity;
            }
        }
                
        return implode(' ', $autocomplete);
    }
    
    /**
     * Auto complete entity attribute
     *
     * @param   string  $entity
     * @param   string  $attributePart
     * @return  string
     */
    protected function _completeEntityAttribute($entity, $attributePart)
    {
        $message        = '';
        $autocomplete   = array();        
        $entityIds      = array();
        
        foreach (Mage::getResourceModel('eav/entity_type_collection') as $item) {
            $entityIds[$item->getEntityTypeCode()] = $item->getId();
        }
                        
        $entityType = $this->_entities[$entity];                
        $entityId   = $entityIds[$entityType];
            
        /* EAV */                        
        $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->addFieldToFilter('entity_type_id', $entityId);
                
        foreach ($attributes as $attribute) {
            if (preg_match('/^' . $attributePart .'.+/', $attribute->getAttributeCode())) {
                $autocomplete[] = $attribute->getAttributeCode();
            }
        }
        
        /* Flat */
        if (!count($attributes)) {
            $model  = Mage::getResourceModel($this->_models[$entityType]);
            $table  = $model->getMainTable();
            
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $attributes = array_keys($connection->describeTable('sales_flat_order'));            
            
            foreach ($attributes as $attribute) {
                if (preg_match('/^' . $attributePart .'.+/', $attribute)) {
                    $autocomplete[] = $attribute;
                }
            }            
        }
                
        return implode(' ', $autocomplete);         
    }

    /**
     * Get options
     *
     * @return  MageHack_MageConsole_Model_Autocomplete
     */
    public function getOptions()
    {        
        /* Entity autocompletion */     
        if (!$this->getRequest(2)) {
            $this->setMessage($this->_completeEntity($this->getRequest(1)));
        } else {            
            if (!$this->getRequest(3)) {
                $this->setMessage($this->_completeEntityAttribute($this->getRequest(1), $this->getRequest(2)));                
            } else if ($this->getRequest(3) && strtolower($this->getRequest(3) != self::WHERE)) {
                $this->setMessage($this->_completeEntityAttribute($this->getRequest(1), $this->getRequest(3)));                                
            }
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        
        return $this;   
    }
}
