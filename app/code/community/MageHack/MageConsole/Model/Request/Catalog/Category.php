<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Catalog_Category
    extends MageHack_MageConsole_Model_Abstract
    implements MageHack_MageConsole_Model_Request_Interface
{

    protected $_attrToShow = array(
        'entity_id' => 'entity_id',
        'name' => 'name',
        'is_active' => 'is_active',
        'display_mode' => 'display_mode',
    );

    protected $_columnWidths = array(
        'columnWidths' => array(12, 40, 12, 12)
    );


    /**
     * Get instance of Customer model
     *
     * @return  Mage_Customer_Model_Customer
     */
    protected function _getModel()
    {
        return Mage::getModel('catalog/category');
    }

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add()
    {

    }

    /**
     * Update command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update()
    {

    }

    /**
     * Remove command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function remove()
    {
        $collection = $this->_getMatchedResults();

        if (!$collection->count()) {
            $message    = 'No match found';
        } else if ($collection->count() > 1) {
            $message    = 'Multiple matches found, please use the list command';
        } else {
            $category    = $collection->getFirstItem();
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
    public function show()
    {
        $collection = $this->_getMatchedResults();

        if (!$collection->count()) {
            $message    = 'No match found';
        } else if ($collection->count() > 1) {
            $message    = 'Multiple matches found, please use the list command';
        } else {
            $category    = $collection->getFirstItem();
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
    public function listing()
    {
        $collection = $this->_getMatchedResults();

        foreach ($this->_attrToShow as $attr) {
            $collection->addAttributeToSelect($attr);
        }

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
    public function help()
    {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('help was requested for a product - this is the help message');
        return $this;
    }
}
