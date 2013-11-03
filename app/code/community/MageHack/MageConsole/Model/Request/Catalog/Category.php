<?php

/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Catalog_Category extends MageHack_MageConsole_Model_Abstract implements MageHack_MageConsole_Model_Request_Interface {

    /**
     * Columns
     *
     * @var     array
     */
    protected $_columns = array(
        'entity_id' => 12,
        'name' => 40,
        'is_active' => 12,
        'display_mode' => 15,
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Customer_Model_Customer
     */
    protected function _getModel() {
        return Mage::getModel('catalog/category');
    }

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add() {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('This action is not available');
        return $this;
    }

    /**
     * Update command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update() {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('This action is not available');
        return $this;
    }

    /**
     * Remove command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function remove() {
        $collection = $this->_getMatchedResults();

        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 1) {
            $message = 'Multiple matches found, please use the list command';
        } else {
            $category = $collection->getFirstItem();
            try {
                $name = $category->getName();
                $id = $category->getId();
                $category->delete();
                $message = sprintf('Category: %s (%s) deleted.', $name, $id);
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $this;
    }

    /**
     * Show command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function show() {
        $collection = $this->_getMatchedResults();

        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 1) {
            $message = 'Multiple matches found, please use the list command';
        } else {
            $category = $collection->getFirstItem();
            $details = $category->getData();
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
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing() {
        $collection = $this->_getMatchedResults();

        foreach ($this->_columns as $attr => $width) {
            $collection->addAttributeToSelect($attr);
        }

        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            foreach ($values as $row) {
                $value = array();

                foreach ($this->_columns as $attr => $width) {
                    if (isset($row[$attr]))
                        $value[$attr] = $row[$attr];
                    else
                        $value[$attr] = '';
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
     *
     */
    public function help() {
        $message = <<<USAGE
Usage: 
list category [where <attribute> <op> value]
show category where <attribute> <op> value
remove category where <attribute> <op> value

<attribute>
name, entity_id,is_active,display_mode
</attribute>

<op>
=,<,<=,>,>=,eq,neq,like,in,nin,notnull,
null,moreq,gt,lt,gteq,lteq,finset,regexp,seq,sneq
</op>

USAGE;
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $message;
    }

    /**
     * Get all commands for tab completion
     *
     * @return array
     */
    public function allCommands()
    {
        return array(
            'list category',
            'list category where',
            'show category where',
            'remove category where'
        );

    }


}
