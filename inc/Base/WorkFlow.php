<?php

/**
 * @package  AlecadddPlugin
 */

namespace inc\Base;

use Exception;
use Inc;
use Inc\RCF_Module;

class WorkFlow extends RCF_Module
{
    // const steps = ['ec', 'loc', 'final'];
    const steps = ['ec' => 'EC', 'trustee' => 'Trustee', 'loc' => 'LOC', 'final' => 'Final'];

    const workflow = [
        'auto-draft' => ['save-draft' => 'ec'],
        'ec' => ['save-draft' => 'ec', 'submit' => 'trustee'],
        'trustee' => [
            'accept' => 'loc',
            'reject' => 'ec'
        ],
        'loc' => [
            'accept' => 'final',
            'reject' =>  'ec'
        ],
        'final' => [
            'reject' =>  'ec'
        ]
    ];
    const types = ['oc', 'tc', 'ec', 'trustee', 'loc'];
    public function register()
    {

        // /add_action('save_post', array($this, 'debug_save_post_update'), 10, 3);
        add_action('save_post', array($this, 'save_post_update'), 10, 3);
        add_filter('wp_insert_post_data', array($this, 'rcf_change_status'), 10, 2);

        add_action('admin_notices', array($this, 'no_js_notice'));
        add_action('add_meta_boxes', array($this, 'submit_box'), 9);
        $this->set_post_status_list();
        add_action('admin_enqueue_scripts', array($this, 'action_admin_enqueue_scripts'));
        //hook at the very end of all filters to prevent other filters from overwriting your return value ( 99 should be high enaugh )
        // add_filter('wp_insert_post_empty_content', array($this, 'cancel_post_save_function'), 99, 2);
        add_action('admin_head-post.php', array($this, 'publish_admin_hook'));
        add_action('admin_head-post-new.php', array($this, 'publish_admin_hook'));
        add_action('wp_ajax_ep_pre_submit_validation', array($this, 'ep_pre_submit_validation'));
        add_filter('wp_save_post_revision_check_for_changes', array($this, 'wp_save_post_revision_check_for_changes'), 10, 3);
        
        
        add_action('add_meta_boxes', array($this,'remove_my_custom_metabox'));
        add_action('edit_form_after_title',array($this,'add_requirement_text' ));
    }
 
function add_requirement_text($post) {
  
  if ($post->post_type == 'requirement') {
    echo '<h3>';
    echo $post->post_title;
    echo '</h3>';
  }
}
    function remove_my_custom_metabox() {
        remove_meta_box('acf-Req_group', 'requirement', 'normal');
        remove_meta_box('commentstatusdiv', 'requirement', 'normal');
        remove_meta_box('slugdiv', 'requirement', 'normal');
        remove_meta_box('acf-Req_group', 'requirement', 'normal');
    }
    
    function wp_save_post_revision_check_for_changes($return, $last_revision, $post)
    {

        // if acf has changed, return false and prevent WP from performing 'compare' logic

        rcf_log("last_revision=", $last_revision);
        rcf_log("meta=", get_post_meta($last_revision->ID, 'req_status'));
        rcf_log("post=", $post);


        // return
        return $return;
    }
    function isRightPlace()
    {
        global $post;
        if ($post == null) return false;

        if (is_admin() && $post->post_type == 'requirement')
            return true;
        return false;
    }

