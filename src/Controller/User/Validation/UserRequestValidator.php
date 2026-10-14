<?php

declare(strict_types=1);

namespace App\Controller\User\Validation;

use App\Application\DTO\UserRequestDTO;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UserRequestValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws ValidationFailedException
     */
    public function validate(array $data): UserRequestDTO
    {
        foreach (['username', 'email'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        $violations = $this->validator->validate($data, new Assert\Collection(
            fields: [
                'username' => new Assert\Sequentially([
                    new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 50),
                ]),
                'email' => new Assert\Sequentially([
                    new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 100), new Assert\Email(),
                ]),
                'password' => new Assert\Sequentially([
                    new Assert\NotBlank(), new Assert\Type('string'),
                ]),
                'avatar' => new Assert\Sequentially([
                    new Assert\NotNull(), new Assert\Type('integer'), new Assert\Positive(),
                ]),
            ],
            allowExtraFields: false,
        ));

        if (count($violations) > 0) {
            // Do not retain the payload containing the plaintext password in the exception.
            throw new ValidationFailedException(null, $violations);
        }

        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $avatar = $data['avatar'] ?? null;

        // Narrow validated types for static analysis; only a programming error can reach this branch.
        if (!is_string($username) || !is_string($email) || !is_string($password) || !is_int($avatar)) {
            throw new \LogicException('Validated user fields have unexpected types.');
        }

        return new UserRequestDTO($username, $email, $password, $avatar);
    }
}
