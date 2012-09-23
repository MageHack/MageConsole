<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Cache
    extends MageHack_MageConsole_Model_Abstract
{

    protected $_attrToShow = array(
        'id'            => 'id',
        'cache_type'    => 'cache_type',
        'description'   => 'description',
        'tags'          => 'tags',
        'status'        => 'status'
    );

    protected $_columnWidths = array(
        'columnWidths' => array(20, 30, 30, 50, 20)
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Core_Model_Cache
     */
    protected function _getModel()
    {
        return Mage::getModel('core/cache');
    }


    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing()
    {
        $collection = new Varien_Data_Collection();
        foreach (Mage::app()->getCacheInstance()->getTypes() as $type) {
            $collection->addItem($type);
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

    public function clear()
    {
        $cacheType = $this->getRequest(2);
        if ($cacheType) {
            switch ($cacheType) {
                case 'all':
                    foreach (Mage::app()->getCacheInstance()->getTypes() as $type) {
                        $tags = Mage::app()->getCacheInstance()->cleanType($type->getId());
                        Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $type->getId()));
                    }
                    $message = 'All cache type(s) refreshed.';
                    break;
                case 'storage':
                    Mage::dispatchEvent('adminhtml_cache_flush_all');
                    Mage::app()->getCacheInstance()->flush();
                    $message = 'The cache storage has been flushed.';
                    break;
                case 'magento':
                    Mage::app()->cleanCache();
                    Mage::dispatchEvent('adminhtml_cache_flush_system');
                    $message = 'The Magento cache storage has been flushed.';
                    break;
                case 'images':
                    try {
                        Mage::getModel('catalog/product_image')->clearCache();
                        Mage::dispatchEvent('clean_catalog_images_cache_after');
                        $message = 'The image cache was cleaned.';
                    }
                    catch (Mage_Core_Exception $e) {
                        $this->_getSession()->addError($e->getMessage());
                        $message = $e->getMessage();
                    }
                    catch (Exception $e) {
                        $message = $e->getMessage();
                    }
                    break;
                default:
                    Mage::app()->getCacheInstance()->cleanType($cacheType);
                    Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $cacheType));
                    $message = sprintf('The %s cache was refreshed.', $cacheType);
                    break;
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
    public function help()
    {
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage('help was requested for a product - this is the help message');
        return $this;
    }
}
