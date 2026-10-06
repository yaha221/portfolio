<?php

declare(strict_types=1);

namespace App\Validation;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * 422 for checks that need the database (unique slug, existing technology).
 * Rendered by ApiExceptionListener exactly like constraint violations from request DTOs.
 */
final class InvalidRequest
{
    public static function at(string $propertyPath, string $message, mixed $invalidValue = null): UnprocessableEntityHttpException
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation($message, $message, [], null, $propertyPath, $invalidValue),
        ]);

        return new UnprocessableEntityHttpException($message, new ValidationFailedException($invalidValue, $violations));
    }
}
