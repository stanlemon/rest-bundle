<?php
namespace Lemon\RestBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table()
 * @ORM\Entity()
 * @MongoDB\Document
 */
class Tag
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @MongoDB\Id
     * @Serializer\Type("string")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @MongoDB\Field(type="string")
     */
    public $name;
}
