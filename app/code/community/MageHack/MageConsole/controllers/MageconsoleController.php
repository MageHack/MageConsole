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
     * Retrieve autocomplete model
     *
     * @return  MageHack_MageConsole_Model_Autocomplete
     */
    protected function _getAutocompleteModel()
    {
        return Mage::getModel('mageconsole/autocomplete');
    }

    /**
     * Retrieve request model
     *
     * @return  MageHack_MageConsole_Model_Request
     */
    protected function _getRequestModel()
    {
        return Mage::getModel('mageconsole/request');
    }
    
    /**
     * Autocomplete request
     *
     * @return  string
     */
    public function autocompleteAction()
    {
        $params     = $this->getRequest()->getParams();
        $response   = new Varien_Object();

        try {
            $autocomplete = $this->_getAutocompleteModel()
                ->setRequest($params['request'])
                ->getOptions();
                
            $response->setStatus('OK');
            $response->setRequest($params['request']);
            $response->setMessage($autocomplete->getMessage());
            $response->setType($autocomplete->getType());            
        } catch (Exception $e) {
            $response->setType(MageHack_MageConsole_Model_Abstract::ERROR);            
            $response->setMessage($e->getMessage());
        }

        $this->getResponse()->setBody($response->toJson());        
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
            $request = $this->_getRequestModel()
                ->setRequest($params['request'])
                ->dispatch();
            
            $response->setStatus('OK');
            $response->setRequest($params['request']);
            $response->setMessage($request->getMessage());
            $response->setType($request->getType());
        } catch (Exception $e) {
            $response->setStatus('ERROR');
            $response->setType(MageHack_MageConsole_Model_Abstract::ERROR);            
            $response->setMessage($e->getMessage());
        }

        $this->getResponse()->setBody($response->toJson());
    }
}
