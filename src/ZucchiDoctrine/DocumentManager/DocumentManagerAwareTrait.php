<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\DocumentManager;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Trait providing document manager functionality
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine
 * @subpackage DocumentManager
 */

trait DocumentManagerAwareTrait
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $documentManager;

    /**
     * set the entity manager
     * @param EntityManager $em
     * @return $this
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->documentManager = $dm;
        return $this;
    }

    /**
     * get the currently set Entity Manager
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager()
    {
        if (!$this->documentManager && method_exists($this, 'getServiceManager')) {
            $this->documentManager = $this->getServiceManager()->get('doctrine.documentmanager.odm_default');
        }
        return $this->documentManager;
    }
}