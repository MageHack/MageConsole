<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class MageHack_MageConsole_Model_Abstract
{

    /**
     * Console response types
     */
    const RESPONSE_TYPE_PROMPT  = 'PROMPT';
    const RESPONSE_TYPE_MESSAGE = 'MESSAGE';
    const RESPONSE_TYPE_LIST    = 'LIST';
    const RESPONSE_TYPE_ERROR   = 'ERROR';

    /**
     * 'WHERE' keyword
     */
    const WHERE = 'where';

    /**
     * Operators
     *
     * @var     array
     */
    protected $_operators = array(
        '='         => 'eq',
        '<'         => 'lt',
        '<='        => 'lteq',
        '>'         => 'gt',
        '>='        => 'gteq',
        'eq'        => 'eq',
        'neq'       => 'neq',
        'like'      => 'like',
        'in'        => 'in',
        'nin'       => 'nin',
        'notnull'   => 'notnull',
        'null'      => 'null',
        'moreq'     => 'moreq',
        'gt'        => 'gt',
        'lt'        => 'lt',
        'gteq'      => 'gteq',
        'lteq'      => 'lteq',
        'finset'    => 'finset',
        'regexp'    => 'regexp',
        'seq'       => 'seq',
        'sneq'      => 'sneq',
    );

    /**
     * Request
     *
     * @var     string
     */
    protected $_request;

    /**
     * Type
     *
     * @var     string
     */
    protected $_type;

    /**
     * Message
     *
     * @var     string
     */
    protected $_message;

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

    /**
     * Get conditions
     *
     * @return  array
     */
    public function getConditions()
    {
        $i      = 2;
        $where  = 0;

        $index      = 'key';
        $key        = null;
        $value      = null;
        $operator   = null;
        $conditions = array();

        while (true) {
            if (!$part = $this->getRequest($i)) {
                break;
            }

            if (!$where && strtolower($part) == self::WHERE) {
                $where = 1;
            } else {
                $continue = 1;

                if ($index == 'key' && $continue) {
                    $key        = $part;
                    $index      = 'operator';
                    $continue   = 0;
                }

                if ($index == 'operator' && $continue) {
                    if (array_key_exists($part, $this->_operators)) {
                        $operator   = $this->_operators[$part];
                        $continue   = 0;
                    }

                    $index = 'value';
                }

                if ($index == 'value' && $continue) {
                    $value  = $part;
                    $index  = 'key';
                }
            }

            if (!is_null($key) && !is_null($value)) {
                if (is_null($operator)) {
                    $operator = 'eq';
                }

                $conditions[] = array(
                    'attribute' => $key,
                    'value'     => str_replace(array('\'', '"'), '', $value),
                    'operator'  => $operator,
                );

                $key        = null;
                $value      = null;
                $operator   = null;
            }

            $i++;
        }

        return $conditions;
    }

    /*
     * Get Matched results of the entity for given condition
     *
     * @return Varien_Data_Collection
     */
    protected function _getMatchedResults()
    {
        $collection = $this->_getModel()
            ->getCollection()
            ->addAttributeToSelect('*');

        foreach ($this->getConditions() as $condition) {
            $collection->addFieldToFilter($condition['attribute'], array($condition['operator'] => $condition['value']));
        }

        return $collection;
    }
}
