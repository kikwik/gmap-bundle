<?php

namespace Kikwik\GmapBundle\Twig;

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
SCRIPT;

    public function getGmapScriptTags()
    {
        return str_replace('YOUR_API_KEY_HERE',$this->gmapApiKeyJs,$this->_gmapInit).'<script src="bundles/kikwikgmap/kwMap.js"></script>';
    }
}