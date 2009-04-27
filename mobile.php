<?php

// James Pearce,
// CTO, dotMobi
// 8th December 2008

// This sample implmentation is code that accompanies the article:
// http://mobiforge.com/designing/story/a-very-modern-mobile-switching-algorithm-part-ii
//
// This sample code is provided on an Òas isÓ basis, without warranty of any
// kind, to the fullest extent permitted by law.
//
// MTLD Top Level Domain Ltd does not warrant or guarantee the individual
// success developers may have in implementing the sample code on their
// development platforms or in using their own web server configurations.


/**
 * Constants for domains being used by application. Change these for your site.
 */

//define("SWITCHER_DESKTOP_DOMAIN", "www.mydomain.com");
//define("SWITCHER_MOBILE_DOMAIN", "m.mydomain.com");

/**
 * Constants for cookie and CGI flags.
 * Change only if in conflict with your application.
 */
define("SWITCHER_COOKIE_VAR", "switcher");
define("SWITCHER_CGI_VAR", "switcher");

/**
 * Constants for indicating switcher outcome
 */
define("SWITCHER_DESKTOP_PAGE", 1);
define("SWITCHER_MOBILE_PAGE", 2);
define("SWITCHER_REDIRECT_TO_MOBILE", 3);
define("SWITCHER_REDIRECT_TO_DESKTOP", 4);
define("SWITCHER_MOBILE_INTERSTITIAL", 5);
define("SWITCHER_DESKTOP_INTERSTITIAL", 6);

/**
 * Main entry point
 */
switcher();

/**
 * Harness for the switcher algorithm behaviour
 */
function switcher() {
  $desktop_domain = _switcher_is_domain(SWITCHER_DESKTOP_DOMAIN);
  $mobile_domain = _switcher_is_domain(SWITCHER_MOBILE_DOMAIN);
  $desktop_browser = _switcher_is_desktop_browser();
  $mobile_browser = _switcher_is_mobile_browser();
  $desktop_cookie = _switcher_is_desktop_cookie();
  $mobile_cookie = _switcher_is_mobile_cookie();
  $cgi = _switcher_is_cgi_parameter_present();

  $outcome = _switcher_outcome(
    $desktop_domain, $mobile_domain,
    $desktop_browser, $mobile_browser,
    $desktop_cookie, $mobile_cookie,
    $cgi
  );

  _switcher_handle_outcome($outcome);
}

/**
 * Main switcher algorithm
 * @return integer the switcher outcome
 */
function _switcher_outcome(
  $desktop_domain, $mobile_domain,
  $desktop_browser, $mobile_browser,
  $desktop_cookie, $mobile_cookie,
  $cgi
) {

  if ($desktop_domain) {
    if ($desktop_browser) {
      if ($mobile_cookie && !$cgi) {
        return SWITCHER_REDIRECT_TO_MOBILE;
      } else {
        return SWITCHER_DESKTOP_PAGE;
      }
    } else {
      if ($cgi || $desktop_cookie) {
        return SWITCHER_DESKTOP_PAGE;
      } else {
        if ($mobile_cookie) {
          return SWITCHER_REDIRECT_TO_MOBILE;
        } else {
          return SWITCHER_MOBILE_INTERSTITIAL;
        }
      }
    }
  } else {
    if ($mobile_browser) {
      if ($desktop_cookie && !$cgi) {
        return SWITCHER_REDIRECT_TO_DESKTOP;
      } else {
        return SWITCHER_MOBILE_PAGE;
      }
    } else {
      if ($cgi || $mobile_cookie) {
        return SWITCHER_MOBILE_PAGE;
      } else {
        if ($desktop_cookie) {
          return SWITCHER_REDIRECT_TO_DESKTOP;
        } else {
          return SWITCHER_DESKTOP_INTERSTITIAL;
        }
      }
    }
  }
}

/**
 * Main switcher handler, based on previously-determined outcome
 * @param integer $outcome the switcher outcome
 */
