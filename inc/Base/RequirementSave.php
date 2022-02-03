<?php

/**
 * @package  RCF Req Plugin
 */

namespace Inc\Base;

class RequirementSave
{
	public function register()
	{
		add_filter('wp_insert_post_data', array($this, 'rcf_req_change_title'), 1, 2);
		// 	add_filter('wp_post_revision_meta_keys', array($this, 'revision_meta_keys'));
		// }
		// function revision_meta_keys($keys){
		// 	error_log(var_dump($keys));
		// 	return '';
	}

	function rcf_req_change_title($data, $postarr)
	{

		if (isset($postarr['league']) && isset($postarr['req_key'])) {
			$req_slug = '';
			if ($term = get_term_by('slug', $postarr['req_key'], 'req_key'))
				$req_slug = $term->name;
			$req_league = '';
			if ($term = get_term_by('slug', $postarr['league'], 'league'))
				$req_league = $term->name;
			$data['post_title'] =  $req_slug . ' - ' . $req_league;
			$postarr['post_title'] = $data['post_title'];
		}
		// $data['post_status']="loc";
		// error_log(var_dump($data));
		// error_log('===========');
		// error_log(var_dump($postarr));
		// $data['field_req_key']='';
		// $data['field_league']='';
		// $data['comment_status'] = 1;

		return $data;
	}
}
