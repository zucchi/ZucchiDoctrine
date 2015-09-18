<?php
namespace ZucchiDoctrine\Behaviour\Loggable;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass as MappedSuperclass;

/**
 * Gedmo\Loggable\Entity\LogEntry
 *
 * @ORM\Table(
 *  indexes={
 *      @ORM\Index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *      @ORM\Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class AbstractLog extends MappedSuperclass\AbstractLogEntry implements \JsonSerializable
{
    /**
     * (non-PHPdoc)
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return array(
            'version' => $this->getVersion(),
            'loggedAt'=> $this->getLoggedAt(),
            'username' => $this->getUsername(),
            'action' => $this->getAction(),
            'changes' => $this->getData(),
        );
    }
}
