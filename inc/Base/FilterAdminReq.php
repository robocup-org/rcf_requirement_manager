<?php 
/**
 * @package  RCF Req Plugin
 */
namespace Inc\Base;

use Inc\RCF_Module;

/**
* 
*/
class FilterAdminReq extends RCF_Module
{
	public function register() {
        add_action('pre_get_posts', array($this,'getposts'));
        
        add_action('restrict_manage_posts', array($this,'filter_by_league')); 
    }
    function filter_by_league() {
        if(strpos($_SERVER['REQUEST_URI'],"/wp-admin/edit.php?")===false)return;
        if(get_query_var("post_type")!=="requirement")return;
        $params = array(
            'taxonomy'=>'league',
            'name' => 'league', // this is the "name" attribute for filter <select>
            'show_option_all' => 'All leagues', // label for all authors (display posts without filter)
            // 'show_count'=>1,
            'hide_empty'=>0,
            'hierarchical'=>1,
            'depth'=>5,
            'value_field'       => 'slug',

        );
     
        if ( isset($_GET['league']) )
            $params['selected'] = $_GET['league']; // choose selected user by $_GET variable
     
            wp_dropdown_categories( $params ); // print the ready author list


            $params = array(
                'taxonomy'=>'req_key',
                'name' => 'Key', // this is the "name" attribute for filter <select>
                'show_option_all' => 'All Key', // label for all authors (display posts without filter)
                // 'show_count'=>1,
                'hide_empty'=>0,
                'hierarchical'=>1,
                'depth'=>5,
                'value_field'       => 'slug',
    
            );
         
            if ( isset($_GET['req_key']) )
                $params['selected'] = $_GET['req_key']; // choose selected user by $_GET variable
         
                wp_dropdown_categories( $params ); // print the ready author list
    }
     
    
    function getposts( $query ) {
        if ( !is_admin()  )return;

        if(strpos($_SERVER['REQUEST_URI'],"/wp-admin/edit.php?")===false)return;
        if(get_query_var("post_type")!=="requirement")return;
        
        // 
        // if($query->query['post_status']&&$query->query['post_type']=='requirement')return;
        $all=[];
        $roles = wp_get_current_user()->roles;
        $leagues = [];
        foreach ($roles as $role => $role_name) {
            
            if ($role_name == "administrator"){
                $types=['oc','tc','ec','trustee','loc'];
                foreach($types as $t)
                   if(get_query_var("post_status")==""||get_query_var("post_status")==$t)
                       $all[$t]=1;
                
            }else{
                $t=explode('-',$role_name)[0];
                if(get_query_var("post_status")==""||get_query_var("post_status")==$t){
                    $all[$t]=1;
                }
            }
            $terms = get_terms( 'league', array(
                'hide_empty' => false,
            ));
            foreach ($terms as $term){
                if( $role_name == "administrator"||$this->endsWith($role_name, $term->slug))
                if(get_query_var("league")==""||strpos($term->slug,get_query_var("league"))!==false)
                array_push($leagues,$term->term_id);
            }
        }
        $res=array();
        foreach($all as $role=>$enable)
             array_push($res,$role);
        // if(get_query_var("post_status")==""){
            $query->set( 'post_status', $res );
        // }else{
        //     if(array_key_exists(get_query_var("post_status"),$all))
        //         $query->set( 'post_status', get_query_var("post_status") );
        //     else
        //         $query->set( 'post_status','');
        // }
        
        $taxquery = array(
            array(
                'taxonomy' => 'league',
                'field' => 'id',
                'terms' => $leagues,
                'operator'=> 'IN',
                'include_children'=>true
            )
        );
    
        $query->set( 'tax_query', $taxquery );
        // $query->set( 'taxonomy', $res );
        //   $query->set( 'post_status', "trustee11" );
        return $query;
      }
}