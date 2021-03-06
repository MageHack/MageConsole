<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Sales_Invoice
    extends MageHack_MageConsole_Model_Abstract
    implements MageHack_MageConsole_Model_Request_Interface
{

    /**
     * Columns
     *
     * @var     array
     */
    protected $_columns = array(
        'increment_id'      => 15,
        'order_id'          => 12,
        'grand_total'       => 15,
        'shipping_amount'   => 20,
        'created_at'        => 25,
    );
    
    /**
     * Get instance of Customer model
     *
     * @return  Mage_Customer_Model_Customer
     */
    protected function _getModel()
    {
        return Mage::getModel('sales/order_invoice');
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
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('This action is not available');
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
            $invoice    = $collection->getFirstItem();
            $details = $invoice->getData();
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
            $this->setMessage($message);
            $this->setType(self::RESPONSE_TYPE_MESSAGE);
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
            'list invoice where',
            'show invoice where',
            'remove invoice where',
            'add invoice'
        );

    }

}
