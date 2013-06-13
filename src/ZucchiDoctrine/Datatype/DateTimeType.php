<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */
namespace ZucchiDoctrine\Datatype;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Zucchi\DateTime\DateTime as DateTime;

/**
 * Type that maps an SQL DATETIME/TIMESTAMP to an Extended PHP DateTime object.
 *
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine 
 * @subpackage Datatype
 */
class DateTimeType extends Type
{
    public function getName()
    {
        return Type::DATETIME;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value instanceof \DateTime)
            ? $value->format($platform->getDateTimeFormatString())
            : date($platform->getDateTimeFormatString(), strtotime($value));
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof DateTime) {
            return $value;
        }

        $val = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);
        if ( ! $val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getDateTimeFormatString());
        }
        return $val;
    }
}
