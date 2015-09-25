<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\Service;

use Zend\EventManager\EventManagerAwareInterface;

use ZucchiDoctrine\Entity\AbstractEntity;
use ZucchiDoctrine\EntityManager\EntityManagerAwareTrait;
use ZucchiDoctrine\Query\QueryBuilderTrait;
use Zucchi\Event\EventProviderTrait as EventProvider;
use Zucchi\ServiceManager\ServiceManagerAwareTrait;


use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\DBAL\LockMode;

/**
 * Abstract Service
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @author Rick Nicol <rick@zucchi.co.uk>
 * @package ZucchiDoctrine
 * @subpackage Service
 */
class AbstractService implements EventManagerAwareInterface
{
    use EventProvider;
    use EntityManagerAwareTrait;
    use ServiceManagerAwareTrait;
    use QueryBuilderTrait;
    
    /**
	 * Default offset value for index method.
     *
	 * @var integer
	 */
	const INDEX_OFFSET = 0;
	
	/**
	 * Default limit for index method.
     *
	 * @var integer
	 */
	const INDEX_LIMIT = 25;
	
    /**
     * Qualified name of entity to work with.
     *
     * @var string
     */
    protected $entityName;
    
    /**
     * The identifying field for the entity.
     *
     * @var string
     */
    protected $identifier = 'id';
    
    /**
     * Get the metadata for the defined entity.
     *
     * @return ClassMetadata
     * @throws \RuntimeException
     */
    public function getMetadata()
    {
        if (!$this->entityName) {
            throw new \RuntimeException('No Entity defined for ' . get_called_class() . ' service');
        }
        
        $data = $this->getEntityManager()->getClassMetadata($this->entityName);
        
        return $data;
    }
    
    /**
     * Get a new instance of the entity.
     * 
     * @return AbstractEntity
     */
    public function getEntity()
    {
        $class = $this->entityName;
        return new $class();
    }

    /**
     * get count of entities
     *
     * @param array $where
     * @param array $order
     * @return int
     */
    public function getCount(
        $where = array()
    ){
        if (!$this->entityName) {
            throw new \RuntimeException('No Entity defined for ' . get_called_class() . ' service');
        }

        $hydrate = \Doctrine\ORM\Query::HYDRATE_ARRAY;

        $em = $this->entityManager;
        $qb = $em->createQueryBuilder();
        $qb->select('count(' . $this->alias . ') as total')
            ->from($this->entityName, $this->alias);

        $this->addWhere($qb, $where);

        $result = $qb->getQuery()->getSingleScalarResult();
        return $result;
    }

    /**
     * Get a list of entities.
     * 
     * @param array $where
     * @param array $order
     * @param int $limit
     * @param int $offset
     * @param int $hydrate
     * @param array $options
     * @return mixed
     * @throws \RuntimeException
     */
    public function getList(
        $where = array(), 
        $order = array(),
        $offset = self::INDEX_OFFSET,
        $limit = self::INDEX_LIMIT,
        $hydrate = \Doctrine\ORM\Query::HYDRATE_OBJECT,
        array $options = array()
    ){
        
        if (!$this->entityName) {
            throw new \RuntimeException('No Entity defined for ' . get_called_class() . ' service');
        }
        
        // allow for hydration to be set to null
        if ($hydrate == null) {
            $hydrate = \Doctrine\ORM\Query::HYDRATE_OBJECT;
        }
        
        $em = $this->entityManager;
        $qb = $em->createQueryBuilder();
        $qb->select($this->alias)
           ->from($this->entityName, $this->alias);
        
        $this->addWhere($qb, $where)
             ->addOrder($qb, $order)
             ->addLimit($qb, $limit, $offset);
        
        $result = $qb->getQuery()->getResult($hydrate);
        return $result;
    }
    
