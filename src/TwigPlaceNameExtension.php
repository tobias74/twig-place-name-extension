<?php

namespace Tobias74;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigPlaceNameExtension extends AbstractExtension
{
    public function __construct($geocoder)
    {
        $this->placesGeocoderClient = $geocoder;
    }

    protected function getPlacesGeocoderClient()
    {
        return $this->placesGeocoderClient;
    }

    public function getPlaceNameInfo($latitude, $longitude)
    {
        try {
            $placeData = json_decode($this->getPlacesGeocoderClient()->get($latitude, $longitude), true);
        } catch (\Exception $e) {
            $placeData = array(
              'address_components' => false,
              'formatted_address' => 'unknown location',
            );
        }

        $addressComponents = $placeData['address_components'];

        $shortPlaceName = '';
        $cityName = '';

        if (is_array($addressComponents)) {
            $sublocalities = array_merge(array_filter($addressComponents, function ($element) {
                return false !== array_search('sublocality', $element['types']);
            }));
            $localities = array_merge(array_filter($addressComponents, function ($element) {
                return false !== array_search('locality', $element['types']);
            }));

            if (isset($localities[0])) {
                $shortPlaceName .= $localities[0]['short_name'];
                $cityName = $localities[0]['short_name'];
            }

            if (isset($sublocalities[0])) {
                $shortPlaceName .= ', '.$sublocalities[0]['short_name'];
            }
        } else {
            $shortPlaceName = '';
        }

        //error_log(print_r($placeData, true));

        return array(
            'city_name' => $cityName,
            'short_place_name' => $shortPlaceName,
            'formatted_address' => $placeData['formatted_address'],
        );
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('shortPlaceName', [$this, 'getShortPlaceName']),
            new TwigFunction('placeName', [$this, 'getPlaceName']),
            new TwigFunction('cityName', [$this, 'getCityName']),
        ];
    }

    public function getCityName($lat, $lon)
    {
        return $this->getPlaceNameInfo($lat, $lon)['city_name'];
    }

    public function getShortPlaceName($lat, $lon)
    {
        return $this->getPlaceNameInfo($lat, $lon)['short_place_name'];
    }

    public function getPlaceName($lat, $lon)
    {
        return $this->getPlaceNameInfo($lat, $lon)['formatted_address'];
    }
}