function _switcher_handle_outcome($outcome) {
  switch ($outcome) {

    case SWITCHER_DESKTOP_PAGE:
      _switcher_set_cookie(SWITCHER_DESKTOP_DOMAIN);
      _switcher_desktop_page();
      exit;

    case SWITCHER_MOBILE_PAGE:
      _switcher_set_cookie(SWITCHER_MOBILE_DOMAIN);
      _switcher_mobile_page();
      exit;

    case SWITCHER_REDIRECT_TO_MOBILE:
      $target_url = "http://" . SWITCHER_MOBILE_DOMAIN . _switcher_current_path_plus_cgi();
      header("Location: $target_url");
      exit;

    case SWITCHER_REDIRECT_TO_DESKTOP:
      $target_url = "http://" . SWITCHER_DESKTOP_DOMAIN . _switcher_current_path_plus_cgi();
      header("Location: $target_url");
      exit;

    case SWITCHER_DESKTOP_INTERSTITIAL:
      _switcher_desktop_interstitial();
      exit;

    case SWITCHER_MOBILE_INTERSTITIAL:
      _switcher_mobile_interstitial();
      exit;

    default:
      die("Switcher error");
  }
}

/**
 * Identifies whether user is on a certain domain
 * @param string $domain suitably-qualified domain suffix
 */
function _switcher_is_domain($domain) {
  $host = $_SERVER['HTTP_HOST'];
  return (substr($host, -strlen($domain)) == $domain);
}

/**
 * Is user's a desktop browser
 * @returns boolean true if using desktop browser
 */
function _switcher_is_desktop_browser() {
  return !_switcher_is_mobile_browser();
}

/**
 * Is user's a mobile browser?
 * @returns boolean true if using mobile browser
 */
function _switcher_is_mobile_browser() {
  global $_switcher_is_mobile_browser;
  if (isset($_switcher_is_mobile_browser)) {
    return $_switcher_is_mobile_browser;
  }

  $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
  $mobile_browser = '0';
  if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', $ua)) {
    $mobile_browser++;
  }
  if (stripos($_SERVER['HTTP_ACCEPT'], 'application/vnd.wap.xhtml+xml') !== false ||
      isset($_SERVER['HTTP_X_WAP_PROFILE']) ||
      isset($_SERVER['HTTP_PROFILE'])) {
    $mobile_browser++;
  }
  $mobile_ua = substr($ua, 0, 4);
  $mobile_agents = array(
    'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
    'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
    'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
    'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
    'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
    'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
    'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
    'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
    'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-');
  if (in_array($mobile_ua, $mobile_agents)) {
    $mobile_browser++;
  }

  if (isset($_SERVER['ALL_HTTP']) && stripos($_SERVER['ALL_HTTP'], 'operamini') !== false) {
    $mobile_browser++;
  }
  if (strpos($ua, 'windows') > 0) {
    $mobile_browser = 0;
  }
  $_switcher_is_mobile_browser = ($mobile_browser > 0);
  return $_switcher_is_mobile_browser;
}

/**
 * Is the 'desktop cookie' set?
 * @returns boolean true if a cookie exists indicating desktop preference
 */
function _switcher_is_desktop_cookie() {
  return (
    isset($_COOKIE[SWITCHER_COOKIE_VAR])
    && $_COOKIE[SWITCHER_COOKIE_VAR] == "desktop"
  );
}

/**
 * Is the 'mobile cookie' set?
 * @returns boolean true if a cookie exists indicating mobile preference
 */
function _switcher_is_mobile_cookie() {
  return (
    isset($_COOKIE[SWITCHER_COOKIE_VAR])
    && $_COOKIE[SWITCHER_COOKIE_VAR] == "mobile"
  );
}

/**
 * Is the switcher's CGI parameter set?
 * @returns boolean true if the magic parameter exists to override a cookie's preference
 */
function _switcher_is_cgi_parameter_present() {
  return (
    isset($_GET[SWITCHER_CGI_VAR])
  );
}

/**
 * Create an anchor link to switch between sites
 * @param int $target either SWITCHER_DESKTOP_DOMAIN or SWITCHER_MOBILE_DOMAIN
 * @param string $label descriptive text to go in the link
 */
