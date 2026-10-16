<?php

declare(strict_types=1);

namespace App\Tests\Controller\User;

use App\Application\User\CreateUserHandler;
use App\Controller\User\UserController;
use App\Controller\User\Validation\UserRequestValidator;
use App\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;

final class UserControllerTest extends TestCase
{
    public function testInvalidPayloadReturnsFieldErrorsWithoutCreatingUser(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::never())->method('createUser');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $controller = new UserController(
            new CreateUserHandler($repository, $hasher),
            new UserRequestValidator(Validation::createValidator()),
        );

        foreach (['{}', '{"username":[],"email":"invalid","password":false,"avatar":"1"}'] as $payload) {
            $response = $controller(new Request(content: $payload));
            self::assertSame(422, $response->getStatusCode());
            $content = $response->getContent();
            self::assertIsString($content);
            $body = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($body);
            self::assertFalse($body['success']);
            self::assertIsArray($body['errors']);
            self::assertSame(['username', 'email', 'password', 'avatar'], array_keys($body['errors']));
        }
    }
}
