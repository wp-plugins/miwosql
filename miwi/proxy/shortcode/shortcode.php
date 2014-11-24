<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MShortcode {

	public function __construct() {
		$this->document = MFactory::getDocument();
	}

	public function popup($plugin) {
		$layouts = array();

		// Get the views for this component.
		if (is_dir(MPATH_WP_PLG.'/'.$plugin.'/site')) {
			$folders = MFolder::folders(MPATH_WP_PLG.'/'.$plugin.'/site', '^view[s]?$', false, true);
		}
		$path = '';

		if (!empty($folders[0])) {
			$path = $folders[0];
		}

		if (is_dir($path)) {
			$views = MFolder::folders($path);
		}
		else {
			return false;
		}

		foreach ($views as $view) {
			// Ignore private views.
			if (strpos($view, '_') !== 0) {
				$layouts = array_merge($layouts, (array)$this->getLayoutsFromViews($plugin, $view));
			}
		}

		$pack = strtoupper($plugin).'_PACK';
		if (defined($pack) == 'Lite') {
			$this->formOutput($layouts, $plugin);
		}
	}

	protected function getLayoutsFromViews($plugin, $view) {
		$options     = array();
		$layouts     = array();
		$layoutNames = array();
		$app         = MFactory::getApplication();
		$path        = '';

		// Get the views for this component.
		if (is_dir(MPATH_WP_PLG.'/'.$plugin.'/site')) {
			$folders = MFolder::folders(MPATH_WP_PLG.'/'.$plugin.'/site', '^view[s]?$', false, true);
		}

		if (!empty($folders[0])) {
			$path = $folders[0].'/'.$view.'/tmpl';
		}

		if (is_dir($path)) {
			$layouts = array_merge($layouts, MFolder::files($path, '.xml$', false, true));
		}
		else {
			return $options;
		}

		// Build list of standard layout names
		foreach ($layouts as $layout) {
			// Ignore private layouts.
			if (strpos(basename($layout), '_') === false) {
				// Get the layout name.
				$layoutNames[] = basename($layout, '.xml');
			}
		}

		// Get the template layouts
		$tmpl = $app->getTemplate();

		// Array to hold association between template file names and templates
		$templateName = array();

		//@TODO : Do not forget that delete "com_" string
		if (is_dir(MPATH_THEMES.'/'.$tmpl.'/html/com_'.$plugin.'/'.$view)) {
			$templateLayouts = MFolder::files(MPATH_THEMES.'/'.$tmpl.'/html/com_'.$plugin.'/'.$view, '.xml$', false, true);

			foreach ($templateLayouts as $templateLayout) {
				// Get the layout name.
				$templateLayoutName = basename($templateLayout, '.xml');

				// add to the list only if it is not a standard layout
				if (array_search($templateLayoutName, $layoutNames) === false) {
					$layouts[] = $templateLayout;

					// Set template name array so we can get the right template for the layout
					$templateName[ $templateLayout ] = $tmpl;
				}
			}
		}

		// Process the found layouts.
		foreach ($layouts as $layout) {
			$file  = $layout;
			$array = array();
			// Ignore private layouts.
			if (strpos(basename($layout), '_') === false) {
				$form = new MForm(basename($layout));
				$form->loadFile($layout, true, '/metadata');

				if (is_file($file)) {
					// Attempt to load the xml file.
					if ($xml = simplexml_load_file($file)) {
						// Look for the first view node off of the root node.
						if ($menu = $xml->xpath('layout[1]')) {
							$menu = $menu[0];

							// If the view is hidden from the menu, discard it and move on to the next view.
							if (!empty($menu['hidden']) && $menu['hidden'] == 'true') {
								unset($xml);
								unset($o);
								continue;
							}

							// Populate the title and description if they exist.
							if (!empty($menu['title'])) {
								$array['title'] = trim((string)$menu['title']);
							}
						}
					}
				}

				// Add the layout to the options array.
				$array['form']                                  = $form;
				$options[ $view.'_'.basename($layout, '.xml') ] = $array;
			}
		}

		return $options;
	}

	public function formOutput($views, $plugin) {
		$title = explode('miwo', $plugin);

		?>
		<script type="text/javascript">
			jQuery(document).ready(function () {
				var plugin = '<?php echo $plugin; ?>';
				var selected_view = jQuery('#'+plugin+'_view_select option:selected').val();
				jQuery('#'+plugin+'-shortcode > div > #'+selected_view).show();
				jQuery('#'+plugin+'_view_select').change(function () {
					var new_view = jQuery(this).val();
					jQuery('#TB_ajaxContent > div > li').hide();
					jQuery('#TB_ajaxContent > div #'+new_view).show();
				});
			});
			function <?php echo $plugin; ?>Shortcode() {
				var win = window.dialogArguments || opener || parent || top;
				var plugin = '<?php echo $plugin; ?>';
				var string = '';
				var view = jQuery('#'+plugin+'_view_select option:selected').val();
				var children = jQuery('#'+view).children(':not(label,br)');
				view = view.split("_");
				if (view[2] !== 'default') {
					string += ' layout="'+view[2]+'"';
				}
				for (i = 0; i < children.length; i++) {
					var id = children[i].id.replace('request_', '');
					id = id.replace('request', '');
					string += ' '+id+'="'+children[i].value+'" ';
				}
				win.send_to_editor('[<?php echo $plugin; ?> view="'+view[1]+'"'+string+']');
			}
		</script>
		<style>
			#TB_window {height: 350px !important; width: 450px !important; margin-top: 130px !important;}
		</style>
	<div id="<?php echo $plugin; ?>-shortcode" style="display:none;">
		<h3><?php echo MText::sprintf('MLIB_X_SHORTCODE_HELPER', 'Miwo'.ucfirst($title[1])); ?></h3>

		<div class="miwi_shortcode_view">
			<label for="<?php echo $plugin; ?>view">View:</label>
			<select id="<?php echo $plugin; ?>_view_select" name="<?php echo $plugin; ?>_view_select">;
				<?php foreach ($views as $name => $view) { ?>
					<option value="<?php echo $plugin.'_'.$name; ?>"><?php echo $view['title']; ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="miwi_shortcode_fields">
			<?php foreach ($views as $name => $view) { ?>
				<li id="<?php echo $plugin.'_'.$name; ?>" style="display:none">
					<?php $hidden_fields = '';
					foreach ($view['form']->getFieldset('request') as $field) {
						if (!$field->hidden) {
							?>
							<?php echo $field->label; ?>
							<?php echo $field->input; ?><br/>
						<?php
						}
						else {
							$hidden_fields .= $field->input;
						}
					}
					echo $hidden_fields; ?>
				</li>
			<?php } ?>
		</div>
		<div>
			<button class="button" onclick="<?php echo $plugin; ?>Shortcode()"><?php echo MText::_('MLIB_ADD_SHORTCODE'); ?></button>
		</div>
		</div><?php
	}
}