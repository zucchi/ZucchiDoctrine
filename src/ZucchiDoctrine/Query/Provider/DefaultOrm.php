<?php
namespace ZucchiDoctrine\Query\Provider;

use Doctrine\ORM\QueryBuilder;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use ZF\Apigility\Doctrine\Server\Query\Provider\DefaultOrm as ApiDoctrineDefaultOrm;
use ZF\Rest\ResourceEvent;
use Zucchi\Controller\RequestParserTrait;
use ZucchiDoctrine\Query\QueryBuilderTrait;

/**
 * Class DefaultOrm
 * @package ZucchiDoctrine\Query\Provider
 */
class DefaultOrm extends ApiDoctrineDefaultOrm implements EventManagerAwareInterface
{
    use ServiceLocatorAwareTrait;
    use RequestParserTrait;
    use QueryBuilderTrait;
    use EventManagerAwareTrait;

    /**
     * @param string $entityClass
     * @param array  $parameters
     *
     * @return mixed This will return an ORM or ODM Query\Builder
     */
    public function createQuery(ResourceEvent $event, $entityClass, $parameters)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();
        $queryBuilder->select('e')
            ->from($entityClass, 'e');

		$routeParams = $event->getRouteMatch()->getParams();

        $where = $event->getQueryParam('where', []);

        if (is_array($where)) {
            $where = array_merge($where, $routeParams);
            unset($where['controller'], $where['version'], $where['action']);
        }

        // trigger event for manipulating $where
        $providerEvent = new Event(Event::EVENT_WHERE, $where, array('entityClass' => $entityClass));
        $providerEvent->setEntityManager($this->getObjectManager());
        $this->getEventManager()->trigger($providerEvent);
        $where = $providerEvent->getTarget();

		if ($where) {
            $where = $this->parseWhere($where);
            $this->addWhere($queryBuilder, $where);
        }

        // trigger event for manipulating $order
        $order = $event->getQueryParam('order', false);
        $providerEvent = new Event(Event::EVENT_ORDER, $order, array('entityClass' => $entityClass));
        $providerEvent->setEntityManager($this->getObjectManager());
        $this->getEventManager()->trigger($providerEvent);
        $order = $providerEvent->getTarget();

        if ($order) {
            $this->addOrder($queryBuilder, $order);
        }

        // trigger event for manipulating $limit
        $limit = $event->getQueryParam('limit', false);
        $providerEvent = new Event(Event::EVENT_LIMIT, $limit, array('entityClass' => $entityClass));
        $providerEvent->setEntityManager($this->getObjectManager());
        $this->getEventManager()->trigger($providerEvent);
        $limit = $providerEvent->getTarget();

        if ($limit) {
            $this->addLimit($queryBuilder, $limit);
        }

        return $queryBuilder;
    }

}