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

    public function testCreatesUserWithHashedPasswordAndTrimmedIdentity(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())->method('createUser')->with(self::callback(
            static function (\App\Domain\User\User $user): bool {
                self::assertSame('demo', $user->getUsername());
                self::assertSame('demo@example.com', $user->getEmail());
                self::assertSame('hashed-password', $user->getPassword());
                self::assertSame(3, $user->getAvatar());
                self::assertTrue($user->isActive());

                return true;
            },
        ));
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')
            ->with(self::callback(static fn (\App\Domain\User\User $user): bool => 'demo' === $user->getUserIdentifier()), ' secret ')
            ->willReturn('hashed-password');
        $controller = new UserController(new CreateUserHandler($repository, $hasher), new UserRequestValidator(Validation::createValidator()));

        $response = $controller(new Request(content: '{"username":" demo ","email":" demo@example.com ","password":" secret ","avatar":3}'));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"success":true}', $response->getContent());
    }

    public function testDuplicateUserReturnsConflict(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())->method('createUser')->willThrowException(new \App\Domain\User\Exception\UserAlreadyExistsException());
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')->willReturn('hash');
        $controller = new UserController(new CreateUserHandler($repository, $hasher), new UserRequestValidator(Validation::createValidator()));

        $response = $controller(new Request(content: '{"username":"demo","email":"demo@example.com","password":"secret","avatar":1}'));
        self::assertSame(409, $response->getStatusCode());
        $content = $response->getContent();
        self::assertIsString($content);
        self::assertSame(['success' => false, 'message' => 'A user with these details already exists.'], json_decode($content, true, flags: JSON_THROW_ON_ERROR));
    }

    public function testMalformedJsonDoesNotCreateUser(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::never())->method('createUser');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $controller = new UserController(new CreateUserHandler($repository, $hasher), new UserRequestValidator(Validation::createValidator()));

        foreach (['', '{', 'null', '"text"'] as $payload) {
            $response = $controller(new Request(content: $payload));
            self::assertSame(400, $response->getStatusCode());
            $content = $response->getContent();
            self::assertIsString($content);
            self::assertSame(['success' => false, 'message' => 'Invalid JSON request body.'], json_decode($content, true, flags: JSON_THROW_ON_ERROR));
        }
    }
}
