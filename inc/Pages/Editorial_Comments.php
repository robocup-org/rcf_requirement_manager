<?php
namespace Inc\Pages;
// namespace \;
/**
 * 
 * class EF_Editorial_Comments
 * Threaded commenting in the admin for discussion between writers and editors
 *
 * @author batmoo
 */

class Editorial_Comments
{
	// This is comment type used to differentiate editorial comments
	const comment_type = 'req_editorial-comment';

	/**
	 * Initialize the rest of the stuff in the class if the module is active
	 */
	function register()
	{
        add_action('save_post', array($this, 'commentfromsubmit'), 10, 3);

		add_action('add_meta_boxes', array($this, 'add_post_meta_box'),11);
		add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
		add_action('wp_ajax_editflow_ajax_insert_comment', array($this, 'ajax_insert_comment'));
	}


	/**
	 * Checks for the current post type
	 *
	 * @since 0.7
	 * @return string|null $post_type The post type we've found, or null if no post type
	 */
	function get_current_post_type()
	{
		global $post, $typenow, $pagenow, $current_screen;
		//get_post() needs a variable
		$post_id = isset($_REQUEST['post']) ? (int)$_REQUEST['post'] : false;

		if ($post && $post->post_type) {
			$post_type = $post->post_type;
		} elseif ($typenow) {
			$post_type = $typenow;
		} elseif ($current_screen && !empty($current_screen->post_type)) {
			$post_type = $current_screen->post_type;
		} elseif (isset($_REQUEST['post_type'])) {
			$post_type = sanitize_key($_REQUEST['post_type']);
		} elseif (
			'post.php' == $pagenow
			&& $post_id
			&& !empty(get_post($post_id)->post_type)
		) {
			$post_type = get_post($post_id)->post_type;
		} elseif ('edit.php' == $pagenow && empty($_REQUEST['post_type'])) {
			$post_type = 'post';
		} else {
			$post_type = null;
		}

		return $post_type;
	}
	/**
	 * Load any of the admin scripts we need but only on the pages we need them
	 */
	function add_admin_scripts()
	{
		global $pagenow;

		$post_type = $this->get_current_post_type();

		if (!in_array($post_type, ['requirement']))
			return;

		if (!in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'page-new.php')))
			return;

			
		wp_enqueue_script('edit_flow-post_comment', PLUGIN_URL . 'assets/editorial-comments.js', array('jquery', 'wp-ajax-response'), 1, true);
		wp_localize_script('edit_flow-post_comment', '__ef_localize_post_comment', array(
			'and'           => esc_html__('and', 'edit-flow'),
			'none_notified' => esc_html__('No one will be notified.', 'edit-flow'),
		));

		wp_enqueue_style('edit-flow-editorial-comments-css',  PLUGIN_URL . 'assets/editorial-comments.css', false, 1, 'all');

		$thread_comments = (int) get_option('thread_comments');
