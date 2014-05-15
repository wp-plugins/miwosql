<?php

class MiwisoftAutoUpdate {

    public $current_version;
    public $remote_version;
    public $update_path;
    public $plugin_slug;
    public $slug;

    function __construct($update_path, $slug) {

        $this->update_path  = $update_path;
        $this->slug         = $slug;
        $this->plugin_slug  = $slug.'/'.$slug.'.php';

        if(empty($this->current_version)) {
            $plugin_file    = MPATH_WP_PLG. '/'. $this->plugin_slug;

            if(!function_exists('get_plugin_data')) {
                require_once(ABSPATH . '/wp-admin/includes/plugin.php');
            }

            $current        = get_plugin_data($plugin_file);
            $this->current_version   = $current['Version'];
        }

        if(empty($this->remote_version)) {
            $this->remote_version    = $this->getRemoteVersion($this->slug);
        }

        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));
        add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // If a newer version is available, add the update
        if (version_compare($this->current_version, $this->remote_version, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $this->remote_version;
            $obj->url = $this->update_path;
            $obj->package = $this->update_path;
            $transient->response[$this->plugin_slug] = $obj;
        }

        return $transient;
    }

    public function check_info($false, $action, $arg) {
        if ($arg->slug === $this->slug) {
            $args       = (object) array( 'slug' => $this->slug);
            $request    = array( 'action' => 'plugin_information', 'timeout' => 15, 'request' => serialize( $args) );
            $url        = 'http://api.wordpress.org/plugins/info/1.0/';

            $response = wp_remote_post( $url, array( 'body' => $request ) );
            $plugin_info = unserialize( $response['body'] );

            $plugin_info->download_link = $this->update_path;
            $plugin_info->new_version = $this->remote_version;;

            return $plugin_info;
        }
        return false;
    }

    public function getRemoteVersion($plugin) {
        $version = '?.?.?';

        $components = $this->getRemoteData('http://miwisoft.com/index.php?option=com_mijoextensions&view=xml&format=xml&catid=5');

        if (!strstr($components, '<?xml version="1.0" encoding="UTF-8" ?>')) {
            return $version;
        }

        $manifest = simplexml_load_string($components, 'SimpleXMLElement');

        if (is_null($manifest)) {
            return $version;
        }

        $category = $manifest->category;
        if (!($category instanceof SimpleXMLElement) || (count($category->children()) == 0)) {
            return $version;
        }

        foreach ($category->children() as $component) {
            $option = (string)$component->attributes()->option;
            $compability = (string)$component->attributes()->compability;

            if (($option == $plugin) and $compability == 'wpall') {
                $version = trim((string)$component->attributes()->version);
                break;
            }
        }

        return $version;
    }

    public function getRemoteData($url) {
        $user_agent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)";
        $data = false;

        // cURL
        if (extension_loaded('curl')) {
            $process = @curl_init($url);

            @curl_setopt($process, CURLOPT_HEADER, false);
            @curl_setopt($process, CURLOPT_USERAGENT, $user_agent);
            @curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
            @curl_setopt($process, CURLOPT_AUTOREFERER, true);
            @curl_setopt($process, CURLOPT_FAILONERROR, true);
            @curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
            @curl_setopt($process, CURLOPT_TIMEOUT, 10);
            @curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 10);
            @curl_setopt($process, CURLOPT_MAXREDIRS, 20);

            $data = @curl_exec($process);

            @curl_close($process);

            return $data;
        }

        // fsockopen
        if (function_exists('fsockopen')) {
            $errno = 0;
            $errstr = '';

            $url_info = parse_url($url);
            if($url_info['host'] == 'localhost')  {
                $url_info['host'] = '127.0.0.1';
            }

            // Open socket connection
            if ($url_info['scheme'] == 'http') {
                $fsock = @fsockopen($url_info['scheme'].'://'.$url_info['host'], 80, $errno, $errstr, 5);
            } else {
                $fsock = @fsockopen('ssl://'.$url_info['host'], 443, $errno, $errstr, 5);
            }

            if ($fsock) {
                @fputs($fsock, 'GET '.$url_info['path'].(!empty($url_info['query']) ? '?'.$url_info['query'] : '').' HTTP/1.1'."\r\n");
                @fputs($fsock, 'HOST: '.$url_info['host']."\r\n");
                @fputs($fsock, "User-Agent: ".$user_agent."\n");
                @fputs($fsock, 'Connection: close'."\r\n\r\n");

                // Set timeout
                @stream_set_blocking($fsock, 1);
                @stream_set_timeout($fsock, 5);

                $data = '';
                $passed_header = false;
                while (!@feof($fsock)) {
                    if ($passed_header) {
                        $data .= @fread($fsock, 1024);
                    } else {
                        if (@fgets($fsock, 1024) == "\r\n") {
                            $passed_header = true;
                        }
                    }
                }

                // Clean up
                @fclose($fsock);

                // Return data
                return $data;
            }
        }

        // fopen
        if (function_exists('fopen') && ini_get('allow_url_fopen')) {
            // Set timeout
            if (ini_get('default_socket_timeout') < 5) {
                ini_set('default_socket_timeout', 5);
            }

            $url = str_replace('://localhost', '://127.0.0.1', $url);

            $handle = @fopen($url, 'r');

            @stream_set_blocking($handle, 1);
            @stream_set_timeout($handle, 5);
            @ini_set('user_agent',$user_agent);

            if ($handle) {
                $data = '';
                while (!feof($handle)) {
                    $data .= @fread($handle, 8192);
                }

                // Clean up
                @fclose($handle);

                // Return data
                return $data;
            }
        }

        // file_get_contents
        if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
            $url = str_replace('://localhost', '://127.0.0.1', $url);
            @ini_set('user_agent',$user_agent);
            $data = @file_get_contents($url);

            // Return data
            return $data;
        }

        return $data;
    }
}