<?php
namespace ZucchiDoctrine\Mapping\Driver;

use Doctrine\ORM\Mapping\Driver;

/**
 * The ArrayDriver reads the mapping metadata from php array files.
 *
 */
class ArrayDriver extends YamlDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.php';

    /**
     * {@inheritDoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        return include $file;
    }
}
