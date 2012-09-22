<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Help extends MageHack_MageConsole_Model_Abstract implements MageHack_MageConsole_Model_Request_Interface
{

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add() {
	$this->setType(self::RESPONSE_TYPE_MESSAGE);
	$this->setMessage("correct syntax is add {entity}");
	return $this;
    }

    /**
     * Update command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update() {

    }
    
    /**
     * Remove command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function remove() {

    }

    /**
     * Delete command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function delete() {
        
    }

    /**
     * Show command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function show() {
        
    }

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing() {
        
    }

    /**
     * Help command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function help() {
	$this->setType(self::RESPONSE_TYPE_MESSAGE);
	$this->setMessage("Hellp World!");
	return $this;
    }

}
