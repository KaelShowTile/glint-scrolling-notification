<?php
class Notification_Manager {
    
    public function get_active_notifications() {
        $args = array(
            'post_type' => 'notification',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_gsn_enable_expiry',
                    'compare' => '!=',
                    'value' => '1'
                ),
                array(
                    'relation' => 'AND',
                    array(
                        'key' => '_gsn_enable_expiry',
                        'value' => '1',
                        'compare' => '='
                    ),
                    array(
                        'key' => '_gsn_expiry_datetime',
                        'value' => current_time('mysql'),
                        'compare' => '>=',
                        'type' => 'DATETIME'
                    )
                )
            )
        );
        
        return get_posts($args);
    }
    
    public function is_notification_expired($post_id) {
        $enable_expiry = get_post_meta($post_id, '_gsn_enable_expiry', true);
        $expiry_datetime = get_post_meta($post_id, '_gsn_expiry_datetime', true);
        
        if (!$enable_expiry) {
            return false;
        }
        
        if (empty($expiry_datetime)) {
            return false;
        }
        
        $current_time = current_time('timestamp');
        $expiry_time = strtotime($expiry_datetime);
        
        return $current_time > $expiry_time;
    }
}