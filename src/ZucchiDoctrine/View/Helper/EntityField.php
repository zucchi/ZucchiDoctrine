<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\View\Helper;

use Zend\View\Helper\AbstractHelper;
use ZucchiDoctrine\Entity\AbstractEntity;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * return an appropriate value based upon field type
 *
 * @package    ZucchiDoctrine
 * @subpackage View
 * @category Helper
 */
class EntityField extends AbstractHelper
{
    /**
     * Truncate input text
     *
     * @param AbstractEntity $entity
     * @param string $field
     * @param
     * @return mixed
     */
    public function __invoke(AbstractEntity $entity, $field, ClassMetadata $metadata = null)
    {
        if (strpos($field, '.')) {
            list($Assoc, $field) = explode('.',$field, 2);
            return $this->__invoke($entity->{$Assoc}, $field);

        } else {
            if (property_exists('__isInitialised__',$entity) && $entity->__isInitialized__) {
                // entity is an uninitialised proxy, lets load it
                $entity->load();
            }
            if (!isset($entity->{$field})) {
                throw new \RuntimeException('Field "' . $field . '" not found in Entity: ' . get_class($entity));
            }

            $value = $entity->{$field};

            if ($metadata) {
                if ($column = $metadata->getFieldMapping($field)) {
                    switch (strtolower($column['type'])) {
                        case "money":
                            $value = $this->getView()->currencyFormat($value, 'GBP');
                            break;
                        case "datetime":
                            $value = $this->getView()->dateFormat(
                                $value,
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::SHORT
                            );
                            break;
                        case "date":
                            $value = $this->getView()->dateFormat(
                                $value,
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::NONE
                            );
                            break;
                        case "time":
                            $value = $this->getView()->dateFormat(
                                $value,
                                \IntlDateFormatter::NONE,
                                \IntlDateFormatter::SHORT
                            );
                            break;
                        case 'boolean':
                            $value = ($value) ? '<span class="true">yes</span>' : '<span class="false">no</span>';
                            break;
                    }
                }
            }
        }

        return $value;
    }
}