<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
if (function_exists('rcf_log')) return;

function rcf_log($title,$log=""){
        if(!$log){
            $log=$title;
            $title="";
        }
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log($title.print_r($log, true));
            } else {
                error_log($title.$log);
            }
        }
    }