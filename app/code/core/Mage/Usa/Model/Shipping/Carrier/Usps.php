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
 * @package     Mage_Usa
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * USPS shipping rates estimation
 *
 * @link       http://www.usps.com/webtools/htm/Development-Guide-v3-0b.htm
 * @category   Mage
 * @package    Mage_Usa
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Usa_Model_Shipping_Carrier_Usps
    extends Mage_Usa_Model_Shipping_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * USPS containers
     */
    const CONTAINER_VARIABLE           = 'VARIABLE';
    const CONTAINER_FLAT_RATE_BOX      = 'FLAT RATE BOX';
    const CONTAINER_FLAT_RATE_ENVELOPE = 'FLAT RATE ENVELOPE';
    const CONTAINER_RECTANGULAR        = 'RECTANGULAR';
    const CONTAINER_NONRECTANGULAR     = 'NONRECTANGULAR';

    /**
     * USPS size
     */
    const SIZE_REGULAR = 'REGULAR';
    const SIZE_LARGE   = 'LARGE';

    /**
     * Default api revision
     *
     * @var int
     */
    const DEFAULT_REVISION = 2;

    /**
     * Code of the carrier
     *
     * @var string
     */
    const CODE = 'usps';

    /**
     * Ounces in one pound for conversion
     */
    const OUNCES_POUND = 16;

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Destination Zip Code required flag
     *
     * @var boolean
     * @deprecated since 1.7.0 functionality implemented in Mage_Usa_Model_Shipping_Carrier_Abstract
     */
    protected $_isZipCodeRequired;

    /**
     * Rate request data
     *
     * @var Mage_Shipping_Model_Rate_Request|null
     */
    protected $_request = null;

    /**
     * Raw rate request data
     *
     * @var Varien_Object|null
     */
    protected $_rawRequest = null;

    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

    /**
     * Default cgi gateway url
     *
     * @var string
     */
    protected $_defaultGatewayUrl = 'http://production.shippingapis.com/ShippingAPI.dll';

    /**
     * Container types that could be customized for USPS carrier
     *
     * @var array
     */
    protected $_customizableContainerTypes = array('VARIABLE', 'RECTANGULAR', 'NONRECTANGULAR');

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag($this->_activeFlag)) {
            return false;
        }

        $this->setRequest($request);

        $this->_result = $this->_getQuotes();

        $this->_updateFreeMethodQuote($request);

        return $this->getResult();
    }

    /**
     * Prepare and set request to this instance
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Usa_Model_Shipping_Carrier_Usps
     */
    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->_request = $request;

        $r = new Varien_Object();

        if ($request->getLimitMethod()) {
            $r->setService($request->getLimitMethod());
        } else {
            $r->setService('ALL');
        }

        if ($request->getUspsUserid()) {
            $userId = $request->getUspsUserid();
        } else {
            $userId = $this->getConfigData('userid');
        }
        $r->setUserId($userId);

        if ($request->getUspsContainer()) {
            $container = $request->getUspsContainer();
        } else {
            $container = $this->getConfigData('container');
        }
        $r->setContainer($container);

        if ($request->getUspsSize()) {
            $size = $request->getUspsSize();
        } else {
            $size = $this->getConfigData('size');
        }
        $r->setSize($size);

        if ($request->getGirth()) {
            $girth = $request->getGirth();
        } else {
            $girth = $this->getConfigData('girth');
        }
        $r->setGirth($girth);

        if ($request->getHeight()) {
            $height = $request->getHeight();
        } else {
            $height = $this->getConfigData('height');
        }
        $r->setHeight($height);

        if ($request->getLength()) {
            $length = $request->getLength();
        } else {
            $length = $this->getConfigData('length');
        }
        $r->setLength($length);

        if ($request->getWidth()) {
            $width = $request->getWidth();
        } else {
            $width = $this->getConfigData('width');
        }
        $r->setWidth($width);

        if ($request->getUspsMachinable()) {
            $machinable = $request->getUspsMachinable();
        } else {
            $machinable = $this->getConfigData('machinable');
        }
        $r->setMachinable($machinable);

        if ($request->getOrigPostcode()) {
            $r->setOrigPostal($request->getOrigPostcode());
        } else {
            $r->setOrigPostal(Mage::getStoreConfig(
                Mage_Shipping_Model_Shipping::XML_PATH_STORE_ZIP,
                $request->getStoreId()
            ));
        }

        if ($request->getOrigCountryId()) {
            $r->setOrigCountryId($request->getOrigCountryId());
        } else {
            $r->setOrigCountryId(Mage::getStoreConfig(
                Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID,
                $request->getStoreId()
            ));
        }

        if ($request->getDestCountryId()) {
            $destCountry = $request->getDestCountryId();
        } else {
            $destCountry = self::USA_COUNTRY_ID;
        }

        $r->setDestCountryId($destCountry);

        if (!$this->_isUSCountry($destCountry)) {
            $r->setDestCountryName($this->_getCountryName($destCountry));
        }

        if ($request->getDestPostcode()) {
            $r->setDestPostal($request->getDestPostcode());
        }

        $weight = $this->getTotalNumOfBoxes($request->getPackageWeight());
        $r->setWeightPounds(floor($weight));
        $r->setWeightOunces(round(($weight-floor($weight)) * self::OUNCES_POUND, 1));
        if ($request->getFreeMethodWeight()!=$request->getPackageWeight()) {
            $r->setFreeMethodWeight($request->getFreeMethodWeight());
        }

        $r->setValue($request->getPackageValue());
        $r->setValueWithDiscount($request->getPackageValueWithDiscount());

        $r->setBaseSubtotalInclTax($request->getBaseSubtotalInclTax());

        $this->_rawRequest = $r;

        return $this;
    }

    /**
     * Get result of request
     *
     * @return mixed
     */
    public function getResult()
    {
       return $this->_result;
    }

    /**
     * Get quotes
     *
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _getQuotes()
    {
        return $this->_getXmlQuotes();
    }

    /**
     * Set free method request
     *
     * @param  $freeMethod
     * @return void
     */
    protected function _setFreeMethodRequest($freeMethod)
    {
        $r = $this->_rawRequest;

        $weight = $this->getTotalNumOfBoxes($r->getFreeMethodWeight());
        $r->setWeightPounds(floor($weight));
        $r->setWeightOunces(round(($weight-floor($weight)) * self::OUNCES_POUND, 1));
        $r->setService($freeMethod);
    }

    /**
     * Build RateV3 request, send it to USPS gateway and retrieve quotes in XML format
     *
     * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _getXmlQuotes()
    {
        $r = $this->_rawRequest;

        // The origin address(shipper) must be only in USA
        if(!$this->_isUSCountry($r->getOrigCountryId())){
            $responseBody = '';
            return $this->_parseXmlResponse($responseBody);
        }

        if ($this->_isUSCountry($r->getDestCountryId())) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RateV4Request/>');
            $xml->addAttribute('USERID', $r->getUserId());
            // according to usps v4 documentation
            $xml->addChild('Revision', '2');

            $package = $xml->addChild('Package');
            $package->addAttribute('ID', 0);
            $service = $this->getCode('service_to_code', $r->getService());
            if (!$service) {
                $service = $r->getService();
            }
            if ($r->getContainer() == 'FLAT RATE BOX' || $r->getContainer() == 'FLAT RATE ENVELOPE') {
                $service = 'PRIORITY';
            }
            $package->addChild('Service', $service);

            // no matter Letter, Flat or Parcel, use Parcel
            if ($r->getService() == 'FIRST CLASS' || $r->getService() == 'FIRST CLASS HFP COMMERCIAL') {
                $package->addChild('FirstClassMailType', 'PARCEL');
            }
            $package->addChild('ZipOrigination', $r->getOrigPostal());
            //only 5 chars avaialble
            $package->addChild('ZipDestination', substr($r->getDestPostal(), 0, 5));
            $package->addChild('Pounds', $r->getWeightPounds());
            $package->addChild('Ounces', $r->getWeightOunces());
            // Because some methods don't accept VARIABLE and (NON)RECTANGULAR containers
            $package->addChild('Container', $r->getContainer());
            $package->addChild('Size', $r->getSize());
            if ($r->getSize() == 'LARGE') {
                $package->addChild('Width', $r->getWidth());
                $package->addChild('Length', $r->getLength());
                $package->addChild('Height', $r->getHeight());
                if ($r->getContainer() == 'NONRECTANGULAR' || $r->getContainer() == 'VARIABLE') {
                    $package->addChild('Girth', $r->getGirth());
                }
            }
            $package->addChild('Machinable', $r->getMachinable());

            $api = 'RateV4';
        } else {
            $xml = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><IntlRateV2Request/>');
            $xml->addAttribute('USERID', $r->getUserId());
            // according to usps v4 documentation
            $xml->addChild('Revision', '2');

            $package = $xml->addChild('Package');
            $package->addAttribute('ID', 0);
            $package->addChild('Pounds', $r->getWeightPounds());
            $package->addChild('Ounces', $r->getWeightOunces());
            $package->addChild('MailType', 'All');
            $package->addChild('ValueOfContents', $r->getValue());
            $package->addChild('Country', $r->getDestCountryName());
            $package->addChild('Container', $r->getContainer());
            $package->addChild('Size', $r->getSize());
            $width = $length = $height = $girth = '';
            if ($r->getSize() == 'LARGE') {
                $width = $r->getWidth();
                $length = $r->getLength();
                $height = $r->getHeight();
                if ($r->getContainer() == 'NONRECTANGULAR') {
                    $girth = $r->getGirth();
                }
            }
            $package->addChild('Width', $width);
            $package->addChild('Length', $length);
            $package->addChild('Height', $height);
            $package->addChild('Girth', $girth);


            $api = 'IntlRateV2';
        }
        $request = $xml->asXML();

        $responseBody = $this->_getCachedQuotes($request);
        if ($responseBody === null) {
            $debugData = array('request' => $request);
            try {
                $url = $this->getConfigData('gateway_url');
                if (!$url) {
                    $url = $this->_defaultGatewayUrl;
                }
                $client = new Zend_Http_Client();
                $client->setUri($url);
                $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
                $client->setParameterGet('API', $api);
                $client->setParameterGet('XML', $request);
                $response = $client->request();
                $responseBody = $response->getBody();

                $debugData['result'] = $responseBody;
                $this->_setCachedQuotes($request, $responseBody);
            } catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                $responseBody = '';
            }
            $this->_debug($debugData);
        }
        return $this->_parseXmlResponse($responseBody);
    }

    /**
     * Parse calculated rates
     *
     * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
     * @param string $response
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _parseXmlResponse($response)
    {
        $costArr = array();
        $priceArr = array();
        if (strlen(trim($response)) > 0) {
            if (strpos(trim($response), '<?xml') === 0) {
                if (strpos($response, '<?xml version="1.0"?>') !== false) {
                    $response = str_replace(
                        '<?xml version="1.0"?>',
                        '<?xml version="1.0" encoding="ISO-8859-1"?>',
                        $response
                    );
                }

                $xml = simplexml_load_string($response);

                if (is_object($xml)) {
                    if (is_object($xml->Number) && is_object($xml->Description) && (string)$xml->Description!='') {
                        $errorTitle = (string)$xml->Description;
                    } elseif (is_object($xml->Package)
                          && is_object($xml->Package->Error)
                          && is_object($xml->Package->Error->Description)
                          && (string)$xml->Package->Error->Description!=''
                    ) {
                        $errorTitle = (string)$xml->Package->Error->Description;
                    } else {
                        $errorTitle = 'Unknown error';
                    }
                    $r = $this->_rawRequest;
                    $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));
                    $allMethods = $this->getCode('method');
                    $newMethod = false;
                    if ($this->_isUSCountry($r->getDestCountryId())) {
                        if (is_object($xml->Package) && is_object($xml->Package->Postage)) {
                            foreach ($xml->Package->Postage as $postage) {
                                $serviceName = $this->_filterServiceName((string)$postage->MailService);
                                $postage->MailService = $serviceName;
                                if (in_array($serviceName, $allowedMethods)) {
                                    $costArr[$serviceName] = (string)$postage->Rate;
                                    $priceArr[$serviceName] = $this->getMethodPrice(
                                        (string)$postage->Rate,
                                        $serviceName
                                    );
                                } elseif (!in_array($serviceName, $allMethods)) {
                                    $allMethods[] = $serviceName;
                                    $newMethod = true;
                                }
                            }
                            asort($priceArr);
                        }
                    } else {
                        /*
                         * International Rates
                         */
                        if (is_object($xml->Package) && is_object($xml->Package->Service)) {
                            foreach ($xml->Package->Service as $service) {
                                $serviceName = $this->_filterServiceName((string)$service->SvcDescription);
                                $service->SvcDescription = $serviceName;
                                if (in_array($serviceName, $allowedMethods)) {
                                    $costArr[$serviceName] = (string)$service->Postage;
                                    $priceArr[$serviceName] = $this->getMethodPrice(
                                        (string)$service->Postage,
                                        $serviceName
                                    );
                                } elseif (!in_array($serviceName, $allMethods)) {
                                    $allMethods[] = $serviceName;
                                    $newMethod = true;
                                }
                            }
                            asort($priceArr);
                        }
                    }
                    /**
                     * following if statement is obsolete
                     * we don't have adminhtml/config resoure model
                     */
                    if (false && $newMethod) {
                        sort($allMethods);
                        $insert['usps']['fields']['methods']['value'] = $allMethods;
                        Mage::getResourceModel('adminhtml/config')->saveSectionPost('carriers','','',$insert);
                    }
                }
            } else {
                $errorTitle = 'Response is in the wrong format';
            }
        }

        $result = Mage::getModel('shipping/rate_result');
        if (empty($priceArr)) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier('usps');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
        } else {
            foreach ($priceArr as $method=>$price) {
                $rate = Mage::getModel('shipping/rate_result_method');
                $rate->setCarrier('usps');
                $rate->setCarrierTitle($this->getConfigData('title'));
                $rate->setMethod($method);
                $rate->setMethodTitle($method);
                $rate->setCost($costArr[$method]);
                $rate->setPrice($price);
                $result->append($rate);
            }
        }

        return $result;
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code='')
    {
        $codes = array(

            'service'=>array(
                'FIRST CLASS' => Mage::helper('usa')->__('First-Class'),
                'PRIORITY'    => Mage::helper('usa')->__('Priority Mail'),
                'EXPRESS'     => Mage::helper('usa')->__('Express Mail'),
                'BPM'         => Mage::helper('usa')->__('Bound Printed Matter'),
                'PARCEL'      => Mage::helper('usa')->__('Parcel Post'),
                'MEDIA'       => Mage::helper('usa')->__('Media Mail'),
                'LIBRARY'     => Mage::helper('usa')->__('Library'),
            ),

            'service_to_code'=>array(
                'First-Class'                                   => 'FIRST CLASS',
                'First-Class Mail International Large Envelope' => 'FIRST CLASS',
                'First-Class Mail International Letter'         => 'FIRST CLASS',
                'First-Class Mail International Package'        => 'FIRST CLASS',
                'First-Class Mail International Parcel'         => 'FIRST CLASS',
                'First-Class Mail'                 => 'FIRST CLASS',
                'First-Class Mail Flat'            => 'FIRST CLASS',
                'First-Class Mail Large Envelope'  => 'FIRST CLASS',
                'First-Class Mail International'   => 'FIRST CLASS',
                'First-Class Mail Letter'          => 'FIRST CLASS',
                'First-Class Mail Parcel'          => 'FIRST CLASS',
                'First-Class Mail Package'         => 'FIRST CLASS',
                'Parcel Post'                      => 'PARCEL',
                'Bound Printed Matter'             => 'BPM',
                'Media Mail'                       => 'MEDIA',
                'Library Mail'                     => 'LIBRARY',
                'Express Mail'                     => 'EXPRESS',
                'Express Mail PO to PO'            => 'EXPRESS',
                'Express Mail Flat Rate Envelope'  => 'EXPRESS',
                'Express Mail Flat-Rate Envelope Sunday/Holiday Guarantee'  => 'EXPRESS',
                'Express Mail Sunday/Holiday Guarantee'            => 'EXPRESS',
                'Express Mail Flat Rate Envelope Hold For Pickup'  => 'EXPRESS',
                'Express Mail Hold For Pickup'                     => 'EXPRESS',
                'Global Express Guaranteed (GXG)'                  => 'EXPRESS',
                'Global Express Guaranteed Non-Document Rectangular'     => 'EXPRESS',
                'Global Express Guaranteed Non-Document Non-Rectangular' => 'EXPRESS',
                'USPS GXG Envelopes'                               => 'EXPRESS',
                'Express Mail International'                       => 'EXPRESS',
                'Express Mail International Flat Rate Envelope'    => 'EXPRESS',
                'Priority Mail'                        => 'PRIORITY',
                'Priority Mail Small Flat Rate Box'    => 'PRIORITY',
                'Priority Mail Medium Flat Rate Box'   => 'PRIORITY',
                'Priority Mail Large Flat Rate Box'    => 'PRIORITY',
                'Priority Mail Flat Rate Box'          => 'PRIORITY',
                'Priority Mail Flat Rate Envelope'     => 'PRIORITY',
                'Priority Mail International'                            => 'PRIORITY',
                'Priority Mail International Flat Rate Envelope'         => 'PRIORITY',
                'Priority Mail International Small Flat Rate Box'        => 'PRIORITY',
                'Priority Mail International Medium Flat Rate Box'       => 'PRIORITY',
                'Priority Mail International Large Flat Rate Box'        => 'PRIORITY',
                'Priority Mail International Flat Rate Box'              => 'PRIORITY'
            ),

            'first_class_mail_type'=>array(
                'LETTER'      => Mage::helper('usa')->__('Letter'),
                'FLAT'        => Mage::helper('usa')->__('Flat'),
                'PARCEL'      => Mage::helper('usa')->__('Parcel'),
            ),

            'container'=>array(
                'VARIABLE'           => Mage::helper('usa')->__('Variable'),
                'FLAT RATE BOX'      => Mage::helper('usa')->__('Flat-Rate Box'),
                'FLAT RATE ENVELOPE' => Mage::helper('usa')->__('Flat-Rate Envelope'),
                'RECTANGULAR'        => Mage::helper('usa')->__('Rectangular'),
                'NONRECTANGULAR'     => Mage::helper('usa')->__('Non-rectangular'),
            ),

            'containers_filter' => array(
                array(
                    'containers' => array('VARIABLE'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Express Mail Flat Rate Envelope',
                                'Express Mail Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Flat Rate Envelope',
                                'Priority Mail Large Flat Rate Box',
                                'Priority Mail Medium Flat Rate Box',
                                'Priority Mail Small Flat Rate Box',
                                'Express Mail',
                                'Priority Mail',
                                'Parcel Post',
                                'Media Mail',
                                'First-Class Mail Large Envelope',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Express Mail International Flat Rate Envelope',
                                'Priority Mail International Flat Rate Envelope',
                                'Priority Mail International Large Flat Rate Box',
                                'Priority Mail International Medium Flat Rate Box',
                                'Priority Mail International Small Flat Rate Box',
                                'Global Express Guaranteed (GXG)',
                                'USPS GXG Envelopes',
                                'Express Mail International',
                                'Priority Mail International',
                                'First-Class Mail International Package',
                                'First-Class Mail International Large Envelope',
                                'First-Class Mail International Parcel',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('FLAT RATE BOX'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Priority Mail Large Flat Rate Box',
                                'Priority Mail Medium Flat Rate Box',
                                'Priority Mail Small Flat Rate Box',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Priority Mail International Large Flat Rate Box',
                                'Priority Mail International Medium Flat Rate Box',
                                'Priority Mail International Small Flat Rate Box',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('FLAT RATE ENVELOPE'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Express Mail Flat Rate Envelope',
                                'Express Mail Flat Rate Envelope Hold For Pickup',
                                'Priority Mail Flat Rate Envelope',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Express Mail International Flat Rate Envelope',
                                'Priority Mail International Flat Rate Envelope',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('RECTANGULAR'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Express Mail',
                                'Priority Mail',
                                'Parcel Post',
                                'Media Mail',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'USPS GXG Envelopes',
                                'Express Mail International',
                                'Priority Mail International',
                                'First-Class Mail International Package',
                                'First-Class Mail International Parcel',
                            )
                        )
                    )
                ),
                array(
                    'containers' => array('NONRECTANGULAR'),
                    'filters'    => array(
                        'within_us' => array(
                            'method' => array(
                                'Express Mail',
                                'Priority Mail',
                                'Parcel Post',
                                'Media Mail',
                            )
                        ),
                        'from_us' => array(
                            'method' => array(
                                'Global Express Guaranteed (GXG)',
                                'USPS GXG Envelopes',
                                'Express Mail International',
                                'Priority Mail International',
                                'First-Class Mail International Package',
                                'First-Class Mail International Parcel',
                            )
                        )
                    )
                ),
             ),

            'size'=>array(
                'REGULAR'     => Mage::helper('usa')->__('Regular'),
                'LARGE'       => Mage::helper('usa')->__('Large'),
            ),

            'machinable'=>array(
                'true'        => Mage::helper('usa')->__('Yes'),
                'false'       => Mage::helper('usa')->__('No'),
            ),

            'delivery_confirmation_types' => array(
                'True' => Mage::helper('usa')->__('Not Required'),
                'False'  => Mage::helper('usa')->__('Required'),
            ),
        );

        $methods = $this->getConfigData('methods');
        if (!empty($methods)) {
            $codes['method'] = explode(",", $methods);
        } else {
            $codes['method'] = array();
        }

        if (!isset($codes[$type])) {
            return false;
        } elseif (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    /**
     * Get tracking
     *
     * @param mixed $trackings
     * @return mixed
     */
    public function getTracking($trackings)
    {
        $this->setTrackingReqeust();

        if (!is_array($trackings)) {
            $trackings = array($trackings);
        }

        $this->_getXmlTracking($trackings);

        return $this->_result;
    }

    /**
     * Set tracking request
     *
     * @return null
     */
    protected function setTrackingReqeust()
    {
        $r = new Varien_Object();

        $userId = $this->getConfigData('userid');
        $r->setUserId($userId);

        $this->_rawTrackRequest = $r;
    }

    /**
     * Send request for tracking
     *
     * @param array $tracking
     * @return null
     */
    protected function _getXmlTracking($trackings)
    {
         $r = $this->_rawTrackRequest;

         foreach ($trackings as $tracking) {
             $xml = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><TrackRequest/>');
             $xml->addAttribute('USERID', $r->getUserId());

             $trackid = $xml->addChild('TrackID');
             $trackid->addAttribute('ID',$tracking);

             $api = 'TrackV2';
             $request = $xml->asXML();
             $debugData = array('request' => $request);

             try {
                $url = $this->getConfigData('gateway_url');
                if (!$url) {
                    $url = $this->_defaultGatewayUrl;
                }
                $client = new Zend_Http_Client();
                $client->setUri($url);
                $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
                $client->setParameterGet('API', $api);
                $client->setParameterGet('XML', $request);
                $response = $client->request();
                $responseBody = $response->getBody();
                $debugData['result'] = $responseBody;
            }
            catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                $responseBody = '';
            }

            $this->_debug($debugData);
            $this->_parseXmlTrackingResponse($tracking, $responseBody);
         }
    }

    /**
     * Parse xml tracking response
     *
     * @param array $trackingvalue
     * @param string $response
     * @return null
     */
    protected function _parseXmlTrackingResponse($trackingvalue, $response)
    {
        $errorTitle = Mage::helper('usa')->__('Unable to retrieve tracking');
        $resultArr=array();
        if (strlen(trim($response)) > 0) {
            if (strpos(trim($response), '<?xml')===0) {
                $xml = simplexml_load_string($response);
                if (is_object($xml)) {
                    if (isset($xml->Number) && isset($xml->Description) && (string)$xml->Description!='') {
                        $errorTitle = (string)$xml->Description;
                    } elseif (isset($xml->TrackInfo)
                          && isset($xml->TrackInfo->Error)
                          && isset($xml->TrackInfo->Error->Description)
                          && (string)$xml->TrackInfo->Error->Description!=''
                    ) {
                        $errorTitle = (string)$xml->TrackInfo->Error->Description;
                    } else {
                        $errorTitle = Mage::helper('usa')->__('Unknown error');
                    }

                    if(isset($xml->TrackInfo) && isset($xml->TrackInfo->TrackSummary)){
                       $resultArr['tracksummary'] = (string)$xml->TrackInfo->TrackSummary;

                    }
                }
            }
        }

        if (!$this->_result) {
            $this->_result = Mage::getModel('shipping/tracking_result');
        }
        $defaults = $this->getDefaults();

        if ($resultArr) {
             $tracking = Mage::getModel('shipping/tracking_result_status');
             $tracking->setCarrier('usps');
             $tracking->setCarrierTitle($this->getConfigData('title'));
             $tracking->setTracking($trackingvalue);
             $tracking->setTrackSummary($resultArr['tracksummary']);
             $this->_result->append($tracking);
         } else {
            $error = Mage::getModel('shipping/tracking_result_error');
            $error->setCarrier('usps');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setTracking($trackingvalue);
            $error->setErrorMessage($errorTitle);
            $this->_result->append($error);
         }
    }

    /**
     * Get tracking response
     *
     * @return string
     */
    public function getResponse()
    {
        $statuses = '';
        if ($this->_result instanceof Mage_Shipping_Model_Tracking_Result) {
            if ($trackings = $this->_result->getAllTrackings()) {
                foreach ($trackings as $tracking) {
                    if($data = $tracking->getAllData()) {
                        if (!empty($data['track_summary'])) {
                            $statuses .= Mage::helper('usa')->__($data['track_summary']);
                        } else {
                            $statuses .= Mage::helper('usa')->__('Empty response');
                        }
                    }
                }
            }
        }
        if (empty($statuses)) {
            $statuses = Mage::helper('usa')->__('Empty response');
        }
        return $statuses;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = array();
        foreach ($allowed as $k) {
            $arr[$k] = $k;
        }
        return $arr;
    }

    /**
     * Return USPS county name by country ISO 3166-1-alpha-2 code
     * Return false for unknown countries
     *
     * @param string $countryId
     * @return string|false
     */
    protected function _getCountryName($countryId)
    {
        $countries = array (
          'AD' => 'Andorra',
          'AE' => 'United Arab Emirates',
          'AF' => 'Afghanistan',
          'AG' => 'Antigua and Barbuda',
          'AI' => 'Anguilla',
          'AL' => 'Albania',
          'AM' => 'Armenia',
          'AN' => 'Netherlands Antilles',
          'AO' => 'Angola',
          'AR' => 'Argentina',
          'AT' => 'Austria',
          'AU' => 'Australia',
          'AW' => 'Aruba',
          'AX' => 'Aland Island (Finland)',
          'AZ' => 'Azerbaijan',
          'BA' => 'Bosnia-Herzegovina',
          'BB' => 'Barbados',
          'BD' => 'Bangladesh',
          'BE' => 'Belgium',
          'BF' => 'Burkina Faso',
          'BG' => 'Bulgaria',
          'BH' => 'Bahrain',
          'BI' => 'Burundi',
          'BJ' => 'Benin',
          'BM' => 'Bermuda',
          'BN' => 'Brunei Darussalam',
          'BO' => 'Bolivia',
          'BR' => 'Brazil',
          'BS' => 'Bahamas',
          'BT' => 'Bhutan',
          'BW' => 'Botswana',
          'BY' => 'Belarus',
          'BZ' => 'Belize',
          'CA' => 'Canada',
          'CC' => 'Cocos Island (Australia)',
          'CD' => 'Congo, Democratic Republic of the',
          'CF' => 'Central African Republic',
          'CG' => 'Congo, Republic of the',
          'CH' => 'Switzerland',
          'CI' => 'Cote d Ivoire (Ivory Coast)',
          'CK' => 'Cook Islands (New Zealand)',
          'CL' => 'Chile',
          'CM' => 'Cameroon',
          'CN' => 'China',
          'CO' => 'Colombia',
          'CR' => 'Costa Rica',
          'CU' => 'Cuba',
          'CV' => 'Cape Verde',
          'CX' => 'Christmas Island (Australia)',
          'CY' => 'Cyprus',
          'CZ' => 'Czech Republic',
          'DE' => 'Germany',
          'DJ' => 'Djibouti',
          'DK' => 'Denmark',
          'DM' => 'Dominica',
          'DO' => 'Dominican Republic',
          'DZ' => 'Algeria',
          'EC' => 'Ecuador',
          'EE' => 'Estonia',
          'EG' => 'Egypt',
          'ER' => 'Eritrea',
          'ES' => 'Spain',
          'ET' => 'Ethiopia',
          'FI' => 'Finland',
          'FJ' => 'Fiji',
          'FK' => 'Falkland Islands',
          'FM' => 'Micronesia, Federated States of',
          'FO' => 'Faroe Islands',
          'FR' => 'France',
          'GA' => 'Gabon',
          'GB' => 'Great Britain and Northern Ireland',
          'GD' => 'Grenada',
          'GE' => 'Georgia, Republic of',
          'GF' => 'French Guiana',
          'GH' => 'Ghana',
          'GI' => 'Gibraltar',
          'GL' => 'Greenland',
          'GM' => 'Gambia',
          'GN' => 'Guinea',
          'GP' => 'Guadeloupe',
          'GQ' => 'Equatorial Guinea',
          'GR' => 'Greece',
          'GS' => 'South Georgia (Falkland Islands)',
          'GT' => 'Guatemala',
          'GW' => 'Guinea-Bissau',
          'GY' => 'Guyana',
          'HK' => 'Hong Kong',
          'HN' => 'Honduras',
          'HR' => 'Croatia',
          'HT' => 'Haiti',
          'HU' => 'Hungary',
          'ID' => 'Indonesia',
          'IE' => 'Ireland',
          'IL' => 'Israel',
          'IN' => 'India',
          'IQ' => 'Iraq',
          'IR' => 'Iran',
          'IS' => 'Iceland',
          'IT' => 'Italy',
          'JM' => 'Jamaica',
          'JO' => 'Jordan',
          'JP' => 'Japan',
          'KE' => 'Kenya',
          'KG' => 'Kyrgyzstan',
          'KH' => 'Cambodia',
          'KI' => 'Kiribati',
          'KM' => 'Comoros',
          'KN' => 'Saint Kitts (St. Christopher and Nevis)',
          'KP' => 'North Korea (Korea, Democratic People\'s Republic of)',
          'KR' => 'South Korea (Korea, Republic of)',
          'KW' => 'Kuwait',
          'KY' => 'Cayman Islands',
          'KZ' => 'Kazakhstan',
          'LA' => 'Laos',
          'LB' => 'Lebanon',
          'LC' => 'Saint Lucia',
          'LI' => 'Liechtenstein',
          'LK' => 'Sri Lanka',
          'LR' => 'Liberia',
          'LS' => 'Lesotho',
          'LT' => 'Lithuania',
          'LU' => 'Luxembourg',
          'LV' => 'Latvia',
          'LY' => 'Libya',
          'MA' => 'Morocco',
          'MC' => 'Monaco (France)',
          'MD' => 'Moldova',
          'MG' => 'Madagascar',
          'MK' => 'Macedonia, Republic of',
          'ML' => 'Mali',
          'MM' => 'Burma',
          'MN' => 'Mongolia',
          'MO' => 'Macao',
          'MQ' => 'Martinique',
          'MR' => 'Mauritania',
          'MS' => 'Montserrat',
          'MT' => 'Malta',
          'MU' => 'Mauritius',
          'MV' => 'Maldives',
          'MW' => 'Malawi',
          'MX' => 'Mexico',
          'MY' => 'Malaysia',
          'MZ' => 'Mozambique',
          'NA' => 'Namibia',
          'NC' => 'New Caledonia',
          'NE' => 'Niger',
          'NG' => 'Nigeria',
          'NI' => 'Nicaragua',
          'NL' => 'Netherlands',
          'NO' => 'Norway',
          'NP' => 'Nepal',
          'NR' => 'Nauru',
          'NZ' => 'New Zealand',
          'OM' => 'Oman',
          'PA' => 'Panama',
          'PE' => 'Peru',
          'PF' => 'French Polynesia',
          'PG' => 'Papua New Guinea',
          'PH' => 'Philippines',
          'PK' => 'Pakistan',
          'PL' => 'Poland',
          'PM' => 'Saint Pierre and Miquelon',
          'PN' => 'Pitcairn Island',
          'PT' => 'Portugal',
          'PY' => 'Paraguay',
          'QA' => 'Qatar',
          'RE' => 'Reunion',
          'RO' => 'Romania',
          'RS' => 'Serbia',
          'RU' => 'Russia',
          'RW' => 'Rwanda',
          'SA' => 'Saudi Arabia',
          'SB' => 'Solomon Islands',
          'SC' => 'Seychelles',
          'SD' => 'Sudan',
          'SE' => 'Sweden',
          'SG' => 'Singapore',
          'SH' => 'Saint Helena',
          'SI' => 'Slovenia',
          'SK' => 'Slovak Republic',
          'SL' => 'Sierra Leone',
          'SM' => 'San Marino',
          'SN' => 'Senegal',
          'SO' => 'Somalia',
          'SR' => 'Suriname',
          'ST' => 'Sao Tome and Principe',
          'SV' => 'El Salvador',
          'SY' => 'Syrian Arab Republic',
          'SZ' => 'Swaziland',
          'TC' => 'Turks and Caicos Islands',
          'TD' => 'Chad',
          'TG' => 'Togo',
          'TH' => 'Thailand',
          'TJ' => 'Tajikistan',
          'TK' => 'Tokelau (Union) Group (Western Samoa)',
          'TL' => 'East Timor (Indonesia)',
          'TM' => 'Turkmenistan',
          'TN' => 'Tunisia',
          'TO' => 'Tonga',
          'TR' => 'Turkey',
          'TT' => 'Trinidad and Tobago',
          'TV' => 'Tuvalu',
          'TW' => 'Taiwan',
          'TZ' => 'Tanzania',
          'UA' => 'Ukraine',
          'UG' => 'Uganda',
          'UY' => 'Uruguay',
          'UZ' => 'Uzbekistan',
          'VA' => 'Vatican City',
          'VC' => 'Saint Vincent and the Grenadines',
          'VE' => 'Venezuela',
          'VG' => 'British Virgin Islands',
          'VN' => 'Vietnam',
          'VU' => 'Vanuatu',
          'WF' => 'Wallis and Futuna Islands',
          'WS' => 'Western Samoa',
          'YE' => 'Yemen',
          'YT' => 'Mayotte (France)',
          'ZA' => 'South Africa',
          'ZM' => 'Zambia',
          'ZW' => 'Zimbabwe',
          'US' => 'United States',
        );

        if (isset($countries[$countryId])) {
            return $countries[$countryId];
        }

        return false;
    }

    /**
     * Clean service name from unsupported strings and characters
     *
     * @param  string $name
     * @return string
     */
    protected function _filterServiceName($name)
    {
        $name = (string)preg_replace(array('~<[^/!][^>]+>.*</[^>]+>~sU', '~\<!--.*--\>~isU', '~<[^>]+>~is'), '',
            html_entity_decode($name)
        );
        $name = str_replace('*', '', $name);

        return $name;
    }

    /**
     * Form XML for US shipment request
     * As integration guide it is important to follow appropriate sequence for tags e.g.: <FromLastName /> must be
     * after <FromFirstName />
     *
     * @param Varien_Object $request
     * @return string
     */
    protected function _formUsExpressShipmentRequest(Varien_Object $request)
    {
        $packageParams = $request->getPackageParams();

        $packageWeight = $request->getPackageWeight();
        if ($packageParams->getWeightUnits() != Zend_Measure_Weight::OUNCE) {
            $packageWeight = round(Mage::helper('usa')->convertMeasureWeight(
                $request->getPackageWeight(),
                $packageParams->getWeightUnits(),
                Zend_Measure_Weight::OUNCE
            ));
        }

        list($fromZip5, $fromZip4) = $this->_parseZip($request->getShipperAddressPostalCode());
        list($toZip5, $toZip4) = $this->_parseZip($request->getRecipientAddressPostalCode(), true);

        $rootNode = 'ExpressMailLabelRequest';
        // the wrap node needs for remove xml declaration above
        $xmlWrap = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><wrap/>');
        $xml = $xmlWrap->addChild($rootNode);
        $xml->addAttribute('USERID', $this->getConfigData('userid'));
        $xml->addAttribute('PASSWORD', $this->getConfigData('password'));
        $xml->addChild('Option');
        $xml->addChild('Revision');
        $xml->addChild('EMCAAccount');
        $xml->addChild('EMCAPassword');
        $xml->addChild('ImageParameters');
        $xml->addChild('FromFirstName', $request->getShipperContactPersonFirstName());
        $xml->addChild('FromLastName', $request->getShipperContactPersonLastName());
        $xml->addChild('FromFirm', $request->getShipperContactCompanyName());
        $xml->addChild('FromAddress1', $request->getShipperAddressStreet2());
        $xml->addChild('FromAddress2', $request->getShipperAddressStreet1());
        $xml->addChild('FromCity', $request->getShipperAddressCity());
        $xml->addChild('FromState', $request->getShipperAddressStateOrProvinceCode());
        $xml->addChild('FromZip5', $fromZip5);
        $xml->addChild('FromZip4', $fromZip4);
        $xml->addChild('FromPhone', $request->getShipperContactPhoneNumber());
        $xml->addChild('ToFirstName', $request->getRecipientContactPersonFirstName());
        $xml->addChild('ToLastName', $request->getRecipientContactPersonLastName());
        $xml->addChild('ToFirm', $request->getRecipientContactCompanyName());
        $xml->addChild('ToAddress1', $request->getRecipientAddressStreet2());
        $xml->addChild('ToAddress2', $request->getRecipientAddressStreet1());
        $xml->addChild('ToCity', $request->getRecipientAddressCity());
        $xml->addChild('ToState', $request->getRecipientAddressStateOrProvinceCode());
        $xml->addChild('ToZip5', $toZip5);
        $xml->addChild('ToZip4', $toZip4);
        $xml->addChild('ToPhone', $request->getRecipientContactPhoneNumber());
        $xml->addChild('WeightInOunces', $packageWeight);
        $xml->addChild('WaiverOfSignature', $packageParams->getDeliveryConfirmation());
        $xml->addChild('POZipCode');
        $xml->addChild('ImageType', 'PDF');

        $xml = $xmlWrap->{$rootNode}->asXML();
        return $xml;
    }

    /**
     * Form XML for US Signature Confirmation request
     * As integration guide it is important to follow appropriate sequence for tags e.g.: <FromLastName /> must be
     * after <FromFirstName />
     *
     * @param Varien_Object $request
     * @param string $serviceType
     * @return string
     */
    protected function _formUsSignatureConfirmationShipmentRequest(Varien_Object $request, $serviceType)
    {
        switch ($serviceType) {
            case 'PRIORITY':
                $serviceType = 'Priority';
                break;
            case 'FIRST CLASS':
                $serviceType = 'First Class';
                break;
            case 'PARCEL':
                $serviceType = 'Parcel Post';
                break;
            case 'MEDIA':
                $serviceType = 'Media Mail';
                break;
            case 'LIBRARY':
                $serviceType = 'Library Mail';
                break;
            default:
                throw new Exception(Mage::helper('usa')->__('Service type does not match'));
        }
        $packageParams = $request->getPackageParams();
        $packageWeight = $request->getPackageWeight();
        if ($packageParams->getWeightUnits() != Zend_Measure_Weight::OUNCE) {
            $packageWeight = round(Mage::helper('usa')->convertMeasureWeight(
                $request->getPackageWeight(),
                $packageParams->getWeightUnits(),
                Zend_Measure_Weight::OUNCE
            ));
        }

        list($fromZip5, $fromZip4) = $this->_parseZip($request->getShipperAddressPostalCode());
        list($toZip5, $toZip4) = $this->_parseZip($request->getRecipientAddressPostalCode(), true);

        if ($this->getConfigData('mode')) {
            $rootNode = 'SignatureConfirmationV3.0Request';
        } else {
            $rootNode = 'SigConfirmCertifyV3.0Request';
        }
        // the wrap node needs for remove xml declaration above
        $xmlWrap = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><wrap/>');
        $xml = $xmlWrap->addChild($rootNode);
        $xml->addAttribute('USERID', $this->getConfigData('userid'));
        $xml->addChild('Option', 1);
        $xml->addChild('ImageParameters');
        $xml->addChild('FromName', $request->getShipperContactPersonName());
        $xml->addChild('FromFirm', $request->getShipperContactCompanyName());
        $xml->addChild('FromAddress1', $request->getShipperAddressStreet2());
        $xml->addChild('FromAddress2', $request->getShipperAddressStreet1());
        $xml->addChild('FromCity', $request->getShipperAddressCity());
        $xml->addChild('FromState', $request->getShipperAddressStateOrProvinceCode());
        $xml->addChild('FromZip5', $fromZip5);
        $xml->addChild('FromZip4', $fromZip4);
        $xml->addChild('ToName', $request->getRecipientContactPersonName());
        $xml->addChild('ToFirm', $request->getRecipientContactCompanyName());
        $xml->addChild('ToAddress1', $request->getRecipientAddressStreet2());
        $xml->addChild('ToAddress2', $request->getRecipientAddressStreet1());
        $xml->addChild('ToCity', $request->getRecipientAddressCity());
        $xml->addChild('ToState', $request->getRecipientAddressStateOrProvinceCode());
        $xml->addChild('ToZip5', $toZip5);
        $xml->addChild('ToZip4', $toZip4);
        $xml->addChild('WeightInOunces', $packageWeight);
        $xml->addChild('ServiceType', $serviceType);
        $xml->addChild('WaiverOfSignature', $packageParams->getDeliveryConfirmation());
        $xml->addChild('ImageType', 'PDF');

        $xml = $xmlWrap->{$rootNode}->asXML();
        return $xml;
    }

    /**
     * Convert decimal weight into pound-ounces format
     *
     * @param float $weightInPounds
     * @return array
     */
    protected function _convertPoundOunces($weightInPounds)
    {
        $weightInOunces = ceil($weightInPounds * self::OUNCES_POUND);
        $pounds = floor($weightInOunces / self::OUNCES_POUND);
        $ounces = $weightInOunces % self::OUNCES_POUND;
        return array($pounds, $ounces);
    }

    /**
     * Form XML for international shipment request
     * As integration guide it is important to follow appropriate sequence for tags e.g.: <FromLastName /> must be
     * after <FromFirstName />
     *
     * @param Varien_Object $request
     * @return string
     */
    protected function _formIntlShipmentRequest(Varien_Object $request)
    {
        $packageParams = $request->getPackageParams();
        $height = $packageParams->getHeight();
        $width = $packageParams->getWidth();
        $length = $packageParams->getLength();
        $girth = $packageParams->getGirth();
        $packageWeight = $request->getPackageWeight();
        if ($packageParams->getWeightUnits() != Zend_Measure_Weight::POUND) {
            $packageWeight = Mage::helper('usa')->convertMeasureWeight(
                $request->getPackageWeight(),
                $packageParams->getWeightUnits(),
                Zend_Measure_Weight::POUND
            );
        }
        if ($packageParams->getDimensionUnits() != Zend_Measure_Length::INCH) {
            $length = round(Mage::helper('usa')->convertMeasureDimension(
                $packageParams->getLength(),
                $packageParams->getDimensionUnits(),
                Zend_Measure_Length::INCH
            ));
            $width = round(Mage::helper('usa')->convertMeasureDimension(
                $packageParams->getWidth(),
                $packageParams->getDimensionUnits(),
                Zend_Measure_Length::INCH
            ));
            $height = round(Mage::helper('usa')->convertMeasureDimension(
                $packageParams->getHeight(),
                $packageParams->getDimensionUnits(),
                Zend_Measure_Length::INCH
            ));
        }
        if ($packageParams->getGirthDimensionUnits() != Zend_Measure_Length::INCH) {
            $girth = round(Mage::helper('usa')->convertMeasureDimension(
                $packageParams->getGirth(),
                $packageParams->getGirthDimensionUnits(),
                Zend_Measure_Length::INCH
            ));
        }

        $container = $request->getPackagingType();
        switch ($container) {
            case 'VARIABLE':
                $container = 'VARIABLE';
                break;
            case 'FLAT RATE ENVELOPE':
                $container = 'FLATRATEENV';
                break;
            case 'FLAT RATE BOX':
                $container = 'FLATRATEBOX';
                break;
            case 'RECTANGULAR':
                $container = 'RECTANGULAR';
                break;
            case 'NONRECTANGULAR':
                $container = 'NONRECTANGULAR';
                break;
            default:
                $container = 'VARIABLE';
        }
        $shippingMethod = $request->getShippingMethod();
        list($fromZip5, $fromZip4) = $this->_parseZip($request->getShipperAddressPostalCode());

        // the wrap node needs for remove xml declaration above
        $xmlWrap = new SimpleXMLElement('<?xml version = "1.0" encoding = "UTF-8"?><wrap/>');
        $method = '';
        if (stripos($shippingMethod, 'Priority') !== false) {
            $method = 'Priority';
            $rootNode = 'PriorityMailIntlRequest';
            $xml = $xmlWrap->addChild($rootNode);
        } else if (stripos($shippingMethod, 'First-Class') !== false) {
            $method = 'FirstClass';
            $rootNode = 'FirstClassMailIntlRequest';
            $xml = $xmlWrap->addChild($rootNode);
        } else {
            $method = 'Express';
            $rootNode = 'ExpressMailIntlRequest';
            $xml = $xmlWrap->addChild($rootNode);
        }

        $xml->addAttribute('USERID', $this->getConfigData('userid'));
        $xml->addAttribute('PASSWORD', $this->getConfigData('password'));
        $xml->addChild('Option');
        $xml->addChild('Revision', self::DEFAULT_REVISION);
        $xml->addChild('ImageParameters');
        $xml->addChild('FromFirstName', $request->getShipperContactPersonFirstName());
        $xml->addChild('FromLastName', $request->getShipperContactPersonLastName());
        $xml->addChild('FromFirm', $request->getShipperContactCompanyName());
        $xml->addChild('FromAddress1', $request->getShipperAddressStreet2());
        $xml->addChild('FromAddress2', $request->getShipperAddressStreet1());
        $xml->addChild('FromCity', $request->getShipperAddressCity());
        $xml->addChild('FromState', $request->getShipperAddressStateOrProvinceCode());
        $xml->addChild('FromZip5', $fromZip5);
        $xml->addChild('FromZip4', $fromZip4);
        $xml->addChild('FromPhone', $request->getShipperContactPhoneNumber());
        if ($method != 'FirstClass') {
            if ($request->getReferenceData()) {
                $referenceData = $request->getReferenceData() . ' P' . $request->getPackageId();
            } else {
                $referenceData = $request->getOrderShipment()->getOrder()->getIncrementId()
                                 . ' P'
                                 . $request->getPackageId();
            }
            $xml->addChild('FromCustomsReference', 'Order #' . $referenceData);
        }
        $xml->addChild('ToName', $request->getRecipientContactPersonName());
        $xml->addChild('ToFirm', $request->getRecipientContactCompanyName());
        $xml->addChild('ToAddress1', $request->getRecipientAddressStreet1());
        $xml->addChild('ToAddress2', $request->getRecipientAddressStreet2());
        $xml->addChild('ToCity', $request->getRecipientAddressCity());
        $xml->addChild('ToProvince', $request->getRecipientAddressStateOrProvinceCode());
        $xml->addChild('ToCountry', $this->_getCountryName($request->getRecipientAddressCountryCode()));
        $xml->addChild('ToPostalCode', $request->getRecipientAddressPostalCode());
        $xml->addChild('ToPOBoxFlag', 'N');
        $xml->addChild('ToPhone', $request->getRecipientContactPhoneNumber());
        $xml->addChild('ToFax');
        $xml->addChild('ToEmail');
        if ($method != 'FirstClass') {
            $xml->addChild('NonDeliveryOption', 'Return');
        }
        if ($method == 'FirstClass') {
            if (stripos($shippingMethod, 'Letter') !== false) {
                $xml->addChild('FirstClassMailType', 'LETTER');
            } else if (stripos($shippingMethod, 'Flat') !== false) {
                $xml->addChild('FirstClassMailType', 'FLAT');
            } else{
                $xml->addChild('FirstClassMailType', 'PARCEL');
            }
        }
        if ($method != 'FirstClass') {
            $xml->addChild('Container', $container);
        }
        $shippingContents = $xml->addChild('ShippingContents');
        $packageItems = $request->getPackageItems();
        // get countries of manufacture
        $countriesOfManufacture = array();
        $productIds = array();
        foreach ($packageItems as $itemShipment) {
                $item = new Varien_Object();
                $item->setData($itemShipment);

                $productIds[]= $item->getProductId();
        }
        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addStoreFilter($request->getStoreId())
            ->addFieldToFilter('entity_id', array('in' => $productIds))
            ->addAttributeToSelect('country_of_manufacture');
        foreach ($productCollection as $product) {
            $countriesOfManufacture[$product->getId()] = $product->getCountryOfManufacture();
        }

        $packagePoundsWeight = $packageOuncesWeight = 0;
        // for ItemDetail
        foreach ($packageItems as $itemShipment) {
            $item = new Varien_Object();
            $item->setData($itemShipment);

            $itemWeight = $item->getWeight() * $item->getQty();
            if ($packageParams->getWeightUnits() != Zend_Measure_Weight::POUND) {
                $itemWeight = Mage::helper('usa')->convertMeasureWeight(
                    $itemWeight,
                    $packageParams->getWeightUnits(),
                    Zend_Measure_Weight::POUND
                );
            }
            if (!empty($countriesOfManufacture[$item->getProductId()])) {
                $countryOfManufacture = $this->_getCountryName(
                    $countriesOfManufacture[$item->getProductId()]
                );
            } else {
                $countryOfManufacture = '';
            }
            $itemDetail = $shippingContents->addChild('ItemDetail');
            $itemDetail->addChild('Description', $item->getName());
            $ceiledQty = ceil($item->getQty());
            if ($ceiledQty < 1) {
                $ceiledQty = 1;
            }
            $individualItemWeight = $itemWeight / $ceiledQty;
            $itemDetail->addChild('Quantity', $ceiledQty);
            $itemDetail->addChild('Value', $item->getCustomsValue() * $item->getQty());
            list($individualPoundsWeight, $individualOuncesWeight) = $this->_convertPoundOunces($individualItemWeight);
            $itemDetail->addChild('NetPounds', $individualPoundsWeight);
            $itemDetail->addChild('NetOunces', $individualOuncesWeight);
            $itemDetail->addChild('HSTariffNumber', 0);
            $itemDetail->addChild('CountryOfOrigin', $countryOfManufacture);

            list($itemPoundsWeight, $itemOuncesWeight) = $this->_convertPoundOunces($itemWeight);
            $packagePoundsWeight += $itemPoundsWeight;
            $packageOuncesWeight += $itemOuncesWeight;
        }
        $additionalPackagePoundsWeight = floor($packageOuncesWeight / self::OUNCES_POUND);
        $packagePoundsWeight += $additionalPackagePoundsWeight;
        $packageOuncesWeight -= $additionalPackagePoundsWeight * self::OUNCES_POUND;
        if ($packagePoundsWeight + $packageOuncesWeight / self::OUNCES_POUND < $packageWeight) {
            list($packagePoundsWeight, $packageOuncesWeight) = $this->_convertPoundOunces($packageWeight);
        }

        $xml->addChild('GrossPounds', $packagePoundsWeight);
        $xml->addChild('GrossOunces', $packageOuncesWeight);
        if ($packageParams->getContentType() == 'OTHER' && $packageParams->getContentTypeOther() != null) {
            $xml->addChild('ContentType', $packageParams->getContentType());
            $xml->addChild('ContentTypeOther ', $packageParams->getContentTypeOther());
        } else {
            $xml->addChild('ContentType', $packageParams->getContentType());
        }

        $xml->addChild('Agreement', 'y');
        $xml->addChild('ImageType', 'PDF');
        $xml->addChild('ImageLayout', 'ALLINONEFILE');
        if ($method == 'FirstClass') {
            $xml->addChild('Container', $container);
        }
        // set size
        if ($packageParams->getSize()) {
            $xml->addChild('Size', $packageParams->getSize());
        }
        // set dimensions
        $xml->addChild('Length', $length);
        $xml->addChild('Width', $width);
        $xml->addChild('Height', $height);
        if ($girth) {
            $xml->addChild('Girth', $girth);
        }

        $xml = $xmlWrap->{$rootNode}->asXML();
        return $xml;
    }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param Varien_Object $request
     * @return Varien_Object
     */
    protected function _doShipmentRequest(Varien_Object $request)
    {
        $this->_prepareShipmentRequest($request);
        $result = new Varien_Object();
        $service = $this->getCode('service_to_code', $request->getShippingMethod());
        $recipientUSCountry = $this->_isUSCountry($request->getRecipientAddressCountryCode());

        if ($recipientUSCountry && $service == 'EXPRESS') {
            $requestXml = $this->_formUsExpressShipmentRequest($request);
            $api = 'ExpressMailLabel';
        } else if ($recipientUSCountry) {
            $requestXml = $this->_formUsSignatureConfirmationShipmentRequest($request, $service);
            if ($this->getConfigData('mode')) {
                $api = 'SignatureConfirmationV3';
            } else {
                $api = 'SignatureConfirmationCertifyV3';
            }
        } else if ($service == 'FIRST CLASS') {
            $requestXml = $this->_formIntlShipmentRequest($request);
            $api = 'FirstClassMailIntl';
        } else if ($service == 'PRIORITY') {
            $requestXml = $this->_formIntlShipmentRequest($request);
            $api = 'PriorityMailIntl';
        } else {
            $requestXml = $this->_formIntlShipmentRequest($request);
            $api = 'ExpressMailIntl';
        }

        $debugData = array('request' => $requestXml);
        $url = $this->getConfigData('gateway_secure_url');
        if (!$url) {
            $url = $this->_defaultGatewayUrl;
        }
        $client = new Zend_Http_Client();
        $client->setUri($url);
        $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
        $client->setParameterGet('API', $api);
        $client->setParameterGet('XML', $requestXml);
        $response = $client->request()->getBody();

        $response = simplexml_load_string($response);
        if ($response === false || $response->getName() == 'Error') {
            $debugData['result'] = array(
                'error' => $response->Description,
                'code' => $response->Number,
                'xml' => $response->asXML()
            );
            $this->_debug($debugData);
            $result->setErrors($debugData['result']['error']);
        } else {
            if ($recipientUSCountry && $service == 'EXPRESS') {
                $labelContent = base64_decode((string) $response->EMLabel);
                $trackingNumber = (string) $response->EMConfirmationNumber;
            } else if ($recipientUSCountry) {
                $labelContent = base64_decode((string) $response->SignatureConfirmationLabel);
                $trackingNumber = (string) $response->SignatureConfirmationNumber;
            } else  {
                $labelContent = base64_decode((string) $response->LabelImage);
                $trackingNumber = (string) $response->BarcodeNumber;
            }
            $result->setShippingLabelContent($labelContent);
            $result->setTrackingNumber($trackingNumber);
        }

        $result->setGatewayResponse($response);
        $debugData['result'] = $response;
        $this->_debug($debugData);
        return $result;
    }

    /**
     * Return container types of carrier
     *
     * @param Varien_Object|null $params
     * @return array|bool
     */
    public function getContainerTypes(Varien_Object $params = null)
    {
        if (is_null($params)) {
            return $this->_getAllowedContainers();
        }
        return $this->_isUSCountry($params->getCountryRecipient()) ? array() : $this->_getAllowedContainers($params);
    }

    /**
     * Return all container types of carrier
     *
     * @return array|bool
     */
    public function getContainerTypesAll()
    {
        return $this->getCode('container');
    }

    /**
     * Return structured data of containers witch related with shipping methods
     *
     * @return array|bool
     */
    public function getContainerTypesFilter()
    {
        return $this->getCode('containers_filter');
    }

    /**
     * Return delivery confirmation types of carrier
     *
     * @param Varien_Object|null $params
     * @return array
     */
    public function getDeliveryConfirmationTypes(Varien_Object $params = null)
    {
        if ($params == null) {
            return array();
        }
        $countryRecipient = $params->getCountryRecipient();
        if ($this->_isUSCountry($countryRecipient)) {
            return $this->getCode('delivery_confirmation_types');
        } else {
            return array();
        }
    }

    /**
     * Check whether girth is allowed for the USPS
     *
     * @param null|string $countyDest
     * @return bool
     */
    public function isGirthAllowed($countyDest = null)
    {
        return $this->_isUSCountry($countyDest) ? false : true;
    }

    /**
     * Return content types of package
     *
     * @param Varien_Object $params
     * @return array
     */
    public function getContentTypes(Varien_Object $params)
    {
        $countryShipper     = $params->getCountryShipper();
        $countryRecipient   = $params->getCountryRecipient();

        if ($countryShipper == self::USA_COUNTRY_ID
            && $countryRecipient != self::USA_COUNTRY_ID
        ) {
            return array(
                'MERCHANDISE' => Mage::helper('usa')->__('Merchandise'),
                'SAMPLE' => Mage::helper('usa')->__('Sample'),
                'GIFT' => Mage::helper('usa')->__('Gift'),
                'DOCUMENTS' => Mage::helper('usa')->__('Documents'),
                'RETURN' => Mage::helper('usa')->__('Return'),
                'OTHER' => Mage::helper('usa')->__('Other'),
            );
        }
        return array();
    }

    /**
     * Parse zip from string to zip5-zip4
     *
     * @param string $zipString
     * @param bool $returnFull
     * @return array
     */
    protected function _parseZip($zipString, $returnFull = false)
    {
        $zip4 = '';
        $zip5 = '';
        $zip = array($zipString);
        if (preg_match('/[\\d\\w]{5}\\-[\\d\\w]{4}/', $zipString) != 0) {
            $zip = explode('-', $zipString);
        }
        for ($i = 0; $i < count($zip); ++$i) {
            if (strlen($zip[$i]) == 5) {
                $zip5 = $zip[$i];
            } elseif (strlen($zip[$i]) == 4) {
                $zip4 = $zip[$i];
            }
        }
        if (empty($zip5) && empty($zip4) && $returnFull) {
            $zip5 = $zipString;
        }

        return array($zip5, $zip4);
    }
}
