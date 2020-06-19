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
     * @Serializer\Type("string")
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
     * @ORM\Column(name="favorite_color", type="string", length=255, nullable=true)
     */
    public $favoriteColor;

    /**
     * @ORM\Column(name="created", type="datetime", nullable=true)
     */
    public $created;

    /**
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     * @Serializer\ReadOnly()
     */
    public $updated;

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

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Lemon\RestBundle\Tests\Fixtures\Tag[]
     * @ORM\ManyToMany(targetEntity="Lemon\RestBundle\Tests\Fixtures\Tag", cascade={"all"})
     * @ORM\JoinTable(name="Person_Tag",
     *     joinColumns={
     *          @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="tag_id", referencedColumnName="id", unique=true)
     *     }
     * )
     */
    public $tags;

    public function __construct()
    {
        $this->cars = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }
}
