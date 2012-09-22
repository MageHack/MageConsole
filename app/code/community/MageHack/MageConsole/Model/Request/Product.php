<?php

/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Product extends MageHack_MageConsole_Model_Abstract implements MageHack_MageConsole_Model_Request_Interface {

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
       // var_dump('called');
        $this->setType(self::RESPONSE_TYPE_PROMPT);
        $this->setMessage($this->_getAddPrompt());

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
        $collection = $this->_getModel()
                ->getCollection()
                ->addAttributeToSelect('*');

        foreach ($this->getConditions() as $condition) {
            $collection->addFieldToFilter($condition['attribute'], array($condition['operator'] => $condition['value']));
        }

        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 1) {
            $message = 'Multiple matches found, use the list command';
        } else {
            $product = $collection->getFirstItem();
            $message = sprintf('Name: %s', $product->getName());
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);

        return $this;
    }

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing() {
        
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
        $sql = "SELECT e.attribute_code, e.frontend_label FROM `eav_attribute` as e WHERE entity_type_id IN(select eat.entity_type_id FROM eav_entity_type as eat where eat.entity_type_code = 'catalog_product') and e.is_required =1 and frontend_label is not null";

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $attributes = array();
        $_attributes = $connection->fetchAll($sql);
        if (count($_attributes) > 0) {
            foreach ($_attributes as $_attribute) {
                $attributes[$_attribute['attribute_code']] = $_attribute['frontend_label'];
            }
        }
        return $attributes;
    }

    protected function _getAddPrompt() {
        $message = array_map(create_function('$val', 'return "Please enter $val :";'), array_values($this->getReqAttr()));
        return $message;
    }

}
