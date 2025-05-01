<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CurrencyService;

class CurrencyController extends AbstractController
{
    #[Route('/currency', name: 'app_currency')]
    public function index(CurrencyService $currencyService): Response
    {
        $rates = $currencyService->getExchangeRates();

        return $this->render('currency/index.html.twig', [
            'rates' => $rates,
        ]);
    }
}
