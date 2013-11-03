<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Config
    extends MageHack_MageConsole_Model_Abstract
{

    protected $_attrToShow = array(
        'config_id'     => 'config_id',
        'scope'         => 'scope',
        'scope_id'      => 'scope_id',
        'path'          => 'path',
        'value'         => 'value',
    );

    protected $_columnWidths = array(
        'columnWidths' => array(20, 30, 20, 50, 50)
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Core_Model_Cache
     */
    protected function _getModel()
    {
        return Mage::getModel('core/config_data');
    }

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing()
    {
        $collection = $this->_getModel()
            ->getCollection();

        foreach ($this->getConditions() as $condition) {
            $collection->addFieldToFilter($condition['attribute'], array($condition['operator'] => $condition['value']));
        }

        if (!$collection->count()) {
            $message = 'This is strange, we did not find any cache types.';
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            if (is_array($values) && $values['totalRecords'] > 0) {
                foreach ($values['items'] as $row) {
                    if (is_array($row)) {
                        $_values[] = (array_intersect_key($row, $this->_attrToShow));
                    }
                }
                $message = Mage::helper('mageconsole')->createTable($_values, true, $this->_columnWidths);
            }
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $this;
    }

    /**
     * Set store command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function set()
    {
//        $this->setType(self::RESPONSE_TYPE_PROMPT);
//        $vals = array();
//        foreach (Mage::app()->getStores() as $store) {
//            $vals[] = array('value' => $store->getId(), 'label'=>$store->getName());
//        }
//        $this->setMessage(array('label' => 'Set config to:', 'values' => $vals));
//        return $this;
    }

    public function update($data = null) {
        if ($data) {
            $this->setType(self::RESPONSE_TYPE_MESSAGE);
            $w = Mage::getSingleton('core/resource')->getConnection('core_write');
            $msg = '';
            foreach ($data as $key => $value) {
                $id = str_replace('conf_','',$key)*1;
                if ($id) {
                    $w->query("update core_config_data set value=? where config_id=?",array($value,$id));
                    $msg .= " updated $id to '$value', ";
                } else {
                    $msg .= "bad key '$key', ";
                }
            }
            $msg .= 'OK';
            $this->setMessage($msg);
        } else {
            $this->setType(self::RESPONSE_TYPE_PROMPT);
            $collection = $this->_getModel()
                ->getCollection();
            foreach ($this->getConditions() as $condition) {
                $collection->addFieldToFilter($condition['attribute'], array($condition['operator'] => $condition['value']));
            }
            $ret = array();
            $attributes = $this->_getModel()->getAttributes();
            foreach ($collection as $conf) {
                $values = $conf->getData();
    //            if ($a->getData('frontend_input') == 'hidden' || !$a->getData('frontend_label')) continue;
                $ret["conf_".$conf['config_id']] = array('label' => "{$conf['path']}({$conf['scope']},{$conf['scope_id']})", 'values'=>array(), 'value'=>$conf['value']);
            }
            $this->setMessage($ret);
        }
        return $this;
    }

    /**
     * Help command
     *
     * @return MageHack_MageConsole_Model_Abstract
     *
     */
    public function help() {
        $message = <<<USAGE
Usage: list config

USAGE;
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $this;
    }

    /**
     * Get all commands for tab completion
     *
     * @return array
     */
    public function allCommands()
    {
        return array(
            'list config'
        );

    }

}
