<?php

/**
 * @package  RCF Req Plugin
 */

namespace Inc\Base;

/**
 * 
 */
class AddCustomPost
{
    public function register()
    {

        add_action('init',  array($this, 'cptui_register_my_taxes'));
        add_action('init',  array($this, 'cptui_register_my_cpts'));
        add_action('req_key_add_form_fields',  array($this, 'add_req_key_field'), 10, 2);
        add_action('created_req_key',  array($this, 'save_req_key'), 10, 2);
        add_action('edited_req_key',  array($this, 'edited_req_key'), 10, 2);

        add_action('req_key_edit_form_fields',  array($this, 'edit_req_key_field'), 10, 2);
    }

    function edited_req_key($term_id, $tt_id)
    {
        if (isset($_POST['active'])) {

            update_term_meta($term_id, 'active', $_POST['active'] == 'Active');
        }
    }

    function save_req_key($term_id, $tt_id)
    {
        if (isset($_POST['active'])) {

            add_term_meta($term_id, 'active', $_POST['active'] == 'Active', true);
        }
    }

    function edit_req_key_field($term, $taxonomy)
    {

?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="active">Is Active</label></th>
            <td>
                <input type="checkbox" id="active" name="active" value="Active" <?php echo get_term_meta($term->term_id, 'active', true) ? "checked=checked" : ""; ?>"></label>
            </td>
        </tr>
    <?php
    }
    function add_req_key_field($taxonomy)
    {
    ?><div class="form-field term-group">
            <label for="active">Is Active
                <input type="checkbox" id="active" name="active" value="Active"></label>
        </div><?php
            }

            function cptui_register_my_cpts()
            {

                /**
                 * Post Type: Requirements.
                 */

                $labels = [
                    "name" => __("Requirements", "rcf_requirement"),
                    "singular_name" => __("Requirement", "rcf_requirement"),
                    'edit_item' => __("Edit Requirement", "rcf_requirement"),
                    'new_item' => __("New Requirement", "rcf_requirement"),
                    'add_new_item'=> __("Add New Requirement", "rcf_requirement"),
                ];

                $args = [
                    "label" => __("Requirements", "rcf_requirement"),
                    "labels" => $labels,
                    "description" => "",
                    "public" => false,
                    "publicly_queryable" => true,
                    "show_ui" => true,
                    "show_in_rest" => true,
                    "rest_base" => "",
                    "rest_controller_class" => "WP_REST_Posts_Controller",
                    "has_archive" => true,
                    "show_in_menu" => true,
                    "show_in_nav_menus" => true,
                    "delete_with_user" => false,
                    "exclude_from_search" => false,
                    "capability_type" => "requirement",
                    "map_meta_cap" => true,
                    "hierarchical" => true,
                    "rewrite" => ["slug" => "requirement", "with_front" => true],
                    "query_var" => true,
                    "menu_icon" => "dashicons-media-document",
                    "supports" => ["custom-fields", "revisions", 'comments'],
                    "taxonomies" => ["league", 'req_key'],
                ];

                register_post_type("requirement", $args);
            }




