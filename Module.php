<?php

namespace ZucchiDoctrine;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Types\Type;

class Module implements 
    AutoloaderProviderInterface,
    ConfigProviderInterface
    
{
    public function onBootstrap($e)
    {
        $app = $e->getApplication();
        $events = $app->getEventManager()->getSharedManager();
        $sm = $app->getServiceManager();
        $em = $sm->get('doctrine.entitymanager.orm_default');
        
        Type::overrideType('datetime', 'ZucchiDoctrine\Datatype\DateTimeType');
        Type::overrideType('date', 'ZucchiDoctrine\Datatype\DateType');
        Type::overrideType('time', 'ZucchiDoctrine\Datatype\TimeType');
        
        Type::addType('money', 'ZucchiDoctrine\Datatype\MoneyType');
        $em->getConnection()
           ->getDatabasePlatform()
           ->registerDoctrineTypeMapping('money', 'money');
    }
    
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

    public function getConfig($env = null)
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
}
