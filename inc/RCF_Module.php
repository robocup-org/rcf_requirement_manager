<?php

namespace Inc;

/**
 * class EF_Module
 *
 * @desc Base class any Edit Flow module should extend
 */

if (class_exists('RCF_Module')) return;

class RCF_Module
{

    public $published_statuses = array(
        'publish',
        'future',
        'private',
    );

    function __construct()
    {
    }


    /**
     * Gets an array of allowed post types for a module
     *
     * @return array post-type-slug => post-type-label
     */
    function get_all_post_types()
    {

        $allowed_post_types = array(
            'post' => __('Post'),
            'page' => __('Page'),
            'requirement' => __('requirement'),
        );
        $custom_post_types = $this->get_supported_post_types_for_module();

        foreach ($custom_post_types as $custom_post_type => $args) {
            $allowed_post_types[$custom_post_type] = $args->label;
        }
        return $allowed_post_types;
    }


    /**
     * Cleans up the 'on' and 'off' for post types on a given module (so we don't get warnings all over)
     * For every post type that doesn't explicitly have the 'on' value, turn it 'off'
     * If add_post_type_support() has been used anywhere (legacy support), inherit the state
     *
     * @param array $module_post_types Current state of post type options for the module
     * @param string $post_type_support What the feature is called for post_type_support (e.g. 'ef_calendar')
     * @return array $normalized_post_type_options The setting for each post type, normalized based on rules
     *
     * @since 0.7
     */
    function clean_post_type_options($module_post_types = array(), $post_type_support = null)
    {
        $normalized_post_type_options = array();
        $all_post_types = array_keys($this->get_all_post_types());
        foreach ($all_post_types as $post_type) {
            if ((isset($module_post_types[$post_type]) && $module_post_types[$post_type] == 'on') || post_type_supports($post_type, $post_type_support))
                $normalized_post_type_options[$post_type] = 'on';
            else
                $normalized_post_type_options[$post_type] = 'off';
        }
        return $normalized_post_type_options;
    }

    /**
     * Get all of the possible post types that can be used with a given module
     *
     * @param object $module The full module
     * @return array $post_types An array of post type objects
     *
     * @since 0.7.2
     */
    function get_supported_post_types_for_module($module = null)
    {

        $pt_args = array(
            '_builtin' => false,
            'public' => true,
        );
        $pt_args = apply_filters('edit_flow_supported_module_post_types_args', $pt_args, $module);
        return get_post_types($pt_args, 'objects');
    }

    /**
     * Collect all of the active post types for a given module
     *
     * @param object $module Module's data
     * @return array $post_types All of the post types that are 'on'
     *
     * @since 0.7
     */
    function get_post_types_for_module($module)
    {

        $post_types = array();
        if (isset($module->options->post_types) && is_array($module->options->post_types)) {
            foreach ($module->options->post_types as $post_type => $value)
                if ('on' == $value)
                    $post_types[] = $post_type;
        }
        return $post_types;
    }


    /**
     * Filter to all posts with a given post status (can be a custom status or a built-in status) and optional custom post type.
     *
     * @since 0.7
     *
     * @param string $slug The slug for the post status to which to filter
     * @param string $post_type Optional post type to which to filter
     * @return an edit.php link to all posts with the given post status and, optionally, the given post type
     */
    function filter_posts_link($slug, $post_type = 'post')
    {
        $filter_link = add_query_arg('post_status', $slug, get_admin_url(null, 'edit.php'));
        if ($post_type != 'post' && in_array($post_type, get_post_types('', 'names')))
            $filter_link = add_query_arg('post_type', $post_type, $filter_link);
        return $filter_link;
    }

    /**
     * Enqueue any resources (CSS or JS) associated with datepicker functionality
     *
     * @since 0.7
     */
    function enqueue_datepicker_resources()
    {

        wp_enqueue_script('jquery-ui-datepicker');

        //Timepicker needs to come after jquery-ui-datepicker and jquery
        wp_enqueue_script('edit_flow-timepicker', EDIT_FLOW_URL . 'common/js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-datepicker'), EDIT_FLOW_VERSION, true);
        wp_enqueue_script('edit_flow-date_picker', EDIT_FLOW_URL . 'common/js/ef_date.js', array('jquery', 'jquery-ui-datepicker', 'edit_flow-timepicker'), EDIT_FLOW_VERSION, true);
        wp_add_inline_script('edit_flow-date_picker', sprintf('var ef_week_first_day =  %s;', wp_json_encode(get_option('start_of_week'))), 'before');

