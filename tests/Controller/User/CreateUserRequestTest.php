<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Controller\User\Validation\UserRequestValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateUserRequestTest extends TestCase
{
    public function testValidRequestMapsAvatarToApplicationDTO(): void
    {
        $validator = new UserRequestValidator(Validation::createValidator());
        $dto = $validator->validate([
            'username' => 'player', 'email' => 'player@example.com', 'password' => 'secret', 'avatar' => 1,
        ]);
        self::assertSame('player', $dto->username);
        self::assertSame(1, $dto->avatar);
    }

    public function testInvalidInputsHaveFieldViolations(): void
    {
        $validator = new UserRequestValidator(Validation::createValidator());
        $valid = ['username' => 'player', 'email' => 'player@example.com', 'password' => 'secret', 'avatar' => 1];
        $invalidValues = [
            'username' => [null, '', [], 42, str_repeat('a', 51)],
            'email' => [null, '', [], 42, 'invalid', str_repeat('a', 101).'@example.com'],
            'password' => [null, '', [], 42],
            'avatar' => [null, '1', 1.5, [], false, 0, -1],
        ];
        foreach ($invalidValues as $field => $values) {
            foreach ($values as $value) {
                try {
                    $validator->validate(array_replace($valid, [$field => $value]));
                    self::fail('Expected validation to fail for '.$field);
                } catch (ValidationFailedException $exception) {
                    $violation = $exception->getViolations()[0];
                    self::assertNotNull($violation);
                    self::assertSame('['.$field.']', $violation->getPropertyPath());
                }
            }
        }
        try {
            $validator->validate([]);
            self::fail('Expected missing fields to fail validation.');
        } catch (ValidationFailedException $exception) {
            self::assertCount(4, $exception->getViolations());
        }
    }

    /** @param array<string, mixed> $payload */
    #[DataProvider('unexpectedValidatedTypes')]
    public function testRejectsUnexpectedTypesWhenUnderlyingValidatorReportsNoViolations(array $payload): void
    {
        // Simulate an incorrectly configured validator bypassing the real constraints.
        $underlyingValidator = $this->createMock(ValidatorInterface::class);
        $underlyingValidator->expects(self::once())->method('validate')
            ->with($payload, self::isInstanceOf(\Symfony\Component\Validator\Constraints\Collection::class))
            ->willReturn(new ConstraintViolationList());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Validated user fields have unexpected types.');

        (new UserRequestValidator($underlyingValidator))->validate($payload);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function unexpectedValidatedTypes(): iterable
    {
        $valid = ['username' => 'player', 'email' => 'player@example.com', 'password' => 'secret', 'avatar' => 1];
        foreach (['username' => 42, 'email' => [], 'password' => false, 'avatar' => '1'] as $field => $value) {
            yield 'invalid '.$field => [array_replace($valid, [$field => $value])];
            $missing = $valid;
            unset($missing[$field]);
            yield 'missing '.$field => [$missing];
        }
    }
}
