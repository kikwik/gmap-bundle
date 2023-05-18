<?php

namespace Kikwik\GmapBundle\Twig;

use Kikwik\GmapBundle\Geocodable\GeocodableEntityInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GmapExtension extends AbstractExtension
{
    /**
     * @var string
     */
    private $gmapApiKeyJs;

    public function __construct(string $gmapApiKeyJs)
    {
        $this->gmapApiKeyJs = $gmapApiKeyJs;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('kw_gmap_script_tags', [$this, 'getGmapScriptTags'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_center', [$this, 'getDataAttributeMapCenter'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_zoom', [$this, 'getDataAttributeMapZoom'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_markers', [$this, 'getDataAttributeMapMarkers'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_cluster', [$this, 'getDataAttributeMapCluster'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_remote_markers', [$this, 'getDataAttributeMapRemoteMarkers'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_search_address', [$this, 'getDataAttributeMapSearchAddress'], ['is_safe'=>['html']]),
            new TwigFunction('kw_map_data_street_view', [$this, 'getDataAttributeMapStreetView'], ['is_safe'=>['html']]),
        ];
    }

    private $_gmapInit = <<<'SCRIPT'
<script>
  (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    key: "YOUR_API_KEY_HERE",
    // Add other bootstrap parameters as needed, using camel case.
    // Use the 'v' parameter to indicate the version to load (alpha, beta, weekly, etc.)
  });
</script>
<script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
SCRIPT;

    public function getGmapScriptTags()
    {
        $kwMapVersion = filemtime(__DIR__.'/../Resources/public/kwMap.js');
        $kwAutocompleteVersion = filemtime(__DIR__.'/../Resources/public/kwAutocomplete.js');

        return str_replace('YOUR_API_KEY_HERE',$this->gmapApiKeyJs,$this->_gmapInit)
            .'<script src="/bundles/kikwikgmap/kwMap.js?v='.$kwMapVersion.'"></script>'
            .'<script src="/bundles/kikwikgmap/kwAutocomplete.js?v='.$kwAutocompleteVersion.'"></script>'
            ;
    }

    public function getDataAttributeMapCenter($objectOrLatitude, $longitude = null)
    {
        if($objectOrLatitude instanceof GeocodableEntityInterface)
        {
            $data = [
                'lat' => $objectOrLatitude->getLatitude(),
                'lng' => $objectOrLatitude->getLongitude()
            ];
        }
        elseif(is_float($objectOrLatitude) && is_float($longitude))
        {
            $data = [
                'lat' => $objectOrLatitude,
                'lng' => $longitude
            ];
        }
        else
        {
            throw new \TypeError('Arguments passed to Kikwik\GmapBundle\Twig\GmapExtension::getDataAttributeMapCenter() must be one object that implement interface Kikwik\GmapBundle\Geocodable\GeocodableEntityInterface or two floats');
        }

        return 'data-map-center="'.htmlspecialchars(json_encode($data)).'"';
    }

    public function getDataAttributeMapZoom(int $value)
    {
        return 'data-map-zoom="'.$value.'"';
    }

    public function getDataAttributeMapMarkers(array $entities)
    {
        $data = [];
        foreach($entities as $entity)
        {
            if($entity->isGeocoded())
            {
                $data[] = [
                    'lat' => $entity->getLatitude(),
                    'lng' => $entity->getLongitude(),
                    'info' => $entity->getInfoWindowContent(),
                    'icon' => $entity->getMarkerIcon(),
                    'id' => $entity->getId()
                ];
            }
        }
        return 'data-map-markers="'.htmlspecialchars(json_encode($data)).'"';
    }

    public function getDataAttributeMapCluster(array $options = [])
    {
        return 'data-map-cluster="'.htmlspecialchars(json_encode($options)).'"';
    }

    public function getDataAttributeMapRemoteMarkers(string $url)
    {
        return 'data-map-remote-markers="'.$url.'"';
    }

    public function getDataAttributeMapSearchAddress(string $addressSelector, string $submitSelector, int $zoom = 13)
    {
        return 'data-map-search-address="'.$addressSelector.'" data-map-search-submit="'.$submitSelector.'" data-map-search-zoom="'.$zoom.'"';
    }

    public function getDataAttributeMapStreetView(string $selector, $objectOrLatitude, $longitude = null)
    {
        if($objectOrLatitude instanceof GeocodableEntityInterface)
        {
            $data = [
                'lat' => $objectOrLatitude->getLatitude(),
                'lng' => $objectOrLatitude->getLongitude()
            ];
        }
        elseif(is_float($objectOrLatitude) && is_float($longitude))
        {
            $data = [
                'lat' => $objectOrLatitude,
                'lng' => $longitude
            ];
        }
        else
        {
            throw new \TypeError('Arguments passed to Kikwik\GmapBundle\Twig\GmapExtension::getDataAttributeMapStreetView() must be one object that implement interface Kikwik\GmapBundle\Geocodable\GeocodableEntityInterface or two floats');
        }

        return 'data-map-street-view="'.$selector.'" data-map-street-view-position="'.htmlspecialchars(json_encode($data)).'"';
    }

}