<?php

$bootstrapBase = dirname(__FILE__) . DIRECTORY_SEPARATOR;
$bootstrapProject = $bootstrapBase . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

/* Define path to application directory */
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', $bootstrapProject . 'app');

defined('LIB_PATH')
    || define('LIB_PATH', $bootstrapProject . 'lib');

$_SERVER['MAGE_TEST'] = true;

// require_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'code/community/Mage/Core/Model/App.php';
require_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'Mage.php';

// require_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'code/community/Mage/Admin/Model/Session.php';
// require_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'code/community/Mage/Core/Controller/Varien/Front.php';
require_once APPLICATION_PATH . DIRECTORY_SEPARATOR . 'code/community/Ibuildings/Test/Model/Email/Template.php';

/* Include the test cases */
require_once LIB_PATH . DIRECTORY_SEPARATOR . 'Ibuildings/Mage/Controller/Request/HttpTestCase.php';
require_once LIB_PATH . DIRECTORY_SEPARATOR . 'Ibuildings/Mage/Controller/Response/HttpTestCase.php';
require_once LIB_PATH . DIRECTORY_SEPARATOR . 'Ibuildings/Mage/Test/PHPUnit/ControllerTestCase.php';
require_once LIB_PATH . DIRECTORY_SEPARATOR . 'Ibuildings/Mage/Test/PHPUnit/TestCase.php';

/* Flush the cache once on execution rather than on every test */
Mage::app()->getCacheInstance()->flush();
