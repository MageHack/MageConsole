<?php

/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Retrieve status
     *
     * @return  boolean
     */
    public function isEnabled()
    {
        $enabled = (Mage::getStoreConfig('admin/mageconsole/enable') == 1) ? true : false;
        return $enabled;
    }

    public function createTable(array $data, $showHeader = true, $columnWidth = array('columnWidths' => array(10, 20)))
    {
        $tableConfig = array(
            'columnWidths' =>  $columnWidth['columnWidths'],
            'AutoSeparate' => Zend_Text_Table::AUTO_SEPARATE_ALL,
            'padding'      => 1
        );

        $table = new Zend_Text_Table($tableConfig);

        if ($showHeader) {
            $table->appendRow($this->getHeader($data));
        }
        foreach ($data as $row) {
            $row = $this->cleanArray($row);
            $table->appendRow($row);
        }

        return (string) $table;
    }

    protected function cleanArray($row = array())
    {
        $data = array();
        foreach ($row as $val) {
            $data[] = (string) $val;
        }
        return $data;
    }

    public function getHeader($data)
    {
        $header = array();
        foreach ($data as $row) {
            foreach ($row as $key => $value) {
                if (!in_array($key, $header))
                    $header[] = $key;
            }
        }
        return $header;
    }

    public function getCsv(Varien_Data_Collection $collection,$getAll=false)
    {
        $csv = '';

        $collectionData = $collection->toArray();

        $type = $collection->getEntity()->getType();

        // first pass, collect header
        $_columns = array();
        $_ignoredColumns = array(); // speedup
        foreach ($collectionData as $item) {
            foreach ($item as $key => $value) {
                if (gettype($value) == 'Array') $_ignoredColumns[] = $key;
                if ($getAll) {
                    $_columns[] = $key;
                } else
                if (!in_array($key,$_columns) && !in_array($key,$_ignoredColumns)) {
                    $attr = Mage::getModel('eav/entity_attribute')->loadByCode($type , $key);
                    // get rid of non-attributes
                    if (!count($attr->getData())) {
                        $_ignoredColumns[] = $key;
                    } else {
                        $_columns[] = $key;
                    }
                }
            }
        }

        $data = array();
        // add headers as first row
        foreach ($_columns as $column) {
            $data[] = "\"$column\"";
        }
        $csv.= implode(',', $data)."\n";

        // second pass, concatenate data content
        foreach ($collectionData as $item) {
            $data = array();
            foreach ($_columns as $column) {
                $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'),$item[$column]) . '"';
            }
            $csv.= implode(',', $data)."\n";
        }

        return $csv;
    }

}
