<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Form
 */

namespace ZucchiDoctrine\Form\Element;

use Zend\Form\Element\Collection AS ZendCollection;
use Zend\Form\Factory as ZendFactory;
use Zucchi\Form\Factory;

/**
 * @category   Zend
 * @package    Zend_Form
 * @subpackage Element
 */
class Collection extends ZendCollection
{
    /**
     * Compose a form factory to use when calling add() with a non-element/fieldset
     *
     * @param  Factory $factory
     * @return Form
     */
    public function setFormFactory(ZendFactory $factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * Retrieve composed form factory
     *
     * Lazy-loads one if none present.
     *
     * @return Factory
     */
    public function getFormFactory()
    {
        if (null === $this->factory) {
            $this->setFormFactory(new Factory());
        }

        return $this->factory;
    }

    public function bindValues(array $values = array())
    {
        $collection = array();
        foreach ($values as $name => $value) {
            $element = $this->get($name);

            if ($element instanceof FieldsetInterface) {
                $collection[] = $element->bindValues($value);
            } else {
                $collection[] = $value;
            }
        }

        return $collection;
    }
}
