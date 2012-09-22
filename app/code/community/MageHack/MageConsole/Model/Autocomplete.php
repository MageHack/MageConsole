<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Autocomplete extends MageHack_MageConsole_Model_Abstract
{

    /**
     * Get options
     *
     * @return  MageHack_MageConsole_Model_Autocomplete
     */
    public function getOptions()
    {             
        $this->setType(self::RESPONSE_TYPE_LIST);
        $this->setMessage(array($this->getEntity()));
        
        return $this;   
    }
}
