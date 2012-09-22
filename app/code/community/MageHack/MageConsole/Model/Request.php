<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request extends MageHack_MageConsole_Model_Abstract
{
    
    /**
     * Entity model
     *
     * @var     MageHack_MageConsole_Model_Abstract
     */
    var $_entityModel;

    /**
     * Entity mapping
     *
     * @var array
     */
    var $_entityMapping = array(
        'category'      => 'mageconsole/request_category',
        'product'       => 'mageconsole/request_product',
        'creditmemo'    => 'mageconsole/request_creditmemo',
        'customer'      => 'mageconsole/request_customer',
        'address'       => 'mageconsole/request_address',
        'invoice'       => 'mageconsole/request_invoice',
        'order'         => 'mageconsole/request_order',
        'shipment'      => 'mageconsole/request_shipment',
        ''              => 'mageconsole/request_help',
    );

    /**
     * Entity mapping
     *
     * @var array
     */
    var $_actionMapping = array(
        'add'       => 'add',
        'update'    => 'update',
        'remove'    => 'remove',
        'delete'    => 'delete',
        'show'      => 'show',
        'list'      => 'listing',
        'help'      => 'help',
    );

    /**
     * Get entity mapping
     *
     * @return  mixed
     **/
    public function getEntityMapping($entity = null)
    {
        if (is_null($entity)) {
            return $this->_entityMapping;
        }
        
        if (array_key_exists($entity, $this->_entityMapping)) {
            return $this->_entityMapping[$entity];
        }
        
        return false;
    }
    
    /**
     * Get action mapping
     *
     * @return  mixed
     **/
    public function getActionMapping($action = null)
    {
        if (is_null($action)) {
            return $this->_actionMapping;
        }
        
        if (array_key_exists($action, $this->_actionMapping)) {
            return $this->_actionMapping[$action];
        }
        
        return false;
    }    
    
    /**
     * Get entity model
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function getEntityModel()
    {
        return $this->_entityModel;
    }
    
    /**
     * Set entity model
     *
     * @param   MageHack_MageConsole_Model_Abstract $entityModel 
     */
    public function setEntityModel($entityModel)
    {
        $this->_entityModel = $entityModel;
        return $this;
    }    

    /**
     * Dispatch request
     *
     * @throws  Mage_Core_Exception
     * @return  array
     */
    public function dispatch()
    {               
        if (!$actionName = $this->getActionMapping($this->getAction())) {
            Mage::throwException('Invalid action: ' . $this->getAction());
        }

        if (!$entityModelName = $this->getEntityMapping($this->getEntity())) {
            Mage::throwException('Invalid entity: ' . $this->getEntity());
        }
                
        if (!$entityModel = Mage::getModel($entityModelName)) {
            Mage::throwException('Model cannot be found: ' . $entityModel);
        }
                
        $entityModel->setRequest($this->getRequest());
        $this->setEntityModel($entityModel);
        call_user_func(array($entityModel, $actionName));                
        
        return $this->getEntityModel();
    }
}
