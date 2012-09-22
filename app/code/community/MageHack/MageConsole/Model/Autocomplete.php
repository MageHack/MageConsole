<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Autocomplete extends MageHack_MageConsole_Model_Abstract
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
     * Auto complete entity
     *
     * @param   string  $entityPart
     * @return  string
     */
    protected function _completeEntity($entityPart)
    {
        $message        = $message;
        $entities       = array_keys($this->_getRequestModel()->getEntityMapping());
        $autocomplete   = array();
        
        foreach ($entities as $entity) {
            if (preg_match('/^' . $entityPart .'.+/', $entity)) {
                $autocomplete[] = $entity;
            }
        }
                
        return implode(' ', $autocomplete);
    }

    /**
     * Get options
     *
     * @return  MageHack_MageConsole_Model_Autocomplete
     */
    public function getOptions()
    {        
        /* Entity autocompletion */     
        if (!$this->getRequest(2)) {
            $this->setMessage($this->_completeEntity($this->getRequest(1)));
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        
        return $this;   
    }
}
