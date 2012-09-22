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
 * @package     Mage_GoogleCheckout
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Checkout XML API processing model
 *
 * @category    Mage
 * @package     Mage_GoogleCheckout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_GoogleCheckout_Model_Api_Xml_Checkout extends Mage_GoogleCheckout_Model_Api_Xml_Abstract
{
    /**
     * Representation value of item weight unit
     */
    const ITEM_WEIGHT_UNIT = 'LB';

    /**
     * Representation value of item size unit
     */
    const ITEM_SIZE_UNIT = 'IN';

    /**
     * Google checkout namespace URI
     */
    const CHECKOUT_SHOPPING_CART_XMLNS = 'http://checkout.google.com/schema/2';

    /**
     * @deprecated after 0.8.16100
     *
     * @var string
     */
    protected $_currency;

    /**
     * Define if shipping rates already calculated
     *
     * @var boolean
     */
    protected $_shippingCalculated = false;

    /**
     * API URL getter
     *
     * @return string
     */
    protected function _getApiUrl()
    {
        $url = $this->_getBaseApiUrl();
        $url .= 'merchantCheckout/Merchant/' . $this->getMerchantId();
        return $url;
    }

    /**
     * Send checkout data to google
     *
     * @return Mage_GoogleCheckout_Model_Api_Xml_Checkout
     */
    public function checkout()
    {
        $quote = $this->getQuote();
        if (!($quote instanceof Mage_Sales_Model_Quote)) {
            Mage::throwException('Invalid quote');
        }

        $xmlns = self::CHECKOUT_SHOPPING_CART_XMLNS;
        $xml = <<<EOT
<checkout-shopping-cart xmlns="{$xmlns}">
    <shopping-cart>
{$this->_getItemsXml()}
{$this->_getMerchantPrivateDataXml()}
{$this->_getCartExpirationXml()}
    </shopping-cart>
    <checkout-flow-support>
{$this->_getMerchantCheckoutFlowSupportXml()}
    </checkout-flow-support>
    <order-processing-support>
{$this->_getRequestInitialAuthDetailsXml()}
    </order-processing-support>
</checkout-shopping-cart>
EOT;

        $result = $this->_call($xml);
        $this->setRedirectUrl($result->{'redirect-url'});

        return $this;
    }

    /**
     * Retrieve quote items in XML format
     *
     * @return string
     */
    protected function _getItemsXml()
    {
        $xml = <<<EOT
        <items>

EOT;

        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $taxClass   = ($item->getTaxClassId() == 0) ? 'none' : $item->getTaxClassId();
            $weight     = (float) $item->getWeight();
            $weightUnit = self::ITEM_WEIGHT_UNIT;

            $unitPrice = $item->getBaseCalculationPrice();
            if (Mage::helper('weee')->includeInSubtotal()) {
                $unitPrice += $item->getBaseWeeeTaxAppliedAmount();
            }

            $xml .= <<<EOT
            <item>
                <merchant-item-id><![CDATA[{$item->getSku()}]]></merchant-item-id>
                <item-name><![CDATA[{$item->getName()}]]></item-name>
                <item-description><![CDATA[{$item->getDescription()}]]></item-description>
                <unit-price currency="{$this->getCurrency()}">{$unitPrice}</unit-price>
                <quantity>{$item->getQty()}</quantity>
                <item-weight unit="{$weightUnit}" value="{$weight}" />
                <tax-table-selector>{$taxClass}</tax-table-selector>
                {$this->_getDigitalContentXml($item->getIsVirtual())}
                {$this->_getMerchantPrivateItemDataXml($item)}
            </item>

EOT;
        }

        $billingAddress = $this->getQuote()->getBillingAddress();
        $shippingAddress = $this->getQuote()->getShippingAddress();

        $shippingDiscount = (float)$shippingAddress->getBaseDiscountAmount();
        $billingDiscount = (float)$billingAddress->getBaseDiscountAmount();
        $discount = $billingDiscount + $shippingDiscount;

        // Exclude shipping discount
        // Discount is negative value
        $discount += $shippingAddress->getBaseShippingDiscountAmount();

        $discountItem = new Varien_Object(array(
            'price' => $discount,
            'name'  => $this->__('Cart Discount'),
            'description' => $this->__('A virtual item to reflect the discount total')
        ));

        Mage::dispatchEvent('google_checkout_discount_item_price', array(
            'quote'         => $this->getQuote(),
            'discount_item' => $discountItem
        ));

        $discount = $discountItem->getPrice();
        if ($discount) {
            $xml .= <<<EOT
            <item>
                <merchant-item-id>_INTERNAL_DISCOUNT_</merchant-item-id>
                <item-name>{$discountItem->getName()}</item-name>
                <item-description>{$discountItem->getDescription()}</item-description>
                <unit-price currency="{$this->getCurrency()}">{$discount}</unit-price>
                <quantity>1</quantity>
                <item-weight unit="{$weightUnit}" value="0.00" />
                <tax-table-selector>none</tax-table-selector>
                {$this->_getDigitalContentXml($this->getQuote()->isVirtual())}
            </item>

EOT;
        }

        $hiddenTax = $shippingAddress->getBaseHiddenTaxAmount() + $billingAddress->getBaseHiddenTaxAmount();
        if ($hiddenTax) {
            $xml .= <<<EOT
            <item>
                <merchant-item-id>_INTERNAL_TAX_</merchant-item-id>
                <item-name>{$this->__('Discount Tax')}</item-name>
                <item-description>{$this->__('A virtual item to reflect the tax total')}</item-description>
                <unit-price currency="{$this->getCurrency()}">{$hiddenTax}</unit-price>
                <quantity>1</quantity>
                <item-weight unit="{$weightUnit}" value="0.00" />
                <tax-table-selector>none</tax-table-selector>
                {$this->_getDigitalContentXml($this->getQuote()->isVirtual())}
            </item>
EOT;
        }
        $xml .= <<<EOT
        </items>
EOT;

        return $xml;
    }

    /**
     * Retrieve digital content XML
     *
     * @param boolean $isVirtual
     * @return string
     */
    protected function _getDigitalContentXml($isVirtual)
    {
        if (!$isVirtual) {
            return '';
        }

        $storeId = $this->getQuote()->getStoreId();
        $active = Mage::getStoreConfigFlag(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_VIRTUAL_ACTIVE, $storeId);
        if (!$active) {
            return '';
        }

        $schedule = Mage::getStoreConfig(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_VIRTUAL_SCHEDULE, $storeId);
        $method   = Mage::getStoreConfig(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_VIRTUAL_METHOD, $storeId);

        $xml = "<display-disposition>{$schedule}</display-disposition>";

        if ($method == 'email') {
            $xml .= '<email-delivery>true</email-delivery>';
        } elseif ($method == 'key_url') {
        } elseif ($method == 'description_based') {
        }

        $xml = "<digital-content>{$xml}</digital-content>";

        return $xml;
    }

    /**
     * Convert quote item to private item XML
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    protected function _getMerchantPrivateItemDataXml($item)
    {
        $xml = <<<EOT
            <merchant-private-item-data>
                <quote-item-id>{$item->getId()}</quote-item-id>
            </merchant-private-item-data>
EOT;
        return $xml;
    }

    /**
     * Retrieve merchant private data XML
     *
     * @return string
     */
    protected function _getMerchantPrivateDataXml()
    {
        $xml = <<<EOT
            <merchant-private-data>
                <quote-id><![CDATA[{$this->getQuote()->getId()}]]></quote-id>
                <store-id><![CDATA[{$this->getQuote()->getStoreId()}]]></store-id>
            </merchant-private-data>
EOT;
        return $xml;
    }

    /**
     * Retrieve quote expiration XML
     *
     * @return string
     */
    protected function _getCartExpirationXml()
    {
        $xml = <<<EOT
EOT;
        return $xml;
    }

    /**
     * Retrieve merchant checkout flow support XML
     *
     * @return string
     */
    protected function _getMerchantCheckoutFlowSupportXml()
    {
        $xml = <<<EOT
        <merchant-checkout-flow-support>
            <edit-cart-url><![CDATA[{$this->_getEditCartUrl()}]]></edit-cart-url>
            <continue-shopping-url><![CDATA[{$this->_getContinueShoppingUrl()}]]></continue-shopping-url>
            {$this->_getRequestBuyerPhoneNumberXml()}
            {$this->_getMerchantCalculationsXml()}
            {$this->_getShippingMethodsXml()}
            {$this->_getAllTaxTablesXml()}
            {$this->_getParameterizedUrlsXml()}
            {$this->_getPlatformIdXml()}
            {$this->_getAnalyticsDataXml()}
        </merchant-checkout-flow-support>
EOT;
        return $xml;
    }

    /**
     * Retrieve request buyer phone number XML
     *
     * @return string
     */
    protected function _getRequestBuyerPhoneNumberXml()
    {
        $requestPhone = Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_REQUEST_PHONE,
            $this->getQuote()->getStoreId()
        );
        $requestPhone = $requestPhone ? 'true' : 'false';
        $xml = <<<EOT
            <request-buyer-phone-number>{$requestPhone}</request-buyer-phone-number>
