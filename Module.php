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
        $sm = $app->getServiceManager();
        $em = $sm->get('doctrine.entitymanager.orm_default');


        $controllerLoader = $sm->get('ControllerLoader');
        $controllerLoader->addInitializer(function ($instance) use ($em) {
            if (method_exists($instance, 'setEntityManager')) {
                $instance->setEntityManager($em);
            }
        });

        $serviceLoader = $sm->get('ServiceManager');
        $serviceLoader->addInitializer(function ($instance) use ($em) {
            if (method_exists($instance, 'setEntityManager')) {
                $instance->setEntityManager($em);
            }
        });

        $events = $app->getEventManager();
        $layoutListener = $sm->get('zucchidoctrine.listener');
        $layoutListener->attach($events);

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
