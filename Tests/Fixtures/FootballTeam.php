<?php
namespace Lemon\RestBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class FootballTeam
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Serializer\Type("string")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    public $name;

    /**
     * @ORM\Column(name="league", type="string", length=255, nullable=false)
     * @Serializer\Until("1.0")
     */
    public $league;

    /**
     * @ORM\Column(name="conference", type="string", length=255, nullable=false)
     * @Serializer\Since("1.1")
     */
    public $conference;
}
