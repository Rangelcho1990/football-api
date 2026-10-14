<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Application\CreateUserHandler;
use App\Controller\User\Validation\UserRequestValidator;
use App\Domain\User\Exception\UserAlreadyExistsException;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidationFailedException;

readonly class UserController
{
    public function __construct(
        private CreateUserHandler $createUserHandler,
        private UserRequestValidator $validator,
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $userRequestDTO = $this->validator->validate($request->toArray());
        } catch (JsonException) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON request body.'], Response::HTTP_BAD_REQUEST);
        } catch (ValidationFailedException $exception) {
            $errors = [];
            foreach ($exception->getViolations() as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')][] = (string) $violation->getMessage();
            }

            return new JsonResponse(['success' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $this->createUserHandler->handler(
                $userRequestDTO,
            );
        } catch (UserAlreadyExistsException $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(['success' => true]);
    }
}
