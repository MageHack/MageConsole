<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Sales_Order
    extends MageHack_MageConsole_Model_Abstract
    implements MageHack_MageConsole_Model_Request_Interface
{

    /**
     * Columns
     *
     * @var     array
     */
    protected $_columns = array(
        'increment_id'          => 15,
        'customer_firstname'    => 20,
        'customer_lastname'     => 20,
        'customer_email'        => 30,
        'grand_total'           => 15,
        'status'                => 10,
        'order_currency_code'   => 22,
        'created_at'            => 22,
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Customer_Model_Customer
     */
    protected function _getModel()
    {
        return Mage::getModel('sales/order');
    }

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add()
    {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('This action is not available');
        return $this;
    }

    /**
     * Update command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update()
    {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('This action is not available');
        return $this;
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
            $order    = $collection->getFirstItem();
            try {
                $id  = $order->getIncrementId();
                $order->delete();
                $message = sprintf('Order with ref# %s deleted.', $id);
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
            $order    = $collection->getFirstItem();
            $details = $order->getData();
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
            $this->setType(self::RESPONSE_TYPE_MESSAGE);
            $this->setMessage($message);
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            if (is_array($values) && $values['totalRecords'] > 0) {
                foreach ($values['items'] as $row) {
                    if (is_array($row)) {
                        $value = array();

                        foreach ($this->_columns as $attr => $width) {
                            $value[$attr] = $row[$attr];
                        }

                        $_values[] = $value;                        
                    }
                }
                if (Mage::getStoreConfig('admin/mageconsole/html_tables') != 1) {
                    $this->setType(self::RESPONSE_TYPE_MESSAGE);
                    $message = Mage::helper('mageconsole')->createTable($_values, true, array('columnWidths' => array_values($this->_columns)));
                    $this->setMessage($message);
                } else {
                    $this->setMessage($_values);
                    $this->setType(self::RESPONSE_TYPE_LIST);
                }
            }
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
        $this->setMessage('help was requested for a product - this is the help message');
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
            'list order where',
            'show order where',
            'remove order where',
            'add order'
        );

    }


}
