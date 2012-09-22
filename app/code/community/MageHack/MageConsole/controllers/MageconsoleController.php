<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_MageconsoleController extends Mage_Adminhtml_Controller_Action
{
	
    /**
     * Retrieve MageConsole helper
     *
     * @return  MageHack_MageConsole_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('mageconsole');
    }
    
    /**
     * Submit action
     *
     * @return  string
     */
    public function submitAction()
    {
        $params     = $this->getRequest()->getParams();
        $response   = new Varien_Object();

        try {
            $response->setStatus('OK');
            $response->setCommand($params['command']);
        } catch (Exception $e) {
            $response->setStatus('ERROR');
            $response->setMessage($e->getMessage());
        }

        $this->getResponse()->setBody($response->toJson());
    }
}
