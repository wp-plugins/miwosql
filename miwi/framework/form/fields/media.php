<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldMedia extends MFormField {

    protected $type = 'Media';

    protected static $initialised = false;

    protected function getInput() {
        $assetField  = $this->element['asset_field'] ? (string)$this->element['asset_field'] : 'asset_id';
        $authorField = $this->element['created_by_field'] ? (string)$this->element['created_by_field'] : 'created_by';
        $asset       = $this->form->getValue($assetField) ? $this->form->getValue($assetField) : (string)$this->element['asset_id'];
        if ($asset == '') {
            $asset = MRequest::getCmd('option');
        }

        $link = (string)$this->element['link'];
        if (!self::$initialised) {

            // Load the modal behavior script.
            MHtml::_('behavior.modal');

            // Build the script.
            $script   = array();
            $script[] = '	function jInsertFieldValue(value, id) {';
            $script[] = '		var old_value = document.id(id).value;';
            $script[] = '		if (old_value != value) {';
            $script[] = '			var elem = document.id(id);';
            $script[] = '			elem.value = value;';
            $script[] = '			elem.fireEvent("change");';
            $script[] = '			if (typeof(elem.onchange) === "function") {';
            $script[] = '				elem.onchange();';
            $script[] = '			}';
            $script[] = '			jMediaRefreshPreview(id);';
            $script[] = '		}';
            $script[] = '	}';

            $script[] = '	function jMediaRefreshPreview(id) {';
            $script[] = '		var value = document.id(id).value;';
            $script[] = '		var img = document.id(id + "_preview");';
            $script[] = '		if (img) {';
            $script[] = '			if (value) {';
            $script[] = '				img.src = "' . MURI::root() . '" + value;';
            $script[] = '				document.id(id + "_preview_empty").setStyle("display", "none");';
            $script[] = '				document.id(id + "_preview_img").setStyle("display", "");';
            $script[] = '			} else { ';
            $script[] = '				img.src = ""';
            $script[] = '				document.id(id + "_preview_empty").setStyle("display", "");';
            $script[] = '				document.id(id + "_preview_img").setStyle("display", "none");';
            $script[] = '			} ';
            $script[] = '		} ';
            $script[] = '	}';

            $script[] = '	function jMediaRefreshPreviewTip(tip)';
            $script[] = '	{';
            $script[] = '		tip.setStyle("display", "block");';
            $script[] = '		var img = tip.getElement("img.media-preview");';
            $script[] = '		var id = img.getProperty("id");';
            $script[] = '		id = id.substring(0, id.length - "_preview".length);';
            $script[] = '		jMediaRefreshPreview(id);';
            $script[] = '	}';

            // Add the script to the document head.
            MFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

            self::$initialised = true;
        }

        // Initialize variables.
        $html = array();
        $attr = '';

        // Initialize some field attributes.
        $attr .= $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $attr .= $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';

        // Initialize JavaScript field attributes.
        $attr .= $this->element['onchange'] ? ' onchange="' . (string)$this->element['onchange'] . '"' : '';

        // The text field.
        $html[] = '<div class="fltlft">';
        $html[] = '	<input type="text" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
            . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . ' readonly="readonly"' . $attr . ' />';
        $html[] = '</div>';

        $directory = (string)$this->element['directory'];
        if ($this->value && file_exists(MPATH_ROOT . '/' . $this->value)) {
            $folder = explode('/', $this->value);
            array_shift($folder);
            array_pop($folder);
            $folder = implode('/', $folder);
        }
        elseif (file_exists(MPATH_ROOT . '/' . MComponentHelper::getParams('com_media')->get('image_path', 'images') . '/' . $directory)) {
            $folder = $directory;
        }
        else {
            $folder = '';
        }
        // The button.
        $html[] = '<div class="button2-left">';
        $html[] = '	<div class="blank">';
        $html[] = '		<a class="modal" title="' . MText::_('MLIB_FORM_BUTTON_SELECT') . '"' . ' href="'
            . ($this->element['readonly'] ? ''
                : ($link ? $link
                    : 'index.php?option=com_media&amp;view=images&amp;tmpl=component&amp;asset=' . $asset . '&amp;author='
                    . $this->form->getValue($authorField)) . '&amp;fieldid=' . $this->id . '&amp;folder=' . $folder) . '"'
            . ' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
        $html[] = MText::_('MLIB_FORM_BUTTON_SELECT') . '</a>';
        $html[] = '	</div>';
        $html[] = '</div>';

        $html[] = '<div class="button2-left">';
        $html[] = '	<div class="blank">';
        $html[] = '		<a title="' . MText::_('MLIB_FORM_BUTTON_CLEAR') . '"' . ' href="#" onclick="';
        $html[] = 'jInsertFieldValue(\'\', \'' . $this->id . '\');';
        $html[] = 'return false;';
        $html[] = '">';
        $html[] = MText::_('MLIB_FORM_BUTTON_CLEAR') . '</a>';
        $html[] = '	</div>';
        $html[] = '</div>';

        // The Preview.
        $preview       = (string)$this->element['preview'];
        $showPreview   = true;
        $showAsTooltip = false;
        switch ($preview) {
            case 'false':
            case 'none':
                $showPreview = false;
                break;
            case 'true':
            case 'show':
                break;
            case 'tooltip':
            default:
                $showAsTooltip = true;
                $options       = array(
                    'onShow' => 'jMediaRefreshPreviewTip',
                );
                MHtml::_('behavior.tooltip', '.hasTipPreview', $options);
                break;
        }

        if ($showPreview) {
            if ($this->value && file_exists(MPATH_ROOT . '/' . $this->value)) {
                $src = MURI::root() . $this->value;
            }
            else {
                $src = '';
            }

            $attr            = array(
                'id'    => $this->id . '_preview',
                'class' => 'media-preview',
                'style' => 'max-width:160px; max-height:100px;'
            );
            $img             = MHtml::image($src, MText::_('MLIB_FORM_MEDIA_PREVIEW_ALT'), $attr);
            $previewImg      = '<div id="' . $this->id . '_preview_img"' . ($src ? '' : ' style="display:none"') . '>' . $img . '</div>';
            $previewImgEmpty = '<div id="' . $this->id . '_preview_empty"' . ($src ? ' style="display:none"' : '') . '>'
                . MText::_('MLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>';

            $html[] = '<div class="media-preview fltlft">';
            if ($showAsTooltip) {
                $tooltip = $previewImgEmpty . $previewImg;
                $options = array(
                    'title' => MText::_('MLIB_FORM_MEDIA_PREVIEW_SELECTED_IMAGE'),
                    'text'  => MText::_('MLIB_FORM_MEDIA_PREVIEW_TIP_TITLE'),
                    'class' => 'hasTipPreview'
                );
                $html[]  = MHtml::tooltip($tooltip, $options);
            }
            else {
                $html[] = ' ' . $previewImgEmpty;
                $html[] = ' ' . $previewImg;
            }
            $html[] = '</div>';
        }

        return implode("\n", $html);
    }
}
