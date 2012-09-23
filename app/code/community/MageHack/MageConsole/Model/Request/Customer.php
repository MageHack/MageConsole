<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Customer
    extends MageHack_MageConsole_Model_Abstract
    implements MageHack_MageConsole_Model_Request_Interface
{

    /**
     * Columns
     *
     * @var     array
     */
    protected $_columns = array(
        'entity_id'     => 12,
        'firstname'     => 20,
        'lastname'      => 20,
        'email'         => 40,
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Customer_Model_Customer
     */
    protected function _getModel()
    {
        return Mage::getModel('customer/customer');
    }

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add()
    {
        $this->setType(self::RESPONSE_TYPE_PROMPT);
        $this->setMessage($this->_getReqAttr());
        return $this;
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
            $customer    = $collection->getFirstItem();
            try {
                $email  = $customer->getEmail();
                $customer->delete();
                $message = sprintf('Customer with email %s deleted.', $email);
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
            $customer    = $collection->getFirstItem();
            $details = $customer->getData();
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

        foreach ($this->_columns as $attr => $width) {
            $collection->addAttributeToSelect($attr);
        }

        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            if (is_array($values)) {
                foreach ($values as $row) {
                    if (is_array($row)) {
                        $value = array();

                        foreach ($this->_columns as $attr => $width) {
                            $value[$attr] = $row[$attr];
                        }

                        $_values[] = $value;
                    }
                }
                $message = Mage::helper('mageconsole')->createTable($_values, true, array('columnWidths' => array_values($this->_columns)));
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