    function ep_pre_submit_validation()
    {
        // if(!$this->isRightPlace())return;
        check_ajax_referer('pre_publish_validation', 'security');

        //convert the string of data received to an array
        //from https://wordpress.stackexchange.com/a/26536/10406
        parse_str($_POST['form_data'], $postarr);
        $res = $this->validate($postarr);
        if (empty($res)) {
            echo 'true';
            die();
        } else {
            _e($res);
            die();
        }
    }
    function generateLeagueKey($post)
    {
        $post_id = $post->ID;
?>
        <table>
            <tr>
                <th>
                    <label for="my_meta_box_post_type">League: </label>
                </th>
                <td>
                    <select name='league' id='league' style="width:100%">
                        <?php $roles = wp_get_current_user()->roles;
                        $leagues = get_terms('league', ['hide_empty' => false,]);
                        foreach ($leagues as $league) {
                            if ($this->hasPermission($post_id, 'save-draft', $league->slug)) {
                                echo "<option value='$league->slug'>$league->name</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            <tr>
                <th><label for="my_meta_box_post_type">Event Key: </label></th>
                <td>
                    <select name='req_key' id='req_key' style="width:100%">
                        <?php
                        $keys = get_terms('req_key', ['hide_empty' => false,]);
                        foreach ($keys  as $key) {
                            if (get_term_meta($key->term_id, "active", true)) {
                                echo "<option value='$key->slug'>$key->name</option>";
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                    <td><button type="button" name="publish1" class="button button-secondary button-large rcfpublish" style="" action="save-draft" value="Save-draft">Next</button></td>

            </tr>
        </table>
        <?php
    }

    function validate($postarr)
    {
        if (empty($postarr))
            return '';
        if (!is_user_logged_in()) {
            return 'permission denied';
        }
        //simple Security check
        $key_term = false;
        $league_term = false;
        $post_id = $postarr['post_ID'];
        if ($post_id > 0) {
            $key_term = wp_get_post_terms($post_id, 'req_key');
            $league_term = wp_get_post_terms($post_id, 'league');

            if (!$key_term) {
                $key_term = [get_term_by('slug', $postarr['req_key'], 'req_key')];
                if (!get_term_meta($key_term[0]->term_id, 'active', true))
                    return 'The Key is not activate';
                wp_set_post_terms($post_id, $key_term[0]->slug, "req_key");
                $key_term = wp_get_post_terms($post_id, 'req_key');
            }
            if (!$league_term) {
                $league_term = [get_term_by('slug', $postarr['league'], 'league')];
                if (!$this->hasPermission($post_id, $postarr['rcfaction'], $league_term[0]->slug))
                    return "Permission denied to submit for this league";
                wp_set_post_terms($post_id, $league_term[0]->term_id, "league");
                $league_term = wp_get_post_terms($post_id, 'league');
            }
        }

        if (!$key_term)
            return 'No Key Selected';

        if (!$league_term)
            return 'No League is selected';

        if (!$this->hasPermission($post_id, $postarr['rcfaction'], $league_term[0]->slug))
            return "Permission denied for this league";


        return '';
    }

    // function getCurrentState($post_id){
    //     $post_status = get_post_status($post_id);
    //     if (!$post_status)
    //         return "auto-draft";
    //     if(!array_key_exists($post_status,$this::workflow))
    //         $post_status='auto-draft';

    //     return $post_status;

    // }




    function getState($post_id)
    {
        $post_status = get_post_status($post_id);
        if (!$post_status || $post_status == 'draft')
            return "auto-draft";

        if (!array_key_exists($post_status, $this::workflow))
            $post_status = 'ec';

        return $post_status;
    }
    function hasPermission($post_id, $action, $league = '')
    { //$league_slug
        list($next, $permission) = $this->getNextState($post_id, $action, $league);
        return $permission;
    }

    function getNextState($post_id, $action, $league = '')
    {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $post_status = $this->getState($post_id);
        if ($league == '')
            $league = $this->getLeague();


        foreach ($this::workflow[$post_status] as $act => $next) {
            if ($act == $action) {
                
                foreach ($roles as $role => $role_name) {
                    if ($role_name == "administrator")
                        return array($next, true);
                    
                    if (!$league) {
                        return array($next, true);
                    } else if (($post_status == 'auto-draft' || $post_status == 'ec')
                                && $action == 'save-draft' 
                                && $this->endsWith($role_name, $league)) {
                        return array($next, true);
                    }else if (strtolower($role_name) == "trustee" && $role_name == $post_status){
                        return array($next, true);
                    } else if ($role_name == $post_status . '-' . $league )
                        return array($next, true);
                }
            }
        }

        return array($post_status, false);
    }


    function publish_admin_hook()
    {
        if (!$this->isRightPlace()) return;
        global $post;
        if (is_admin() && $post->post_type == 'requirement') {
        ?>
            <script language="javascript" type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('.rcfpublish').on('click', function() {
                        jQuery('#rcfaction').val(this.getAttribute('action'));
                        // jQuery("#post").submit();
                        event.preventDefault();
                        jQuery("#rcfcomment").val(jQuery("#rcfcomment-" + this.getAttribute('action')).val());
                        if (jQuery("#post").data("checking")) return false;
                        jQuery("#post").data("checking", true);

                        var form_data = jQuery('#post').serializeArray();
                        var data = {
                            action: 'ep_pre_submit_validation',
                            security: '<?php echo wp_create_nonce('pre_publish_validation'); ?>',
                            form_data: jQuery.param(form_data),
                        };
                        jQuery('.rcfpublish').addClass('button-primary-disabled');
                        jQuery.post(ajaxurl, data).done(function(response) {
                            jQuery("#post").data("checking", false);;
                            if (response.indexOf('true') > -1 || response == true) {
                                jQuery("#post").data("valid", true).submit();
                            } else {
                                alert("Error: " + response);
                                jQuery("#post").data("valid", false);
                                //hide loading icon, return Publish button to normal
                                jQuery('#ajax-loading').hide();
                                jQuery('.rcfpublish').removeClass('button-primary-disabled');
                            }

                            // jQuery('#save-post').removeClass('button-disabled');
                        }).fail(function(err) {
                            alert("connection error", JSON.stringify(err));
                            jQuery("#post").data("checking", false);;
                            jQuery("#post").data("valid", false);
                            //hide loading icon, return Publish button to normal
                            jQuery('#ajax-loading').hide();
                            jQuery('.rcfpublish').removeClass('button-primary-disabled');
                        });
                    });

                    jQuery(document).on('submit', 'form#post', function() {
                        if (jQuery("#post").data("valid"))
                            return true;
                        return false;
                    });
                });
            </script>
        <?php
        }
    }

    function action_admin_enqueue_scripts()
    {
        if (!$this->isRightPlace()) return;
        add_thickbox();
        global $post;
        if ($post && $post->post_type == 'requirement') {
            wp_enqueue_script('jquery');
            wp_register_style('select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all');
            wp_register_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array('jquery'), '1.0', true);
            wp_enqueue_style('select2css');
            wp_enqueue_script('select2');
        }
    }
    function set_post_status_list()
    {
        foreach ($this::steps as $step => $label) {
            register_post_status($step,  array('label' => $label, 'show_in_admin_status_list' => true, 'show_in_admin_all_list' => true));
        }
    }
    function submit_box()
    {
        if (!$this->isRightPlace()) return;
        $screens =  ['requirement'];
        foreach ($screens as $screen) {
            add_meta_box(
                'submit_box',                 // Unique ID
                'Submit Box',      // Box title
                array($this, 'wporg_custom_box_html'),  // Content callback, must be of type callable
                $screen,                            // Post type,
                "side",
                "high"
            );
            global $post;
            if ($this->getState($post->ID) == 'auto-draft')
                
                add_meta_box(
                    'key_box',                 // Unique ID
                    'Requirement Information',      // Box title
                    array($this, 'generateLeagueKey'),  // Content callback, must be of type callable
                    $screen,                            // Post type,
                    "normal",
                    "high"
                );
        }
    }

    function wporg_custom_box_html($post)
    {
        $revisions = wp_get_post_revisions($post->ID, array('fields' => 'ids'));
        $args = array();
        // We should aim to show the revisions meta box only when there are revisions.
        if (count($revisions) > 1) {
            $args = array(
                'revisions_count'        => count($revisions),
                'revision_id'            => reset($revisions),
                '__back_compat_meta_box' => true,
            );
        }
        ?>
        <label>Current Status: <?php echo $post->post_status ?></label>
        
        <a href='/?p=<?php echo $post->ID?>' target='_blank' class='button secondary button-large' style='float:right' ><?php echo $post->post_status=="final"?"Print": "Preview"?></a>
        
        <div class="clear"></div>
        <?php
        if (!empty($args['revisions_count'])) :
        ?>
            <div class="misc-pub-section misc-pub-revisions">
                <?php
                /* translators: Post revisions heading. %s: The number of available revisions. */
                printf(__('Revisions: %s'), '<b>' . number_format_i18n($args['revisions_count']) . '</b>');
                ?>
                <a class="hide-if-no-js" href="<?php echo esc_url(get_edit_post_link($args['revision_id'])); ?>"><span aria-hidden="true"><?php _ex('Browse', 'revisions'); ?></span> <span class="screen-reader-text"><?php _e('Browse revisions'); ?></span></a>
            </div>
        <?php
        endif;
        ?>
        <div class="clear"></div>

        <?php
        // $league = $this->getLeague($post);
        // if (!$league) {
        //     echo '<input type="submit" name="rcfaction" class="button button-secondary button-large" value="Save" />';
        // }
        $save = true;
        $post_status = $this->getState($post->ID);
        echo '<input type="hidden" name="rcfaction" id="rcfaction" value="">';
        echo '<input type="hidden" name="rcfcomment" id="rcfcomment" value=""><input type="hidden" name="old_post_status" value="'. $post->post_status .'">';

        foreach ($this::workflow[$post_status] as $act => $next) {
            if ($this->hasPermission($post->ID, $act)) {
                // if ($save) {
                //     echo '<input type="submit" name="rcfaction" class="button button-secondary button-large" value="Save" />';
                //     $save = false;
                // }
                $style = $act == "reject" ? "background-color:darkred" : "";
                $class = $act == "save-draft" ? "button-secondary" : "button-primary";
                $action = ucfirst($act);
                if ($act != "save-draft" && $act != "save") {
        ?>
                    <div id="my-content-<?php echo $action; ?>" style="display:none;">
                        <p>
                            Are you sure to submit the form? You will not be able to edit it any more.

                        </p>
                        <div>
                            <p>
                                Please add some comments.
                            </p>
                            <textarea id="rcfcomment-<?php echo $act ?>" placeholder="comment" cols="80" rows="5"></textarea>
                        </div>
                        <?php echo "<a href='#'  class='button $class button-large rcfpublish' action='$act' style='$style'> $action</a>"; ?>
                        <a href='#' class="button button-large" onclick="tb_remove();"> Cancel </a>
                    </div>
            <?php
                    echo "<a href='#TB_inline?&inlineId=my-content-$action' action='$act' class='thickbox button $class button-large' style='$style'>$action to $next</a>";
                } else
                    echo "<input type='button' name='publish1' class='button $class button-large rcfpublish' style='$style' action='$act' value='$action'/>";
            }
        }
    }
    /**
     * Displays a notice to users if they have JS disabled
     * Javascript is needed for custom statuses to be fully functional
     */
    function no_js_notice()
    {
        if ($this->is_whitelisted_page()) :
            ?>
            <style type="text/css">
                /* Hide post status dropdown by default in case of JS issues **/
                #submitdiv {
                    display: none;
                }
            </style>
            <div class="update-nag hide-if-js">
                <?php _e('<strong>Note:</strong> Your browser does not support JavaScript or has JavaScript disabled. You will not be able to access or change the post status.', 'edit-flow'); ?>
            </div>
<?php
        endif;
    }

