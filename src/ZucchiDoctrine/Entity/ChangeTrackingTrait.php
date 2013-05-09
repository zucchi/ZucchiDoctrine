<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abstract Entity 
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine 
 * @subpackage Entity
 */
trait ChangeTrackingTrait
{
    private $cleanData = array();
    
    /**
     * @ORM\PostLoad
     */
    public function prepareCleanData()
    {
        if (empty($this->cleanData)) {
            $this->cleanData = $this->toArray(false);
        }
    }
    
    /**
     * Function to retrive the changed fields of an entity
     * 
     * @param bool $original retrieve the original values 
     * @return array of changed values
     */
    public function getChanges($original = false)
    {
        $a = $original ? $this->cleanData : $this->toArray(false);
        $b = $original ? $this->toArray(false) : $this->cleanData;
        return array_udiff_assoc($a, $b, function($a, $b) {

            if (is_array($a) || is_array($b)) {
                return 0;
            } 
            
            if ($a !== $b){
                return 1;
            }

            return 0;
        });
    }
    
    /**
     * Test if a specific field has changed
     * 
     * @param string $field
     * @return boolean
     */
    public function isChanged($field = null)
    {
        $changes = $this->getChanges();
        if (null == $field && count($changes)) {
            return true;
        } 
        
        if (isset($changes[$field])) {
            return true;
        }
        
        return false;
    }
}