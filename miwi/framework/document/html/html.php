<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.application.module.helper');
mimport('framework.utilities.utility');

class MDocumentHTML extends MDocument {

	public $_links = array();
	public $_custom = array();
	public $template = null;
	public $baseurl = null;
	public $params = null;
	public $_file = null;
	protected $_template = '';
	protected $_template_tags = array();
	protected $_caching = null;

	public function __construct($options = array()) {
		parent::__construct($options);

		// Set document type
		$this->_type = 'html';

		// Set default mime type and document metadata (meta data syncs with mime type by default)
		$this->setMimeEncoding('text/html');
	}

	public function getHeadData()
	{
		$data = array();
		$data['title']       = $this->title;
		$data['description'] = $this->description;
		$data['link']        = $this->link;
		$data['metaTags']    = $this->_metaTags;
		$data['links']       = $this->_links;
		$data['styleSheets'] = $this->_styleSheets;
		$data['style']       = $this->_style;
		$data['scripts']     = $this->_scripts;
		$data['script']      = $this->_script;
		$data['custom']      = $this->_custom;
		return $data;
	}

	public function setHeadData($data)
	{
		if (empty($data) || !is_array($data))
		{
			return;
		}

		$this->title = (isset($data['title']) && !empty($data['title'])) ? $data['title'] : $this->title;
		$this->description = (isset($data['description']) && !empty($data['description'])) ? $data['description'] : $this->description;
		$this->link = (isset($data['link']) && !empty($data['link'])) ? $data['link'] : $this->link;
		$this->_metaTags = (isset($data['metaTags']) && !empty($data['metaTags'])) ? $data['metaTags'] : $this->_metaTags;
		$this->_links = (isset($data['links']) && !empty($data['links'])) ? $data['links'] : $this->_links;
		$this->_styleSheets = (isset($data['styleSheets']) && !empty($data['styleSheets'])) ? $data['styleSheets'] : $this->_styleSheets;
		$this->_style = (isset($data['style']) && !empty($data['style'])) ? $data['style'] : $this->_style;
		$this->_scripts = (isset($data['scripts']) && !empty($data['scripts'])) ? $data['scripts'] : $this->_scripts;
		$this->_script = (isset($data['script']) && !empty($data['script'])) ? $data['script'] : $this->_script;
		$this->_custom = (isset($data['custom']) && !empty($data['custom'])) ? $data['custom'] : $this->_custom;

		return $this;
	}

	public function mergeHeadData($data)
	{

		if (empty($data) || !is_array($data))
		{
			return;
		}

		$this->title = (isset($data['title']) && !empty($data['title']) && !stristr($this->title, $data['title']))
			? $this->title . $data['title']
			: $this->title;
		$this->description = (isset($data['description']) && !empty($data['description']) && !stristr($this->description, $data['description']))
			? $this->description . $data['description']
			: $this->description;
		$this->link = (isset($data['link'])) ? $data['link'] : $this->link;

		if (isset($data['metaTags']))
		{
			foreach ($data['metaTags'] as $type1 => $data1)
			{
				$booldog = $type1 == 'http-equiv' ? true : false;
				foreach ($data1 as $name2 => $data2)
				{
					$this->setMetaData($name2, $data2, $booldog);
				}
			}
		}

		$this->_links = (isset($data['links']) && !empty($data['links']) && is_array($data['links']))
			? array_unique(array_merge($this->_links, $data['links']))
			: $this->_links;
		$this->_styleSheets = (isset($data['styleSheets']) && !empty($data['styleSheets']) && is_array($data['styleSheets']))
			? array_merge($this->_styleSheets, $data['styleSheets'])
			: $this->_styleSheets;

		if (isset($data['style']))
		{
			foreach ($data['style'] as $type => $stdata)
			{
				if (!isset($this->_style[strtolower($type)]) || !stristr($this->_style[strtolower($type)], $stdata))
				{
					$this->addStyleDeclaration($stdata, $type);
				}
			}
		}

		$this->_scripts = (isset($data['scripts']) && !empty($data['scripts']) && is_array($data['scripts']))
			? array_merge($this->_scripts, $data['scripts'])
			: $this->_scripts;

		if (isset($data['script']))
		{
			foreach ($data['script'] as $type => $sdata)
			{
				if (!isset($this->_script[strtolower($type)]) || !stristr($this->_script[strtolower($type)], $sdata))
				{
					$this->addScriptDeclaration($sdata, $type);
				}
			}
		}

		$this->_custom = (isset($data['custom']) && !empty($data['custom']) && is_array($data['custom']))
			? array_unique(array_merge($this->_custom, $data['custom']))
			: $this->_custom;

		return $this;
	}

