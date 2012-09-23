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
     * Retrieve prompt model
     *
     * @return  MageHack_MageConsole_Model_Prompt
     */
    protected function _getPromptModel()
    {
        return Mage::getModel('mageconsole/prompt');
    }
    
    /**
     * Save prompt data
     *
     * @param   array   $data
     * @return  string
     */
    protected function _savePrompt($data)
    {
        $prompts = $this->_getSession()->getMageConsolePrompts();
        
        if (!is_array($prompts)) {
            $prompts = array();
        }
        
        $key            = sha1(serialize($data) . time());    
        $prompts[$key]  = $data;
        
        $this->_getSession()->setMageConsolePrompts($prompts);
        
        $prompts = $this->_getSession()->getMageConsolePrompts();
        
        return $key;
    }
    
    /**
     * Load prompt data
     *
     * @param   string  $key
     * @return  boolean|string
     */
    protected function _loadPrompt($key)
    {
        $prompt     = false;
        $prompts    = $this->_getSession()->getMageConsolePrompts();
        
        if (!is_array($prompts)) {
            $prompts = array();
        }
        
        if (array_key_exists($key, $prompts)) {
            $this->_getSession()->setMageConsolePrompts($prompts);
            
            $prompt = $prompts[$key];
            unset($prompts[$key]);            
        }

        return $prompt;
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
            $response->setType(MageHack_MageConsole_Model_Abstract::RESPONSE_TYPE_ERROR);            
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
            
            if ($request->getType() == MageHack_MageConsole_Model_Abstract::RESPONSE_TYPE_PROMPT) {
                $key = $this->_savePrompt(
                    array(
                        'entity'    => $request->getEntity(),
                        'action'    => $request->getAction(),
                    )
                );
                                
                $response->setKey($key);
            }
        } catch (Exception $e) {
            $response->setStatus('ERROR');
            $response->setType(MageHack_MageConsole_Model_Abstract::RESPONSE_TYPE_ERROR);            
            $response->setMessage($e->getMessage());
        }

        $this->getResponse()->setBody($response->toJson());
    }
    
    /**
     * Submit prompt action
     *
     * @return  string
     */
    public function promptAction()
    {
        $params     = $this->getRequest()->getParams();
        $response   = new Varien_Object();

        try {
            $key    = $params['key'];
            $data   = $params['data'];
            
            if (empty($key) || empty($data)) {
                Mage::throwException('Parameters key and data are required');
            }            
            
            if (!$prompt = $this->_loadPrompt($key)) {
                Mage::throwException('Session time out, try again');                
            }            
                        
            $request = $this->_getPromptModel()
                ->setPrompt($prompt)
                ->setAddData($data)
                ->addEntity();            
            
            $response->setStatus('OK');
            $response->setMessage($request->getMessage());
            $response->setType(MageHack_MageConsole_Model_Abstract::RESPONSE_TYPE_MESSAGE);
        } catch (Exception $e) {
            $response->setStatus('ERROR');
            $response->setType(MageHack_MageConsole_Model_Abstract::RESPONSE_TYPE_ERROR);            
            $response->setMessage($e->getMessage());
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json', true)->setBody($response->toJson());
    }    
}
