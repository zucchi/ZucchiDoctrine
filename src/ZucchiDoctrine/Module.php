<?php

namespace ZucchiDoctrine;

use Zend\EventManager\EventManager;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\EventManager\EventManagerAwareInterface;

class Module implements 
    AutoloaderProviderInterface,
    ConfigProviderInterface
    
{
    public function onBootstrap($e)
    {
        $app = $e->getApplication();
        $sm = $app->getServiceManager();

        $controllerLoader = $sm->get('ControllerLoader');
        $controllerLoader->addInitializer(function ($instance) use ($sm) {
            if (method_exists($instance, 'setEntityManager')) {
                $em = $sm->get('doctrine.entitymanager.orm_default');
                $instance->setEntityManager($em);
            }
        });
        $controllerLoader->addInitializer(function ($instance) use ($sm) {
            if (method_exists($instance, 'setDocumentManager')) {
                $dm = $sm->get('doctrine.documentmanager.odm_default');
                $instance->setDocumentManager($dm);
            }
        });


        $serviceLoader = $sm->get('ServiceManager');
        $serviceLoader->addInitializer(function ($instance) use ($sm) {
            if (method_exists($instance, 'setEntityManager')) {
                $em = $sm->get('doctrine.entitymanager.orm_default');
                $instance->setEntityManager($em);
            }
        });
        $serviceLoader->addInitializer(function ($instance) use ($sm){
            if (method_exists($instance, 'setDocumentManager')) {
                $dm = $sm->get('doctrine.documentmanager.odm_default');
                $instance->setDocumentManager($dm);
            }
        });

        $queryProviderLoader = $sm->get('ZfApigilityDoctrineQueryProviderManager');
        $queryProviderLoader->addInitializer(function ($instance) use ($sm) {
            if ($instance instanceof EventManagerAwareInterface) {
                $instance->setEventManager(new EventManager());
            }
        });

        $events = $app->getEventManager();
        $layoutListener = $sm->get('zucchidoctrine.listener');
        $layoutListener->attach($events);

    }
    
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
    
}