	public function addHeadLink($href, $relation, $relType = 'rel', $attribs = array())
	{
		$this->_links[$href]['relation'] = $relation;
		$this->_links[$href]['relType'] = $relType;
		$this->_links[$href]['attribs'] = $attribs;

		return $this;
	}

	public function addFavicon($href, $type = 'image/vnd.microsoft.icon', $relation = 'shortcut icon')
	{
		$href = str_replace('\\', '/', $href);
		$this->addHeadLink($href, $relation, 'rel', array('type' => $type));

		return $this;
	}

	public function addCustomTag($html)
	{
		$this->_custom[] = trim($html);

		return $this;
	}

	public function getBuffer($type = null, $name = null, $attribs = array())
	{
		// If no type is specified, return the whole buffer
		if ($type === null)
		{
			return parent::$_buffer;
		}

		$result = null;
		if (isset(parent::$_buffer[$type][$name]))
		{
			return parent::$_buffer[$type][$name];
		}

		// If the buffer has been explicitly turned off don't display or attempt to render
		if ($result === false)
		{
			return null;
		}

		$renderer = $this->loadRenderer($type);
		if ($this->_caching == true && $type == 'modules')
		{
			$cache = MFactory::getCache('com_modules', '');
			$hash = md5(serialize(array($name, $attribs, $result, $renderer)));
			$cbuffer = $cache->get('cbuffer_' . $type);

			if (isset($cbuffer[$hash]))
			{
				return MCache::getWorkarounds($cbuffer[$hash], array('mergehead' => 1));
			}
			else
			{

				$options = array();
				$options['nopathway'] = 1;
				$options['nomodules'] = 1;
				$options['modulemode'] = 1;

				$this->setBuffer($renderer->render($name, $attribs, $result), $type, $name);
				$data = parent::$_buffer[$type][$name];

				$tmpdata = MCache::setWorkarounds($data, $options);

				$cbuffer[$hash] = $tmpdata;

				$cache->store($cbuffer, 'cbuffer_' . $type);
			}

		}
		else
		{
			$this->setBuffer($renderer->render($name, $attribs, $result), $type, $name);
		}

		return parent::$_buffer[$type][$name];
	}

	public function setBuffer($content, $options = array())
	{
		// The following code is just for backward compatibility.
		if (func_num_args() > 1 && !is_array($options))
		{
			$args = func_get_args();
			$options = array();
			$options['type'] = $args[1];
			$options['name'] = (isset($args[2])) ? $args[2] : null;
		}

		parent::$_buffer[$options['type']][$options['name']] = $content;

		return $this;
	}

	public function parse($params = array())
	{
		return $this->_fetchTemplate($params)->_parseTemplate();
	}

	public function render($caching = false, $params = array())
	{
		$this->_caching = $caching;

		if (!empty($this->_template))
		{
			$data = $this->_renderTemplate();
		}
		else
		{
			$this->parse($params);
			$data = $this->_renderTemplate();
		}

		parent::render();
		return $data;
	}

