<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('ABSPATH') or die('MIWI');

class MWordpress {

	protected $app        = null;
	protected $context    = null;
	protected $menu_id    = 33;
	protected $has_config = true;
	protected $title      = '';

	public function __construct($context, $menu_id = 33, $has_config = true, $title = '') {
		$this->context    = $context;
		$this->menu_id    = $menu_id;
		$this->has_config = $has_config;
		$this->title      = $title;

		$this->constants();
		
		$this->initialise();

		if (!defined('MIWI')) {
			return;
		}

		add_action('init', array($this, 'initialise'));
		//add_action('widgets_init', array($this, 'widgets'));
		$this->widgets();

		if ($this->app->isAdmin()) {
			add_action('admin_menu', array($this, 'menu'));
			add_action('admin_init', array($this, 'preDisplayAdmin'));
            add_action('admin_enqueue_scripts', array($this,'safelyAddScript'),999);
            add_action('admin_enqueue_scripts', array($this,'safelyAddStylesheet'),999);
		}
		else {
			add_action('parse_query', array($this, 'parse'));
			add_action('wp_head', array($this, 'metadata'));
			add_action('get_header', array($this, 'preDisplay'));
            add_action('wp_enqueue_scripts', array($this,'safelyAddScript'),999);
            add_action('wp_enqueue_scripts', array($this,'safelyAddStylesheet'), 999 );
		}

        # ajax hooks
        add_action('wp_head', array($this, 'ajaxurl'), 999);
		add_action('wp_ajax_'.$this->context, array($this, 'ajax'));
		add_action('wp_ajax_nopriv_'.$this->context, array($this, 'ajax'));

		add_shortcode($this->context, array($this, 'shortcode'));
		add_shortcode($this->context.'_item', array($this, 'shortcode'));

		add_filter('plugin_row_meta', array($this, 'links'), 10, 2);

        # upgrade hooks
        add_filter('upgrader_pre_install', array($this,'miwiPreUpgrade'), 10, 2);
        add_filter('upgrader_post_install', array($this,'miwiPostUpgrade'), 10, 3);
	}

	public function constants() {
		if (!defined('MPATH_WP_PLG')) {
			define('MPATH_WP_PLG', dirname(plugin_dir_path(__FILE__)));
		}

		if (!defined('MPATH_WP_CNT')) {
			define('MPATH_WP_CNT', dirname(MPATH_WP_PLG));
		}

		$upload_dir = wp_upload_dir();

		if (!defined('MPATH_MEDIA')) {
			define('MPATH_MEDIA', $upload_dir['basedir']);
		}

		if (!defined('MURL_MEDIA')) {
			define('MURL_MEDIA', $upload_dir['baseurl']);
		}

		if (!defined('MURL_WP_CNT')) {
			define('MURL_WP_CNT', content_url());
		}

		if (!defined('MURL_ADMIN')) {
			$admin_url = rtrim(admin_url(), '/');
			define('MURL_ADMIN', $admin_url);
		}
	}

	public function initialise() {
		$miwi = MPATH_WP_CNT.'/miwi/initialise.php';

		if (!file_exists($miwi)) {
			return false;
		}

		require_once($miwi);

		$this->app = MFactory::getApplication();

		$this->app->initialise();
	}

	public function activate() {
        if(is_dir( MPATH_WP_PLG.'/'.$this->context.'/miwi' )) {
            rename(MPATH_WP_PLG.'/'.$this->context.'/miwi', MPATH_WP_CNT.'/miwi');
        }

		$this->initialise();

		$sql_file = MPATH_WP_PLG.'/'.$this->context.'/admin/install.sql';
		if (file_exists($sql_file)) {
			mimport('framework.installer.installer');

			MInstaller::runSqlFile($sql_file);
		}

		$script_file = MPATH_WP_PLG.'/'.$this->context.'/script.php';
		if (file_exists($script_file)) {
			require_once($script_file);

			$installer_class = 'com_'.ucfirst($this->context).'InstallerScript';

			$installer = new $installer_class();

			if (method_exists($installer, 'preflight')) {
				$installer->preflight(null, null);
			}

			if (method_exists($installer, 'postflight')) {
				$installer->postflight(null, null);
			}
		}
	}
	
	public function deactivate() {}

