<?php

namespace ZucchiDoctrine\Query\Provider;

use Zend\EventManager\Event as ZendEvent;
use ZucchiDoctrine\EntityManager\EntityManagerAwareInterface;
use ZucchiDoctrine\EntityManager\EntityManagerAwareTrait;

/**
 * Class Event
 *
 * Event Class for handling extending interactions with query structures
 *
 * @package ZF\Apigility\Doctrine\Server\Event
 */
class Event extends ZendEvent implements EntityManagerAwareInterface
{
    use EntityManagerAwareTrait;

    const EVENT_WHERE = 'where';
    const EVENT_ORDER = 'order';
    const EVENT_LIMIT = 'limit';
    const EVENT_QUERYBUILDER = 'querybuilder';
}