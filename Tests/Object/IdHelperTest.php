<?php
namespace Lemon\RestBundle\Tests\Object;

use Lemon\RestBundle\Object\IdHelper;
use Lemon\RestBundle\Tests\Fixtures\Person;

/**
 * @coversDefaultClass \Lemon\RestBundle\Object\IdHelper
 */
class IdHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getId
     */
    public function testGetId()
    {
        $person = new Person();
        $person->id = 1;

        $this->assertEquals(
            $person->id,
            IdHelper::getId($person)
        );
    }

    /**
     * @covers ::setId
     */
    public function testSetId()
    {
        $person = new Person();

        IdHelper::setId($person, 1);

        $this->assertEquals(
            1,
            $person->id
        );
    }
}
