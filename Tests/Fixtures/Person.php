<?php
namespace Lemon\RestBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Person
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    public $name;

    /**
     * @ORM\Column(name="ssn", type="string", length=255, nullable=true)
     * @Serializer\ReadOnly()
     */
    public $ssn;

    /**
     * @var \Lemon\RestBundle\Tests\Fixtures\Person
     * @ORM\OneToOne(targetEntity="Lemon\RestBundle\Tests\Fixtures\Person", cascade={"all"})
     */
    public $mother;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Lemon\RestBundle\Tests\Fixtures\Car[]
     * @ORM\OneToMany(
     *  targetEntity="Lemon\RestBundle\Tests\Fixtures\Car",
     *  mappedBy="person",
     *  cascade={"all"}
     * )
     */
    public $cars;

    public function __construct()
    {
        $this->cars = new ArrayCollection();
    }
}
