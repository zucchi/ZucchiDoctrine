<?php
return array(
    'doctrine' => array(
        'configuration' => array(
            'orm_default' => array(
                'stringFunctions' => array(
                    'REGEXP' => 'ZucchiDoctrine\Query\Mysql\Regexp',
                ),
            ),
        ),
        'eventmanager' => array(
            'orm_default' => array(
                'subscribers' => array(
                    'Gedmo\Loggable\LoggableListener',
                    'Gedmo\Sluggable\SluggableListener',
                    'Gedmo\SoftDeleteable\SoftDeleteableListener',
                    'Gedmo\Timestampable\TimestampableListener',
                    'Gedmo\Translatable\TranslatableListener',
                    'Gedmo\Tree\TreeListener',
                    'Gedmo\Uploadable\UploadableListener',
                ),
            ),
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            // generic view helpers
            'entityField' => 'ZucchiDoctrine\View\Helper\EntityField',
        ),
    ),
);