    function rcf_change_status($data, $postarr)
    {
        if (isset($postarr['rcfaction'])) {
            $post_id = $postarr['ID'];
            $league_term = wp_get_post_terms($postarr['ID'], 'league');

            if (!$league_term) {
                // wp_die('error league');
                return;
            }

            $res = $this->validate($postarr);
            if (!empty($res)) {
                wp_die($res);
                return;
            }
            list($nextstatus, $permission) = $this->getNextState($post_id, $postarr['rcfaction'], $league_term[0]->slug);

            $data['post_status'] = $nextstatus;
            // if ($nextstatus !=$this->getState($ID)){
            // wp_save_post_revision($ID);
            //     $revs=wp_get_post_revisions($ID,array('order'=>"ASC"));
            //     $rev=array_pop($revs);
            //     add_post_meta( $post_id, "base_req_id", $base_id, true );
            // }


            return $data;
        }
        return $data;
    }
    function save_post_update($ID, $post, $update)
    {
        // wp_save_post_revision( $ID );
        return;
        $parent_id = wp_is_post_revision($ID);
        rcf_log("parent_id=" . $parent_id . "id=" . $ID);
        if (!$parent_id) {
            return;
        }

        $parent = get_post($parent_id);
        $revs = wp_get_post_revisions($parent_id, array('order' => "DSC"));
        add_post_meta($ID, "req_status", $parent->post_status, true);

        $req_base_id = $ID;

        foreach ($revs as $rev_id => $rev) {
            $req_status = get_post_meta($rev_id, 'req_status', true);
            if ($req_status != $parent->post_status) {
                $req_base_id = $post->ID;
                break;
            }
            $rev_req_base_id = get_post_meta($rev_id, 'req_base_id', true);
            if ($rev_req_base_id) {
                $req_base_id = $rev_req_base_id;
                break;
            }
        }

        add_post_meta($post->ID, "req_base_id", $req_base_id, true);

        // $rev=array_pop($revs);
        // if ($nextstatus !=$this->getState($ID)){
        //     $data['post_status'];
        // }
        // var_dump($ID);
        // echo "====";
        // var_dump($post);
        // echo "====";
        // var_dump($update);
        // echo "====";
        // var_dump($rev);
        // wp_die("");
    }
    // function debug_save_post_update($ID, $post, $update)
    // {
    //     if ($post->post_type !== 'requirement') return;
    //     if (!$update) {
    //         return;
    //     }

