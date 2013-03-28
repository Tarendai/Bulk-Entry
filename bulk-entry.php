<?php
/*
Plugin Name: Bulk Entry
Plugin URI: http://interconnectit.com
Description: A tool for the bulk entry of posts, pages, etc
Version: 1.0
Author: Tom J Nowell
Author Email: contact@tomjn.com
License:

  Copyright 2011 Tom J Nowell (contact@tomjn.com)

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

class BulkEntry {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const NAME = 'Bulk Entry';
	const SLUG = 'bulk_entry';

	public $last_editor_id = 0;


	public $mcesettings = array();

	/**
	 * Constructor
	 */
	function __construct() {
		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_bulk_entry' ) );
		add_action( 'wp_ajax_bulk_entry_new_card', array( &$this, 'wp_ajax_bulk_entry_new_card' ) );
		add_action( 'wp_ajax_bulk_entry_submit_post', array( &$this, 'wp_ajax_bulk_entry_submit_post' ) );
		add_action( 'after_wp_tiny_mce', array( $this, 'steal_away_mcesettings' ) );
	}

	/**
	 * Runs when the plugin is activated
	 */
	function install_bulk_entry() {
		// do not generate any output here
	}

	/**
	 * Runs when the plugin is initialized
	 */
	function init_bulk_entry() {
		// Setup localization
		load_plugin_textdomain( self::SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();


		if ( is_admin() ) {
			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'admin_menu', array( $this, 'action_callback_admin_menu' ) );

	}

	function action_callback_admin_menu() {
		// TODO define your action method here
		add_management_page( 'Bulk Entry', 'Bulk Entry', 'edit_posts', self::SLUG, array( $this, 'admin_menu_page' ) );
	}

	function steal_away_mcesettings( $mcesettings ) {
		$this->mcesettings = $mcesettings;
	}

	function get_editor_id() {
		$this->last_editor_id++;
		return 'bulk-entry-editor'.time().$this->last_editor_id;
	}

	function wp_ajax_bulk_entry_submit_post() {
		$reply = $this->start_block();
		$reply .= $this->start_left_block();
		$reply .= "&nbsp;";
		$reply .= $this->end_left_block();
		$reply .= $this->start_right_block();
		$reply .= '<div class="bulk-entry-block--content bulk-entry-card--content">';
		$type = $_POST['bulk_entry_posttype'];
		$status = $_POST['bulk_entry_poststatus'];
		$content = $_POST['bulk_entry_postcontent'];
		$title = $_POST['bulk_entry_posttitle'];
		// Create post object
		$my_post = array(
			'post_title'    => $title,
			'post_content'  => $content,
			'post_status'   => $status
		);

		// Insert the post into the database
		$id = wp_insert_post( $my_post );
		$permalink = get_permalink( $id );
		$editlink = get_edit_post_link( $id );
		$reply .= '<p><a href="#" class="bulk-entry-card-delete" ><b>x</b></a> "'.$title.'" created, <a href="'.$editlink.'">open in full editor</a> or <a href="'.$permalink.'">click here to view </a></p>';
		$reply .= '</div>';
		$reply .= $this->end_right_block();
		$reply .= $this->end_block();
		echo '{ "content" : '.json_encode( $reply ).' }';
//		error_log();
//		error_log( print_r( $_POST, true ) );
		die();
	}

	function wp_ajax_bulk_entry_new_card() {

		ob_start();
		for ( $i = 0; $i < absint( $_POST['bulk_entry_postcount'] ); $i++ ) {
			echo $this->card();
		}
		ob_start();
		_WP_Editors::editor_js();
		ob_end_clean();
		$content = ob_get_contents();
		ob_end_clean();

		$mceInit = $qtInit = '';

		$ids = array();

		foreach ( $this->mcesettings as $editor_id => $init ) {
			$ids[] = $editor_id;
			//$options = self::tinymce_parse_init( $init );
			$qtInit .= '"'.$editor_id.'":{"id":"'.$editor_id.'","buttons":"strong,em,link,block,del,ins,img,ul,ol,li,code,more,spell,close"},';
		}
		$qtInit = '{' . trim( $qtInit, ',' ) . '}';

		$data = array( 'content' => $content, 'qtInit' => $qtInit, 'mtInit' => $mceInit );

		$data = '{ "content": '.json_encode( $content ).',"qtInit": '.$qtInit.', "editor_ids" : '.json_encode( $ids ).' }';
		echo $data;
		die();

	}


	function admin_menu_page() {
		echo '<div class="wrap bulk-entry--wrap">';
		echo '<h2>Bulk Entry</h2>';
		echo '<div style="display:none;">';
		wp_editor( 'preload', $this->get_editor_id(), array( 'teeny' => true ) );
		echo '</div>';
		echo $this->toolbar();
		echo '<div class="bulk-entry-block"><hr></div>';
		$canvas = '<div id="bulk-entry-canvas" class="bulk-entry-canvas">';
		/*$canvas .= $this->card();
		$canvas .= $this->card();
		$canvas .= $this->card();
		*/
		$canvas .= '</div>';
		echo $canvas;
		echo '</div>';
	}

	function start_block() {
		$block = '<div class="bulk-entry-block">';
		return $block;
	}

	function start_left_block(){
		$block = '<div class="bulk-entry-block--left"><div class="bulk-entry-block--label">';
		return $block;
	}
	function start_right_block(){
		$block = '<div class="bulk-entry-block--right">';;
		return $block;
	}

	function end_block() {
		$block = '</div>';
		return $block;
	}

	function end_left_block() {
		$block = '</div></div>';
		return $block;
	}
	function end_right_block() {
		$block = '</div>';
		return $block;
	}

	function toolbar() {
		$toolbar = $this->start_block();
		$toolbar .= $this->start_left_block();
		$toolbar .= "I'd like a ";
		$toolbar .= $this->end_left_block();
		$toolbar .= $this->start_right_block();
		$toolbar .= '<div id="bulk-entry-toolbar" class="bulk-entry-toolbar">';
		$toolbar .= '<table class="widefat mceToolbar mceToolbarRow1 Enabled"><tr><td>';

		$toolbar .= '<div class="bulk-entry-toolbar-field">';
		$toolbar .= '<input type="hidden" id="bulk-entry-add-post-count" name="bulk-entry-add-post-count" class="bulk-entry-toolbar-field--number" value="1"/>';
		$toolbar .= '</div>';

		$toolbar .= '<div class="bulk-entry-toolbar-field">';

		$stati = get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' );
		$toolbar .= '<select id="bulk-entry-add-post-status" name="bulk-entry-add-post-status" class="">';
		foreach ( $stati as $status ) {
			$toolbar .= '<option value="'.$status->name.'">'.$status->label.'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= '</div>';

		$args = array(
			'show_ui' => true
		);
		$post_types = get_post_types( $args, 'objects' );

		$toolbar .= '<div class="bulk-entry-toolbar-field">';
		$toolbar .= '<select id="bulk-entry-add-post-type" name="bulk-entry-add-post-type" class="">';
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'editor' ) && post_type_supports( $post_type->name, 'title' ) ) {
				$toolbar .= '<option value="'.$post_type->name.'">'.$post_type->labels->singular_name.'</option>';
			}
		}
		$toolbar .= '</select>';
		$toolbar .= '</div>';

		$toolbar .= '<div class="bulk-entry-toolbar-field">';
		$toolbar .= '<input id="bulk-entry-toolbar-add-posts" type="button" name="bulk-entry-add-cards-button" class="button button-primary" value="Go"/>';
		$toolbar .= '</div>';

		$toolbar .= '</td></tr></table>';
		$toolbar .= '</div>';
		$toolbar .= $this->end_right_block();
		$toolbar .= $this->end_block();
		return $toolbar;
	}

	function card() {

		$card = $this->start_block();
		$card .= '<form method="post">';
		$card .= $this->start_left_block();
		$poststatus = $_POST['bulk_entry_poststatus'];
		$posttype = $_POST['bulk_entry_posttype'];

		$type = get_post_type_object( $posttype );
		$status = get_post_stati( array( 'name' => $poststatus ), 'objects' );
		$status = $status[$poststatus];

		$card .= $status->label.' ';
		//
		$card .= $type->labels->singular_name;
		$card .= $this->end_left_block();
		$card .= $this->start_right_block();
		$card .= '<div class="bulk-entry-block--content bulk-entry-card--content">';
		$card .= '<div class="bulk-entry-card-field"><input type="text" name="bulk-entry-card--title" class="widefat bulk-entry-card--title" value="Title"/></div>';
		$editor_id = $this->get_editor_id();
		ob_start();
		wp_editor( 'content', $editor_id, array( 'textarea_rows' => 10, 'media_buttons' => false, 'teeny' => true ) );
		$editor = ob_get_contents();
		ob_end_clean();
		$card .= '<div class="bulk-entry-card-field">'.$editor.'</div>';
		$card .= '<div class="bulk-entry-card--buttons">';
		$card .= '<a href="#" class="bulk-entry-card-control bulk-entry-card-delete" >Discard</a> <input type="submit" class="bulk-entry-card-control button button-primary" value="Save"/>';
		$card .= '</div>';
		$card .= '</div>';
		$card .= $this->end_right_block();
		$card .= '<input type="hidden" name="bulk_entry_editor_id" value="'.$editor_id.'" />';
		$card .= '<input type="hidden" name="bulk_entry_poststatus" value="'.$poststatus.'" />';
		$card .= '<input type="hidden" name="bulk_entry_posttype" value="'.$posttype.'" />';
		//$card .= '<input type="hidden" name="bulk_entry_" value="'.$_POST[''].'" />';
		$card .= '</form>';
		$card .= $this->end_block();
		return $card;
	}

	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {
			$this->load_file( self::SLUG . '-admin-script', '/js/admin.js', true );
			$this->load_file( self::SLUG . '-admin-style', '/css/admin.css' );
		}
	} // end register_scripts_and_styles

	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url( $file_path, __FILE__ );
		$file = plugin_dir_path( __FILE__ ) . $file_path;

		if ( file_exists( $file ) ) {
			if ( $is_script ) {
				wp_register_script( $name, $url, array( 'jquery' ) ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
} // end class
new BulkEntry();


// twitter tests
function icit_get_twitter_follower_count( $twitter_id ) {
	$url = 'http://twitter.com/users/show/'.$twitter_id;
	$response = wp_remote_get( $url );
	$t_profile = new SimpleXMLElement( $response['body'] );
	$count = $t_profile->followers_count;

	return $count;
}
/*
class ICIT_Log_Twitter_Cron {

	public $log = null;
	public $frequency = 'hourly';
	public $username = '';
	public $hook_name = '';

	public function __construct( $log, $twitter_id, $frequency = 'hourly' ) {
		$this->log = $log;
		$this->username = $username;
		$this->frequency = $frequency;

		$this->hook_name = 'icit_auditlogger_'.$log->logname.'_twitter_id_count_'.$twitter_id;
		$this->add_cron();
		add_action( $this->hook_name, array( $this, 'cron_task' ) );
	}

	public function add_cron() {
		if (  !wp_next_scheduled( $this->hook_name ) ) {

			wp_schedule_event( time(), $this->frequency, $this->hook_name );
		}

	}

	public function remove_cron() {
		wp_clear_scheduled_hook( $this->hook_name );
	}

	public function cron_task() {
		//$this->log->removeLogsBeforeCap( $this->cap );
		$count = icit_get_twitter_follower_count( $this->username );
		$logquery = new ICIT_Log_Query( array( 'type' => 'twitter_follower_count', 'subject' => $this->username ));
	}

	public function set_new_cap( $new_cap ) {
		$this->cap = $new_cap;
		$this->remove_cron();
		$this->add_cron();
	}
}

global $auditlog;
$audit_primary_cap = new ICIT_Log_Twitter_Cron( $auditlog, 'tarendai', 'hourly' );

function icit_get_twitter_follower_count( $twitter_id ) {
	$url = 'http://twitter.com/users/show/'.$twitter_id;
	$response = wp_remote_get( $url );
	$t_profile = new SimpleXMLElement( $response['body'] );
	$count = $t_profile->followers_count;

	return $count;
}

wp_die( icit_get_twitter_follower_count( 'tarendai' ) );*/