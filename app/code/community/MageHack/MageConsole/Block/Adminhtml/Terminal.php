<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MageHack_MageConsole_Block_Adminhtml_Terminal extends Mage_Adminhtml_Block_Abstract
{
    /**
     * get Command submission URL
     *
     * @return string
     */

    public function getSubmitUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/mageconsole/submit');
    }

    /**
     * get Command saving URL
     *
     * @return string
     */

    public function getPromptUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/mageconsole/prompt');
    }


    /**
     * get Autocomplete URL
     *
     * @return string
     */
    public function getAutocompleteUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/mageconsole/autocomplete');
    }

    /**
     * Retrieve Session Form Key
     *
     * @return string
     */
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }

}
