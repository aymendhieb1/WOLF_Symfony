<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CityImageExtension extends AbstractExtension
{
    private $cache = [];
    
    // Predefined city images from Wikimedia Commons
    private const CITY_IMAGES = [
        'Paris' => [
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg/800px-La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg/400px-La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/85/Notre-Dame_de_Paris_2013-07-24.jpg/800px-Notre-Dame_de_Paris_2013-07-24.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/85/Notre-Dame_de_Paris_2013-07-24.jpg/400px-Notre-Dame_de_Paris_2013-07-24.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Paris_-_Eiffelturm_und_Marsfeld2.jpg/800px-Paris_-_Eiffelturm_und_Marsfeld2.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Paris_-_Eiffelturm_und_Marsfeld2.jpg/400px-Paris_-_Eiffelturm_und_Marsfeld2.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
        ],
        'London' => [
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/London_Skyline_%28125508655%29.jpeg/800px-London_Skyline_%28125508655%29.jpeg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/London_Skyline_%28125508655%29.jpeg/400px-London_Skyline_%28125508655%29.jpeg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/London_Big_Ben_Phone_box.jpg/800px-London_Big_Ben_Phone_box.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/82/London_Big_Ben_Phone_box.jpg/400px-London_Big_Ben_Phone_box.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/London_Skyline_%28125508655%29.jpeg/800px-London_Skyline_%28125508655%29.jpeg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/London_Skyline_%28125508655%29.jpeg/400px-London_Skyline_%28125508655%29.jpeg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
        ],
        'New York' => [
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/New_york_times_square-terabass.jpg/800px-New_york_times_square-terabass.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/New_york_times_square-terabass.jpg/400px-New_york_times_square-terabass.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg/800px-View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg/400px-View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg/800px-View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7a/View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg/400px-View_of_Empire_State_Building_from_Rockefeller_Center_New_York_City_dllu.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
        ],
        'Tokyo' => [
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Skyscrapers_of_Shinjuku_2009_January.jpg/800px-Skyscrapers_of_Shinjuku_2009_January.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b2/Skyscrapers_of_Shinjuku_2009_January.jpg/400px-Skyscrapers_of_Shinjuku_2009_January.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Tokyo_Tower_night_view.jpg/800px-Tokyo_Tower_night_view.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Tokyo_Tower_night_view.jpg/400px-Tokyo_Tower_night_view.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Tokyo_Tower_night_view.jpg/800px-Tokyo_Tower_night_view.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/88/Tokyo_Tower_night_view.jpg/400px-Tokyo_Tower_night_view.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
        ],
        'Rome' => [
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Colosseum_in_Rome%2C_Italy_-_April_2007.jpg/800px-Colosseum_in_Rome%2C_Italy_-_April_2007.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Colosseum_in_Rome%2C_Italy_-_April_2007.jpg/400px-Colosseum_in_Rome%2C_Italy_-_April_2007.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Colosseum_in_Rome%2C_Italy_-_April_2007.jpg/800px-Colosseum_in_Rome%2C_Italy_-_April_2007.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Colosseum_in_Rome%2C_Italy_-_April_2007.jpg/400px-Colosseum_in_Rome%2C_Italy_-_April_2007.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Colosseum_in_Rome%2C_Italy_-_April_2007.jpg/800px-Colosseum_in_Rome%2C_Italy_-_April_2007.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Colosseum_in_Rome%2C_Italy_-_April_2007.jpg/400px-Colosseum_in_Rome%2C_Italy_-_April_2007.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
        ],
        'default' => [
            ['url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg/800px-La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg',
             'thumb' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg/400px-La_Tour_Eiffel_vue_de_la_Tour_Saint-Jacques%2C_Paris_ao%C3%BBt_2014_%282%29.jpg',
             'credit' => ['name' => 'Wikimedia Commons', 'link' => 'https://commons.wikimedia.org']],
        ]
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('city_images', [$this, 'getCityImages']),
        ];
    }

    public function getCityImages(string $city): array
    {
        // Check cache first
        if (isset($this->cache[$city])) {
            return $this->cache[$city];
        }

        // Normalize city name (remove accents, convert to title case)
        $normalizedCity = $this->normalizeCityName($city);
        
        // Get images for the city or use default
        $images = self::CITY_IMAGES[$normalizedCity] ?? self::CITY_IMAGES['default'];
        
        // Cache the results
        $this->cache[$city] = $images;
        
        return $images;
    }

    private function normalizeCityName(string $city): string
    {
        // Convert to title case
        $city = ucwords(strtolower($city));
        
        // Remove accents
        $city = transliterator_transliterate('Any-Latin; Latin-ASCII', $city);
        
        return $city;
    }
} 