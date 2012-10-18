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
 * Interface for enforcing Document Manager Awareness
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine
 * @subpackage DocumentManager
 */
interface DocumentManagerAwareInterface
{
    /**
     * set the DocumentManager
     * @param DocumentManager $em
     * @return $this
     */
    public function setDocumentManager(DocumentManager $em);

    /**
     * get the currently set DocumentManager
     * @return DocumentManager
     */
    public function getDocumentManager();
}
