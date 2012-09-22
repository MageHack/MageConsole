<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class MageHack_MageConsole_Model_Abstract extends Mage_Core_Model_Abstract
{
    
    /**
     * Console response types
     */
    const RESPONSE_TYPE_PROMPT  = 'PROMPT';
    const RESPONSE_TYPE_MESSAGE = 'MESSAGE';
    const RESPONSE_TYPE_LIST    = 'LIST';
    const RESPONSE_TYPE_ERROR   = 'ERROR';

    /**
     * Request
     *
     * @var     string
     */
    var $_request;    
    
    /**
     * Type
     *
     * @var     string
     */
    var $_type;

    /**
     * Message
     *
     * @var     string
     */
    var $_message;

    /**
     * Set message
     *
     * @param   string  $message
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function setMessage($message)
    {
        $this->_message = $message;
        return $this;
    }
    
    /**
     * Get message
     *
     * @return  string
     */
    public function getMessage()
    {
        return $this->_message;
    }
    
    /**
     * Set type
     *
     * @param   string  $message
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }
    
    /**
     * Get type
     *
     * @return  string
     */
    public function getType()
    {
        return $this->_type;
    }    
    
    /**
     * Set request
     *
     * @param   string  $request
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function setRequest($request)
    {
        $this->_request = $request;
        return $this;
    }

    /**
     * Get request
     *
     * @return  string
     */
    public function getRequest($part = null)
    {
        if (is_null($part)) {
            return $this->_request;            
        } else {
            $parts = explode(' ', preg_replace('/\s+/', ' ', $this->_request));
            
            if (!isset($parts[$part])) {
                return false;
            } else {
                return $parts[$part];                
            }
        }
    }
    
    /**
     * Get action
     *
     * @return  string
     */
    public function getAction()
    {
        return strtolower($this->getRequest(0));
    }
    
    /**
     * Get entity
     *
     * @return  string
     */
    public function getEntity()
    {
        return strtolower($this->getRequest(1));
    }
}
