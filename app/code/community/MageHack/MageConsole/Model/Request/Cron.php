<?php
/**
 * @category    MageHack
 * @package     MageHack_MageConsole
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageHack_MageConsole_Model_Request_Cron
    extends MageHack_MageConsole_Model_Abstract
{

    protected $_columns = array(
        'cron_job'      => 50,
        'schedule'      => 30,
    );

    protected $_columnWidths = array(
        'columnWidths' => array(50, 30)
    );

    /**
     * Get instance of Customer model
     *
     * @return  Mage_Core_Model_Cache
     */
    protected function _getModel()
    {
        return Mage::getModel('cron/schedule');
    }

    /**
     * List command
     *
     * @return  MageHack_MageConsole_Model_Abstract
     */
    public function listing()
    {
        $jobs =  Mage::getConfig()->getNode('crontab')->asArray();

        if (!count($jobs['jobs'])) {
            $message = 'This is strange, we did not find any cron jobs.';
        } else {
            $_values = array();
            foreach (array_keys($jobs['jobs']) as $job) {
                $row = array();
                $row[] = (string) $job;
                if (isset($jobs['jobs'][$job]['schedule']['cron_expr'])) {
                    $row[] = (string) $jobs['jobs'][$job]['schedule']['cron_expr'];
                } else {
                    $row[] = (string) 'Not set';
                }
                $_values[] = $row;
            }
            $message = Mage::helper('mageconsole')->createTable($_values, false, array('columnWidths' => array_values($this->_columns)));
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
    public function help() {
        $message = <<<USAGE
Usage: list cron

USAGE;
        $this->setType(self::RESPONSE_TYPE_MESSAGE);
        $this->setMessage($message);
        return $this;
    }
}