	public function countModules($condition)
	{
		$operators = '(\+|\-|\*|\/|==|\!=|\<\>|\<|\>|\<=|\>=|and|or|xor)';
		$words = preg_split('# ' . $operators . ' #', $condition, null, PREG_SPLIT_DELIM_CAPTURE);
		for ($i = 0, $n = count($words); $i < $n; $i += 2)
		{
			// odd parts (modules)
			$name = strtolower($words[$i]);
			$words[$i] = ((isset(parent::$_buffer['modules'][$name])) && (parent::$_buffer['modules'][$name] === false))
				? 0
				: count(MModuleHelper::getModules($name));
		}

		$str = 'return ' . implode(' ', $words) . ';';

		return eval($str);
	}

	public function countMenuChildren()
	{
		static $children = 0;

		return $children;
	}

	protected function _loadTemplate($directory, $filename)
	{
		//		$component	= MApplicationHelper::getComponentName();

		$contents = '';

		// Check to see if we have a valid template file
		if (file_exists($directory . '/' . $filename))
		{
			// Store the file path
			$this->_file = $directory . '/' . $filename;

			//get the file content
			ob_start();
			require $directory . '/' . $filename;
			$contents = ob_get_contents();
			ob_end_clean();
		}

		// Try to find a favicon by checking the template and root folder
		$path = $directory . '/';
		$dirs = array($path, MPATH_BASE . '/');
		foreach ($dirs as $dir)
		{
			$icon = $dir . 'favicon.ico';
			if (file_exists($icon))
			{
				$path = str_replace(MPATH_BASE . '/', '', $dir);
				$path = str_replace('\\', '/', $path);
				$this->addFavicon(MUri::base(true) . '/' . $path . 'favicon.ico');
				break;
			}
		}

		return $contents;
	}

	protected function _fetchTemplate($params = array())
	{
		// Check
		$directory = isset($params['directory']) ? $params['directory'] : 'templates';
		$filter = MFilterInput::getInstance();
		$template = $filter->clean($params['template'], 'cmd');
		$file = $filter->clean($params['file'], 'cmd');

		if (!file_exists($directory . '/' . $template . '/' . $file))
		{
			$template = 'system';
		}

		// Load the language file for the template
		$lang = MFactory::getLanguage();
		// 1.5 or core then 1.6

		$lang->load('tpl_' . $template, MPATH_BASE, null, false, false)
			|| $lang->load('tpl_' . $template, $directory . '/' . $template, null, false, false)
			|| $lang->load('tpl_' . $template, MPATH_BASE, $lang->getDefault(), false, false)
			|| $lang->load('tpl_' . $template, $directory . '/' . $template, $lang->getDefault(), false, false);

		// Assign the variables
		$this->template = $template;
		$this->baseurl = MUri::base(true);
		$this->params = isset($params['params']) ? $params['params'] : new MRegistry;

		// Load
		$this->_template = $this->_loadTemplate($directory . '/' . $template, $file);

		return $this;
	}

	protected function _parseTemplate()
	{
		$matches = array();

		if (preg_match_all('#<jdoc:include\ type="([^"]+)" (.*)\/>#iU', $this->_template, $matches))
		{
			$template_tags_first = array();
			$template_tags_last = array();

			// Step through the jdocs in reverse order.
			for ($i = count($matches[0]) - 1; $i >= 0; $i--)
			{
				$type = $matches[1][$i];
				$attribs = empty($matches[2][$i]) ? array() : MUtility::parseAttributes($matches[2][$i]);
				$name = isset($attribs['name']) ? $attribs['name'] : null;

				// Separate buffers to be executed first and last
				if ($type == 'module' || $type == 'modules')
				{
					$template_tags_first[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
				}
				else
				{
					$template_tags_last[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
				}
			}
			// Reverse the last array so the jdocs are in forward order.
			$template_tags_last = array_reverse($template_tags_last);

			$this->_template_tags = $template_tags_first + $template_tags_last;
		}

		return $this;
	}

	protected function _renderTemplate()
	{
		$replace = array();
		$with = array();

		foreach ($this->_template_tags as $jdoc => $args)
		{
			$replace[] = $jdoc;
			$with[] = $this->getBuffer($args['type'], $args['name'], $args['attribs']);
		}

		return str_replace($replace, $with, $this->_template);
	}
}
