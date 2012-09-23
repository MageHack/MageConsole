<?php

/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Product extends MageHack_MageConsole_Model_Abstract implements MageHack_MageConsole_Model_Request_Interface {

    protected $_attrToShow = array('name' => 'name', 'sku' => 'sku', 'status' => 'status');
    protected $_columnWidths = array(
        'columnWidths' => array(12, 40, 4)
    );

    /**
     * Get instance of product model
     *
     * @return  Mage_Catalog_Model_Produt
     */
    protected function _getModel() {
        return Mage::getModel('catalog/product');
    }

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add() {
        $this->setType(self::RESPONSE_TYPE_PROMPT);
        $this->setMessage($this->getReqAttr());

        return $this;
    }

    /**
     * Update command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update() {
        
    }

    /**
     * Remove command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function remove() {
        
    }

    /**
     * Show command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function show() {
        $_collection = $this->_getModel()
                ->getCollection();
        $collection = $this->_prepareCollection($_collection);
        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 1) {
            $message = 'Multiple matches found, use the list command';
        } else {
            $product = $collection->getFirstItem();
            $details = $product->getData();
            $message = array();
            foreach ($details as $key => $info) {
                $message[] = sprintf('%s: %s', $key, $info);
            }
        }
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);

        return $this;
    }

    /**
     * 
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _prepareCollection(Mage_Catalog_Model_Resource_Product_Collection $collection) {

        foreach ($this->getConditions() as $condition) {
            $collection->addFieldToFilter($condition['attribute'], array($condition['operator'] => $condition['value']));
        }
        foreach ($this->_attrToShow as $attr) {
            $collection->addAttributeToSelect($attr);
        }
        return $collection;
    }

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing() {
        $_collection = $this->_getModel()
                ->getCollection();
        $collection = $this->_prepareCollection($_collection);

        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            foreach ($values as $row) {
                $_values[] = (array_intersect_key($row, $this->_attrToShow));
            }
            $message = Mage::helper('mageconsole')->createTable($_values, true, $this->_columnWidths);
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);

        return $this;
    }

    /**
     * Help command
     *
     * @return MageHack_MageConsole_Model_Abstract
     *
     */
    public function help() {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('help was requested for a product - this is the help message');
        return $this;
    }

    /**
     * Get all required attributes of the product entity
     * @return array
     */
    public function getReqAttr() {
        $ret = array();
        $attributes = Mage::getModel('catalog/product')->getAttributes();
        foreach ($attributes as $a) {
            $values = $a->getSource()->getAllOptions(false);
            if ($a->getData('frontend_input') == 'hidden' || !$a->getData('frontend_label')) continue;
            $ret[$a->getAttributeCode()] = array('label' => $a->getData('frontend_label'), 'values'=>$values);
        }

//        $sql = "SELECT e.attribute_code, e.frontend_label, e.backend_type FROM `eav_attribute` as e WHERE entity_type_id IN(select eat.entity_type_id FROM eav_entity_type as eat where eat.entity_type_code = 'catalog_product') and e.is_required =1 and frontend_label is not null";
//
//        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
//        $attributes = array();
//        $_attributes = $connection->fetchAll($sql);
//        if (count($_attributes) > 0) {
//            foreach ($_attributes as $_attribute) {
//                $attributes[$_attribute['attribute_code']] = array('label' => $_attribute['frontend_label'], 'type' => $_attribute['backend_type']);
//            }
//        }
        return $ret;
    }

    protected function _getAddPrompt() {
        $message = array_map(create_function('$val', 'return "Please enter $val :";'), array_values($this->getReqAttr()));
        return $message;
    }

}
