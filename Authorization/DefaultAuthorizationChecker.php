<?php
namespace Lemon\RestBundle\Authorization;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DefaultAuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @param mixed $attributes
     * @param mixed $object
     * @return bool
     */
    public function isGranted($attributes, $object = null)
    {
        return true;
    }
}
