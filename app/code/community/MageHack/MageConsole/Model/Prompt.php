<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Prompt extends MageHack_MageConsole_Model_Abstract
{

    /**
     * Prompt
     *
     * @var     array
     */
    protected $_prompt;

    /**
     * Data to add
     *
     * @var     array
     */
    protected $_addData;

    /**
     * Set add data
     *
     * @param   array   $data
     * @return  MageHack_MageConsole_Model_Prompt
     */
    public function setAddData($addData)
    {
        $this->_addData = $addData;
        return $this;
    }
    
    /**
     * Get add data
     *
     * @return  array
     */
    public function getAddData()
    {
        return $this->_addData;
    }
    
    /**
     * Set prompt data
     *
     * @param   array   $prompt
     * @return  MageHack_MageConsole_Model_Prompt
     */
    public function setPrompt($prompt)
    {
        $this->_prompt = $prompt;
        return $this;
    }    
    
    /**
     * Get prompt
     *
     * @return  array
     */
    public function getPrompt()
    {
        return $this->_prompt;
    }    
    
    /**
     * Add entity
     *
     * @return  MageHack_MageConsole_Model_Prompt
     */
    public function addEntity()
    {
        $prompt     = $this->getPrompt();
        $entity     = $prompt['entity'];        
        $entityType = $this->_entities[$entity];                
        $model      = Mage::getModel($this->_models[$entityType]);
                
        $default = array(
            'type_id'           => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            'attribute_set_id'  => 4,
            'tax_class_id'      => 2,
            'price'             => '0.00',
            'websites'          => array(
                'base',
            ),
        );
                
        $model->addData(
            array_merge($default, $this->getAddData())
        );
        
        $model->save();
        
        $this->setMessage('Entity created: ' . $model->getId());
        
        return $this;
    }
}