    /**
     * Get a a specific entity.
     *
     * @param int $id
     * @return mixed
     * @throws \RuntimeException
     */
    public function get($id)
    {
        if (!$this->entityName) {
            throw new \RuntimeException('No Entity defined for ' . get_called_class() . ' service');
        }

        $result = $this->entityManager->find($this->entityName, $id, LockMode::OPTIMISTIC);
        return $result;
    }

    /**
     * Refresh entity.
     *
     * @param AbstractEntity $entity
     * @return $this
     */
    public function refresh(AbstractEntity $entity)
    {
        $this->entityManager->refresh($entity);
        $metaData = $this->entityManager->getClassMetaData($this->entityName);
        $mappings = $metaData->getAssociationMappings();

        foreach ($mappings as $mapping) {
            $fieldName = $mapping['fieldName'];
            $assoc = $entity->{$fieldName};

            if ($assoc instanceof \Traversable) {
                foreach($assoc as $ent) {
                    if ($ent instanceof AbstractEntity) {
                        $this->entityManager->refresh($ent);
                    }
                }
            } else if ($assoc instanceof AbstractEntity) {
                $this->entityManager->refresh($assoc);
            }
        }

        return $this;
    }
    
    /**
     * Save the supplied entity.
     *
     * @param AbstractEntity $entity
     * @return AbstractEntity
     */
    public function save(AbstractEntity $entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }
    
    /**
     * Delete the specified entities by id.
     *
     * @param integer|string|array $id
     * @return integer the number of rows affected
     */
    public function remove($id)
    {
        if (!$id) {
            return 0;
        }

        if ($id instanceof AbstractEntity) {
            $this->entityManager->remove($id);
            $result =  $this->entityManager->flush();
        } else {

            if (!is_array($id)) {
                $id = array($id);
            }

            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete($this->entityName, $this->alias)
               ->where($this->alias . '.' . $this->identifier . ' IN (:ids)')
               ->setParameter('ids', $id);

            $result = $qb->getQuery()->execute();
        }
        
        return $result;
    }

    /**
     * Build where statement and add to the query builder.
     * 
     * @param \Doctrine\Orm\QueryBuilder $qb
     * @param mixed $where
     * @return $this
     */
    protected function addWhere($qb, $where) 
    {
        // process the $where
        if (is_string($where)) {
            // straight DQL string
            $qb->andWhere($where);
        } elseif (is_array($where) && count($where)) {
            // create where expression
            $whereExp = $qb->expr()->andx();
            $params = array();
            
            // index for the parameters
            $i = 0;
            
            // loop through all the clauses supplied
            foreach ($where as $col => $val) {
                
                if ((is_array($val) && (!isset($val['value']) || (is_string($val['value']) && strlen($val['value']) == 0))) ||
                     (is_string($val) && (!$val || strlen($val) == 0))
                ){
                    // skip if invalid value;
                    continue;
                }
                
                // check if we've been provided with an operator as well as a value
                if (!is_array($val)) {
                    $operator = Expr\Comparison::EQ;
                    $val = $val;
                } elseif (count($val) == 1) {
                    $operator = Expr\Comparison::EQ;
                    $val = end($val);
                } else {
                    $operator = isset($val['operator']) ? $val['operator'] : Expr\Comparison::EQ;
                    $val = array_key_exists('value', $val) ? $val['value'] : array();
                    
                }
                
                // set the alias to the default
                $alias = $this->alias;

                // if col relates to a relation i.e. Role.id
                // then perform a join and set up the alias and column names
                if (strpos($col, '.') !== false) {
                    $parts = explode('.', $col);
                    $col = array_pop($parts);
                    $par = $this->alias;
                    
                    foreach ($parts AS $rel) {
                        $alias = strtolower($rel);
                        $jt = new Expr\Join(Expr\Join::LEFT_JOIN, $par . '.' . $rel, $alias);
                        if (!strpos($qb->getDql(), $jt->__toString()) !== false) {
                            $qb->leftJoin($par . '.' . $rel, $alias);    
                        }    
                        $par = $alias;
                    }
                }
                
                // process sets a little differently
                if (!is_array($val)) {
                    $val = array($val);
                }
                
                if ($operator == 'regexp') {
                    $whereExp->add("REGEXP(" . $alias . '.' . $col . ",'" . $val[0] . "') = 1");
                    
                } else if ($operator == 'between') {
                    if (count($val) == 2) {
                        // $value should now be an array with 2 values
                        $expr= new Expr();
                        $from = (is_int($val[0])) ? $val[0] : "'" . $val[0] . "'";
                        $to = (is_int($val[1])) ? $val[1] : "'" . $val[1] . "'";
                        
                        $stmt = $expr->between($alias . '.' . $col, $from, $to);
                        $whereExp->add($stmt);
                    }

                } else if ($operator == 'is') {
                    $expr= new Expr();
                    $method = 'is' . ucfirst($val[0]);
                    if (method_exists($expr, $method)) {
                        $stmt = $expr->{$method}($alias . '.' . $col);
                        $whereExp->add($stmt);
                    }

                } else {
                    // this holds the subquery for this field, each component being an OR
                    $subWhereExp = $qb->expr()->orX();
                    
                    foreach ($val as $value) {
                        if ($value == null) {
                            $cmpValue = 'NULL';
                        } else {
                            $cmpValue = '?' . $i;
                            
                            // wrap LIKE values
                            if ($operator == 'like') {
                                $value = '%' . trim($value, '%') . '%';
                            }
    
                            // add the parameter value into the parameters stack
                            $params[$i] = $value;
                            $i++;
                        }
                            
                        $comparison = new Expr\Comparison($alias . '.' . $col, $operator, $cmpValue);
                        $subWhereExp->add($comparison);
                    }
                    
                    // add in the subquery as an AND
                    $whereExp->add($subWhereExp);
                }
            }
            
            // only add where expression if actually has parts
            if (count($whereExp->getParts())) {
                $qb->where($whereExp);
            }
            
            // set the params from the where clause above
            $qb->setParameters($params);
        }
        
        return $this;
    }
    
