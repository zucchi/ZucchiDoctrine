<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk/)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */

namespace ZucchiDoctrine\Event;

use Zend\EventManager\Event;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Code\Annotation\Parser;

use Zend\Form\Annotation\Options;

use Zucchi\Debug\Debug;

/**
 * Strategy for allowing manipulation of layout
 *
 * @category   Doctrine
 * @package    ZucchiDoctrine
 * @subpackage Event
 */
class DoctrineListener implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    protected $allowedAnnotations = array(
        'ZucchiDoctrine\Form\Annotation\OneToMany',
    );

    /**
     * Attach the a listener to the specified event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $shared = $events->getSharedManager();
        $this->listeners = array(
            $shared->attach(
                'Zend\Form\Annotation\AnnotationBuilder',
                'configureElement',
                array($this, 'handleOneToMany')
            ),
        );
    }

    /**
     * remove listeners from events
     * @param EventManagerInterface $events
     */
    public function detach(EventManagerInterface $events)
    {
        array_walk($this->listeners, array($events,'detach'));
        $this->listeners = array();
    }


    public function handleOneToMany($event)
    {
        $annotation = $event->getParam('annotation');

        if (!$annotation instanceof Options) {
            return;
        }

        $options = $annotation->getOptions();

        if (!isset($options['target_element']['composedObject'])) {
            return;
        }

        $elementSpec = $event->getParam('elementSpec');

        $annotationManager = $event->getTarget();
        $specification = $annotationManager->getFormSpecification($options['target_element']['composedObject']);

        $specification['type'] = 'Zend\Form\Fieldset';
        $specification['object'] = $options['target_element']['composedObject'];

        $elementSpec['spec']['options']['target_element'] = $specification;
    }
}
