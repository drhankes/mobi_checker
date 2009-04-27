<?php
Plugin::setInfos(array(
    'id'          => 'mobi_checker',
    'title'       => 'Mobi Checker', 
    'description' => 'Desktop/mobile detection. 2 domains, 1 website, multiple user experience.', 
    'version'     => '1.0.0',
    'author'      => 'David Hankes',
    'website'     => 'http://www.davidhankes.com/',
    'require_frog_version' => '0.9.4')
);

Observer::observe('page_found', 'observe_mobi');

function observe_mobi() {
  define("SWITCHER_DESKTOP_DOMAIN", "www.mydomain.com");
  define("SWITCHER_MOBILE_DOMAIN", "m.mydomain.com");
  require_once 'mobile.php';
}

?>