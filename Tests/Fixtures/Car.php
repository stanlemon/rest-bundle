<?php
namespace Lemon\RestBundle\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table()
 * @ORM\Entity()
 * @MongoDB\Document
 */
class Car
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @MongoDB\Id
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    public $name;

    /**
     * @ORM\Column(name="year", type="string", length=255, nullable=false)
     */
    public $year;

    /**
     * @ORM\ManyToOne(targetEntity="Lemon\RestBundle\Tests\Fixtures\Person", inversedBy="cars")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    public $person;

    /**
     * @ORM\Column(name="created", type="datetime", nullable=true)
     * @Serializer\ReadOnly()
     */
    public $created;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Lemon\RestBundle\Tests\Fixtures\Place[]
     * @ORM\OneToMany(
     *  targetEntity="Lemon\RestBundle\Tests\Fixtures\Place",
     *  mappedBy="car",
     *  cascade={"all"}
     * )
     */
    public $places;
}
