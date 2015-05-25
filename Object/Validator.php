<?php
namespace Lemon\RestBundle\Object;

use Lemon\RestBundle\Object\Exception\InvalidException;
use Symfony\Component\Validator\ValidatorInterface;

class Validator
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param object $object
     */
    public function validate($object)
    {
        $flattenedErros = array();

        $errors = $this->validator->validate($object);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $flattenedErros[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new InvalidException("Object is invalid", $flattenedErros);
        }
    }
}