EOT;
        return $xml;
    }

    /**
     * Retrieve merchant calculations XML
     *
     * @return string
     */
    protected function _getMerchantCalculationsXml()
    {
        $xml = <<<EOT
            <merchant-calculations>
                <merchant-calculations-url><![CDATA[{$this->_getCalculationsUrl()}]]></merchant-calculations-url>
            </merchant-calculations>
EOT;
        return $xml;
    }

    /**
     * Retrieve free shipping rate XML
     *
     * @return string
     */
    protected function _getVirtualOrderShippingXml()
    {
        $title = Mage::helper('googlecheckout')->__('Free Shipping');

        $xml = <<<EOT
            <shipping-methods>
                <flat-rate-shipping name="{$title}">
                    <shipping-restrictions><allowed-areas><world-area /></allowed-areas></shipping-restrictions>
                    <price currency="{$this->getCurrency()}">0</price>
                </flat-rate-shipping>
            </shipping-methods>
EOT;
        return $xml;
    }

    /**
     * Retrieve shipping methods XML
     *
     * @return string
     */
    protected function _getShippingMethodsXml()
    {
        if ($this->_isOrderVirtual()) {
            return $this->_getVirtualOrderShippingXml();
        }

        $xml = <<<EOT
            <shipping-methods>
                {$this->_getCarrierCalculatedShippingXml()}
                {$this->_getFlatRateShippingXml()}
                {$this->_getMerchantCalculatedShippingXml()}
                {$this->_getPickupXml()}
            </shipping-methods>
EOT;
        return $xml;
    }

    /**
     * Generate XML of calculated shipping carriers rates
     *
     * @return string
     */
    protected function _getCarrierCalculatedShippingXml()
    {
        /*
         * Prevent sending more then one shipping option to Google
         */
        if ($this->_shippingCalculated) {
            return '';
        }

        $storeId = $this->getQuote()->getStoreId();
        $active = Mage::getStoreConfigFlag(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_ACTIVE, $storeId);
        $methods = Mage::getStoreConfig(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_METHODS, $storeId);

        if (!$active || !$methods) {
            return '';
        }

        $country  = Mage::getStoreConfig(Mage_Shipping_Model_Config::XML_PATH_ORIGIN_COUNTRY_ID, $storeId);
        $region   = Mage::getStoreConfig(Mage_Shipping_Model_Config::XML_PATH_ORIGIN_REGION_ID, $storeId);
        $postcode = Mage::getStoreConfig(Mage_Shipping_Model_Config::XML_PATH_ORIGIN_POSTCODE, $storeId);
        $city     = Mage::getStoreConfig(Mage_Shipping_Model_Config::XML_PATH_ORIGIN_CITY, $storeId);

        $defPrice = (float)Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_DEFAULT_PRICE,
            $storeId
        );
        $width = Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_DEFAULT_WIDTH,
            $storeId
        );
        $height = Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_DEFAULT_HEIGHT,
            $storeId
        );
        $length = Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_DEFAULT_LENGTH,
            $storeId
        );
        $sizeUnit = self::ITEM_SIZE_UNIT;

        $addressCategory = Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_CARRIER_ADDRESS_CATEGORY,
            $storeId
        );
        $defPrice = (float) Mage::helper('tax')->getShippingPrice($defPrice, false, false);

        $this->getQuote()->getShippingAddress()
            ->setCountryId($country)
            ->setCity($city)
            ->setPostcode($postcode)
            ->setRegionId($region)
            ->setCollectShippingRates(true);

        $address = $this->getQuote()->getShippingAddress();
        $address->collectShippingRates();
        $shipments = $address->getGroupedAllShippingRates();

        $shippingMethodsList = array();
        foreach (explode(',', $methods) as $method) {
            list($company, $type) = explode('/', $method);
            $shippingMethodsList[$method] = array('company' => $company, 'type' => $type);
        }

        $freeMethodsList = array();
        foreach ($this->_getGoogleCarriersMap() as $mageCode => $map) {
            if (!isset($shipments[$mageCode])) {
                continue;
            }
            $freeMethod = Mage::getStoreConfig('carriers/' . $mageCode . '/free_method', $storeId);

            foreach ($shipments[$mageCode] as $rate) {
                $mageRateCode = $rate->getMethod();
                if ($mageRateCode != $freeMethod) {
                    continue;
                }

                $googleRateCode = isset($map['methods'][$mageRateCode]) ? $map['methods'][$mageRateCode] : false;
                if (false == $googleRateCode || $rate->getPrice() != 0) {
                    continue;
                }

                $methodName = $map['googleCarrierCompany'] . '/'.  $googleRateCode;
                if (empty($shippingMethodsList[$methodName])) {
                    continue;
                }
                $freeMethodsList[$methodName] = array(
                    'company' => $map['googleCarrierCompany'],
                    'type' => $googleRateCode
                );
                unset($shippingMethodsList[$methodName]);
            }
        }

        $xml = '';
        $sendShipMethods = (bool)count($shippingMethodsList) > 0;
        if ($sendShipMethods) {
            $xml .= <<<EOT
                <carrier-calculated-shipping>
                    <shipping-packages>
                        <shipping-package>
                            <ship-from id="Origin">
                                <city>{$city}</city>
                                <region>{$region}</region>
                                <postal-code>{$postcode}</postal-code>
                                <country-code>{$country}</country-code>
                            </ship-from>
                            <width unit="{$sizeUnit}" value="{$width}"/>
                            <height unit="{$sizeUnit}" value="{$height}"/>
                            <length unit="{$sizeUnit}" value="{$length}"/>
                            <delivery-address-category>{$addressCategory}</delivery-address-category>
                        </shipping-package>
                    </shipping-packages>
EOT;
            $xml .= '<carrier-calculated-shipping-options>';

            foreach ($shippingMethodsList as $method) {
                $xml .= <<<EOT
                        <carrier-calculated-shipping-option>
                            <shipping-company>{$method['company']}</shipping-company>
                            <shipping-type>{$method['type']}</shipping-type>
                            <price currency="{$this->getCurrency()}">{$defPrice}</price>
                        </carrier-calculated-shipping-option>
EOT;
            }
            $xml .= '</carrier-calculated-shipping-options>';
            $xml .= '</carrier-calculated-shipping>';
        }

        foreach ($freeMethodsList as $method) {
            $xml .= <<<EOT
                    <flat-rate-shipping name="{$method['company']} {$method['type']}">
                    <price currency="{$this->getCurrency()}">0.00</price></flat-rate-shipping>
EOT;
        }

        $this->_shippingCalculated = true;
        return $xml;
    }

    /**
     * Generate flat rate shipping XML
     *
     * @return string
     */
    protected function _getFlatRateShippingXml()
    {
        /*
         * Prevent sending more then one shipping option to Google
         */
        if ($this->_shippingCalculated) {
            return '';
        }

        $storeId = $this->getQuote()->getStoreId();
        if (!Mage::getStoreConfigFlag(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_FLATRATE_ACTIVE, $storeId)) {
            return '';
        }

        // If is set Tax Class for Shipping - create ability to manage shipping rates in MerchantCalculationCallback
        $nodeName = 'merchant-calculated-shipping';
        if (!$this->_getTaxClassForShipping($this->getQuote())) {
            $nodeName = 'flat-rate-shipping';
        }

        $xml = '';
        for ($i = 1; $i <= 3; $i++) {
            $title         = Mage::getStoreConfig('google/checkout_shipping_flatrate/title_' . $i, $storeId);
            $price         = (float)Mage::getStoreConfig('google/checkout_shipping_flatrate/price_' . $i, $storeId);
            $price         = number_format($price, 2, '.', '');
            $price         = (float)Mage::helper('tax')->getShippingPrice($price, false, false);
            $allowSpecific = Mage::getStoreConfigFlag(
                'google/checkout_shipping_flatrate/sallowspecific_' . $i,
                $storeId
            );
            $specificCountries = Mage::getStoreConfig(
                'google/checkout_shipping_flatrate/specificcountry_' . $i,
                $storeId
            );
            $allowedAreasXml = $this->_getAllowedCountries($allowSpecific, $specificCountries);

            if (empty($title) || $price <= 0) {
                continue;
            }

            $xml .= <<<EOT
                <{$nodeName} name="{$title}">
                    <shipping-restrictions>
                        <allowed-areas>
                        {$allowedAreasXml}
                        </allowed-areas>
                    </shipping-restrictions>
                    <price currency="{$this->getCurrency()}">{$price}</price>
                </{$nodeName}>
EOT;
        }

        $this->_shippingCalculated = true;

        return $xml;
    }

    /**
     * Generate shipping allowed countries XML
     *
     * @param boolean $allowSpecific
     * @param string $specific
     * @return string
     */
    protected function _getAllowedCountries($allowSpecific, $specific)
    {
        $xml = '';
        if ($allowSpecific == 1) {
            if ($specific) {
                foreach (explode(',', $specific) as $country) {
                    $xml .= "<postal-area><country-code>{$country}</country-code></postal-area>\n";
                }
            }
        }
        if ($xml) {
            return $xml;
        }

        return '<world-area />';
    }

    /**
     * Retrieve merchant calculated shipping carriers rates XML
     *
     * @return string
     */
    protected function _getMerchantCalculatedShippingXml()
    {
        /*
         * Prevent sending more then one shipping option to Google
         */
        if ($this->_shippingCalculated) {
            return '';
        }

        $storeId = $this->getQuote()->getStoreId();
        $active = Mage::getStoreConfigFlag(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_MERCHANT_ACTIVE,
            $storeId
        );
        $methods = Mage::getStoreConfig(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_MERCHANT_ALLOWED_METHODS,
            $storeId
        );

        if (!$active || !$methods) {
            return '';
        }

        $xml           = '';
        $methods       = unserialize($methods);
        $taxHelper     = Mage::helper('tax');
        $shippingModel = Mage::getModel('shipping/shipping');

        foreach ($methods['method'] as $i => $method) {
            if (!$i || !$method) {
                continue;
            }
            list($carrierCode, $methodCode) = explode('/', $method);
            if ($carrierCode) {
                $carrier = $shippingModel->getCarrierByCode($carrierCode);
                if ($carrier) {
                    $allowedMethods = $carrier->getAllowedMethods();

                    if (isset($allowedMethods[$methodCode])) {
                        $method = Mage::getStoreConfig('carriers/' . $carrierCode . '/title', $storeId);
                        $method .= ' - '.$allowedMethods[$methodCode];
                    }

                    $defaultPrice = (float) $methods['price'][$i];
                    $defaultPrice = $taxHelper->getShippingPrice($defaultPrice, false, false);

                    $allowedAreasXml = $this->_getAllowedCountries(
                        $carrier->getConfigData('sallowspecific'),
                        $carrier->getConfigData('specificcountry')
                    );

                    $xml .= <<<EOT
                        <merchant-calculated-shipping name="{$method}">
                            <address-filters>
                                <allowed-areas>
                                    {$allowedAreasXml}
                                </allowed-areas>
                            </address-filters>
                            <price currency="{$this->getCurrency()}">{$defaultPrice}</price>
                        </merchant-calculated-shipping>
EOT;
                }
            }
        }
        $this->_shippingCalculated = true;

        return $xml;
    }

    /**
     * Retrieve pickup XML
     *
     * @return string
     */
    protected function _getPickupXml()
    {
        $storeId = $this->getQuote()->getStoreId();
        if (!Mage::getStoreConfig(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_PICKUP_ACTIVE, $storeId)) {
            return '';
        }

        $title = Mage::getStoreConfig(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_PICKUP_TITLE, $storeId);
        $price = Mage::getStoreConfig(Mage_GoogleCheckout_Helper_Data::XML_PATH_SHIPPING_PICKUP_PRICE, $storeId);
        $price = (float) Mage::helper('tax')->getShippingPrice($price, false, false);

        $xml = <<<EOT
                <pickup name="{$title}">
                    <price currency="{$this->getCurrency()}">{$price}</price>
                </pickup>
EOT;

        return $xml;
    }

    /**
     * Retrieve specific tax table XML
     *
     * @param array|float $rules
     * @param string $type
     * @return string
     */
    protected function _getTaxTableXml($rules, $type)
    {
        $xml = '';
        if (is_array($rules)) {
            foreach ($rules as $group => $taxRates) {
                if ($type != 'default') {
                    $nameAttribute       = "name=\"{$group}\"";
                    $standaloneAttribute = "standalone=\"true\"";
                    $rulesTag            = "{$type}-tax-rules";
                    $shippingTaxed       = false;
                } else {
                    $nameAttribute       = '';
                    $standaloneAttribute = '';
                    $rulesTag            = 'tax-rules';
                    $shippingTaxed       = true;
                }

                $xml .= <<<EOT
                        <{$type}-tax-table {$nameAttribute} {$standaloneAttribute}>
                            <{$rulesTag}>
EOT;
                if (is_array($taxRates)) {
                    foreach ($taxRates as $rate) {
                        $xml .= <<<EOT
                                    <{$type}-tax-rule>
                                        <tax-areas>

EOT;
                        if ($rate['country'] === Mage_Usa_Model_Shipping_Carrier_Abstract::USA_COUNTRY_ID) {
                            if (!empty($rate['postcode']) && $rate['postcode'] !== '*') {
                                $rate['postcode'] = Mage::helper('googlecheckout')
                                    ->zipRangeToZipPattern($rate['postcode']);
                                foreach ($rate['postcode'] as $postcode) {
                                    $xml .= <<<EOT
                                                <us-zip-area>
                                                    <zip-pattern>$postcode</zip-pattern>
                                                </us-zip-area>

EOT;
                                }
                            } else if (!empty($rate['state'])) {
                                $xml .= <<<EOT
                                            <us-state-area>
                                                <state>{$rate['state']}</state>
                                            </us-state-area>

EOT;
                            } else {
                                $xml .= <<<EOT
                                            <us-zip-area>
                                                <zip-pattern>*</zip-pattern>
                                            </us-zip-area>

EOT;
                            }
                        } else {
                            if (!empty($rate['country'])) {
                                $xml .= <<<EOT
                                            <postal-area>
                                                <country-code>{$rate['country']}</country-code>
EOT;
                                if (!empty($rate['postcode']) && $rate['postcode'] !== '*') {
                                    $xml .= <<<EOT
                                                <postal-code-pattern>{$rate['postcode']}</postal-code-pattern>

EOT;
                                }
                                $xml .= <<<EOT
                                            </postal-area>

EOT;
                            }
                        }
                        $xml .= <<<EOT
                                        </tax-areas>
                                        <rate>{$rate['value']}</rate>
EOT;
                        if ($shippingTaxed) {
                            $xml .= '<shipping-taxed>true</shipping-taxed>';
                        }
                        $xml .= "</{$type}-tax-rule>";
                    }

                } else {
                    $taxRate = $taxRates/100;
                    $xml .= <<<EOT
                                <{$type}-tax-rule>
                                    <tax-area>
                                        <world-area/>
                                    </tax-area>
                                    <rate>{$taxRate}</rate>
EOT;
                    if ($shippingTaxed) {
                        $xml .= '<shipping-taxed>true</shipping-taxed>';
                    }
                    $xml .= "</{$type}-tax-rule>";
                }

                $xml .= <<<EOT
                            </$rulesTag>
                        </{$type}-tax-table>
EOT;
            }
        } else {
            if (is_numeric($rules)) {
                $taxRate = $rules / 100;
                $xml .= <<<EOT
                        <{$type}-tax-table>
                            <tax-rules>
                                <{$type}-tax-rule>
                                    <tax-area>
                                        <world-area/>
                                    </tax-area>
                                    <rate>{$taxRate}</rate>
                                    <shipping-taxed>true</shipping-taxed>
                                </{$type}-tax-rule>
                            </tax-rules>
                        </{$type}-tax-table>
EOT;
            }
        }

        return $xml;
    }

    /**
     * Generate all tax tables XML
     *
     * @return string
     */
    protected function _getAllTaxTablesXml()
    {
        $isDefaultTaxTablesDisabled = Mage::getStoreConfigFlag(
            Mage_GoogleCheckout_Helper_Data::XML_PATH_DISABLE_DEFAULT_TAX_TABLES,
            $this->getQuote()->getStoreId()
        );
        if ($isDefaultTaxTablesDisabled) {
            return '<tax-tables merchant-calculated="true" />';
        }

        $xml = <<<EOT
            <tax-tables merchant-calculated="true">
                {$this->_getTaxTableXml($this->_getShippingTaxRules(), 'default')}

                <!-- default-tax-table>
                    <tax-rules>
                        <default-tax-rule>
                        </default-tax-rule>
                    </tax-rules>
                </default-tax-table -->

                <alternate-tax-tables>
                    <alternate-tax-table name="none" standalone="true">
                        <alternate-tax-rules>
                            <alternate-tax-rule>
                                <tax-area>
                                    <world-area/>
                                </tax-area>
                                <rate>0</rate>
                            </alternate-tax-rule>
                        </alternate-tax-rules>
                    </alternate-tax-table>
                    {$this->_getTaxTableXml($this->_getTaxRules(), 'alternate')}
                </alternate-tax-tables>
            </tax-tables>
EOT;
        return $xml;
    }

    /**
     * Retrieve customer tax class id
     *
     * @return int
     */
    protected function _getCustomerTaxClass()
    {
        $customerGroup = $this->getQuote()->getCustomerGroupId();
        if (!$customerGroup) {
            $customerGroup = Mage::helper('customer')->getDefaultCustomerGroupId($this->getQuote()->getStoreId());
        }
        return Mage::getModel('customer/group')->load($customerGroup)->getTaxClassId();
    }

    /**
     * Retrieve shipping tax rules
     *
     * @return array
     */
    protected function _getShippingTaxRules()
    {
        $customerTaxClass = $this->_getCustomerTaxClass();
        $shippingTaxClass = Mage::getStoreConfig(
            Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            $this->getQuote()->getStoreId()
        );
        $taxCalculationModel = Mage::getSingleton('tax/calculation');

        if ($shippingTaxClass) {
            if (Mage::helper('tax')->getTaxBasedOn() == 'origin') {
                $request = $taxCalculationModel->getRateRequest();
                $request
                    ->setCustomerClassId($customerTaxClass)
                    ->setProductClassId($shippingTaxClass);

                return $taxCalculationModel->getRate($request);
            }
            $customerRules = $taxCalculationModel->getRatesByCustomerAndProductTaxClasses(
                $customerTaxClass,
                $shippingTaxClass
            );
            $rules = array();
            foreach ($customerRules as $rule) {
                $rules[$rule['product_class']][] = $rule;
            }

            return $rules;
        }

        return array();
    }

    /**
     * Retrieve tax rules
     *
     * @return array
     */
    protected function _getTaxRules()
    {
        $customerTaxClass    = $this->_getCustomerTaxClass();
        $taxCalculationModel = Mage::getSingleton('tax/calculation');

        if (Mage::helper('tax')->getTaxBasedOn() == 'origin') {
            $request = $taxCalculationModel->getRateRequest()->setCustomerClassId($customerTaxClass);
            return $taxCalculationModel->getRatesForAllProductTaxClasses($request);
        }

        $customerRules = $taxCalculationModel->getRatesByCustomerTaxClass($customerTaxClass);
        $rules = array();
        foreach ($customerRules as $rule) {
            $rules[$rule['product_class']][] = $rule;
        }

        return $rules;
    }

    /**
     * Getter for request initial auth details flag XML
     *
     * @return string
     */
    protected function _getRequestInitialAuthDetailsXml()
    {
        $xml = <<<EOT
        <request-initial-auth-details>true</request-initial-auth-details>
EOT;
        return $xml;
    }

    /**
     * Getter for parametrized url XML
     *
     * @return string
     */
    protected function _getParameterizedUrlsXml()
    {
        return '';
        $xml = <<<EOT
            <parameterized-urls>
                <parameterized-url url="{$this->_getParameterizedUrl()}" />
            </parameterized-urls>
EOT;
        return $xml;
    }

    /**
     * Getter for platform Id XML
     *
     * @return string
     */
    protected function _getPlatformIdXml()
    {
        $xml = <<<EOT
            <platform-id>473325629220583</platform-id>
EOT;
        return $xml;
    }

    /**
     * Retrieve analytics data XML
     *
     * @return string
     */
    protected function _getAnalyticsDataXml()
    {
        if (!($analytics = $this->getApi()->getAnalyticsData())) {
            return '';
        }
        $xml = <<<EOT
            <analytics-data><![CDATA[{$analytics}]]></analytics-data>
EOT;
        return $xml;
    }

    /**
     * Getter for cart edit url
     *
     * @return string
     */
    protected function _getEditCartUrl()
    {
        return Mage::getUrl('googlecheckout/redirect/cart');
    }

    /**
     * Getter for continue shopping url
     *
     * @return string
     */
    protected function _getContinueShoppingUrl()
    {
        return Mage::getUrl('googlecheckout/redirect/continue');
    }

    /**
     * Getter for notifications url
     *
     * @return string
     */
    protected function _getNotificationsUrl()
    {
        return $this->_getCallbackUrl();
    }

    /**
     * Getter for calculations url
     *
     * @return string
     */
    protected function _getCalculationsUrl()
    {
        return $this->_getCallbackUrl();
    }

    /**
     * Getter for parametrized url
     *
     * @return string
     */
    protected function _getParameterizedUrl()
    {
        return Mage::getUrl('googlecheckout/api/beacon');
    }

    /**
     * Define if current quote is virtual
     *
     * @return bool
     */
    protected function _isOrderVirtual()
    {
        foreach ($this->getQuote()->getAllItems() as $item) {
            if (!$item->getIsVirtual()) {
                return false;
            }
        }
        return true;
    }

   /**
    * Retrieve native carriers to Google carriers map
    *
    * @return array
    */
    protected function _getGoogleCarriersMap() {
        return array(
            'ups' => array(
                'googleCarrierCompany' => 'UPS',
                'methods' => array(
                    'GND' => Mage::helper('usa')->__('Ground'),
                    '1DA' => Mage::helper('usa')->__('Next Day Air'),
                    '1DM' => Mage::helper('usa')->__('Next Day Air Early AM'),
                    '1DP' => Mage::helper('usa')->__('Next Day Air Saver'),
                    '2DA' => Mage::helper('usa')->__('2nd Day Air'),
                    '2DM' => Mage::helper('usa')->__('2nd Day Air AM'),
                    '3DS' => Mage::helper('usa')->__('3 Day Select'),
                    '03'  => Mage::helper('usa')->__('Ground'),
                    '01'  => Mage::helper('usa')->__('Next Day Air'),
                    '14'  => Mage::helper('usa')->__('Next Day Air Early AM'),
                    '13'  => Mage::helper('usa')->__('Next Day Air Saver'),
                    '02'  => Mage::helper('usa')->__('2nd Day Air'),
                    '59'  => Mage::helper('usa')->__('2nd Day Air AM'),
                    '12'  => Mage::helper('usa')->__('3 Day Select')
                )
            ),
            'usps' => array(
                'googleCarrierCompany' => 'USPS',
                'methods' => array(
                    'Express Mail'  => Mage::helper('usa')->__('Express Mail'),
                    'Priority Mail' => Mage::helper('usa')->__('Priority Mail'),
                    'Parcel Post'   => Mage::helper('usa')->__('Parcel Post'),
                    'Media Mail'    => Mage::helper('usa')->__('Media Mail')
                )
            ),
            'fedex' => array(
                'googleCarrierCompany' => 'FedEx',
                'methods' => array(
                    'FEDEX_GROUND'        => Mage::helper('usa')->__('Ground'),
                    'GROUND_HOME_DELIVERY' => Mage::helper('usa')->__('Home Delivery'),
                    'FEDEX_EXPRESS_SAVER'  => Mage::helper('usa')->__('Express Saver'),
                    'FIRST_OVERNIGHT'     => Mage::helper('usa')->__('First Overnight'),
                    'PRIORITY_OVERNIGHT'  => Mage::helper('usa')->__('Priority Overnight'),
                    'STANDARD_OVERNIGHT'  => Mage::helper('usa')->__('Standard Overnight'),
                    'FEDEX_2_DAY'          => Mage::helper('usa')->__('2Day')
                )
            )
        );
    }
}
