<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueEmailConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmailConstraint) {
            throw new UnexpectedTypeException($constraint, UniqueEmailConstraint::class);
        }
        // Si la valeur est vide, on laisse les contraintes NotBlank et Email faire leur travail
        if (null === $value || '' === $value) {
            return;
        }

        // On cherche si un utilisateur possède déjà cet email
        $existingUser = $this->userRepository->findOneBy(['email' => $value]);
        if ($existingUser) {
            // L'email existe déjà, on déclenche l'erreur sur le champ du formulaire
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
