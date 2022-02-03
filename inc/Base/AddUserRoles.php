<?php 
/**
 * @package  RCF Req Plugin
 */
namespace Inc\Base;

use Inc\RCF_Module;

/**
* 
*/
class AddUserRoles extends RCF_Module
{
	public function register() {
        add_action('admin_init', array($this,'ui_new_role'));
        // $wp_roles = new \WP_Roles(); // create new role object
        // var_dump($wp_roles->roles);
        // die('1');
        // foreach ($wp_roles->roles as $role=>$x )
        //     remove_role($role);
        // $wp_user_object = new \WP_User(1);
        // $wp_user_object->set_role('administrator');
    }
    const types=['OC','TC','EC','Trustee','LOC'];
    function ui_new_role() {  
        $terms = get_terms( 'league', array(
            'hide_empty' => false,
        ));
        foreach ($terms as $term){
            
            foreach ($this::types as $type){
                add_role(
                    strtolower($type . '-'. $term->slug),
                    $type . ' ' . $term->name,
                    array(
                        'edit_requirements'	=> true,
                        'edit_others_requirements'	=> true,
                        // 'delete_requirements'	=> false,
                        'read_private_requirements'	=> true,
                        // 'delete_private_requirements'	=> false,
                        // 'delete_published_requirements'	=> false,
                        // 'delete_others_requirements'	=> false,
                        'edit_private_requirements'	=> true,
                        // 'edit_published_requirements'	=> false,
                    )
                );
            }    
        }
        $role = get_role( 'administrator' );
 
        // Add a new capability.
        $role->add_cap( 'edit_requirements', true );
        $role->add_cap( 'edit_others_requirements', true );
        $role->add_cap( 'read_private_requirements', true );
        $role->add_cap( 'delete_requirements', true );
        $role->add_cap( 'delete_private_requirements', true );
        $role->add_cap( 'delete_published_requirements', true );
        $role->add_cap( 'delete_others_requirements', true );
        $role->add_cap( 'edit_private_requirements', true );
        $role->add_cap( 'edit_published_requirements', true );
        

        
     
    }
}