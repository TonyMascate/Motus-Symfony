<?php

// src/EventListener/ExceptionListener.php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        $response = new JsonResponse([
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            ]
        ]);

        $event->setResponse($response);
    }
}
