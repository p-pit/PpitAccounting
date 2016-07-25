<?php
namespace PpitAccounting;

use PpitCore\Model\GenericTable;
use PpitAccounting\Model\AccountingYear;
use PpitAccounting\Model\Journal;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'PpitAccounting\Model\AccountingYearTable' =>  function($sm) {
                    $tableGateway = $sm->get('AccountingYearTableGateway');
                    $table = new GenericTable($tableGateway);
                    return $table;
                },
                'AccountingYearTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new AccountingYear());
                    return new TableGateway('accounting_year', $dbAdapter, null, $resultSetPrototype);
                },
                'PpitAccounting\Model\JournalTable' =>  function($sm) {
                    $tableGateway = $sm->get('JournalTableGateway');
                    $table = new GenericTable($tableGateway);
                    return $table;
                },
                'JournalTableGateway' => function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Journal());
                    return new TableGateway('accounting_journal', $dbAdapter, null, $resultSetPrototype);
                },
            ),
        );
    }
}
