<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MConfig {

	public $offline                 = '0';
	public $offline_message         = '';
	public $display_offline_message = '';
	public $offline_image           = '';
	public $sitename                = '';
	public $editor                  = 'tinymce';
	public $captcha                 = '';
	public $list_limit              = '30';
	public $access                  = '';
	public $debug                   = 0;
	public $debug_lang              = '';
	public $dbtype                  = 'mysql';
	public $host                    = '';
	public $user                    = '';
	public $password                = '';
	public $db                      = '';
	public $dbprefix                = '';
	public $live_site               = '';
	public $secret                  = 'WxMDqtjYHCV2754C';
	public $gzip                    = '';
	public $error_reporting         = 'default';
	public $helpurl                 = '';
	public $ftp_host                = '';
	public $ftp_port                = '21';
	public $ftp_user                = '';
	public $ftp_pass                = '';
	public $ftp_root                = '';
	public $ftp_enable              = '0';
	public $offset                  = 'UTC';
	public $mailer                  = '';
	public $mailfrom                = '';
	public $fromname                = '';
	public $sendmail                = '';
	public $smtpauth                = '0';
	public $smtpuser                = '';
	public $smtppass                = '';
	public $smtphost                = 'localhost';
	public $smtpsecure              = 'none';
	public $smtpport                = '25';
	public $caching                 = '';
	public $cache_handler           = 'file';
	public $cachetime               = '15';
	public $MetaDesc                = '';
	public $MetaKeys                = '';
	public $MetaTitle               = '';
	public $MetaAuthor              = '';
	public $MetaVersion             = '';
	public $robots                  = '';
	public $sef                     = '1';
	public $sef_rewrite             = '1';
	public $sef_suffix              = '0';
	public $unicodeslugs            = '0';
	public $feed_limit              = '10';
	public $log_path                = '';
	public $tmp_path                = '';
	public $lifetime                = '15';
	public $session_handler         = false;

	public function __construct() {
		global $wpdb;

		$this->host     = $wpdb->dbhost;
		$this->user     = $wpdb->dbuser;
		$this->password = $wpdb->dbpassword;
		$this->db       = $wpdb->dbname;
		$this->dbprefix = $wpdb->prefix;

		$this->sitename = get_site_option('blogname');
		$this->fromname = get_site_option('blogname');
		$this->access   = get_site_option('default_role');
        $this->sef      = (!is_admin() and get_site_option('permalink_structure')) ? '1' : '0';

		$temp_dir = get_temp_dir();

		$this->log_path   = $temp_dir;
		$this->tmp_path   = $temp_dir;
		$this->smtpport   = defined('SMTP_PORT') ? SMTP_PORT : '25';
		$this->smtpsecure = defined('SMTPSecure') ? SMTPSecure : 'none';
		$this->smtpauth   = defined('SMTPAuth') ? SMTPAuth : '0';
		$this->ftp_pass   = defined('FTP_PASS') ? FTP_PASS : '';
		$this->ftp_user   = defined('FTP_USER') ? FTP_USER : '';
		$this->ftp_host   = defined('FTP_HOST') ? FTP_HOST : '';
		$this->debug   	  = defined('WP_DEBUG') ? WP_DEBUG : false;
		$this->caching    = defined('WP_CACHE') ? WP_CACHE : false;
	}
}