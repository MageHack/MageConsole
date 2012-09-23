<?php

/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Catalog_Product
    extends MageHack_MageConsole_Model_Abstract implements MageHack_MageConsole_Model_Request_Interface
{

    /**
     * Columns
     *
     * @var     array
     */
    protected $_columns = array(
        'sku'           => 20,
        'name'          => 30,
        'price'         => 8,
        'visibility'    => 12,
        'status'        => 8,
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
        $this->setMessage($this->_getReqAttr());

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
     * @param   Mage_Catalog_Model_Resource_Product_Collection $collection
     * @return  Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _prepareCollection(Mage_Catalog_Model_Resource_Product_Collection $collection) {

        foreach ($this->getConditions() as $condition) {
            $collection->addFieldToFilter($condition['attribute'], array($condition['operator'] => $condition['value']));
        }
        foreach ($this->_columns as $attr => $width) {
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
                $value = array();
                
                foreach ($this->_columns as $attr => $width) {
                    $value[$attr] = $row[$attr];
                }
                
                $_values[] = $value;
            }
            $message = Mage::helper('mageconsole')->createTable($_values, true, array('columnWidths' => array_values($this->_columns)));
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);

        return $this;
    }

    /**
     * Help command
     *
     * @return MageHack_MageConsole_Model_Abstract
     */
    public function help() {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('help was requested for a product - this is the help message');
        return $this;
    }

    /**
     * Get all required attributes of the product entity
     * 
     * @return  array
     */
    protected function getReqAttr() {
        $ret = array();
        $attributes = Mage::getModel('catalog/product')->getAttributes();
        foreach ($attributes as $a) {
            $values = $a->getSource()->getAllOptions(false);
            if ($a->getData('frontend_input') == 'hidden' || !$a->getData('frontend_label')) continue;
            $ret[$a->getAttributeCode()] = array('label' => $a->getData('frontend_label'), 'values'=>$values);
        }

        return $ret;
    }
}
