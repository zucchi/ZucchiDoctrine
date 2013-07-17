<?php
return array(
    'doctrine' => array(
        'configuration' => array(
            'orm_default' => array(
                'stringFunctions' => array(
                    'REGEXP' => 'ZucchiDoctrine\Query\Mysql\Regexp',
                ),
                'types' => array(
                    'datetime' => 'ZucchiDoctrine\Datatype\DateTimeType',
                    'date' => 'ZucchiDoctrine\Datatype\DateType',
                    'time' => 'ZucchiDoctrine\Datatype\TimeType',
                    'money' => 'ZucchiDoctrine\Datatype\MoneyType',
                    'image' => 'ZucchiDoctrine\Datatype\ImageType',
                ),
            ),
        ),
        'connection' => array(
            'orm_default' => array(
                'doctrine_type_mappings' => array(
                    'money' => 'money',
                    'image' => 'image',
                    'date' => 'date',
                    'time' => 'time',
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
//                    'Gedmo\Translatable\TranslatableListener',
                    'Gedmo\Tree\TreeListener',
                    'Gedmo\Uploadable\UploadableListener',
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'invokables' => array(
            'zucchidoctrine.listener' => 'ZucchiDoctrine\Event\DoctrineListener',
            'zucchidoctrine.entityhydrator' => 'ZucchiDoctrine\Hydrator\DoctrineEntity'
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            // generic view helpers
            'entityField' => 'ZucchiDoctrine\View\Helper\EntityField',
        ),
    ),
);