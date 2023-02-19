<?php 
/**
 * @package  RCF Req Plugin
 */
namespace Inc\Base;

use Inc\RCF_Module;

/**
* 
*/
class FrontEnd extends RCF_Module
{
	public function register() {
        add_filter('single_template', array($this,'front_req'));        
        // add_filter( 'the_posts', array($this,'show_unpublished_custom_post'), 10, 2 );
        add_filter( 'template_include', array($this,'custom_requirement_template') );
    }

    function custom_requirement_template( $template ) {
        if ( !get_query_var( 'p' ) ) return $template;
        $post =get_post( get_query_var( 'p' ) );
        // var_dump($post);
        if (!$post) return $template;

        if ($post->post_parent){
            if (get_post($post->post_parent)->post_type!="requirement")
                return $template;
        }else if ($post->post_type!="requirement") return $template;
        

        return PLUGIN_PATH . '/front/single-requirement.php';

    }
    
function front_req($single) {

    global $post;

    if ( $post->post_type == 'requirement' ) {
        if ( file_exists( PLUGIN_PATH . '/front/single-requirement.php' ) ) {
            return PLUGIN_PATH . '/front/single-requirement.php';
        }
    }
    return $single;

}
}