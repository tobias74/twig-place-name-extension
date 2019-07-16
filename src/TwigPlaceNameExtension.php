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

        if (is_array($addressComponents)) {
            $sublocalities = array_merge(array_filter($addressComponents, function ($element) {
                return false !== array_search('sublocality', $element['types']);
            }));
            $localities = array_merge(array_filter($addressComponents, function ($element) {
                return false !== array_search('locality', $element['types']);
            }));

            $shortPlaceName = '';
            if (isset($localities[0])) {
                $shortPlaceName .= $localities[0]['short_name'];
            }
            if (isset($sublocalities[0])) {
                $shortPlaceName .= ', '.$sublocalities[0]['short_name'];
            }
        } else {
            $shortPlaceName = '';
        }

        $placeData['short_place_name'] = $shortPlaceName;

        return $placeData;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('shortPlaceName', [$this, 'getShortPlaceName']),
            new TwigFunction('placeName', [$this, 'getPlaceName']),
        ];
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
