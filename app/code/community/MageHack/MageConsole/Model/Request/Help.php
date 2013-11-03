<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Help extends MageHack_MageConsole_Model_Abstract
{
    
    /**
     * Get request model
     *
     * @return  MageHack_MageConsole_Model_Request
     */
    public function _getRequestModel()
    {
        return Mage::getModel('mageconsole/request');
    }
    
    /**
     * Help command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function help() {
        $message    = "Available commands:\n";        
        $message   .= implode("\n", array_keys($this->_getRequestModel()->getActionMapping()));
        
        $message   .= "\n\nAvailable entities:\n";        
        $message   .= implode("\n", array_keys($this->_getRequestModel()->getEntityMapping()));
        $message   .= "\n\nFor more help try help [entity], for example help product";        
        
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $this;
    }


    /**
     * Get all commands for tab completion
     *
     * @return array
     */
    public function allCommands()
    {
        $arr = array('help');
        $list = array_keys($this->_getRequestModel()->getEntityMapping());
        foreach ($list as $cmd) {
            $arr[] = 'help '.$cmd;
        }
        return $arr;
    }

}
