ZucchiDoctrine
==============

Custom Extensions to Doctrine 2 ORM for use with Zucchi ZF2 Modules

Installation
------------

From the root of your ZF2 Skeleton Application run

    ./composer.phar require zucchi/doctrine
    
Features
--------

*    Automatic registration of Gedmo DoctrineExtensions
*    Timestampable Trait
*    Override of Date/Time Mappings to use Zucchi\DateTime Extended classes
*    Abstract Entity Class
*    Abstract Servcie Class
*    Custom DQL Regex Function
*    EntityField View helper for handling output of different entity field types
*    One To Many Form Population via Annotations
*    EntityManagerAware Interface and Trait

OneToMany Annotation Example
----------------------------

This example comes directly from the **ZucchiLayout** Module

<pre>
    /**
     * @var PersistantCollection
     * @ORM\OneToMany(targetEntity="ZucchiLayout\Entity\Schedule", mappedBy="Layout")
     * @Form\Type("Zend\Form\Element\Collection")
     * @Form\Options({
     *     "label" : "Schedule",
     *     "count" : 2,
     *     "should_create_template" : true,
     *     "allow_add" : true,
     *     "allow_remove" : true,
     *     "target_element" : {
     *          "composedObject" : "ZucchiLayout\Entity\Schedule"
     *      }
     * })
     */
    public $Schedule;
</pre>