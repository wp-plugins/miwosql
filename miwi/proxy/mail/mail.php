<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MMail extends MObject {

	public function sendMail($from_mail = null, $from_name = null, $to_mail, $subject, $body, $is_html = true, $cc = null, $bcc = null, $attachment = null, $reply_to = null, $reply_to_name = null) {
		$this->from_mail = $from_mail;
		$this->from_name = $from_name;
		$this->to_mail = $to_mail;
		$this->subject = $subject;
		$this->body = $body;
		$this->is_html = $is_html;
		$this->cc = $cc;
		$this->bcc = $bcc;
		$this->attachment = $attachment;
		$this->reply_to = $reply_to;
		$this->reply_to_name = $reply_to_name;
		
		if (empty($this->from_mail)) {
			$this->from_mail = MFactory::getWOption('admin_email');
		}
		
		if (empty($this->from_name)) {
			$this->from_name = MFactory::getWOption('blogname');
		}

		$this->fromWP();
	}
	
	public function fromWP() {
		$headers = array();
		
		$headers[] = 'Content-type: text/html';
		
		if (!empty($this->cc)) {
			$headers[] = 'cc: '.$this->cc;
		}
		
		if (!empty($this->bcc)) {
			$headers[] = 'bcc: '.$this->bcc;
		}
		
		if (!empty($this->from_mail)) {
			//$headers[] = 'from: '.$this->from_mail;
			//add_filter('wp_mail_from', 'mmail_wp_mail_from');
			add_filter('wp_mail_from', array($this, '_getFromMail'));
		}
		
		if (!empty($this->from_name)) {
			//add_filter('wp_mail_from_name', 'mmail_wp_mail_from_name');
			add_filter('wp_mail_from_name', array($this, '_getFromName'));
		}
		
		$attachments = array();
		if (!empty($this->attachment)) {
			$attachments[] = $this->attachment;
		}
		
		wp_mail($this->to_mail, $this->subject, $this->body, $headers, $attachments);
	}

	public function _getFromMail() {
		return $this->from_mail;
	}

	public function _getFromName() {
		return $this->from_name;
	}
}