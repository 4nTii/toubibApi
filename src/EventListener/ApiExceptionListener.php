<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();

        // Seulement pour les routes /api
        if (str_starts_with($request->getPathInfo(), '/api')) {
            $exception = $event->getThrowable();

            $data = [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
            $errorCode = 500;

            if ($exception instanceof AuthenticationException) {
                $errorCode = 401;
            } elseif ($exception instanceof AccessDeniedException) {
                $errorCode = 403;
            }

            $response = new JsonResponse($data, $errorCode);
            $event->setResponse($response);
        }
    }
}
