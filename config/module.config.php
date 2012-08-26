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
    ),
);