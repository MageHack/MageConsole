<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Tax
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Configuration pathes storage
 *
 * @category   Mage
 * @package    Mage_Tax
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Tax_Model_Config
{
    // tax classes
    const CONFIG_XML_PATH_SHIPPING_TAX_CLASS = 'tax/classes/shipping_tax_class';

    // tax calculation
    const CONFIG_XML_PATH_PRICE_INCLUDES_TAX = 'tax/calculation/price_includes_tax';
    const CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX = 'tax/calculation/shipping_includes_tax';
    const CONFIG_XML_PATH_BASED_ON = 'tax/calculation/based_on';
    const CONFIG_XML_PATH_APPLY_ON = 'tax/calculation/apply_tax_on';
    const CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT = 'tax/calculation/apply_after_discount';
    const CONFIG_XML_PATH_DISCOUNT_TAX = 'tax/calculation/discount_tax';
    const XML_PATH_ALGORITHM = 'tax/calculation/algorithm';

    // tax defaults
    const CONFIG_XML_PATH_DEFAULT_COUNTRY = 'tax/defaults/country';
    const CONFIG_XML_PATH_DEFAULT_REGION = 'tax/defaults/region';
    const CONFIG_XML_PATH_DEFAULT_POSTCODE = 'tax/defaults/postcode';

    /**
     * Prices display settings
     */
    const CONFIG_XML_PATH_PRICE_DISPLAY_TYPE    = 'tax/display/type';
    const CONFIG_XML_PATH_DISPLAY_SHIPPING      = 'tax/display/shipping';

    /**
     * Shopping cart display settings
     */
    const XML_PATH_DISPLAY_CART_PRICE       = 'tax/cart_display/price';
    const XML_PATH_DISPLAY_CART_SUBTOTAL    = 'tax/cart_display/subtotal';
    const XML_PATH_DISPLAY_CART_SHIPPING    = 'tax/cart_display/shipping';
    const XML_PATH_DISPLAY_CART_DISCOUNT    = 'tax/cart_display/discount';
    const XML_PATH_DISPLAY_CART_GRANDTOTAL  = 'tax/cart_display/grandtotal';
    const XML_PATH_DISPLAY_CART_FULL_SUMMARY= 'tax/cart_display/full_summary';
    const XML_PATH_DISPLAY_CART_ZERO_TAX    = 'tax/cart_display/zero_tax';

    /**
     * Shopping cart display settings
     */
    const XML_PATH_DISPLAY_SALES_PRICE       = 'tax/sales_display/price';
    const XML_PATH_DISPLAY_SALES_SUBTOTAL    = 'tax/sales_display/subtotal';
    const XML_PATH_DISPLAY_SALES_SHIPPING    = 'tax/sales_display/shipping';
    const XML_PATH_DISPLAY_SALES_DISCOUNT    = 'tax/sales_display/discount';
    const XML_PATH_DISPLAY_SALES_GRANDTOTAL  = 'tax/sales_display/grandtotal';
    const XML_PATH_DISPLAY_SALES_FULL_SUMMARY= 'tax/sales_display/full_summary';
    const XML_PATH_DISPLAY_SALES_ZERO_TAX    = 'tax/sales_display/zero_tax';

    const CALCULATION_STRING_SEPARATOR = '|';

    const DISPLAY_TYPE_EXCLUDING_TAX = 1;
    const DISPLAY_TYPE_INCLUDING_TAX = 2;
    const DISPLAY_TYPE_BOTH = 3;

    /**
     * @deprecated
     */
    const CONFIG_XML_PATH_SHOW_IN_CATALOG = 'tax/display/show_in_catalog';
    const CONFIG_XML_PATH_DEFAULT_PRODUCT_TAX_GROUP = 'catalog/product/default_tax_group';
    const CONFIG_XML_PATH_DISPLAY_TAX_COLUMN    = 'tax/display/column_in_summary';
    const CONFIG_XML_PATH_DISPLAY_FULL_SUMMARY  = 'tax/display/full_summary';
    const CONFIG_XML_PATH_DISPLAY_ZERO_TAX      = 'tax/display/zero_tax';

    /**
     * Flag which notify what we need use prices exclude tax for calculations
     *
     * @var bool
     */
    protected $_needUsePriceExcludeTax = false;

    /**
     * Flag which notify what we need use shipping prices exclude tax for calculations
     *
     * @var bool
     */
    protected $_needUseShippingExcludeTax = false;

    /**
     * @var $_shippingPriceIncludeTax bool
     */
    protected $_shippingPriceIncludeTax = null;

    /**
     * Check if product prices inputed include tax
     *
     * @param   mix $store
     * @return  bool
     */
    public function priceIncludesTax($store=null)
    {
        if ($this->_needUsePriceExcludeTax) {
            return false;
        }
        return (bool)Mage::getStoreConfig(self::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, $store);
    }

    /**
     * Check what taxes should be applied after discount
     *
     * @param   mixed $store
     * @return  bool
     */
    public function applyTaxAfterDiscount($store=null)
    {
        return (bool)Mage::getStoreConfig(self::CONFIG_XML_PATH_APPLY_AFTER_DISCOUNT, $store);
    }

    /**
     * Get product price display type
     *  1 - Excluding tax
     *  2 - Including tax
     *  3 - Both
     *
     * @param   mixed $store
     * @return  int
     */
    public function getPriceDisplayType($store = null)
    {
        return (int)Mage::getStoreConfig(self::CONFIG_XML_PATH_PRICE_DISPLAY_TYPE, $store);
    }

    /**
     * Get configuration setting "Apply Discount On Prices Including Tax" value
     *
     * @param   null|int $store
     * @return  0|1
     */
    public function discountTax($store=null)
    {
        return ((int)Mage::getStoreConfig(self::CONFIG_XML_PATH_DISCOUNT_TAX, $store) == 1);
    }

    /**
     * Get taxes/discounts calculation sequence.
     * This sequence depends on "Apply Customer Tax" and "Apply Discount On Prices" configuration options.
     *
     * @param   null|int|string|Mage_Core_Model_Store $store
     * @return  string
     */
    public function getCalculationSequence($store=null)
    {
        if ($this->applyTaxAfterDiscount($store)) {
            if ($this->discountTax($store)) {
                $seq = Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL;
            } else {
                $seq = Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL;
            }
        } else {
            if ($this->discountTax($store)) {
                $seq = Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL;
            } else {
                $seq = Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL;
            }
        }
        return $seq;
    }

    /**
     * Specify flag what we need use price exclude tax
     *
     * @param   bool $flag
     * @return  Mage_Tax_Model_Config
     */
    public function setNeedUsePriceExcludeTax($flag)
    {
        $this->_needUsePriceExcludeTax = $flag;
        return $this;
    }

    /**
     * Get flag what we need use price exclude tax
     *
     * @return bool $flag
     */
    public function getNeedUsePriceExcludeTax()
    {
        return $this->_needUsePriceExcludeTax;
    }

    /**
     * Specify flag what we need use shipping price exclude tax
     *
     * @param   bool $flag
     * @return  Mage_Tax_Model_Config
     */
    public function setNeedUseShippingExcludeTax($flag)
    {
        $this->_needUseShippingExcludeTax = $flag;
        return $this;
    }

    /**
     * Get flag what we need use shipping price exclude tax
     *
     * @return bool $flag
     */
    public function getNeedUseShippingExcludeTax()
    {
        return $this->_needUseShippingExcludeTax;
    }


    /**
     * Get defined tax calculation agorithm
     *
     * @param   store $store
     * @return  string
     */
    public function getAlgorithm($store=null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ALGORITHM, $store);
    }

    /**
     * Get tax class id specified for shipping tax estimation
     *
     * @param   store $store
     * @return  int
     */
    public function getShippingTaxClass($store=null)
    {
        return (int)Mage::getStoreConfig(self::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $store);
    }

    /**
     * Get shipping methods prices display type
     *
     * @param   store $store
     * @return  int
     */
    public function getShippingPriceDisplayType($store = null)
    {
        return (int)Mage::getStoreConfig(self::CONFIG_XML_PATH_DISPLAY_SHIPPING, $store);
    }

    /**
     * Check if shiping prices include tax
     *
     * @param   store $store
     * @return  bool
     */
    public function shippingPriceIncludesTax($store = null)
    {
        if ($this->_shippingPriceIncludeTax === null) {
            $this->_shippingPriceIncludeTax = (bool)Mage::getStoreConfig(
                self::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX,
                $store
            );
        }
        return $this->_shippingPriceIncludeTax;
    }

    /**
     * Declare shipping prices type
     * @param bool $flag
     */
    public function setShippingPriceIncludeTax($flag)
    {
        $this->_shippingPriceIncludeTax = $flag;
        return $this;
    }


    /**
     * Check if need display full tax summary information in totals block
     *
     * @deprecated please use displayCartFullSummary or displaySalesFullSummary
     * @param   mixed $store
     * @return  bool
     */
    public function displayFullSummary($store = null)
    {
        return $this->displayCartFullSummary($store);
    }

    /**
     * Check if need display zero tax in subtotal
     *
     * @deprecated please use displayCartZeroTax or displaySalesZeroTax
     * @param   mixed $store
     * @return  bool
     */
    public function displayZeroTax($store = null)
    {
        return $this->displayCartZeroTax($store);
    }

    /**
     * Get shopping cart prices display type
     *
     * @deprecated please use displayCartPrice or displaySalesZeroTax
     * @param   mixed $store
     * @return  bool
     */
    public function displayTaxColumn($store = null)
    {
        return (bool)Mage::getStoreConfig(self::CONFIG_XML_PATH_DISPLAY_TAX_COLUMN, $store);
    }




    public function displayCartPricesInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_PRICE, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displayCartPricesExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_PRICE, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displayCartPricesBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_PRICE, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displayCartSubtotalInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_SUBTOTAL, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displayCartSubtotalExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_SUBTOTAL, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displayCartSubtotalBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_SUBTOTAL, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displayCartShippingInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_SHIPPING, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displayCartShippingExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_SHIPPING, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displayCartShippingBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_SHIPPING, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displayCartDiscountInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_DISCOUNT, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displayCartDiscountExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_DISCOUNT, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displayCartDiscountBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_DISCOUNT, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displayCartTaxWithGrandTotal($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_GRANDTOTAL, $store);
    }

    public function displayCartFullSummary($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_FULL_SUMMARY, $store);
    }

    public function displayCartZeroTax($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_DISPLAY_CART_ZERO_TAX, $store);
    }


    public function displaySalesPricesInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_PRICE, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displaySalesPricesExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_PRICE, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displaySalesPricesBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_PRICE, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displaySalesSubtotalInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_SUBTOTAL, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displaySalesSubtotalExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_SUBTOTAL, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displaySalesSubtotalBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_SUBTOTAL, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displaySalesShippingInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_SHIPPING, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displaySalesShippingExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_SHIPPING, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displaySalesShippingBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_SHIPPING, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displaySalesDiscountInclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_DISCOUNT, $store) == self::DISPLAY_TYPE_INCLUDING_TAX;
    }

    public function displaySalestDiscountExclTax($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_DISCOUNT, $store) == self::DISPLAY_TYPE_EXCLUDING_TAX;
    }

    public function displaySalesDiscountBoth($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_DISCOUNT, $store) == self::DISPLAY_TYPE_BOTH;
    }

    public function displaySalesTaxWithGrandTotal($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_GRANDTOTAL, $store);
    }

    public function displaySalesFullSummary($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_FULL_SUMMARY, $store);
    }

    public function displaySalesZeroTax($store = null)
    {
        return (bool)Mage::getStoreConfig(self::XML_PATH_DISPLAY_SALES_ZERO_TAX, $store);
    }
}

