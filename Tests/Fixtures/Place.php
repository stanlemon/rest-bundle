<?php
namespace Lemon\RestBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Place
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="Lemon\RestBundle\Tests\Fixtures\Car", inversedBy="places")
     * @ORM\JoinColumn(name="car_id", referencedColumnName="id")
     */
    public $car;
}