            function cptui_register_my_taxes()
            {

                /**
                 * Taxonomy: Leagues.
                 */

                $labels = [
                    "name" => __("Leagues", "rcf_requirement"),
                    "singular_name" => __("League", "rcf_requirement"),
                ];
;
                $args = [
                    "label" => __("Leagues", "rcf_requirement"),
                    "labels" => $labels,
                    "public" => true,
                    "publicly_queryable" => true,
                    "hierarchical" => true,
                    "show_ui" => true,
                    'show_in_quick_edit'         => false,
                    'meta_box_cb'                => false,
                    "show_in_menu" => true,
                    "show_in_nav_menus" => true,
                    "query_var" => true,
                    "rewrite" => ['slug' => 'league', 'with_front' => true,],
                    "show_admin_column" => true,
                    "show_in_rest" => true,
                    "rest_base" => "league",
                    "rest_controller_class" => "WP_REST_Terms_Controller",
                    'capabilities' => array(
                        'manage_terms' => 'manage_league',
                        'edit_terms' => 'edit_league',
                        'delete_terms' => 'delete_league',
                        'assign_terms' => 'assign_league',
                    )
                ];
                register_taxonomy("league", ["requirement"], $args);

                /**
                 * Taxonomy: Requirement Keys.
                 */

                $args = [
                    "label" => "Event Key",
                    "public" => true,
                    "publicly_queryable" => true,
                    "hierarchical" => false,
                    "show_ui" => true,
                    "show_in_menu" => true,
                    "show_in_nav_menus" => true,
                    "query_var" => true,
                    "rewrite" => ['slug' => 'req_key', 'with_front' => true,],
                    "show_admin_column" => true,
                    "show_in_rest" => true,
                    "rest_base" => "req_key",
                    "rest_controller_class" => "WP_REST_Terms_Controller",
                    'show_in_quick_edit'         => false,
                    'meta_box_cb'                => false,
                    'capabilities' => array(
                        'manage_terms' => 'manage_req',
                        'edit_terms' => 'edit_req',
                        'delete_terms' => 'delete_req',
                        'assign_terms' => 'assign_req',
                    )
                ];
                register_taxonomy("req_key", ["requirement"], $args);
                $this->add_default_leagues();
            }
            const TDP_TAX_LEAGUE = "league";
            function add_default_leagues()
            {
                $numTerms = wp_count_terms(AddCustomPost::TDP_TAX_LEAGUE, array(
                    'hide_empty' => false,
                    'parent'    => 0
                ));
                if ($numTerms > 0)
                    return;

                $leagues = array(
                    // "Rescue Rapidly Manufactured Robot Challenge",
                    "RoboCup@Home - Domestic Standard Platform",
                    "RoboCup@Home - Open Platform",
                    "RoboCup@Home - Social Standard Platform",
                    "RoboCupIndustrial - RoboCup@Work",
                    "RoboCupIndustrial - RoboCupLogistics",
                    "RoboCupJunior - OnStage",
                    "RoboCupJunior - Rescue",
                    "RoboCupJunior - Rescue - CoSpace",
                    "RoboCupJunior - Soccer",
                    "RoboCupRescue - Robot",
                    "RoboCupRescue - Simulation - Agent",
                    "RoboCupRescue - Simulation - Virtual Robot",
                    "RoboCupSoccer - Humanoid - AdultSize",
                    "RoboCupSoccer - Humanoid - KidSize",
                    "RoboCupSoccer - Humanoid - TeenSize",
                    "RoboCupSoccer - Middle Size",
                    "RoboCupSoccer - Simulation - 2D",
                    "RoboCupSoccer - Simulation - 3D",
                    "RoboCupSoccer - Small Size",
                    "RoboCupSoccer - Standard Platform"
                );
                foreach ($leagues as $k => $league) {
                    $splt = explode("-", $league);

                    $parent_id = 0;
                    $parent_slug = 'rcf-lg-';
                    $parent_name = '';
                    foreach ($splt as $kk => $lg) {
                        $term = trim($lg);
                        $name = $parent_name . $term;
                        $slug = AddCustomPost::slugify($parent_slug . $lg);
                        $tax = get_term_by('slug', $slug, AddCustomPost::TDP_TAX_LEAGUE);

                        if (empty($tax) || is_wp_error($tax)) {
                            //if(! term_exists( $term,AddCustomPost::TDP_TAX_LEAGUE) ){
                            wp_insert_term($name, AddCustomPost::TDP_TAX_LEAGUE, array('parent' => $parent_id, 'slug' => $slug));
                            $tax = get_term_by('slug', $slug, AddCustomPost::TDP_TAX_LEAGUE);
                        }
                        // update_term_meta($tax->term_id,AddCustomPost::TDP_TAX_LEAGUE_META_SHORT_NAME,$term);
                        $parent_id = $tax->term_id;

                        $parent_slug = $slug . '-';
                        $parent_name = $name . ' - ';
                    }
                }
            }


            function slugify($text)
            {
                $text = str_replace('@', '-at-', $text);
                // replace non letter or digits by -
                $text = sanitize_title_with_dashes($text);
                //   $text = preg_replace('~[^\pL\d]+~u', '-', $text);

                // transliterate
                //   $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

                // remove unwanted characters
                //   $text = preg_replace('~[^-\w]+~', '', $text);

                // trim
                //   $text = trim($text, '-');

                // remove duplicate -
                //   $text = preg_replace('~-+~', '-', $text);

                // lowercase
                //   $text = strtolower($text);

                if (empty($text)) {
                    return 'n-a';
                }

                return $text;
            }
        }
