<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\DocumentManager;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Trait providing document manager functionality
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @author Rick Nicol <rick@zucchi.co.uk>
 * @package ZucchiDoctrine
 * @subpackage DocumentManager
 */
trait DocumentManagerAwareTrait
{
    /**
     * Document Manager.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $documentManager;

    /**
     * Set Document Manager.
     *
     * @param DocumentManager $dm
     * @return $this
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->documentManager = $dm;
        return $this;
    }

    /**
     * Get the currently set Document Manager.
     *
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager()
    {
        if (!$this->documentManager && method_exists($this, 'getServiceManager')) {
            $this->documentManager = $this->getServiceManager()->get('doctrine.documentmanager.odm_default');
        }
        return $this->documentManager;
    }
}