<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Store extends MageHack_MageConsole_Model_Abstract
{

    /**
     * Columns
     *
     * @var     array
     */
    protected $_columns = array(
        'store_id'		=> 12,
        'website_id'  	=> 12,
        'name'        	=> 30,
        'is_active'		=> 12,
    );

    /**
     * Get instance of Config model
     *
     * @return  Mage_Core_Model_Config
     */
    protected function _getModel()
    {
        return Mage::getModel('core/store');
    }

    /**
     * Set store command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function set()
    {
        $param = $this->getRequest(2);
        if (strtolower($param) == 'default') {
            $defaultStoreId = Mage::app()
                ->getWebsite()
                ->getDefaultGroup()
                ->getDefaultStoreId();
            $store = Mage::app()->getStore($defaultStoreId);
        }
        elseif (is_numeric($param)) {
            $store = Mage::app()->getStore($param);
        }
        else {
            foreach (Mage::app()->getStores() as $object) {
                if (strtolower($object->getName()) == strtolower($param)) {
                    $store = $object;
                }
            }
        }

        if (!isset($store)) {
            $this->setType(self::RESPONSE_TYPE_MESSAGE);
            $this->setMessage("No store found!");
        } else {
            Mage::getSingleton('adminhtml/session')->setMageConsoleStore($store);
        }
        return $this;
    }

    /**
     * Get store command
     *
     * @return  Mage_Core_Model_Store
     */
    public function get()
    {
        if (!$store = Mage::getSingleton('adminhtml/session')->getMageConsoleStore()) {
            $store = Mage::app()->getStore();
        }
        $data = $store->getData();
        $value = array();
        foreach ($this->_columns as $attr => $width) {
            $value[$attr] = $data[$attr];
        }
        $_values = array($value);

        if (Mage::getStoreConfig('admin/mageconsole/html_tables') != 1) {
            $this->setType(self::RESPONSE_TYPE_MESSAGE);
            $message = Mage::helper('mageconsole')->createTable($_values, true, array('columnWidths' => array_values($this->_columns)));
            $this->setMessage($message);
        } else {
            $this->setMessage($_values);
            $this->setType(self::RESPONSE_TYPE_LIST);
        }
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
        $this->setMessage('help was requested for a store command - this is the help message');
        return $this;
    }

    /**
     * Get all commands for tab completion
     *
     * @return array
     */
    public function allCommands()
    {
        return array(
            'list store',
            'get store'
        );
    }

}
