<?php

/**
 * @package  AlecadddPlugin
 */

namespace Inc\Base;

class AdminTable
{
    public function register()
    {
        add_filter('manage_event_posts_columns', array($this, 'bs_event_table_head'));
        add_action('manage_event_posts_custom_column', array($this, 'bs_event_table_content'), 10, 2);
    }

    function bs_event_table_head($defaults)
    {
        $defaults['event_date']  = 'Event Date';
        $defaults['ticket_status']    = 'Ticket Status';
        $defaults['venue']   = 'Venue';
        $defaults['author'] = 'Added By';
        return $defaults;
    }

    function bs_event_table_content($column_name, $post_id)
    {
        if ($column_name == 'event_date') {
            $event_date = get_post_meta($post_id, '_bs_meta_event_date', true);
            echo  date(_x('F d, Y', 'Event date format', 'textdomain'), strtotime($event_date));
        }
        if ($column_name == 'ticket_status') {
            $status = get_post_meta($post_id, '_bs_meta_event_ticket_status', true);
            echo $status;
        }

        if ($column_name == 'venue') {
            echo get_post_meta($post_id, '_bs_meta_event_venue', true);
        }
    }
}
