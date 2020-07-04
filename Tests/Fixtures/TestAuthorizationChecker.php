<?php
namespace Lemon\RestBundle\Tests\Fixtures;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TestAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function isGranted($attributes, $object = null)
    {
        $attributes = (array) $attributes;

        // Disallow PUT on FootballTeam, but allow everything else
        if (in_array('PUT', $attributes) && $object === 'Lemon\RestBundle\Tests\Fixtures\FootballTeam') {
            return false;
        }

        return true;
    }
}
