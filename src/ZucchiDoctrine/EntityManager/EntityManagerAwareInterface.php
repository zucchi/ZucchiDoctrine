<?php

namespace ZucchiDoctrine\EntityManager;

use Doctrine\ORM\EntityManager;

interface EntityManagerAwareInterface
{
    /**
     * set the entity manager
     * @param EntityManager $em
     * @return $this
     */
    public function setEntityManager(EntityManager $em);

    /**
     * get the currently set Entity Manager
     * @return EntityManager
     */
    public function getEntityManager();
}