    //     $res = $this->validate($_POST);
    //     if (!empty($res)) {
    //         wp_die($res);
    //         return;
    //     }
    //     $league_term = wp_get_post_terms($ID, 'league');

    //     if (!$league_term) {
    //         wp_die('error league');
    //         return;
    //     }
    //     if (isset($_POST['rcfaction'])) {
    //         $status = $this->getNextState($ID, $_POST['rcfaction'], $league_term[0]->slug);
    //         remove_action('save_post', array($this, 'debug_save_post_update'));
    //         // $status = $post->post_status; 
    //         // if ($_POST['rcfaction'] == 'Accept') {

    //         //     if ($status == 'trustee') {
    //         //         $status = 'loc';
    //         //     } else if ($status == 'loc')
    //         //         $status = 'final';
    //         //     else
    //         //         // die("error incorrect state");        
    //         //         $status = 'trustee';
    //         // } else if ($_POST['rcfaction'] == 'Reject') {
    //         //     $status = 'ec';
    //         // } else if ($_POST['rcfaction'] == 'Submit') {
    //         //     $status = 'trustee';
    //         // }
    //         if (!$status)
    //             $status = 'ec';

    //         $args = array(
    //             'ID'             => $ID,
    //             'post_status' => $status,
    //             'fire_after_hooks' => false
    //         );
    //         wp_update_post($args);
    //         // Add hook back again
    //         add_action('save_post', array($this, 'debug_save_post_update'), 10, 3);
    //     }
    //     // var_dump($status);
    //     // var_dump($status=='trustee');
    //     // die();
    // }

    function getLeague()
    {
        global $post;
        $league_term = wp_get_post_terms($post->ID, 'league');
        if ($league_term) {
            return $league_term[0]->slug;
        }
        return false;
    }
}
