<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Store
    extends MageHack_MageConsole_Model_Abstract
    implements MageHack_MageConsole_Model_Request_Interface
{

//    protected $_attrToShow = array(
//        'store' => 'store',
//        'config' => 'config',
//    );
//
//    protected $_columnWidths = array(
//        'columnWidths' => array(12, 40)
//    );

    /**
     * Set store command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function set()
    {
        $this->setType(self::RESPONSE_TYPE_PROMPT);
        $values = array();
        foreach (Mage::app()->getStores() as $store) {
            $values[] = array('value' => $store->getId(), 'label'=>$store->getName());
        }
        $this->setMessage(array('store'=>array('label' => 'Set store to:', 'values' => $values)));
        return $this;
    }

    /**
     * Save command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function prompt()
    {

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
}
