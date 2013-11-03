<?php

/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Index extends MageHack_MageConsole_Model_Abstract
{

    protected $_attrToShow = array(
        'process_id' => 'process_id',
        'indexer_code' => 'indexer_code',
        'status' => 'status',
        'mode' => 'mode',
        'name' => 'name',
        'description' => 'description',
        'update_required' => 'update_required'
    );
    protected $_indexCodes = array('cpa' => 'catalog_product_attribute',
        'cpp' => 'catalog_product_price',
        'cu' => 'catalog_url',
        'cpf' => 'catalog_product_flat',
        'ccf' => 'catalog_category_flat',
        'ccp' => 'catalog_category_product',
        'csf' => 'catalogsearch_fulltext',
        'cis' => 'cataloginventory_stock',
        'ts' => 'tag_summary');
    protected $_columnWidths = array(
        'columnWidths' => array(12, 20, 20, 20, 20, 20, 20)
    );

    /**
     * Get instance of the Indexer model
     *
     * @return  Mage_Index_Model_Indexer
     */
    protected function _getModel() {
        return Mage::getSingleton('index/indexer');
    }

    /**
     * Update command for mode
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function update() {
        $message = '';
        $request = $this->getRequest(2);
        if ($request) {
            $parseResponse = $this->_parseIndexerString($request);
            if (is_array($parseResponse)) {
                $updatePair = $this->getRequest(3);
                if (preg_match_all('/mode\s*=\s*(manual)|mode\s*=\s*(real_time)/', $updatePair, $matches)) {
                    if ($matches[1][0] !== '')
                        $match = $matches[1][0];
                    if ($matches[2][0] !== '')
                        $match = $matches[2][0];
                    if ($match) {
                        foreach ($parseResponse as $process) {
                            $process->setData('mode', $match);
                            $process->save();
                            $message.='
                                The mode of ' . $process->getIndexer()->getName() . ' has been changed to ' . $match;
                        }
                    }
                }
            } else {
                $message.=$parseResponse;
            }
        }
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);

        return $this;
    }

    /*
     * Get Matched results of the entity for given condition
     *
     * @return Varien_Data_Collection
     */

    protected function _getMatchedResults() {
        $collection = $this->_getModel()->getProcessesCollection();
        foreach ($collection as $key => $item) {
            if (!$item->getIndexer()->isVisible()) {
                $collection->removeItemByKey($key);
                continue;
            }
            $item->setName($item->getIndexer()->getName());
            $item->setDescription($item->getIndexer()->getDescription());
            $item->setUpdateRequired($item->getUnprocessedEventsCollection()->count() > 0 ? 1 : 0);
        }
        return $collection;
    }

    /**
     * Show command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function show() {
        $collection = $this->_getMatchedResults();
        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 1) {
            $message = 'Multiple matches found, please use the list command';
        } else {
            $process = $collection->getFirstItem();
            $details = $process->getData();
            $message = array();
            foreach ($details as $key => $info) {
                $message[] = sprintf('%s: %s', $key, $info);
            }
        }

        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);

        return $this;
    }

    /**
     * This runs all the index actions e.g run index all, run index catalog_url
     * @return MageHack_MageConsole_Model_Request_Index
     */
    public function run() {
        $error = 1;
        $message = '';
        $request = $this->getRequest(2);
        if ($request) {
            $parseResponse = $this->_parseIndexerString($request);
            if (is_array($parseResponse)) {
                foreach ($parseResponse as $process) {
                    $process->reindexEverything();
                    $message .= $process->getIndexer()->getName() . " index was rebuilt successfully\n";
                    $error = 0;
                }
            } else {
                $message.=$parseResponse;
            }
        }
        if ($error) {
            $message.=$this->help();
        }
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $this;
    }

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing() {
        $collection = $this->_getMatchedResults();
        if (!$collection->count()) {
            $message = 'No match found';
        } else if ($collection->count() > 0) {
            $values = $collection->toArray();
            if (isset($values['items']) && is_array($values['items'])) {
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
     * Help command
     *
     * @return MageHack_MageConsole_Model_Abstract
     *
     */
    public function help() {
        $message = <<<USAGE
   
Usage: 
update index index_code
update index index_code1,index_code2
update index all
list index

update index index_code mode=real_time
update index index_code mode=manual
update index cu,cpf mode=real_time
update index catalog_url,catalog_product_flat mode=real_time

Note : Only the mode attribute can be updated;

Product Attributes => catalog_product_attribute
Product Prices => catalog_product_price
Catalog URL Rewrites => catalog_url
Product Flat Data => catalog_product_flat
    
Index Aliases are;
        'cpa' => 'catalog_product_attribute',
        'cpp' => 'catalog_product_price',
        'cu' => 'catalog_url',
        'cpf' => 'catalog_product_flat',
        'ccf' => 'catalog_category_flat',
        'ccp' => 'catalog_category_product',
        'csf' => 'catalogsearch_fulltext',
        'cis' => 'cataloginventory_stock',
        'ts' => 'tag_summary'
USAGE;
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $message;
    }

    /**
     * Parse string with indexers and return array of indexer instances
     *
     * @param string $string
     * @return array
     */
    protected function _parseIndexerString($string) {
        $processes = array();
        if ($string == 'all') {
            $collection = $this->_getModel()->getProcessesCollection();
            foreach ($collection as $process) {
                $processes[] = $process;
            }
        } else if (!empty($string)) {
            $codes = explode(',', $string);
            foreach ($codes as $_code) {
                $code = trim($_code);
                if (in_array($code, array_keys($this->_indexCodes)) || in_array($code, array_values($this->_indexCodes))) {
                    $process = $this->_getModel()->getProcessByCode($code);
                    if (!$process) {
                        $indexCodes = $this->_indexCodes;
                        $process = $this->_getModel()->getProcessByCode($indexCodes[$code]);
                    }
                    if ($process) {
                        $processes[] = $process;
                    }
                } else {
                    $processes .= 'Warning: Unknown indexer with code ' . trim($code) . "\n";
                }
            }
        }
        return $processes;
    }

    /**
     * Get all commands for tab completion
     *
     * @return array
     */
    public function allCommands()
    {
        $codes = array('cpa','catalog_product_attribute',
            'cpp', 'catalog_product_price',
            'cu', 'catalog_url',
            'cpf', 'catalog_product_flat',
            'ccf', 'catalog_category_flat',
            'ccp', 'catalog_category_product',
            'csf', 'catalogsearch_fulltext',
            'cis', 'cataloginventory_stock',
            'ts', 'tag_summary');
        $arr =  array(
            'update index all',
            'list index'
        );
        foreach ($codes as $code) {
            $arr[] = 'update index '.$code;
        }
        return $arr;
    }

}