        // Now styles
        wp_enqueue_style('jquery-ui-datepicker', EDIT_FLOW_URL . 'common/css/jquery.ui.datepicker.css', array('wp-jquery-ui-dialog'), EDIT_FLOW_VERSION, 'screen');
        wp_enqueue_style('jquery-ui-theme', EDIT_FLOW_URL . 'common/css/jquery.ui.theme.css', false, EDIT_FLOW_VERSION, 'screen');
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
     * Check whether custom status stuff should be loaded on this page
     *
     * @todo migrate this to the base module class
     */
    function is_whitelisted_page()
    {
        global $pagenow;

        if (!in_array($this->get_current_post_type(), ['requirement']))
            return false;

        $post_type_obj = get_post_type_object($this->get_current_post_type());

        if (!current_user_can($post_type_obj->cap->edit_posts))
            return false;

        // Only add the script to Edit Post and Edit Page pages -- don't want to bog down the rest of the admin with unnecessary javascript
        return in_array($pagenow, array('post.php', 'edit.php', 'post-new.php', 'page.php', 'edit-pages.php', 'page-new.php'));
    }

    /**
     * Take a status and a message, JSON encode and print
     *
     * @since 0.7
     *
     * @param string $status Whether it was a 'success' or an 'error'
     */
    function print_ajax_response($status, $message = '')
    {
        header('Content-type: application/json;');
        echo json_encode(array('status' => $status, 'message' => $message));
        exit;
    }

    /**
     * Whether or not the current page is a user-facing Edit Flow View
     * @todo Think of a creative way to make this work
     *
     * @since 0.7
     *
     * @param string $module_name (Optional) Module name to check against
     */
    function is_whitelisted_functional_view($module_name = null)
    {

        // @todo complete this method

        return true;
    }

    /**
     * Whether or not the current page is an Edit Flow settings view (either main or module)
     * Determination is based on $pagenow, $_GET['page'], and the module's $settings_slug
     * If there's no module name specified, it will return true against all Edit Flow settings views
     *
     * @since 0.7
     *
     * @param string $module_name (Optional) Module name to check against
     * @return bool $is_settings_view Return true if it is
     */
    function is_whitelisted_settings_view($module_name = null)
    {
        global $pagenow, $edit_flow;

        // All of the settings views are based on admin.php and a $_GET['page'] parameter
        if ($pagenow != 'admin.php' || !isset($_GET['page']))
            return false;

        // Load all of the modules that have a settings slug/ callback for the settings page
        foreach ($edit_flow->modules as $mod_name => $mod_data) {
            if (isset($mod_data->options->enabled) && $mod_data->options->enabled == 'on' && $mod_data->configure_page_cb)
                $settings_view_slugs[] = $mod_data->settings_slug;
        }

        // The current page better be in the array of registered settings view slugs
        if (!in_array($_GET['page'], $settings_view_slugs))
            return false;

        if ($module_name && $edit_flow->modules->$module_name->settings_slug != $_GET['page'])
            return false;

        return true;
    }


    /**
     * This is a hack, Hack, HACK!!!
     * Encode all of the given arguments as a serialized array, and then base64_encode
     * Used to store extra data in a term's description field
     *
     * @since 0.7
     *
     * @param array $args The arguments to encode
     * @return string Arguments encoded in base64
     */
    function get_encoded_description($args = array())
    {
        return base64_encode(maybe_serialize($args));
    }

    /**
     * If given an encoded string from a term's description field,
     * return an array of values. Otherwise, return the original string
     *
     * @since 0.7
     *
     * @param string $string_to_unencode Possibly encoded string
     * @return array Array if string was encoded, otherwise the string as the 'description' field
     */
    function get_unencoded_description($string_to_unencode)
    {
        return maybe_unserialize(base64_decode($string_to_unencode));
    }

    /**
     * Get the publicly accessible URL for the module based on the filename
     *
     * @since 0.7
     *
     * @param string $filepath File path for the module
     * @return string $module_url Publicly accessible URL for the module
     */
    function get_module_url($file)
    {
        $module_url = plugins_url('/', $file);
        return trailingslashit($module_url);
    }

    /**
     * Produce a human-readable version of the time since a timestamp
     *
     * @param int $original The UNIX timestamp we're producing a relative time for
     * @return string $relative_time Human-readable version of the difference between the timestamp and now
     */
    function timesince($original)
    {
        // array of time period chunks
        $chunks = array(
            array(60 * 60 * 24 * 365, 'year'),
            array(60 * 60 * 24 * 30, 'month'),
            array(60 * 60 * 24 * 7, 'week'),
            array(60 * 60 * 24, 'day'),
            array(60 * 60, 'hour'),
            array(60, 'minute'),
            array(1, 'second'),
        );

        $today = time(); /* Current unix time  */
        $since = $today - $original;

        if ($since > $chunks[2][0]) {
            $print = date("M jS", $original);

            if ($since > $chunks[0][0]) { // Seconds in a year
                $print .= ", " . date("Y", $original);
            }

            return $print;
        }

        // $j saves performing the count function each time around the loop
        for ($i = 0, $j = count($chunks); $i < $j; $i++) {

            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];

            // finding the biggest chunk (if the chunk fits, break)
            if (($count = floor($since / $seconds)) != 0) {
                break;
            }
        }

        return sprintf(_n("1 $name ago", "$count ${name}s ago", $count), $count);
    }


    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }


    
}
