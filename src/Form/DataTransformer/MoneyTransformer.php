<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<mixed, string>
 */
final class MoneyTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        return (string) $value;
    }

    public function reverseTransform(mixed $value): ?float
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $cleaned = str_replace([' ', "\u{00A0}", "\u{202F}"], '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);

        if (!is_numeric($cleaned)) {
            throw new TransformationFailedException(sprintf('La valeur "%s" n\'est pas un montant valide.', $value), 0, null, 'Veuillez entrer un montant valide (ex : 50000 ou 50 000,50).');
        }

        return (float) $cleaned;
    }
}
