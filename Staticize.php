<?php
class Staticize {
    protected $links = [];
    protected $downloaded = [];
    protected $assets = [];
    protected $downloadedAssets = [];
    protected $domain;
    protected $newDomain;
    protected $sitePath;

    protected $downloadAssetLists = [
        'img'       => 'src',
        'link'      => 'href',
        'script'    => 'src'
    ];

    public function init($domain, $newDomain)
    {
        $obj = new self;
        $obj->domain = $domain;
        $obj->newDomain = $newDomain;

        $siteName = str_replace("https://","",$domain);
        $siteName = str_replace("http://","",$siteName);
        $obj->sitePath = OUTPUT . $siteName;

        if(!is_dir($obj->sitePath)){
            mkdir($obj->sitePath);
        }

        return $obj;
    }

    public function getLinks($html){

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $tags = $dom->getElementsByTagName('a');
        $internalLinks = [];
        foreach ($tags as $tag) {
            $pageLink = $tag->getAttribute('href');
            if(strpos($pageLink,$this->domain) !== false) {
                if($pageLink !== $this->domain && $pageLink !== ($this->domain . "/")) {
                    if(!in_array($pageLink,$this->links) && !in_array($pageLink,$this->downloaded)) {
                        $this->links[] = $tag->getAttribute('href');
                    }
                }
            } else {
                if(substr($pageLink,0,1) == "/") {
                    $pageLink = $this->domain . $pageLink;
                    if ($pageLink !== $this->domain && $pageLink !== ($this->domain . "/")) {
                        if (!in_array($pageLink, $this->links) && !in_array($pageLink, $this->downloaded)) {
                            $this->links[] = $pageLink;
                        }
                    }
                }
            }
        }

        $dom = new DOMDocument();
        // Clean HTML to resolve absolute reference URLs
        $html = str_replace('="/','="' . $this->domain . '/',$html);

        @$dom->loadHTML($html);

        foreach($this->downloadAssetLists as $htmlTag => $htmlAttr){
            $this->getAssets($dom,$htmlTag,$htmlAttr);
        }

        return $this;
    }


    public function getAssets($dom, $htmlTag, $type){
        $tags = $dom->getElementsByTagName($htmlTag);
        foreach ($tags as $tag) {
            $pageLink = $tag->getAttribute($type);
            if(strpos($pageLink,$this->domain) !== false) {
                if($pageLink !== $this->domain && $pageLink !== ($this->domain . "/")) {
                    if(!in_array($pageLink,$this->assets) && !in_array($pageLink,$this->downloadedAssets)) {
                        $this->assets[] = $tag->getAttribute($type);
                    }
                }
            }
        }

        asort($this->assets);

        return $this;
    }


    public function downloadPages(){
        if($this->links){
            foreach($this->links as $key => $link){
                if(!in_array($link,$this->downloaded)) {
                    $path = str_replace($this->domain, "", $link);
                    $linkPath = $this->sitePath . $path;
                    if (!is_dir($linkPath)) {
                        mkdir($linkPath, 0777, true);
                    }

                    $html = file_get_contents($link, false);
                    $this->getLinks($html);
                    $html = str_replace($this->domain,$this->newDomain,$html);
                    file_put_contents($linkPath . "/index.html", $html);
                    $this->downloaded[] = $link;
                    echo "Downloaded: " . $link . "\n";

                }
                unset($this->links[$key]);
            }

            if($this->links){
                $this->downloadPages();
            }
        }

    }

    public function downloadAssets(){
        if($this->assets){
            foreach($this->assets as $key => $link){
                if(!in_array($link,$this->downloadedAssets)) {

                    $path = str_replace($this->domain, "", $link);
                    $linkPath = $this->sitePath . $path;

                    $paths = explode("/",$path);
                    unset($paths[(count($paths) - 1)]);
                    unset($paths[0]);
                    $assetPath = $this->sitePath . "/" . implode("/",$paths);

                    if (!is_dir($assetPath)) {
                        mkdir($assetPath, 0777, true);
                    }

                    $html = file_get_contents($link, false);
                    $this->getLinks($html);
                    $html = str_replace($this->domain,$this->newDomain,$html);



                    if(strpos($link,".css") !== false) {
                        $reg_exUrl = "/\(.*?\)/";

                        $fileParts = explode("/",$link);
                        $pathReplace = $fileParts[(count($fileParts) - 2)];
                        preg_match_all($reg_exUrl, $html, $matches);
                        if($matches[0]){
                            foreach($matches[0] as $match){
                                $match = str_replace("'","",$match);
                                $match = str_replace("(","",$match);
                                $match = str_replace(")","",$match);
                                $match = str_replace("..",$pathReplace,$match);

                                $this->assets[] = $this->domain . $match;
                            }
                        }
                    }

                    $linkPath = preg_replace('/\?.*/', '', $linkPath);
                    if(!file_exists($linkPath)){
                        file_put_contents($linkPath, $html);
                        echo "Downloaded: " . $link . "\n";
                    } else {
                        echo "Already Exists: " . $link . "\n";
                    }

                    $this->downloadedAssets[] = $link;


                }
                unset($this->assets[$key]);
            }

        }

    }

    public function download()
    {
        $html = file_get_contents($this->domain,false);
        $this->getLinks($html);
        $html = str_replace($this->domain,$this->newDomain,$html);
        file_put_contents($this->sitePath . "/index.html",$html);
        echo "Homepage Generated!\n";
        $this->downloadPages();
        $this->downloadAssets();

        // Run again to download any found from within internal css / js
        $this->downloadAssets();

        return $this;
    }
}
