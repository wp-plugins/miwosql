<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MHelperTags {

    protected $tagsChanged = false;
    protected $replaceTags = false;
    public $tags = null;
    public $typeAlias = null;

    public function getItemTags($contentType, $id, $getTagData = true) {
        return false;
        if ($getTagData) {
            $args = array('fields' => 'all');
        }
        else {
            $args = array('fields' => 'ids');
        }

        $tags = wp_get_post_terms($id, $contentType, $args);

        foreach ($tags as $tag) {
            $tag->tag_id = $tag->term_id;
            $tag->id     = $tag->term_id;
            if ($getTagData) {
                $tag->alias = $tag->slug;
                $tag->title = $tag->name;
            }
        }

        return $tags;
    }

    public function getTagIds($id, $prefix) {
        return false;
        if (!empty($id)) {
            $tags = wp_get_post_terms($id, $prefix, array('fields' => 'ids'));
            $tgs  = '';
            foreach ($tags as $tag) {
                $tgs[] = $tag->term_id;
            }

            $this->tags = implode(',', $tgs);
        }
        else {
            $this->tags = null;
        }

        return $this->tags;
    }

    public function getTagNames($tagIds) {
        return false;
        $tagNames = array();

        if (is_array($tagIds) && count($tagIds) > 0) {
            foreach ($tagIds as $tagId) {
                $tagNames[] = wp_get_post_terms($tagId, $this->typeAlias, array('fields' => 'names'));
            }
        }

        return $tagNames;
    }

    public function postStoreProcess($table, $newTags = array(), $replace = true) {
        return false;
        if (!empty($table->newTags) && empty($newTags)) {
            $newTags = $table->newTags;
        }

        // If existing row, check to see if tags have changed.
        $newTable = clone $table;
        $newTable->reset();
        $key       = $newTable->getKeyName();
        $typeAlias = $this->typeAlias;

        $result = false;

        if ($this->tagsChanged || (!empty($newTags) && $newTags[0] != '')) {
            $taxonomy_obj = get_taxonomy($typeAlias);
            if (is_array($newTags)) { // array = hierarchical, string = non-hierarchical.
                $newTags = array_filter($newTags);
            }
            if (current_user_can($taxonomy_obj->cap->assign_terms)) {
                $result = wp_set_post_terms($table->id, $newTags, $typeAlias);
                if (is_array($result) && count($result) > 0) {
                    $result = true;
                }
                elseif (is_object($result)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    public function preStoreProcess($table, $newTags = array()) {
        return false;
        if ($newTags != array()) {
            $this->newTags = $newTags;
        }

        // If existing row, check to see if tags have changed.
        $oldTable = clone $table;
        $oldTable->reset();
        $key       = $oldTable->getKeyName();
        $typeAlias = $this->typeAlias;

        if ($oldTable->$key && $oldTable->load()) {
            $this->oldTags = $this->getTagIds($oldTable->$key, $typeAlias);
        }

        // New items with no tags bypass this step.
        if ((!empty($newTags) && is_string($newTags) || (isset($newTags[0]) && $newTags[0] != '')) || isset($this->oldTags)) {
            if (is_array($newTags)) {
                $newTags = implode(',', $newTags);
            }
            // We need to process tags if the tags have changed or if we have a new row
            $this->tagsChanged = (empty($this->oldTags) && !empty($newTags)) || (!empty($this->oldTags) && $this->oldTags != $newTags) || !$table->$key;
        }
    }
}