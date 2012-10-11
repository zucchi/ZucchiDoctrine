<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace ZucchiDoctrine\Hydrator;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineObjectHydrator;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\PersistentCollection;
use Zend\Stdlib\Hydrator\ObjectProperty as ObjectPropertyHydrator;

/**
 * This hydrator is used as an optimization purpose for Doctrine ORM, and retrieves references to
 * objects instead of fetching the object from the database.
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @since   0.5.0
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 */
class DoctrineEntity extends DoctrineObjectHydrator
{

    /**
     * @param ObjectManager     $objectManager
     * @param HydratorInterface $hydrator
     */
    public function __construct(ObjectManager $objectManager, HydratorInterface $hydrator = null)
    {
        $this->objectManager = $objectManager;

        if (null === $hydrator) {
            $hydrator = new ObjectPropertyHydrator(false);
        }

        $this->setHydrator($hydrator);
    }

    /**
     * {@inheritDoc}
     */
    protected function find($target, $identifiers)
    {
        /* @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $this->objectManager;

        return $entityManager->getReference($target, $identifiers);
    }

    /**
     * Hydrate $object with the provided $data.
     *
     * @param  array  $data
     * @param  object $object
     * @throws \Exception
     * @return object
     */
    public function hydrate(array $data, $object)
    {
        $this->metadata = $this->objectManager->getClassMetadata(get_class($object));

        foreach($data as $field => &$value) {
            if ($value === null) {
                unset($data[$field]);
                continue;
            }

            // @todo DateTime (and other types) conversion should be handled by doctrine itself in future
            if (in_array($this->metadata->getTypeOfField($field), array('datetime', 'time', 'date'))) {
                if (is_int($value)) {
                    $dt = new \DateTime();
                    $dt->setTimestamp($value);
                    $value = $dt;
                } elseif (is_string($value)) {
                    $value = new \DateTime($value);
                }
            }

            if ($this->metadata->hasAssociation($field)) {
                $target = $this->metadata->getAssociationTargetClass($field);

                if ($this->metadata->isSingleValuedAssociation($field)) {
                    $value = $this->toOne($value, $target);
                } elseif ($this->metadata->isCollectionValuedAssociation($field)) {
                    $value = $this->toMany($value, $target, $object->{$field});
                }
            }
        }

        return $this->hydrator->hydrate($data, $object);
    }

    /**
     * @param mixed  $valueOrObject
     * @param string $target
     * @return object
     */
    protected function toOne($valueOrObject, $target)
    {
        if ($valueOrObject instanceof $target) {
            return $valueOrObject;
        }

        $id = (is_array($valueOrObject)) ? $valueOrObject['id'] : $valueOrObject->id;

        return $this->find($target, $id);
    }

    /**
     * @param mixed $valueOrObject
     * @param string $target
     * @return array
     */
    protected function toMany($valueOrObject, $target, Collection $collection = null)
    {
        if (!is_array($valueOrObject) && !$valueOrObject instanceof Traversable) {
            $valueOrObject = (array) $valueOrObject;
        }

        if (!$collection instanceof Collection) {
            $collection = new ArrayCollection();
        }

        $keepers = array();

        foreach($valueOrObject as $value) {
             if (isset($value->id) &&
                 strlen($value->id) &&
                 $found = $this->find($target, $value->id)
             ) {
                 $keepers[] = $found->id;
                 $this->hydrate($value->toArray(),$found);
             } else {
                 $value->id = null;
                 if ($collection instanceof PersistentCollection) {
                    if ($owner = $collection->getOwner()) {
                        $mapping = $collection->getMapping();
                        $mappedBy = $mapping['mappedBy'];
                        $value->{$mappedBy} = $owner;
                    }
                 }
                 $collection->add($value);
             }
        }

        $collection->forAll(function($key, $element) use ($collection, $keepers) {
            if (strlen($element->id) && !in_array($element->id, $keepers)) {
                $collection->remove($key);
            }
        });

        return $collection;
    }
}