	public function menu() {
		MFactory::getLanguage()->load('com_'.$this->context, MPATH_ADMINISTRATOR);

		$title = $this->title;
		if (empty($this->title)) {
			$title = MText::_('COM_'.strtoupper($this->context));
		}

		mimport('framework.filesystem.file');
		$img = '';
		if (MFile::exists(MPATH_WP_PLG.'/'.$this->context.'/admin/assets/images/icon-16-'.$this->context.'.png')) {
			$img = plugins_url($this->context.'/admin/assets/images/icon-16-'.$this->context.'.png');
		}

		add_menu_page($title, $title, 'manage_options', $this->context, array($this, 'display'), $img, $this->menu_id);

		if ($this->has_config == true) {
			add_submenu_page($this->context, MText::_('COM_'.strtoupper($this->context).'_CPANEL_CONFIGURATION'), MText::_('COM_'.strtoupper($this->context).'_CPANEL_CONFIGURATION'), 'manage_options', MRoute::_('index.php?option=com_'.$this->context.'&view=config'));
		}

		$toolbar_file = MPATH_WP_PLG.'/'.$this->context.'/admin/toolbar.php';
		if (file_exists($toolbar_file)) {
			require_once($toolbar_file);
		}

		if (!empty($views)) {
			foreach ($views as $key => $val) {
				if (empty($key)) {
					continue;
				}

				add_submenu_page($this->context, $val, $val, 'manage_options', MRoute::_('index.php?option=com_'.$this->context.$key));
			}
		}
	}

	public function preDisplay() {
		$option = MRequest::getCmd('option');
		if ($option != 'com_'.$this->context) {
			return;
		}

		global $post;

		if ($this->_hasShortcode($post->post_content, $this->context.'_item')) {
			define('MIWI_IS_ITEM', true);
			return;
		}

		preg_match_all('/'.get_shortcode_regex().'/s', $post->post_content, $matches, PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach ($matches as $shortcode) {
				if ($this->context !== $shortcode[2]) {
					continue;
				}

				$args = shortcode_parse_atts($shortcode[3]);
				break;
			}

			$view = MRequest::getCmd('view');
			if (!empty($args) and empty($view)) {
				MRequest::set($args, 'GET', false);
			}
		}

		$this->app->route();
		$this->app->dispatch();
	}
	
	public function preDisplayAdmin($args = null) {
		$page = MRequest::getCmd('page');
		if ($page != $this->context) {
			return;
		}

		MRequest::setVar('option', 'com_'.$this->context);

		$this->app->route();
		$this->app->dispatch();
	}

	public function display($args = null) {
		MRequest::setVar('option', 'com_'.$this->context);

		if (!empty($args)) {

			MPluginHelper::importPlugin('content');
			$article       = new stdClass();
			$article->text = '{'.$this->context.' id='.$args['id'].'}';
			$params        = null;
			MDispatcher::getInstance()->trigger('onContentPrepare', array($this->context, &$article, &$params, 0));
		}

		$this->app->route();
		$this->app->dispatch();
		$this->app->render();
	}

	public function shortcode($args) {
		if (isset($args[ $this->context ])) {
			return null;
		}

		ob_start();
		echo $this->display($args);
		return ob_get_clean();
	}

	public function ajax() {
		$this->display();
		exit();
	}

	public function widgets() {
		mimport('framework.widget.helper');
		MWidgetHelper::startWidgets($this->context);
	}

	public function parse($query) {
		$post = null;

		if ($this->app->getCfg('sef', 0) == 0) {
			$id = $query->get('page_id');

			if (empty($id)) {
				$id = $query->get('p');
			}

			$post = MFactory::getWPost($id);
		}
		else {
			$segments = explode('/', $query->get('pagename'));

			if (empty($segments[0])) {
				return;
			}

			$post = get_page_by_path($segments[0]);
		}

		if (!is_object($post)) {
			$page_id = MFactory::getWOption($this->context.'_page_id');

			$post   = MFactory::getWPost($page_id);
			$option = MRequest::getCmd('option', '');

			if (!is_object($post) or $option != 'com_'.$this->context) {
				return;
			}

			$query->set('page_id', $page_id);
			$query->set('post_type', 'page');
		}

		if ($this->_hasShortcode($post->post_content, $this->context) or $this->_hasShortcode($post->post_content, $this->context.'_item')) {
			MRequest::setVar('option', 'com_'.$this->context);

			$vars = $this->app->parse();

			MRequest::set($vars, 'POST');
			MRequest::set($vars, 'GET');

			$query->query_vars = array_merge($query->query_vars, $vars);
		}
	}

