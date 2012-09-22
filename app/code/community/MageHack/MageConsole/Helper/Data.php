<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Helper_Data extends Mage_Core_Helper_Abstract {

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
}
