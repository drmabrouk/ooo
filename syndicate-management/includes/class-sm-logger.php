<?php

class SM_Logger {
    public static function log($action, $details = '') {
        global $wpdb;
        $user_id = get_current_user_id();

        $wpdb->insert(
            "{$wpdb->prefix}sm_logs",
            array(
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'details' => sanitize_textarea_field($details),
                'created_at' => current_time('mysql')
            )
        );

        // Limit to 200 entries
        $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_logs");
        if ($count > 200) {
            $limit = $count - 200;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sm_logs ORDER BY created_at ASC LIMIT %d", $limit));
        }
    }

    public static function get_logs($limit = 100, $offset = 0, $search = '') {
        global $wpdb;
        $user = wp_get_current_user();
        $is_officer = in_array('sm_syndicate_admin', (array)$user->roles) || in_array('sm_syndicate_member', (array)$user->roles);
        $has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
        $my_gov = get_user_meta($user->ID, 'sm_governorate', true);

        $where = "1=1";
        if ($is_officer && !$has_full_access && $my_gov) {
            $where = $wpdb->prepare("(
                EXISTS (SELECT 1 FROM {$wpdb->prefix}usermeta um WHERE um.user_id = l.user_id AND um.meta_key = 'sm_governorate' AND um.meta_value = %s)
                OR EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.wp_user_id = l.user_id AND m.governorate = %s)
            )", $my_gov, $my_gov);
        }

        if (!empty($search)) {
            $s = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (l.action LIKE %s OR l.details LIKE %s OR u.display_name LIKE %s OR l.created_at LIKE %s)", $s, $s, $s, $s);
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}sm_logs l LEFT JOIN {$wpdb->base_prefix}users u ON l.user_id = u.ID WHERE $where ORDER BY l.created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    public static function get_total_logs($search = '') {
        global $wpdb;
        $user = wp_get_current_user();
        $is_officer = in_array('sm_syndicate_admin', (array)$user->roles) || in_array('sm_syndicate_member', (array)$user->roles);
        $has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
        $my_gov = get_user_meta($user->ID, 'sm_governorate', true);

        $where = "1=1";
        if ($is_officer && !$has_full_access && $my_gov) {
            $where = $wpdb->prepare("(
                EXISTS (SELECT 1 FROM {$wpdb->prefix}usermeta um WHERE um.user_id = l.user_id AND um.meta_key = 'sm_governorate' AND um.meta_value = %s)
                OR EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.wp_user_id = l.user_id AND m.governorate = %s)
            )", $my_gov, $my_gov);
        }

        if (!empty($search)) {
            $s = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (l.action LIKE %s OR l.details LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->base_prefix}users u WHERE u.ID = l.user_id AND u.display_name LIKE %s) OR l.created_at LIKE %s)", $s, $s, $s, $s);
        }

        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_logs l WHERE $where");
    }
}
