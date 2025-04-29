<?php

namespace App\Twig;

use App\Repository\HotelRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HotelExtension extends AbstractExtension
{
    private $hotelRepository;

    public function __construct(HotelRepository $hotelRepository)
    {
        $this->hotelRepository = $hotelRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getMinRoomPrice', [$this, 'getMinRoomPrice']),
        ];
    }

    public function getMinRoomPrice($hotel): array
    {
        $minPrice = $this->hotelRepository->getMinRoomPrice($hotel);
        
        // If no rooms are available, return null values
        if ($minPrice === null) {
            return [
                'originalPrice' => null,
                'finalPrice' => null,
                'hasPromotion' => false
            ];
        }

        // Calculate final price if there's a promotion
        $finalPrice = $minPrice;
        if ($hotel->getPromotion() > 0) {
            $finalPrice = $this->hotelRepository->getDiscountedPrice($minPrice, $hotel->getPromotion());
        }

        return [
            'originalPrice' => $minPrice,
            'finalPrice' => $finalPrice,
            'hasPromotion' => $hotel->getPromotion() > 0
        ];
    }
} 