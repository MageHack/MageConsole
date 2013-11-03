<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface MageHack_MageConsole_Model_Request_Interface
{

    /**
     * Add command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function add();
        
    /**
     * Update command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update();

    /**
     * Remove command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function remove();

    /**
     * Show command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function show();

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing();

    /**
     * Help command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function help();

    /**
     * Get all commands for tab completion
     *
     * @return array
     */
    public function allCommands();
}
