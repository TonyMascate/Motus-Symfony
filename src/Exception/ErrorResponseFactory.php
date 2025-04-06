<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ErrorResponseFactory {
    public static function create(string $message, int $statusCode, array $details = []): JsonResponse {
        $responseData = [
            'error' => [
                'message' => $message,
                'code' => $statusCode,
            ]
        ];
        if ($details) {
            $responseData['error']['details'] = $details;
        }
        return new JsonResponse($responseData, $statusCode);
    }
    public static function formatValidationErrors(ConstraintViolationListInterface $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $errorMessages;
    }
}