    /**
     * Build and add an oder field to the query builder.
     * 
     * @param \Doctrine\Orm\QueryBuilder $qb
     * @param mixed $order
     * @return Gmg_Service_Abstract
     */
    protected function addOrder($qb, $order)
    {
        // add the where expression to the query
        // process the $order
        if (is_string($order)) {
            // straight DQL string
            $qb->orderBy($order);
            
        } elseif (is_array($order) && count($order)) {
            // loop through each order clause supplied
            foreach ($order as $col => $dir) {
                // set the alias to the default
                $alias = $this->alias;

                // if col relates to Relation i.e. Role.id
                // then set up the alias and column names (the join should have
                // already been performed in the $where)
                // TODO: this will cause an error if the column wasn't specified in the $where
                if (strpos($col, '.') !== false) {
                    $parts = explode('.', $col);
                    $col = array_pop($parts);
                    $par = $this->alias;
                    
                    // test for existing joins
                    $as = array();
                    foreach($qb->getDQLPart('join') AS $j) {
                        $as[] = $j;
                    }
                    
                    foreach ($parts AS $rel) {
                        $alias = strtolower($rel);
                        $jt = new Expr\Join(Expr\Join::LEFT_JOIN, $par . '.' . $rel, $alias);
                        if (!strpos($qb->getDql(), $jt->__toString()) !== false) {
                            $qb->leftJoin($par . '.' . $rel, $alias);    
                        }                        
                        $par = $alias;
                    }
                }
                $qb->addOrderBy($alias . '.' . $col, $dir);    
            }
        }
        
        return $this;
    }
    
    /**
     * Build and add a limit and offset for the query builder.
     * 
     * @param \Doctrine\Orm\QueryBuilder $qb
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    protected function addLimit($qb, $limit, $offset) 
    {
        // add the limit and offset
        if ($limit) {
            $qb->setMaxResults($limit);
            
            if ($offset) {
                $qb->setFirstResult($offset);
            }
        }    
        return $this;
    }
}
