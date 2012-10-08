<?php
namespace ZucchiDoctrine\Form\Annotation;

use Zend\Form\Annotation\AbstractStringAnnotation;

/**
 * OneToMany annotation
 *
 * @Annotation
 * @package    ZucchiDoctrine
 * @subpackage Form
 */
class OneToMany extends AbstractStringAnnotation
{
    /**
     * Retrieve the composed object classname
     *
     * @return null|string
     */
    public function getReferenceClass()
    {
        return $this->value;
    }
}