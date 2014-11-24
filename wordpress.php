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
			$pack = strtoupper($this->context).'_PACK';
			if (MRequest::getWord('option') != 'com_'.$this->context and constant($pack) == 'pro') {
				add_filter('media_buttons_context', array($this, 'shortcodeButton'));
				add_action('admin_footer', array($this, 'shortcodePopup'));
			}
            add_action('admin_enqueue_scripts', array($this,'safelyAddScript'),999);
            add_action('admin_enqueue_scripts', array($this,'safelyAddStylesheet'),999);
		}
		else {
			add_filter('rewrite_rules_array',array($this, 'miwiFrontendRewrite'));
            add_action('wp_loaded',array($this, 'miwiFlushRewriteRules'));
			add_action('parse_query', array($this, 'parse'));
			add_action('wp_head', array($this, 'metadata'));
			add_action('template_redirect', array($this, 'preDisplay'));
			add_action('template_redirect', array($this, 'modulePreDisplay'));
            add_action('wp_enqueue_scripts', array($this,'safelyAddScript'),999);
            add_action('wp_enqueue_scripts', array($this,'safelyAddStylesheet'), 999 );
		}

        # ajax hooks
        add_action('wp_head', array($this, 'ajaxurl'), 999);
		add_action('wp_ajax_'.$this->context, array($this, 'ajax'));
		add_action('wp_ajax_nopriv_'.$this->context, array($this, 'ajax'));

        #search hooks
        add_filter('the_posts', array($this, 'search'), 10, 2);
		add_filter('post_link', array($this, 'fixSearchLink'), 10, 2);
		add_filter('get_edit_post_link', array($this, 'fixEditPostLink'), 10, 2);

        #shortcode hooks
        add_shortcode($this->context, array($this, 'shortcode'));
		add_shortcode($this->context.'_item', array($this, 'shortcode'));

		add_filter('plugin_row_meta', array($this, 'links'), 10, 2);

        # upgrade hooks
        add_filter('upgrader_source_selection', array($this,'miwiPreUpgrade'), 10, 2);
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
		
		# auto upgrade
        mimport('joomla.application.component.helper');
        $config = MComponentHelper::getParams('com_'.$this->context);
		
		if(!empty($config) and file_exists(MPATH_WP_CNT.'/miwi/autoupdate.php')) {
			$pid = $config->get('pid');
			if(!empty($pid)) {
				$path = 'http://miwisoft.com/index.php?option=com_mijoextensions&view=download&pack=upgrade&model=' . $this->context.'&pid=' . $pid;
				require_once(MPATH_WP_CNT.'/miwi/autoupdate.php');
				new MiwisoftAutoUpdate($path, $this->context);
			}
		}
	}

	public function activate() {
		$src  = MPATH_WP_PLG.'/'.$this->context.'/miwi';
		$dest = MPATH_WP_CNT.'/miwi';
		if (!file_exists($dest)) {
			rename($src, $dest);
		}
		elseif (file_exists($dest) and file_exists($src)) {
			$src_version  = $this->getMiwiVersion($src.'/versions.xml');
			$dest_version = $this->getMiwiVersion($dest.'/versions.xml');
			if (version_compare($src_version, $dest_version, 'gt')) {
				if (!@rename($src, $dest)) {
					$this->copyMiwi($src, $dest);
					$this->deleteMiwi($src);
				}
			}
			else {
				$this->deleteMiwi($src);
			}
		}

		$this->initialise();

		$sql_file = MPATH_WP_PLG.'/'.$this->context.'/admin/install.sql';
		if (file_exists($sql_file)) {
			mimport('framework.installer.installer');

			MInstaller::runSqlFile($sql_file);
		}

		$script_file = MPATH_WP_PLG.'/'.$this->context.'/script.php';
		if (file_exists($script_file)) {
			$installer = $this->getInstaller($script_file);

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

    public function modulePreDisplay(){
        # check
        $option = MRequest::getCmd('option');
        if(!empty($option)) {
            return;
        }

        # get all sidebar widgets
        $sidebars_widgets = wp_get_sidebars_widgets();
        unset($sidebars_widgets['wp_inactive_widgets']);

        # get all miwi modules
        mimport( 'framework.application.module.helper' );
        $modules = MModuleHelper::getModules();

        # load sidebar modules
        foreach($modules as $module){
            foreach($sidebars_widgets as $_sidebars_widgets){

	            $is_in = preg_grep("/".$module->id."_widget./", $_sidebars_widgets);

                if(!empty($is_in)) {
                    MModuleHelper::renderModule($module);
                    $loaded[$module->id] = $module->id;
                    break;
                }
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

		if (!empty($args) and isset($args['id'])) {
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

	public function search($posts, $wp_query) {
		if (!is_search() or !isset($wp_query->query) or !isset($wp_query->query['s'])) {
			return $posts;
		}

		$this->wp_query = $wp_query;
		$text = get_search_query();

		mimport('framework.plugin.helper');
		mimport('framework.application.component.helper');
		MPluginHelper::importPlugin('search');

		$dispatcher = MDispatcher::getInstance();
		$plg_result = $dispatcher->trigger('onContentSearch', array($text, 'all', 'newest', null, $this->context));

		$miwo_result = array();
		foreach($plg_result as $rows) {
			$miwo_result = array_merge($miwo_result, $rows);
		}

		$posts = array_merge($miwo_result, $posts);

		usort($posts, array('MWordpress', '_sortResult'));

		return $posts;
	}

	protected function _sortResult($a, $b) {
		if ($this->wp_query->query_vars['order'] == 'DESC') {
			return strtolower($a->post_title) > strtolower($b->post_title);
		}
		else {
			return strtolower($a->post_title) < strtolower($b->post_title);
		}
	}

   	public function fixSearchLink($url, $post) {
   		if (isset($post->href)) {
   			$url = $post->href;
   		}

   		return $url;
   	}

   	public function fixEditPostLink($url, $post_id) {
   		# Post object should be passed here not its ID (Edit link issue on search page)
   		return $url;
   	}

	public function shortcode($args) {
		if (isset($args[ $this->context ])) {
			return null;
		}

		ob_start();
		echo $this->display($args);
		return ob_get_clean();
	}

	public function shortcodeButton($content) {
		$title = explode('miwo', $this->context);
		$content .= '<a href="#TB_inline?width=450&height=550&inlineId='.$this->context.'-shortcode" class="button thickbox miwi-shortcode-btn" title="'.MText::sprintf('MLIB_X_ADD_SHORTCODE', 'Miwo'.ucfirst($title[1])).'">
						<img src="'.MURL_WP_CNT.'/plugins/'.$this->context.'/admin/assets/images/icon-16-'.$this->context.'.png" alt="'.MText::sprintf('MLIB_X_ADD_SHORTCODE', 'Miwo'.ucfirst($title[1])).'" />'.
		            MText::_('MLIB_ADD_SHORTCODE')
		            .'</a>';
		return $content;
	}

	public function shortcodePopup() {
		mimport('framework.shortcode.shortcode');
		$shortcode = new MShortcode();
		$shortcode->popup($this->context);
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

			//MRequest::set($vars, 'POST');
			//MRequest::set($vars, 'GET');

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

    public function miwiPreUpgrade($source) {       
        if(empty($_GET['action']) or (!empty($_GET['action']) and $_GET['action'] != 'upgrade-plugin' )){
            return $source;
        }

        if(empty($_GET['plugin']) or (!empty($_GET['plugin']) and $_GET['plugin'] != $this->context .'/'.$this->context.'.php')) {
            return $source;
        }
		
		$script_file = MPATH_WP_PLG.'/'.$this->context.'/script.php';
		if (!file_exists($script_file)) {
			return $source;
		}

	    $installer = $this->getInstaller($script_file);
		
		if (!method_exists($installer, 'preflight')) {
			return $source;
		}
		
        $installer->preflight('upgrade', $source);
		
		return $source;
    }

    public function miwiPostUpgrade($install_result, $hook_extra, $child_result) {
        if ($install_result == false ) { //Bypass if there is a error.
            return false;
        }

        if(empty($hook_extra) or (!empty($hook_extra) and $hook_extra['action'] != 'update' )){
            return;
        }

        if(empty($hook_extra['plugin']) or  (!empty($hook_extra['plugin']) and $hook_extra['plugin'] != $this->context .'/'.$this->context.'.php')) {
            return;
        }

        $script_file = MPATH_WP_PLG.'/'.$this->context.'/script.php';
		if (!file_exists($script_file)) {
			return;
		}

	    $installer = $this->getInstaller($script_file);
		
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

	public function copyMiwi($src, $dest) {
		$dir = opendir($src);
		@mkdir($dest);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src.'/'.$file)) {
					$this->copyMiwi($src.'/'.$file, $dest.'/'.$file);
				}
				else {
					copy($src.'/'.$file, $dest.'/'.$file);

				}
			}
		}
		closedir($dir);
	}

	public function deleteMiwi($dir) {
		foreach (glob($dir.'/*') as $file) {
			if (is_dir($file)) {
				$this->deleteMiwi($file);
			}
			else {
				unlink($file);
			}
		}
		rmdir($dir);
	}

	public static function getMiwiVersion($file) {
		$version  = '0.0.0';
		$manifest = simplexml_load_file($file, 'SimpleXMLElement');

		if (is_null($manifest)) {
			return $version;
		}

		if (!($manifest instanceof SimpleXMLElement) or (count($manifest->children()) == 0)) {
			return $version;
		}

		foreach ($manifest->children() as $version) {
			if ($version->attributes()->name == 'Miwi') {
				$version = (string)$version->release;
				break;
			}
		}

		return $version;
	}

	public function getInstaller($script_file) {
		static $scripts = array();

		require_once($script_file);

		if (!isset($scripts[$this->context])) {
			$installer_class = 'com_'.ucfirst($this->context).'InstallerScript';

			$installer = new $installer_class();

			$scripts[$this->context] = $installer;
		}

		return $scripts[$this->context];
	}
	
    public function miwiFrontendRewrite( $rules ) {
        $newrules = array();
        $newrules['([a-z0-9-_]+)/'] =  'index.php?pagename=$matches[1]';

        return $rules + $newrules;
    }

    public function miwiFlushRewriteRules(){
		$rules = MFactory::getWOption('rewrite_rules');
        if (!isset( $rules['([a-z0-9-_]+)/'] ) ) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }
    }
}