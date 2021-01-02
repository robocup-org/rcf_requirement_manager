<?php 
/**
 * @package  RCF Req Plugin
 */
namespace Inc\Pages;

/**
* 
*/
class Admin
{
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
	}

	public function add_admin_pages() {
		add_submenu_page( 'edit.php?post_type=requirement', 'RCF Req Plugin', 'Settings', 'manage_options', 'rcf_req_plugin', array( $this, 'admin_index' ) );
	}

	public function admin_index() {
		require_once PLUGIN_PATH . 'templates/admin.php';
	}
}