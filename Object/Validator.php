<?php
namespace Lemon\RestBundle\Object;

use Lemon\RestBundle\Object\Exception\InvalidException;

class Validator
{
    /**
     * This interface changed from Symfony 2.3 to 2.5, but we only rely upon the first parameter - which
     * is the same between version.
     *
     * @var Symfony\Component\Validator\ValidatorInterface\ValidatorInterface|Symfony\Component\Validator\Validaotr\ValidatorInterface\ValidatorInterface
     */
    protected $validator;

    /**
     * @param Symfony\Component\Validator\ValidatorInterface\ValidatorInterface|Symfony\Component\Validator\Validaotr\ValidatorInterface\ValidatorInterface $validator
     */
    public function __construct($validator)
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

            throw new InvalidException("An error occured.\n".implode(" \n", $flattenedErros), $flattenedErros);
        }
    }
}
