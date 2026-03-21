<?php

namespace App\Controller;

use App\Repository\AppConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    public function catchAll(
        AppConfigRepository $appConfigRepository
    ): Response {

        $config = $appConfigRepository->find(1);

        if (!$config) {
            return $this->json(['error' => 'Configuration n\'est pas encore definie'], 404);
        }

        return $this->render('default/index.html.twig', [
            'config' => $config,
        ]);
    }
}
