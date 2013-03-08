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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Zend\Stdlib\Hydrator\AbstractHydrator;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Stdlib\Hydrator\Reflection as ReflectionHydrator;
use Zend\Stdlib\Hydrator\ObjectProperty as ObjectPropertyHydrator;
use ZucchiDoctrine\EntityManager\EntityManagerAwareTrait;
use Zucchi\ServiceManager\ServiceManagerAwareTrait;
use ZucchiDoctrine\Entity\AbstractEntity;


/**
 * This hydrator is used as an optimization purpose for Doctrine ORM, and retrieves references to
 * objects instead of fetching the object from the database.
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @since   0.5.0
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 */
class DoctrineEntity extends ReflectionHydrator
{
    use EntityManagerAwareTrait;

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * @param EntityManager     $entityManager
     * @param HydratorInterface $hydrator
     */
    public function __construct(EntityManager $entityManager = null)
    {
        if ($entityManager) {
            $this->setEntityManager($entityManager);
        }
        parent::__construct();
    }

    /**
     * Extract values from an object
     *
     * @param  object $object
     * @return array
     */
    public function extract($object)
    {
        $result = array();
        foreach (self::getReflProperties($object) as $property) {
            $propertyName = $property->getName();

            $value = $property->getValue($object);
            if ($value instanceof AbstractEntity) {
                $result[$propertyName] = $value->toArray(true);
            } else {
                $result[$propertyName] = $this->extractValue($propertyName, $value);
            }
        }

        return $result;
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
        $this->metadata = $this->getEntityManager()->getClassMetadata(get_class($object));

        // process fields
        $fields = $this->metadata->getFieldNames();
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                if (in_array($this->metadata->getTypeOfField($field), array('datetime', 'time', 'date'))) {
                    if (is_int($data[$field])) {
                        $dt = new \DateTime();
                        $dt->setTimestamp($data[$field]);
                        $data[$field] = $dt;
                    } elseif (is_string($data[$field])) {
                        $data[$field] = new \DateTime($data[$field]);
                    }
                }
            } else {
                unset($data[$field]);
                continue;
            }
        }

        // process associations
        $assocs = $this->metadata->getAssociationNames();
        foreach($assocs as $assoc) {
            $target = $this->metadata->getAssociationTargetClass($assoc);

            $value = (isset($data[$assoc])) ? $data[$assoc] : null;

            if ($this->metadata->isSingleValuedAssociation($assoc)) {
                $data[$assoc] = $this->toOne($value, $target);
            } elseif ($this->metadata->isCollectionValuedAssociation($assoc)) {
                $data[$assoc] = $this->toMany($value, $target, $object->{$assoc});
            }
        }

        $reflProperties = self::getReflProperties($object);
        foreach ($data as $key => $value) {
            if (isset($reflProperties[$key])) {
                $reflProperties[$key]->setValue($object, $this->hydrateValue($key, $value));
            }
        }

        return $object;

    }

    /**
     * @param mixed  $valueOrObject
     * @param string $target
     * @return object
     */
    protected function toOne($valueOrObject, $target)
    {
        if ($valueOrObject instanceof $target || $valueOrObject == null) {
            return $valueOrObject;
        }

        $id = (is_array($valueOrObject)) ? $valueOrObject['id'] : $valueOrObject->id;

        if ($id > 0){
            $entity = $this->find($target, $id);
        }else{
            $entity = new $target();
        }

        return $this->hydrate($valueOrObject, $entity);
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
            if (method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } else if (!is_array($value) && !$value instanceof Traversable) {
                $value = (array) $value;
            }
            if (isset($value['id']) &&
                strlen($value['id']) &&
                $found = $this->find($target, $value['id'])
            ) {
                $keepers[] = $found->id;
                $this->hydrate($value,$found);
            } else {
                $obj = new $target();
                $obj->fromArray($value);
                $obj->id = null;
                if ($collection instanceof PersistentCollection) {
                   if ($owner = $collection->getOwner()) {
                       $mapping = $collection->getMapping();
                       $mappedBy = $mapping['mappedBy'];
                       $obj->{$mappedBy} = $owner;
                   }
                }
                $collection->add($obj);
            }
        }

        $collection->forAll(function($key, $element) use ($collection, $keepers) {
            if (strlen($element->id) && !in_array($element->id, $keepers)) {
                $collection->remove($key);
            }
        });

        return $collection;
    }

    /**
     * This function tries, given an array of data, to convert it to an object if the given array contains
     * an identifier for the object. This is useful in a context of updating existing entities, without ugly
     * tricks like setting manually the existing id directly into the entity
     *
     * @param  array  $data
     * @param  object $object
     * @return object
     */
    protected function tryConvertArrayToObject($data, $object)
    {
        $identifierNames  = $this->metadata->getIdentifierFieldNames($object);
        $identifierValues = array();

        if (empty($identifierNames)) {
            return $object;
        }

        foreach ($identifierNames as $identifierName) {
            if (!isset($data[$identifierName]) || empty($data[$identifierName])) {
                return $object;
            }

            $identifierValues[$identifierName] = $data[$identifierName];
        }

        return $this->find(get_class($object), $identifierValues);
    }

    /**
     * @param  string    $target
     * @param  mixed     $identifiers
     * @return object
     */
    protected function find($target, $identifiers)
    {
        return $this->getEntityManager()->getReference($target, $identifiers);
    }
}
