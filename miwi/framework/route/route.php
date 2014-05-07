<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MRoute {

	public static function _($url, $xhtml = true, $ssl = null) {
		$app = MFactory::getApplication();
		$router = $app->getRouter();
		
		if (!$router) {
			return null;
		}
		
		if ((strpos($url, '&') !== 0) && (strpos($url, 'index.php') !== 0)) {
			return $url;
		}
		
		$uri = $router->build($url);
		$url = $uri->toString(array('path', 'query', 'fragment'));
		
		$url = preg_replace('/\s/u', '%20', $url);
		
		if ((int) $ssl) {
			$uri = MUri::getInstance();
			
			static $prefix;
			if (! $prefix) {
				$prefix = $uri->toString(array('host', 'port'));
			}
			
			$scheme = ((int) $ssl === 1) ? 'https' : 'http';
			
			if (! preg_match('#^/#', $url)) {
				$url = '/' . $url;
			}
			
			$url = $scheme . '://' . $prefix . $url;
		}
		
		if ($xhtml) {
			$url = htmlspecialchars($url);
		}

		return $url;
	}

    public static function getActiveUrl() {
        return self::cleanUrl(MFactory::getUri()->toString());
    }

    public static function cleanUrl($url) {
        $url = self::cleanText($url);

        $bad_chars = array('#', '>', '<', '\\', '="', 'px;', 'onmouseover=');
        $url = trim(str_replace($bad_chars, '', $url));

        mimport('framework.filter.input');
        MFilterInput::getInstance(array('br', 'i', 'em', 'b', 'strong'), array(), 0, 0, 1)->clean($url);

        return $url;
    }

    public static function cleanText($text) {
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('#<script[^>]*>.*?</script>#si', ' ', $text);
        $text = preg_replace('#<style[^>]*>.*?</style>#si', ' ', $text);
        $text = preg_replace('#<!.*?(--|]])>#si', ' ', $text);
        $text = preg_replace('#<[^>]*>#i', ' ', $text);
        $text = preg_replace('/{.+?}/', '', $text);
        $text = preg_replace("'<(br[^/>]*?/|hr[^/>]*?/|/(div|h[1-6]|li|p|td))>'si", ' ', $text);

        $text = preg_replace('/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/', ' ', $text);

        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = preg_replace('/\n\n+/s', ' ', $text);
        $text = preg_replace('/\s/u', ' ', $text);

        $text = strip_tags($text);

        return $text;
    }
}