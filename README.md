# Staticize
PHP class to download an entire site as static HTML including all resources and assets

Allows you to pass a domain and a local domain (e.g. http://localhost) that will serve up the downloaded content.

All content will be downloaded in the output folder in a new folder of the domain name.

Download includes: 
* Initial Homepage HTML
* Linking pages HTML
* Any other links referenced from any pages scanned
* All images, css and javascript downloaded that are used


### Usage
```
require_once "config.php";

$staticize = Staticize::init("<DOMAIN>","<LOCAL_DOMAIN>")->download();
```
