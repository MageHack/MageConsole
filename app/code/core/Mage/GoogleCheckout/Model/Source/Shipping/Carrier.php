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


class Mage_GoogleCheckout_Model_Source_Shipping_Carrier
{
    public function toOptionArray()
    {
        return array(
            array('label' => Mage::helper('googlecheckout')->__('FedEx'), 'value' => array(
                array('label' => Mage::helper('googlecheckout')->__('Ground'), 'value' => 'FedEx/Ground'),
                array('label' => Mage::helper('googlecheckout')->__('Home Delivery'), 'value' => 'FedEx/Home Delivery'),
                array('label' => Mage::helper('googlecheckout')->__('Express Saver'), 'value' => 'FedEx/Express Saver'),
                array('label' => Mage::helper('googlecheckout')->__('First Overnight'), 'value' => 'FedEx/First Overnight'),
                array('label' => Mage::helper('googlecheckout')->__('Priority Overnight'), 'value' => 'FedEx/Priority Overnight'),
                array('label' => Mage::helper('googlecheckout')->__('Standard Overnight'), 'value' => 'FedEx/Standard Overnight'),
                array('label' => Mage::helper('googlecheckout')->__('2Day'), 'value' => 'FedEx/2Day'),
            )),
            array('label' => Mage::helper('googlecheckout')->__('UPS'), 'value' => array(
                array('label' => Mage::helper('googlecheckout')->__('Next Day Air'), 'value' => 'UPS/Next Day Air'),
                array('label' => Mage::helper('googlecheckout')->__('Next Day Air Early AM'), 'value' => 'UPS/Next Day Air Early AM'),
                array('label' => Mage::helper('googlecheckout')->__('Next Day Air Saver'), 'value' => 'UPS/Next Day Air Saver'),
                array('label' => Mage::helper('googlecheckout')->__('2nd Day Air'), 'value' => 'UPS/2nd Day Air'),
                array('label' => Mage::helper('googlecheckout')->__('2nd Day Air AM'), 'value' => 'UPS/2nd Day Air AM'),
                array('label' => Mage::helper('googlecheckout')->__('3 Day Select'), 'value' => 'UPS/3 Day Select'),
                array('label' => Mage::helper('googlecheckout')->__('Ground'), 'value' => 'UPS/Ground'),
            )),
            array('label' => Mage::helper('googlecheckout')->__('USPS'), 'value' => array(
                array('label' => Mage::helper('googlecheckout')->__('Express Mail'), 'value' => 'USPS/Express Mail'),
                array('label' => Mage::helper('googlecheckout')->__('Priority Mail'), 'value' => 'USPS/Priority Mail'),
                array('label' => Mage::helper('googlecheckout')->__('Parcel Post'), 'value' => 'USPS/Parcel Post'),
                array('label' => Mage::helper('googlecheckout')->__('Media Mail'), 'value' => 'USPS/Media Mail'),
            )),
        );
    }
}