?>
		<script type="text/javascript">
			var ef_thread_comments = <?php echo ($thread_comments) ? $thread_comments : 0; ?>;
		</script>
	<?php

	}

	/**
	 * Add the editorial comments metabox to enabled post types
	 *
	 * @uses add_meta_box()
	 */
	function add_post_meta_box()
	{
		global $post;
		if ($post==null||in_array($post->post_status, array('new', 'auto-draft'))) 
			return;
		$supported_post_types = ['requirement'];
		foreach ($supported_post_types as $post_type)
			add_meta_box('editorial-comments', 'Comments', array($this, 'editorial_comments_meta_box'), $post_type,"side", "high");
	}

	function editorial_comments_meta_box()
	{
		global $post, $post_ID;
	?>
		<div id="ef-comments_wrapper">
			<?php $this->the_comment_form(); ?>
			<a name="editorialcomments"></a>

			<?php
			// Show comments only if not a new post
			if (!in_array($post->post_status, array('new', 'auto-draft'))) :

				// Unused since switched to wp_list_comments
				$editorial_comments = get_comments(
					array(
						'post_id' => $post->ID,
						'comment_type' => '',
						'orderby' => 'comment_date',
						'order' => 'DESC',
						'status' => 1
					)
				);
				
			?>
				
				
				<div class="clear"></div>
				<ul id="ef-comments">
					<?php
					// We use this so we can take advantage of threading and such

					wp_list_comments(
						array(
							'callback' => array($this, 'the_comment'),
							'end-callback' => '__return_false'
						),
						$editorial_comments
					);
					?>
				</ul>

			

			<?php
			else :
			?>
				<p><?php _e('You can add editorial comments to a post once you\'ve saved it for the first time.', 'edit-flow'); ?></p>
			<?php
			endif;
			?>
			
		</div>
		<div class="clear"></div>
	<?php
	}

	/**
	 * Displays the main commenting form
	 */
	function the_comment_form()
	{
		global $post;

	?>
		<a href="#" id="ef-comment_respond" onclick="editorialCommentReply.open();return false;" class="button-primary alignright hide-if-no-js" title="Add Comment"><span>Add Comment</span></a>

		<!-- Reply form, hidden until reply clicked by user -->
		<div id="ef-replyrow" style="display:none">
			<div id="ef-replycontainer">
				<textarea id="ef-replycontent" name="replycontent" cols="40" rows="5"></textarea>
			</div>

			
			<p id="ef-replysubmit">
				<a class="ef-replysave button-primary alignright" href="#comments-form">
					<span id="ef-replybtn"><?php _e('Submit Comment', 'edit-flow') ?></span>
				</a>
				<a class="ef-replycancel button-secondary alignright" href="#comments-form"><?php _e('Cancel', 'edit-flow'); ?></a>
				<img alt="Sending comment..." src="<?php echo admin_url('/images/wpspin_light.gif') ?>" class="alignright" style="display: none;" id="ef-comment_loading" />
				<br class="clear" style="margin-bottom:35px;" />
				<span style="display: none;" class="error"></span>
			</p>

			<input type="hidden" value="" id="ef-comment_parent" name="ef-comment_parent" />
			<input type="hidden" name="ef-post_id" id="ef-post_id" value="<?php echo esc_attr($post->ID); ?>" />

			<?php wp_nonce_field('comment', 'ef_comment_nonce', false); ?>

			<br class="clear" />
		</div>

	<?php
	}

	/**
	 * Maybe display who was notified underneath an editorial comment.
	 *
	 * @param int $comment_id
	 * @return void
	 */
	function maybe_output_comment_meta($comment_id)
	{

		// $notification = get_comment_meta($comment_id, 'notification_list', true);

		// if (empty($notification)) {
		// 	$message = esc_html__('No users or groups were notified.', 'edit-flow');
		// } else {
		// 	$message = '<strong>' . esc_html__('Notified', 'edit-flow') . ':</strong> ' . esc_html($notification);
		// }

		// echo '<p class="ef-notification-meta">' . $message . '</p>';
	}

	/**
	 * Displays a single comment
	 */
	function the_comment($comment, $args, $depth)
	{
	
		global $current_user, $userdata;

		// Get current user
		wp_get_current_user();

		$GLOBALS['comment'] = $comment;

		$actions = array();

		$actions_string = '';
		// Comments can only be added by users that can edit the post
		//if (current_user_can('edit_post', $comment->comment_post_ID)) 
		{
			$actions['reply'] = '<a onclick="editorialCommentReply.open(\'' . $comment->comment_ID . '\',\'' . $comment->comment_post_ID . '\');return false;" class="vim-r hide-if-no-js" title="' . __('Reply to this comment', 'edit-flow') . '" href="#">' . __('Reply', 'edit-flow') . '</a>';

			$sep = ' ';
			$i = 0;
			foreach ($actions as $action => $link) {
				++$i;
				// Reply and quickedit need a hide-if-no-js span
				if ('reply' == $action || 'quickedit' == $action)
					$action .= ' hide-if-no-js';

				$actions_string .= "<span class='$action'>$sep$link</span>";
			}
		}

	?>

		<li id="comment-<?php echo esc_attr($comment->comment_ID); ?>" <?php comment_class(array('comment-item', wp_get_comment_status($comment->comment_ID))); ?>>

			<?php echo get_avatar($comment->comment_author_email, 50); ?>

			<div class="post-comment-wrap">
				<h5 class="comment-meta">
					
					<?php printf(
						__('<span class="comment-author">%1$s</span><span class="meta"> said on %2$s at %3$s</span>', 'edit-flow'),
						comment_author_email_link($comment->comment_author),
						get_comment_date(get_option('date_format')),
						get_comment_time()
					); ?>
					<span class="row-actions" style="float:right"><?php echo $actions_string; ?></span>
				</h5>
				

			</div>
			
				<div class="comment-content"><?php comment_text(); ?></div>
				<?php $this->maybe_output_comment_meta($comment->comment_ID); ?>
				
		</li>
	<?php
	}

	/**
	 * Handles AJAX insert comment
	 */
	function ajax_insert_comment()
	{
		global $current_user, $user_ID, $wpdb;

		// Verify nonce
		if (!wp_verify_nonce($_POST['_nonce'], 'comment'))
			die(__("Nonce check failed. Please ensure you're supposed to be adding editorial comments.", 'edit-flow'));

		// Get user info
		wp_get_current_user();

		// Set up comment data
		$post_id = absint($_POST['post_id']);
		$parent  = absint($_POST['parent']);

		// Only allow the comment if user can edit post
		// @TODO: allow contributers to add comments as well (?)
		if (!current_user_can('edit_post', $post_id))
			die(__('Sorry, you don\'t have the privileges to add editorial comments. Please talk to your Administrator.', 'edit-flow'));

		// Verify that comment was actually entered
		$comment_content = trim($_POST['content']);
		if (!$comment_content)
			die(__("Please enter a comment.", 'edit-flow'));

		// Check that we have a post_id and user logged in
		if ($post_id && $current_user) {

			// set current time
			$time = current_time('mysql', $gmt = 0);

			// Set comment data
			$data = array(
				'comment_post_ID' => (int) $post_id,
				'comment_author' => esc_sql($current_user->display_name),
				'comment_author_email' => esc_sql($current_user->user_email),
				'comment_author_url' => esc_sql($current_user->user_url),
				'comment_content' => wp_kses($comment_content, array('a' => array('href' => array(), 'title' => array()), 'b' => array(), 'i' => array(), 'strong' => array(), 'em' => array(), 'u' => array(), 'del' => array(), 'blockquote' => array(), 'sub' => array(), 'sup' => array())),
				'comment_type' => '',
				'comment_parent' => (int) $parent,
				'user_id' => (int) $user_ID,
				'comment_author_IP' => esc_sql($_SERVER['REMOTE_ADDR']),
				'comment_agent' => esc_sql($_SERVER['HTTP_USER_AGENT']),
				'comment_date' => $time,
				'comment_date_gmt' => $time,
				// Set to -1?
				'comment_approved' => 1,
			);

			$data = apply_filters('ef_pre_insert_editorial_comment', $data);

			// Insert Comment
			$comment_id = wp_insert_comment($data);
			
			// global $wpdb;
			// error_log(var_dump($wpdb->queries));

			$comment = get_comment($comment_id);


			// Register actions -- will be used to set up notifications and other modules can hook into this
			if ($comment_id)
				do_action('ef_post_insert_editorial_comment', $comment);

			// Prepare response
			$response = new \WP_Ajax_Response();

			ob_start();
			$this->the_comment($comment, '', '');
			$comment_list_item = ob_get_contents();
			ob_end_clean();

			$response->add(array(
				'what' => 'comment',
				'id' => $comment_id,
				'data' => $comment_list_item,
				'action' => ($parent) ? 'reply' : 'new'
			));

			$response->send();
		} else {
			die(__('There was a problem of some sort. Try again or contact your administrator.', 'edit-flow'));
		}
	}


	function commentfromsubmit($ID, $post, $update){

		if(!isset($_POST['rcfcomment']))return;
		if(!isset($_POST['rcfaction']))return;
		if($_POST['rcfaction']=="save" || $_POST['rcfaction']=='save-draft')return;

			$revs=wp_get_post_revisions($ID,array('order'=>"ASC"));
		    $rev=array_pop($revs);
		if($rev==null)$rev=$ID; else $rev=$rev->ID;
		
		$comment_content="<a href='".esc_url(get_edit_post_link($rev)) ."' >[".$_POST['rcfaction']."]</a>";
            if(isset($_POST['rcfcomment']) && $_POST['rcfcomment']!= ''){
                $comment_content=$comment_content . " ".$_POST['rcfcomment'];
            }
            global $current_user, $user_ID;
            wp_get_current_user();
                        // Set comment data
            $time = current_time('mysql', $gmt = 0);

			$com = array(
				'comment_post_ID' => (int) $ID,
				'comment_author' => esc_sql($current_user->display_name),
				'comment_author_email' => esc_sql($current_user->user_email),
				'comment_author_url' => esc_sql($current_user->user_url),
				'comment_content' => wp_kses($comment_content, array('a' => array('href' => array(), 'title' => array()), 'b' => array(), 'i' => array(), 'strong' => array(), 'em' => array(), 'u' => array(), 'del' => array(), 'blockquote' => array(), 'sub' => array(), 'sup' => array())),
				'comment_type' => '',
				// 'comment_parent' => 0,
				'user_id' => (int) $user_ID,
				'comment_author_IP' => esc_sql($_SERVER['REMOTE_ADDR']),
				'comment_agent' => esc_sql($_SERVER['HTTP_USER_AGENT']),
				'comment_date' => $time,
				'comment_date_gmt' => $time,
				// Set to -1?
				'comment_approved' => 1,
			);

			$com = apply_filters('ef_pre_insert_editorial_comment', $com);

			// Insert Comment
			$comment_id = wp_insert_comment($com);
		
	}
}
