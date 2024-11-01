<?php
/*
Plugin Name: WP Cookie Consent
Plugin URI: http://sproutee.com/wp-cookie-consent-plugin
Description: Cookie consent plugin that allows for easy customization of messaging and conditional inclusion of code on cookie acceptance.
Version: 1.0
Author: Sproutee Solutions
Author URI: http://sproutee.com
License: GPL2
*/

/*  Copyright 2012  Sproutee Solutions

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php

// some definition we will use
define( 'CCFD_PUGIN_NAME', 'WP Cookie Consent');
define( 'CCFD_PLUGIN_DIRECTORY', 'wp-cookie-consent');
define( 'CCFD_CURRENT_VERSION', '1.0' );
define( 'CCFD_CURRENT_BUILD', '1' );
define( 'CCFD_DEBUG', false);
// i18n plugin domain for language files
define( 'EMU2_I18N_DOMAIN', 'ccfd' );


// load language files
function ccfd_set_lang_file() {
	# set the language file
	$currentLocale = get_locale();
	if(!empty($currentLocale)) {
		$moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
		if (@file_exists($moFile) && is_readable($moFile)) {
			load_textdomain(EMU2_I18N_DOMAIN, $moFile);
		}

	}
}
ccfd_set_lang_file();

// create custom plugin settings menu
add_action( 'admin_menu', 'ccfd_create_menu' );

//call register settings function
add_action( 'admin_init', 'ccfd_register_settings' );


register_activation_hook(__FILE__, 'ccfd_activate');
register_deactivation_hook(__FILE__, 'ccfd_deactivate');
register_uninstall_hook(__FILE__, 'ccfd_uninstall');

// activating the default values
function ccfd_activate() {
	add_option('warning_text', 'We use, we bake and we eat cookies. By browsing our site you agree to our use of cookies.');
	add_option('accept_text', 'Okay!');
	add_option('learn_more_text', 'Learn more');
	add_option('learn_more_link', 'http://www.mysite.com/privacy-policy/');
	add_option('conditional_code', '');
	add_option('base_css', '#ccfd-eucookielaw { background-color: #eeeeee; }' . "\n" . '#ccfd-cookiewarning { }' . "\n" . '#ccfd-removecookie { }' . "\n" . '#ccfd-more { }' );
}

// deactivating
function ccfd_deactivate() {
	delete_option('warning_text');
	delete_option('accept_text');
	delete_option('learn_more_text');
	delete_option('learn_more_link');
	delete_option('conditional_code');
	delete_option('base_css');
}

// uninstalling
function ccfd_uninstall() {
	delete_option('warning_text');
	delete_option('accept_text');
	delete_option('learn_more_text');
	delete_option('learn_more_link');
	delete_option('conditional_code');
	delete_option('base_css');
}

function ccfd_create_menu() {
	// create new top-level menu
	add_menu_page( 
	__('Cookie Consent', EMU2_I18N_DOMAIN),
	__('Cookie Consent', EMU2_I18N_DOMAIN),
	0,
	CCFD_PLUGIN_DIRECTORY.'/cookie_consent_settings_page.php',
	'',
	plugins_url('/images/icon.png', __FILE__));
}

add_action('admin_head', 'admin_register_head');
function admin_register_head() {
    $siteurl = get_option('siteurl');
    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/ccfd-admin.css';
    echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
}

function ccfd_register_settings() {
	//register settings
	register_setting( 'ccfd-settings-group', 'warning_text' );
	register_setting( 'ccfd-settings-group', 'accept_text' );
	register_setting( 'ccfd-settings-group', 'learn_more_text' );
	register_setting( 'ccfd-settings-group', 'learn_more_link' );
	register_setting( 'ccfd-settings-group', 'conditional_code' );
	register_setting( 'ccfd-settings-group', 'base_css' );
}

// check if debug is activated
function ccfd_debug() {
	# only run debug on localhost
	if ($_SERVER["HTTP_HOST"]=="localhost" && defined('CCFD_DEBUG') && CCFD_DEBUG==true) return true;
}

function ccfd_setup_styles() {
	return '<style type="text/css">' .  get_option('base_css') . "\n" . '<!--#ccfd-eucookielaw { display:none }--></style>';
}

function ccfd_setup_cookie() {
	if(!isset($_COOKIE['eucookie'])){
		return '<script type="text/javascript">
			function setCookie(c_name,value,exdays){
				var exdate=new Date();
				exdate.setDate(exdate.getDate() + exdays);
				var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
				document.cookie=c_name + "=" + c_value;
			}
			</script>
			<div id="ccfd-eucookielaw" >
				<p id="ccfd-cookiewarning">' . get_option('warning_text') . '
				<a id="ccfd-removecookie">' . get_option('accept_text') . '</a> <a id="ccfd-more" href="' . get_option('learn_more_link') . '">' . get_option('learn_more_text') . '</a></p>
			</div>
			<script type="text/javascript">
				if( document.cookie.indexOf("eucookie") ===-1 ){
					jQuery("#ccfd-eucookielaw").show();
				}
				jQuery("#ccfd-removecookie").click(function () {
					setCookie(\'eucookie\',\'eucookie\',365*10)
					jQuery("#ccfd-eucookielaw").remove();
			    });
			</script>';
	}
	else{
		return '';
	}
	
}

function ccfd_get_conditional_text() {
	if(isset($_COOKIE['eucookie'])){
		return '<script type="text/javascript">' . esc_js(get_option('conditional_code')) . '</script>';
	}
	else {
		return '';
	}
}

add_action('wp_head','ccfd_render');
function ccfd_render() {
	$style = ccfd_setup_styles();
	$setup_cookie = ccfd_setup_cookie();
	$conditional_text = ccfd_get_conditional_text();
	
	echo $style . $setup_cookie . $conditional_text;
}
?>