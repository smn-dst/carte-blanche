<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class UniqueEmailConstraint extends Constraint
{
    public string $message = 'The email "{{ value }}" is already in use.';
    // Symfony va automatiquement chercher la classe UniqueEmailConstraintValidator
}
