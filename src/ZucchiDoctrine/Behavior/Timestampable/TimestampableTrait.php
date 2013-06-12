<?php
/**
 * ZucchiDoctrine (http://zucchi.co.uk)
 *
 * @link      http://github.com/zucchi/ZucchiDoctrine for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zucchi Limited. (http://zucchi.co.uk)
 * @license   http://zucchi.co.uk/legals/bsd-license New BSD License
 */

namespace ZucchiDoctrine\Behavior\Timestampable;

use Gedmo\Mapping\Annotation as Gedmo;
use Zend\Form\Annotation AS Form;

/**
 * Trait to work with Gedmo Timestampable behavior
 *  
 * @author Matt Cockayne <matt@zucchi.co.uk>
 * @package ZucchiDoctrine 
 * @subpackage Behavior
 */
trait TimestampableTrait
{
    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Form\Required(false)
     * @Form\Attributes({"type":"text", "disabled":"disabled"})
     * @Form\Options({
     *     "label":"Created On", 
     *     "bootstrap": {
     *         "help": {
     *             "style": "inline",
     *             "content": "The date and time of creation"
     *         }
     *     }
     * })
     */
    protected $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     * @Form\Required(false)
     * @Form\Attributes({"type":"text", "disabled":"disabled"})
     * @Form\Options({
     *     "label":"Last Updated", 
     *     "bootstrap": {
     *         "help": {
     *             "style": "inline",
     *             "content": "The date and time of the last update"
     *         }
     *     }
     * })
     */
    protected $updatedAt;
}
