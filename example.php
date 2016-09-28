<?php
require_once "config.php";


$staticize = Staticize::init("https://forpaws.info","http://static.app")->download();
print_r($staticize);
die;

?>