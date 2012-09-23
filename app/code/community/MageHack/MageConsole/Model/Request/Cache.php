<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Cache
    extends MageHack_MageConsole_Model_Abstract
{

    protected $_attrToShow = array(
        'cache_type'    => 'cache_type',
        'description'   => 'description',
        'tags'          => 'tags',
        'status'        => 'status'
    );

    protected $_columnWidths = array(
        'columnWidths' => array(30, 30, 50, 20)
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Core_Model_Cache
     */
    protected function _getModel()
    {
        return Mage::getModel('core/cache');
    }


    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing()
    {
        $collection = new Varien_Data_Collection();
        foreach (Mage::app()->getCacheInstance()->getTypes() as $type) {
            $collection->addItem($type);
        }

        if (!$collection->count()) {
            $message = 'This is strange, we did not find any cache types.';
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            if (is_array($values) && $values['totalRecords'] > 0) {
                foreach ($values['items'] as $row) {
                    if (is_array($row)) {
                        $_values[] = (array_intersect_key($row, $this->_attrToShow));
                    }
                }
                $message = Mage::helper('mageconsole')->createTable($_values, true, $this->_columnWidths);
            }
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
