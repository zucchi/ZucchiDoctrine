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
use Zucchi\DateTime\DateTime;
use Zucchi\DateTime\Date;
use Zucchi\DateTime\Time;



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
    public function extract($object, $depth = 1)
    {
        $result = array();
        foreach (self::getReflProperties($object) as $property) {

            $metaData = $this->entityManager->getClassMetadata(get_class($object));

            $propertyName = $property->getName();

            $value = $property->getValue($object);
            if ($value instanceof AbstractEntity) {
                $result[$propertyName] = $value->toArray($depth);
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
    public function hydrate(array $data, $object, $depth = 2)
    {
        $metadata = $this->getEntityManager()->getClassMetadata(get_class($object));

        // process fields
        $fields = $metadata->getFieldNames();
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $type = $metadata->getTypeOfField($field);
                if (in_array($type, array('datetime', 'time', 'date'))) {
                    switch ($type) {
                        case 'datetime':
                            $dt = new DateTime();
                            break;
                        case 'date':
                            $dt = new Date();
                            break;
                        case 'time':
                            $dt = new Time();
                            break;
                    }

                    if (is_int($data[$field])) {
                        $dt->setTimestamp($data[$field]);
                    } elseif (is_string($data[$field])) {
                        $dt->__construct($data[$field]);
                    }

                    $data[$field] = $dt;
                }
            } else {
                unset($data[$field]);
                continue;
            }
        }

        if (!$object instanceof \Doctrine\ORM\Proxy\Proxy) {
            // process associations only if not proxied to prevent mahoosive nesting issues
            $assocs = $metadata->getAssociationNames();
            foreach($assocs as $assoc) {
                $target = $metadata->getAssociationTargetClass($assoc);
                $value = (isset($data[$assoc])) ? $data[$assoc] : null;
                switch ($metadata->getAssociationMapping($assoc)['type']) {
                    case $metadata::ONE_TO_ONE: // 1
                        $data[$assoc] = $this->toOne($value, $target);
                        break;
                    case $metadata::MANY_TO_ONE: // 2
                        // Not hydrating just linking
                        $entity = $this->find($target, $value);
                        $data[$assoc] = $entity;
                        break;
                    case $metadata::ONE_TO_MANY: // 4
                        $data[$assoc] = $this->toMany($value, $target, $object->{$assoc}, $depth);
                        break;
                    case $metadata::MANY_TO_MANY: // 8
                        $data[$assoc] = $this->toMany($value, $target, $object->{$assoc}, $depth);
                        break;
                }
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

        switch (true) {
            case is_object($valueOrObject):
                $id = (isset($valueOrObject->id)) ? $valueOrObject->id : 0;
                break;

            case is_array($valueOrObject):
                $id = (isset($valueOrObject['id']))? $valueOrObject['id'] : 0;
                break;

            default:
                $id = 0;
        }

        if ($id > 0) {
            $entity = $this->find($target, $id);
        } else {
            $entity = new $target();
        }

        return $this->hydrate($valueOrObject, $entity);
    }

    /**
     * @param mixed $valueOrObject
     * @param string $target
     * @return array
     */
    protected function toMany($valueOrObject, $target, Collection $collection = null, $depth = 2)
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
                $value = $value->toArray($depth);
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
