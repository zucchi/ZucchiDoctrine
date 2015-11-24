<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\Query;

use Doctrine\ORM\Query\Expr;

/**
 * Query Builder Trait
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine
 * @subpackage Service
 */
trait QueryBuilderTrait
{
    /**
     * The default alias key for queries, 'e' for entity.
     *
     * @var string
     */
    protected $alias = 'e';

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
            // @todo: test and provide security for straight DQL string
            $qb->andWhere($where);

        } elseif (is_array($where) && count($where)) {

            $params = array(); // initialise container to store params

            $whereExp = $this->getParts($qb, $where, $params);
            
            // only add where expression if actually has parts
            if (count($whereExp->getParts())) {
                $qb->where($whereExp);
            }
            
            // set the params from the where clause above
            $qb->setParameters($params);
        }
        
        return $this;
    }


    protected function getParts($qb, $where, &$params = array())
    {
        if (array_key_exists('mode', $where) && $where['mode'] == 'or') {
            $whereExp = $qb->expr()->orX();
        } else {
            $whereExp = $qb->expr()->andX();
        }
        unset($where['mode']); // unset to allow flat iteration for simpler structures

        // index for the parameters
        $i = count($params);

        if (array_key_exists('expressions', $where)) {
            foreach($where['expressions'] as $expression) {
                $part = $this->getParts($qb, $expression, $params);
                $whereExp->add($part);
            }
            unset($where['expressions']); // unset to allow flat iteration for simpler structures
        }

        // set fields to iterate over if no fields property assume using remaining elements in $where
        $fields =  (array_key_exists('fields', $where)) ? $where['fields'] : $where;

        // loop through all the clauses supplied
        foreach ($fields as $col => $val) {

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

            if ($val instanceof Expr\Base) {
                $whereExp->add($val);
            } else {
                // process sets a little differently
                if (!is_array($val)) {
                    $val = array($val);
                }
                switch (strtolower($operator)) {
                    case 'regexp':
                        $whereExp->add("REGEXP(" . $alias . '.' . $col . ",'" . $val[0] . "') = 1");
                        break;
                    case 'between':
                        if (count($val) == 2) {
                            // $value should now be an array with 2 values
                            $expr = new Expr();
                            $from = (is_int($val[0])) ? $val[0] : "'" . $val[0] . "'";
                            $to = (is_int($val[1])) ? $val[1] : "'" . $val[1] . "'";

                            $stmt = $expr->between($alias . '.' . $col, $from, $to);
                            $whereExp->add($stmt);
                        }
                        break;
                    case 'is':
                        $expr = new Expr();
                        $method = 'is' . ucfirst($val[0]);
                        if (method_exists($expr, $method)) {
                            $stmt = $expr->{$method}($alias . '.' . $col);
                            $whereExp->add($stmt);
                        }
                        break;
                    default:
                        // this holds the subquery for this field, each component being an OR
                        $subWhereExp = $qb->expr()->orX();

                        foreach ($val as $value) {
                            if ($value == null) {
                                $cmpValue = 'NULL';
                            } else {
                                $cmpValue = '?' . $i;

                                // wrap IN/NOT IN values with parenthesis
                                if ($operator == 'in' || $operator == 'not in') {
                                    $cmpValue = '(' . trim($cmpValue, ')') . ')';
                                }

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
                        break;

                }
            }
        }

        return $whereExp;

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
