<?php

namespace App\Controller;

use App\Repository\AppConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AppConfigController extends AbstractController
{
    public function status(AppConfigRepository $appConfigRepository): JsonResponse
    {
        $config = $appConfigRepository->find(1);

        if (!$config) {
            return $this->json([
                'status' => false,
                'message' => 'Le four a pris feu!'
            ], 500);
        }

        if ($config->isMaintenance()) {
            return $this->json([
                'status' => false,
                'message' => 'En cours de maintenance'
            ], 503);
        }

        return $this->json([
            'status' => true,
            'message' => 'Toubib API est disponible',
            'response' => [
                'appName' => $config->getAppName(),
                'version' => $config->getVersion(),
                'env' => $this->getParameter('kernel.environment'),
                'logo' => $config->getLogo(),
                'maintenance' => $config->isMaintenance(),
                'updatedAt' => $config->getUpdateAt()?->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

    public function catchAllApi(Request $request): JsonResponse
    {
        return $this->json([
            'status' => false,
            'message' => 'Route not found `' . $request->getRequestUri() . '` is not a valid route'
        ], 404);
    }
}