function _switcher_link($target, $label) {
  switch ($target) {
    case SWITCHER_DESKTOP_DOMAIN:
      $cookie = SWITCHER_COOKIE_VAR . "=desktop;path=/;expires=Tue, 01-01-2030 00:00:00 GMT";
      $target_url = "http://" . SWITCHER_DESKTOP_DOMAIN . _switcher_current_path_plus_cgi();
      break;
    case SWITCHER_MOBILE_DOMAIN:
      $cookie = SWITCHER_COOKIE_VAR . "=mobile;path=/;expires=Tue, 01-01-2030 00:00:00 GMT";
      $target_url = "http://" . SWITCHER_MOBILE_DOMAIN . _switcher_current_path_plus_cgi();
      break;
  }
  if ($target_url) {
    return "<a onclick = 'document.cookie = \"$cookie\";' href = '$target_url'>$label</a>";
  }
}

/**
 * Returns the portion of the URL path with the magic CGI parameter set
 * @returns string the URL path and query string with the CGI parameter set
 */
function _switcher_current_path_plus_cgi() {
  $path = $_SERVER['REQUEST_URI'];
  if (stripos($path, SWITCHER_CGI_VAR . "=true") !== false) {
    return $path;
  }
  if (stripos($path, "?") === false) {
    return $path . "?" . SWITCHER_CGI_VAR . "=true";
  }
  return $path . "&" . SWITCHER_CGI_VAR . "=true";
}

/**
 * Sets a cookie on the current domain to indicate user preference
 * @param int $preference either SWITCHER_DESKTOP_DOMAIN or SWITCHER_MOBILE_DOMAIN
 */
function _switcher_set_cookie($preference) {
  switch ($preference) {
    case SWITCHER_DESKTOP_DOMAIN:
      setcookie(SWITCHER_COOKIE_VAR, "desktop", time()+60*60*24*365, '/');
      break;
    case SWITCHER_MOBILE_DOMAIN:
      setcookie(SWITCHER_COOKIE_VAR, "mobile", time()+60*60*24*365, '/');
      break;
  }
}

/**
 * Outputs the desktop interstitial page
 */
function _switcher_desktop_interstitial() {
  print "<html>
    <head>
      <title>Desktop device detected - Village of Downers Grove</title>
    </head>
    <body>
      <h1>Desktop browser detected...</h1>
      <p>You've requested the mobile site, but you appear to have a desktop browser.</p>
      <p>" . _switcher_link(SWITCHER_DESKTOP_DOMAIN, "Revert to the desktop site") . "</p>
      <p>" . _switcher_link(SWITCHER_MOBILE_DOMAIN, "Continue to our mobile site") . "</p>
    </body>
  </html>";
}

/**
 * Outputs the mobile interstitial page
 */
function _switcher_mobile_interstitial() {
  print "<" .
  "?xml version='1.0' encoding='UTF-8'?>
  <!DOCTYPE html PUBLIC '-//WAPFORUM//DTD XHTML Mobile 1.0//EN' 'http://www.wapforum.org/DTD/xhtml-mobile10.dtd'>
  <html xmlns='http://www.w3.org/1999/xhtml'>
    <head>
      <meta http-equiv='Content-type' content='text/html; charset=utf-8' />
      <meta http-equiv='Content-language' content='en-gb' />
      <title>Mobile device detected - Village of Downers Grove</title>
    </head>
    <body>
      <h1>Mobile browser detected...</h1>
      <p>You've requested the desktop site, but you appear to have a mobile browser.</p>
      <p>" . _switcher_link(SWITCHER_MOBILE_DOMAIN, "Revert to the mobile site") . "</p>
      <p>" . _switcher_link(SWITCHER_DESKTOP_DOMAIN, "Continue to our desktop site") . "</p>
    </body>
  </html>";
}

/**
 * Outputs a placeholder desktop page solely for the purposes of demonstrating the switcher
 */
function _switcher_desktop_page() {
  main();
}

/**
 * Outputs a placeholder mobile page solely for the purposes of demonstrating the switcher
 */
function _switcher_mobile_page() {
  main();
}
?>
