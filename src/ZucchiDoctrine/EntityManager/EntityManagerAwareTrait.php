<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\EntityManager;

use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceManager;

trait EntityManagerAwareTrait
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * set the entity manager
     * @param EntityManager $em
     * @return $this
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;
        return $this;
    }

    /**
     * get the currently set Entity Manager
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (!$this->entityManager && method_exists($this, 'getServiceManager')) {
            $sm = $this->getServiceManager();
            if ($sm instanceof ServiceManager) {
               $this->entityManager = $sm->get('doctrine.entitymanager.orm_default');
            }
        }
        return $this->entityManager;
    }
}