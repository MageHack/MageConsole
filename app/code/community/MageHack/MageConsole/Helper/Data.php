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

}