	public function metadata() {
		$option = MRequest::getCmd('option');
		if ($option != 'com_'.$this->context) {
			return;
		}

		if (defined('MIWI_IS_ITEM')) {
			return;
		}

		$document = MFactory::getDocument();
		$metadata = array();

		if ($meta_desc = $document->getMetadata('description')) {
			$metadata[] = '<meta name="description" content="'.$meta_desc.'" />';
		}

		if ($meta_keywords = $document->getMetadata('keywords')) {
			$metadata[] = '<meta name="keywords" content="'.$meta_keywords.'" />';
		}

		if ($meta_author = $document->getMetadata('author')) {
			$metadata[] = '<meta name="author" content="'.$meta_author.'" />';
		}

		$base       = MFactory::getUri()->base();
		$metadata[] = '<base  href="'.$base.'" />';

		echo implode("\n", $metadata);
	}

	public function links($links, $file) {
		if (!current_user_can('install_plugins')) {
			return $links;
		}

		if (!strstr($file, $this->context)) {
			return $links;
		}

		$links[] = '<a href="http://miwisoft.com/support" target="_blank">Support</a>';

		return $links;
	}

    public function safelyAddStylesheet(){
        $document = MFactory::getDocument();
        $style_sheets = $document->_styleSheets;

        foreach($style_sheets as $style_sheet){
            wp_enqueue_style( $style_sheet, $style_sheet);
        }

        #inline styles
        $style = $document->_style;
        if(empty($style)) {
            return;
        }

        global $wp_styles;

        foreach($style as $key => $_style){
            wp_register_style($key, MURL_WP_CNT.'/miwi/media/system/css/miwicss.css');

            $wp_styles->add_inline_style($key, $_style);
            wp_enqueue_style($key);
        }
        #############

        return;
    }

    public function safelyAddScript(){
        $document = MFactory::getDocument();
        $scripts = $document->_scripts;

        foreach($scripts as $script){
            wp_enqueue_script($script, $script);
        }

        #inline scripts
        $script = $document->_script;
        if(empty($script)) {
            return;
        }

        global $wp_scripts;;

        foreach($script as $key => $_script){
            wp_register_script($key, MURL_WP_CNT.'/miwi/media/system/js/miwiscript.js', array(), false, true );

            $wp_scripts->add_data($key, 'data', $_script);
            wp_enqueue_script($key);
        }
        #############

        return;
    }

    public function miwiPreUpgrade($return, $plugin) {
        if ( is_wp_error($return) ) { //Bypass if there is a error.
            return $return;
        }

        if(!empty($plugin) and $plugin['action'] != 'update' ){
            return;
        }

        if(!empty($plugin['plugin']) and $plugin['plugin'] != $this->context .'/'.$this->context.'.php') {
            return;
        }

		
		$script_file = MPATH_WP_PLG.'/'.$this->context.'/script.php';
		if (!file_exists($script_file)) {
			return $return;
		}

        require_once($script_file);

        $class_name = 'com_'.$this->context.'InstallerScript';

        $installer = new $class_name;
		
		if (!method_exists($installer, 'preflight')) {
			return $return;
		}
		
        $installer->preflight('upgrade', '');
    }

    public function miwiPostUpgrade($install_result, $hook_extra, $child_result) {
        if ($install_result == false ) { //Bypass if there is a error.
            return false;
        }

        if(!empty($hook_extra) and $hook_extra['action'] != 'update' ){
            return;
        }

        if(!empty($hook_extra['plugin']) and $hook_extra['plugin'] != $this->context .'/'.$this->context.'.php') {
            return;
        }

        $script_file = MPATH_WP_PLG.'/'.$this->context.'/script.php';
		if (!file_exists($script_file)) {
			return;
		}

        require_once($script_file);

        $class_name = 'com_'.$this->context.'InstallerScript';

        $installer = new $class_name;
		
		if (!method_exists($installer, 'postflight')) {
			return;
		}
		
        $installer->postflight('upgrade', '');
    }

    public function ajaxurl(){
        echo '<script type="text/javascript">
        var miwiajaxurl = \''. MURL_ADMIN .'/admin-ajax.php\';
        var wpcontenturl = \''. MURL_WP_CNT .'\';
        </script>';
    }

	public function _hasShortcode($content, $tag) {
		global $wp_version;

		if (version_compare($wp_version, '3.6.0') == -1) {
			if (false === strpos($content, '[')) {
				return false;
			}

			if ($this->_shortcodeExists($tag)) {
				preg_match_all('/'.get_shortcode_regex().'/s', $content, $matches, PREG_SET_ORDER);
				if (empty($matches)) {
					return false;
				}

				foreach ($matches as $shortcode) {
					if ($tag === $shortcode[2]) {
						return true;
					}
				}
			}

			return false;
		}
		else {
			return has_shortcode($content, $tag);
		}
	}

	public function _shortcodeExists($tag) {
		global $shortcode_tags;
		return array_key_exists($tag, $shortcode_tags);
	}
}