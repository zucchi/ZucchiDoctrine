**ZucchiDoctrine**

Custom Extensions to Doctrine 2 ORM for use with Zucchi ZF2 Modules

*Installation*

From the root of your ZF2 Skeleton Application run

    ./composer.phar require zucchi/doctrine
    
Features

* Automatic registration of Gedmo DoctrineExtensions
* Timestampable Trait
* Override of Date/Time Mappings to use Zucchi\DateTime Extended classes
* Abstract Entity Class
* Abstract Servcie Class
* Custom DQL Regex Function
* EntityField View helper for handling output of different entity field types
