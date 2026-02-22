<?php

class SM_DB {

    public static function get_staff($args = array()) {
        $user = wp_get_current_user();
        $is_syndicate_admin = in_array('sm_syndicate_admin', (array)$user->roles);
        $my_gov = get_user_meta($user->ID, 'sm_governorate', true);

        $default_args = array(
            'role__in' => array('administrator', 'sm_system_admin', 'sm_syndicate_admin', 'sm_syndicate_member'),
            'number' => 20,
            'offset' => 0
        );

        if ($is_syndicate_admin) {
            $default_args['role'] = 'sm_syndicate_member'; // Can only see members
            if ($my_gov) {
                $default_args['meta_query'] = array(
                    array(
                        'key' => 'sm_governorate',
                        'value' => $my_gov,
                        'compare' => '='
                    )
                );
            }
        }

        $args = wp_parse_args($args, $default_args);
        return get_users($args);
    }

    public static function get_members($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_members';
        $query = "SELECT * FROM $table_name WHERE 1=1";
        $params = array();

        $limit = isset($args['limit']) ? intval($args['limit']) : 20;
        $offset = isset($args['offset']) ? intval($args['offset']) : 0;

        // Ensure we don't have negative limits unless specifically -1
        if ($limit < -1) $limit = 20;

        // Role-based filtering (Governorate)
        $user = wp_get_current_user();
        $is_officer = in_array('sm_syndicate_admin', (array)$user->roles) || in_array('sm_syndicate_member', (array)$user->roles);
        if ($is_officer && !current_user_can('manage_options') && !current_user_can('sm_full_access')) {
            $gov = get_user_meta($user->ID, 'sm_governorate', true);
            if ($gov) {
                $query .= " AND governorate = %s";
                $params[] = $gov;
            }
        }

        if (isset($args['professional_grade']) && !empty($args['professional_grade'])) {
            $query .= " AND professional_grade = %s";
            $params[] = $args['professional_grade'];
        }

        if (isset($args['specialization']) && !empty($args['specialization'])) {
            $query .= " AND specialization = %s";
            $params[] = $args['specialization'];
        }

        if (isset($args['membership_status']) && !empty($args['membership_status'])) {
            $query .= " AND membership_status = %s";
            $params[] = $args['membership_status'];
        }

        if (isset($args['search']) && !empty($args['search'])) {
            $query .= " AND (name LIKE %s OR national_id LIKE %s OR membership_number LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $query .= " ORDER BY sort_order ASC, name ASC";

        if ($limit != -1) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
        }

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_member_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_members WHERE id = %d", $id));
    }

    public static function get_member_by_national_id($national_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_members WHERE national_id = %s", $national_id));
    }

    public static function add_member($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_members';

        $national_id = sanitize_text_field($data['national_id'] ?? '');
        if (!preg_match('/^[0-9]{14}$/', $national_id)) {
            return new WP_Error('invalid_national_id', 'الرقم القومي يجب أن يتكون من 14 رقم بالضبط وبدون حروف.');
        }

        // Check if national_id already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE national_id = %s", $national_id));
        if ($exists) {
            return new WP_Error('duplicate_national_id', 'الرقم القومي مسجل مسبقاً.');
        }

        $name = sanitize_text_field($data['name'] ?? '');
        $email = sanitize_email($data['email'] ?? '');

        // Auto-create WordPress User for the Member
        $wp_user_id = null;
        $digits = '';
        for ($i = 0; $i < 10; $i++) {
            $digits .= mt_rand(0, 9);
        }
        $temp_pass = 'IRS' . $digits;

        if (!function_exists('wp_insert_user')) {
            require_once(ABSPATH . 'wp-includes/user.php');
        }

        $wp_user_id = wp_insert_user(array(
            'user_login' => $national_id,
            'user_email' => $email ?: $national_id . '@irseg.org',
            'display_name' => $name,
            'user_pass' => $temp_pass,
            'role' => 'sm_syndicate_member'
        ));

        if (!is_wp_error($wp_user_id)) {
            $wp_user_id = $wp_user_id;
            update_user_meta($wp_user_id, 'sm_temp_pass', $temp_pass);
            if (!empty($data['governorate'])) {
                update_user_meta($wp_user_id, 'sm_governorate', sanitize_text_field($data['governorate']));
            }
        } else {
            return $wp_user_id; // Return WP_Error
        }

        $insert_data = array(
            'national_id' => $national_id,
            'name' => $name,
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'professional_grade' => sanitize_text_field($data['professional_grade'] ?? ''),
            'specialization' => sanitize_text_field($data['specialization'] ?? ''),
            'academic_degree' => sanitize_text_field($data['academic_degree'] ?? ''),
            'governorate' => sanitize_text_field($data['governorate'] ?? ''),
            'membership_number' => sanitize_text_field($data['membership_number'] ?? ''),
            'membership_start_date' => sanitize_text_field($data['membership_start_date'] ?? null),
            'membership_expiration_date' => sanitize_text_field($data['membership_expiration_date'] ?? null),
            'membership_status' => sanitize_text_field($data['membership_status'] ?? ''),
            'license_number' => sanitize_text_field($data['license_number'] ?? ''),
            'license_issue_date' => sanitize_text_field($data['license_issue_date'] ?? null),
            'license_expiration_date' => sanitize_text_field($data['license_expiration_date'] ?? null),
            'facility_number' => sanitize_text_field($data['facility_number'] ?? ''),
            'facility_name' => sanitize_text_field($data['facility_name'] ?? ''),
            'facility_license_issue_date' => sanitize_text_field($data['facility_license_issue_date'] ?? null),
            'facility_license_expiration_date' => sanitize_text_field($data['facility_license_expiration_date'] ?? null),
            'facility_address' => sanitize_textarea_field($data['facility_address'] ?? ''),
            'sub_syndicate' => sanitize_text_field($data['sub_syndicate'] ?? ''),
            'facility_category' => sanitize_text_field($data['facility_category'] ?? 'C'),
            'last_paid_membership_year' => intval($data['last_paid_membership_year'] ?? 0),
            'last_paid_license_year' => intval($data['last_paid_license_year'] ?? 0),
            'email' => $email ?: $national_id . '@irseg.org',
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'alt_phone' => sanitize_text_field($data['alt_phone'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'wp_user_id' => $wp_user_id,
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        $id = $wpdb->insert_id;

        if ($id) {
            SM_Logger::log('إضافة عضو جديد', "تمت إضافة العضو: $name بنجاح (الرقم القومي: $national_id)");
        }

        return $id;
    }

    public static function update_member($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_members';

        $update_data = array();
        $fields = [
            'national_id', 'name', 'gender', 'professional_grade', 'specialization',
            'academic_degree', 'governorate', 'membership_number', 'membership_start_date',
            'membership_expiration_date', 'membership_status', 'license_number',
            'license_issue_date', 'license_expiration_date', 'facility_number',
            'facility_name', 'facility_license_issue_date', 'facility_license_expiration_date',
            'facility_address', 'sub_syndicate', 'facility_category', 'last_paid_membership_year',
            'last_paid_license_year', 'email', 'phone', 'alt_phone', 'notes'
        ];

        foreach ($fields as $f) {
            if (isset($data[$f])) {
                if (in_array($f, ['facility_address', 'notes'])) {
                    $update_data[$f] = sanitize_textarea_field($data[$f]);
                } elseif ($f === 'email') {
                    $update_data[$f] = sanitize_email($data[$f]);
                } else {
                    $update_data[$f] = sanitize_text_field($data[$f]);
                }
            }
        }

        if (isset($data['wp_user_id'])) $update_data['wp_user_id'] = intval($data['wp_user_id']);
        if (isset($data['registration_date'])) $update_data['registration_date'] = sanitize_text_field($data['registration_date']);
        if (isset($data['sort_order'])) $update_data['sort_order'] = intval($data['sort_order']);

        $res = $wpdb->update($table_name, $update_data, array('id' => $id));

        // Sync to WP User
        $member = self::get_member_by_id($id);
        if ($member && $member->wp_user_id) {
            $user_data = ['ID' => $member->wp_user_id];
            if (isset($data['name'])) $user_data['display_name'] = $data['name'];
            if (isset($data['email'])) $user_data['user_email'] = $data['email'];
            if (count($user_data) > 1) {
                wp_update_user($user_data);
            }
            if (isset($data['governorate'])) {
                update_user_meta($member->wp_user_id, 'sm_governorate', sanitize_text_field($data['governorate']));
            }
        }

        return $res;
    }

    public static function update_member_photo($id, $photo_url) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'sm_members', array('photo_url' => $photo_url), array('id' => $id));
    }

    public static function delete_member($id) {
        global $wpdb;

        $member = self::get_member_by_id($id);
        if ($member) {
            SM_Logger::log('حذف عضو (مع إمكانية الاستعادة)', 'ROLLBACK_DATA:' . json_encode(['table' => 'members', 'data' => (array)$member]));
            if ($member->wp_user_id) {
                if (!function_exists('wp_delete_user')) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                }
                wp_delete_user($member->wp_user_id);
            }
        }

        return $wpdb->delete($wpdb->prefix . 'sm_members', array('id' => $id));
    }

    public static function member_exists($national_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_members WHERE national_id = %s",
            $national_id
        ));
    }

    public static function get_next_sort_order() {
        global $wpdb;
        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}sm_members");
        return ($max ? intval($max) : 0) + 1;
    }

    public static function send_message($sender_id, $receiver_id, $message, $member_id = null, $file_url = null, $governorate = null) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'sm_messages', array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'member_id' => $member_id,
            'message' => $message,
            'file_url' => $file_url,
            'governorate' => $governorate,
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_ticket_messages($member_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}sm_messages m
             LEFT JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE m.member_id = %d
             ORDER BY m.created_at ASC",
            $member_id
        ));
    }

    public static function get_governorate_officials($governorate) {
        return get_users(array(
            'role__in' => array('sm_system_admin', 'sm_syndicate_admin', 'administrator'),
            'meta_query' => array(
                array(
                    'key' => 'sm_governorate',
                    'value' => $governorate,
                    'compare' => '='
                )
            )
        ));
    }

    public static function get_governorate_conversations($governorate = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_messages';

        $where = "1=1";
        $params = [];
        if (!empty($governorate)) {
            $where = "governorate = %s";
            $params[] = $governorate;
        }

        $query = "SELECT member_id, MAX(created_at) as last_activity
                  FROM $table
                  WHERE $where
                  GROUP BY member_id
                  ORDER BY last_activity DESC";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $results = $wpdb->get_results($query);
        }

        $conversations = [];
        foreach ($results as $row) {
            $member = self::get_member_by_id($row->member_id);
            if (!$member) continue;

            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_messages
                 WHERE member_id = %d
                 ORDER BY created_at DESC LIMIT 1",
                $row->member_id
            ));

            $conversations[] = [
                'member' => $member,
                'last_message' => $last_msg
            ];
        }
        return $conversations;
    }

    public static function get_conversation_messages($user1, $user2) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}sm_messages m
             JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE (sender_id = %d AND receiver_id = %d)
                OR (sender_id = %d AND receiver_id = %d)
             ORDER BY created_at ASC",
            $user1, $user2, $user2, $user1
        ));
    }

    public static function get_sent_messages($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as receiver_name
             FROM {$wpdb->prefix}sm_messages m
             JOIN {$wpdb->prefix}users u ON m.receiver_id = u.ID
             WHERE m.sender_id = %d
             ORDER BY m.created_at DESC",
            $user_id
        ));
    }

    public static function delete_expired_messages() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->prefix}sm_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    }

    public static function get_conversations($user_id) {
        global $wpdb;
        $other_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT CASE WHEN sender_id = %d THEN receiver_id ELSE sender_id END
             FROM {$wpdb->prefix}sm_messages
             WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        ));

        $conversations = [];
        foreach ($other_ids as $oid) {
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_messages
                 WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)
                 ORDER BY created_at DESC LIMIT 1",
                $user_id, $oid, $oid, $user_id
            ));
            $conversations[] = [
                'user' => get_userdata($oid),
                'last_message' => $last_msg
            ];
        }
        return $conversations;
    }

    public static function get_statistics($filters = array()) {
        global $wpdb;
        $stats = array();

        $user = wp_get_current_user();
        $is_officer = in_array('sm_syndicate_admin', (array)$user->roles) || in_array('sm_syndicate_member', (array)$user->roles);
        $has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
        $my_gov = get_user_meta($user->ID, 'sm_governorate', true);

        $where_member = "1=1";
        if ($is_officer && !$has_full_access && $my_gov) {
            $where_member = $wpdb->prepare("governorate = %s", $my_gov);
        }

        $stats['total_members'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_members WHERE $where_member");
        $stats['total_officers'] = count(self::get_staff(['number' => -1]));

        // Total Revenue
        $join_member_rev = "";
        $where_rev = "1=1";
        if ($is_officer && !$has_full_access && $my_gov) {
            $join_member_rev = "JOIN {$wpdb->prefix}sm_members m ON p.member_id = m.id";
            $where_rev = $wpdb->prepare("m.governorate = %s", $my_gov);
        }
        $stats['total_revenue'] = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}sm_payments p $join_member_rev WHERE $where_rev") ?: 0;

        // Financial Trends (Last 30 Days)
        $join_member = "";
        $where_finance = "payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        if ($is_officer && !$has_full_access && $my_gov) {
            $join_member = "JOIN {$wpdb->prefix}sm_members m ON p.member_id = m.id";
            $where_finance .= $wpdb->prepare(" AND m.governorate = %s", $my_gov);
        }

        $stats['financial_trends'] = $wpdb->get_results("
            SELECT DATE(payment_date) as date, SUM(amount) as total
            FROM {$wpdb->prefix}sm_payments p
            $join_member
            WHERE $where_finance
            GROUP BY DATE(payment_date)
            ORDER BY date ASC
        ");

        // Specialization Distribution
        $stats['specializations'] = $wpdb->get_results("
            SELECT specialization, COUNT(*) as count
            FROM {$wpdb->prefix}sm_members
            WHERE specialization != '' AND $where_member
            GROUP BY specialization
        ");

        return $stats;
    }

    public static function get_member_stats($member_id) {
        return array();
    }

    public static function delete_all_data() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_members");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");
        SM_Logger::log('مسح شامل للبيانات', 'تم تنفيذ أمر مسح كافة بيانات النظام');
    }

    public static function get_backup_data() {
        global $wpdb;
        $data = array();
        $tables = array('members', 'messages');
        foreach ($tables as $t) {
            $data[$t] = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_$t", ARRAY_A);
        }
        return json_encode($data);
    }

    public static function restore_backup($json) {
        global $wpdb;
        $data = json_decode($json, true);
        if (!$data) return false;

        foreach ($data as $table => $rows) {
            $table_name = $wpdb->prefix . 'sm_' . $table;
            $wpdb->query("TRUNCATE TABLE $table_name");
            foreach ($rows as $row) {
                $wpdb->insert($table_name, $row);
            }
        }
        return true;
    }

    public static function get_pending_reports_count() {
        return 0;
    }


    public static function add_survey($title, $questions, $recipients, $user_id) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}sm_surveys", array(
            'title' => $title,
            'questions' => json_encode($questions),
            'recipients' => $recipients,
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => current_time('mysql')
        ));
        return $wpdb->insert_id;
    }

    public static function get_surveys($role) {
        global $wpdb;
        $roles = [$role, 'all'];
        if ($role === 'sm_syndicate_member') $roles[] = 'sm_member';

        $placeholders = implode(',', array_fill(0, count($roles), '%s'));
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_surveys WHERE recipients IN ($placeholders) AND status = 'active' ORDER BY created_at DESC", ...$roles));
    }

    public static function save_survey_response($survey_id, $user_id, $responses) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_survey_responses", array(
            'survey_id' => $survey_id,
            'user_id' => $user_id,
            'responses' => json_encode($responses),
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_survey($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_surveys WHERE id = %d", $id));
    }

    public static function get_survey_results($survey_id) {
        global $wpdb;
        $survey = self::get_survey($survey_id);
        if (!$survey) return array();

        $user = wp_get_current_user();
        $is_officer = in_array('sm_syndicate_admin', (array)$user->roles) || in_array('sm_syndicate_member', (array)$user->roles);
        $has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
        $my_gov = get_user_meta($user->ID, 'sm_governorate', true);

        $where = $wpdb->prepare("survey_id = %d", $survey_id);
        if ($is_officer && !$has_full_access && $my_gov) {
            $where .= $wpdb->prepare(" AND (
                EXISTS (SELECT 1 FROM {$wpdb->prefix}usermeta um WHERE um.user_id = user_id AND um.meta_key = 'sm_governorate' AND um.meta_value = %s)
                OR EXISTS (SELECT 1 FROM {$wpdb->prefix}sm_members m WHERE m.wp_user_id = user_id AND m.governorate = %s)
            )", $my_gov, $my_gov);
        }

        $questions = json_decode($survey->questions, true);
        $responses = $wpdb->get_results("SELECT responses FROM {$wpdb->prefix}sm_survey_responses WHERE $where");

        $results = array();
        foreach ($questions as $index => $q) {
            $results[$index] = array('question' => $q, 'answers' => array());
            foreach ($responses as $r) {
                $res_data = json_decode($r->responses, true);
                $ans = $res_data[$index] ?? 'No Answer';
                $results[$index]['answers'][$ans] = ($results[$index]['answers'][$ans] ?? 0) + 1;
            }
        }
        return $results;
    }

    public static function get_survey_responses($survey_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_survey_responses WHERE survey_id = %d", $survey_id));
    }

    public static function add_update_request($member_id, $data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_update_requests", array(
            'member_id' => $member_id,
            'requested_data' => json_encode($data),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_update_requests($status = 'pending') {
        global $wpdb;
        $user = wp_get_current_user();
        $is_officer = in_array('sm_syndicate_admin', (array)$user->roles) || in_array('sm_syndicate_member', (array)$user->roles);
        $has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');
        $my_gov = get_user_meta($user->ID, 'sm_governorate', true);

        $where = $wpdb->prepare("r.status = %s", $status);
        if ($is_officer && !$has_full_access && $my_gov) {
            $where .= $wpdb->prepare(" AND m.governorate = %s", $my_gov);
        }

        return $wpdb->get_results("
            SELECT r.*, m.name as member_name, m.national_id
            FROM {$wpdb->prefix}sm_update_requests r
            JOIN {$wpdb->prefix}sm_members m ON r.member_id = m.id
            WHERE $where
            ORDER BY r.created_at DESC
        ");
    }

    public static function process_update_request($request_id, $status) {
        global $wpdb;
        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_update_requests WHERE id = %d", $request_id));
        if (!$request) return false;

        if ($status === 'approved') {
            $data = json_decode($request->requested_data, true);
            self::update_member($request->member_id, $data);
            SM_Logger::log('اعتماد طلب تحديث بيانات', "تم تحديث بيانات العضو ID: {$request->member_id}");
        }

        return $wpdb->update(
            "{$wpdb->prefix}sm_update_requests",
            array(
                'status' => $status,
                'processed_at' => current_time('mysql'),
                'processed_by' => get_current_user_id()
            ),
            array('id' => $request_id)
        );
    }

    public static function get_services($args = array()) {
        global $wpdb;
        $status = $args['status'] ?? 'active';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_services WHERE status = %s ORDER BY created_at DESC", $status));
    }

    public static function add_service($data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_services", array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'fees' => floatval($data['fees']),
            'required_fields' => $data['required_fields'] ?? '[]',
            'selected_profile_fields' => $data['selected_profile_fields'] ?? '',
            'status' => 'active',
            'created_at' => current_time('mysql')
        ));
    }

    public static function update_service($id, $data) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}sm_services", $data, array('id' => $id));
    }

    public static function delete_service($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}sm_services", array('id' => $id));
    }

    public static function submit_service_request($data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_service_requests", array(
            'service_id' => intval($data['service_id']),
            'member_id' => intval($data['member_id']),
            'request_data' => $data['request_data'], // JSON string
            'fees_paid' => 0,
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
    }

    public static function get_service_requests($args = array()) {
        global $wpdb;
        $where = "1=1";
        $params = [];

        if (!empty($args['status'])) {
            $where .= " AND r.status = %s";
            $params[] = $args['status'];
        }

        if (!empty($args['member_id'])) {
            $where .= " AND r.member_id = %d";
            $params[] = intval($args['member_id']);
        }

        $query = "SELECT r.*, s.name as service_name, m.name as member_name, m.governorate
                  FROM {$wpdb->prefix}sm_service_requests r
                  JOIN {$wpdb->prefix}sm_services s ON r.service_id = s.id
                  JOIN {$wpdb->prefix}sm_members m ON r.member_id = m.id
                  WHERE $where
                  ORDER BY r.created_at DESC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function update_service_request_status($request_id, $status, $fees_paid = null) {
        global $wpdb;
        $data = array(
            'status' => $status,
            'processed_by' => get_current_user_id(),
            'updated_at' => current_time('mysql')
        );
        if ($fees_paid !== null) $data['fees_paid'] = floatval($fees_paid);

        return $wpdb->update("{$wpdb->prefix}sm_service_requests", $data, array('id' => $request_id));
    }

    // Document Vault Methods
    public static function add_document($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}sm_documents", array(
            'member_id' => intval($data['member_id']),
            'category' => sanitize_text_field($data['category']),
            'title' => sanitize_text_field($data['title']),
            'file_url' => esc_url_raw($data['file_url']),
            'file_type' => sanitize_text_field($data['file_type']),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ));
        if ($res) {
            $doc_id = $wpdb->insert_id;
            self::log_document_action($doc_id, 'upload');
            return $doc_id;
        }
        return false;
    }

    public static function get_member_documents($member_id, $args = []) {
        global $wpdb;
        $query = "SELECT * FROM {$wpdb->prefix}sm_documents WHERE member_id = %d";
        $params = [intval($member_id)];

        if (!empty($args['category'])) {
            $query .= " AND category = %s";
            $params[] = sanitize_text_field($args['category']);
        }

        if (!empty($args['search'])) {
            $query .= " AND title LIKE %s";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $query .= " ORDER BY created_at DESC";
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    public static function delete_document($doc_id) {
        global $wpdb;
        self::log_document_action($doc_id, 'delete');
        return $wpdb->delete("{$wpdb->prefix}sm_documents", array('id' => intval($doc_id)));
    }

    public static function log_document_action($doc_id, $action) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}sm_document_logs", array(
            'document_id' => intval($doc_id),
            'action' => sanitize_text_field($action),
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_document_logs($doc_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name as user_name
             FROM {$wpdb->prefix}sm_document_logs l
             LEFT JOIN {$wpdb->prefix}users u ON l.user_id = u.ID
             WHERE l.document_id = %d
             ORDER BY l.created_at DESC",
            intval($doc_id)
        ));
    }

    // Publishing Center Methods
    public static function save_pub_template($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_pub_templates';
        if (!empty($data['id'])) {
            return $wpdb->update($table, [
                'title' => sanitize_text_field($data['title']),
                'content' => $data['content'],
                'doc_type' => sanitize_text_field($data['doc_type']),
                'settings' => $data['settings']
            ], ['id' => intval($data['id'])]);
        } else {
            return $wpdb->insert($table, [
                'title' => sanitize_text_field($data['title']),
                'content' => $data['content'],
                'doc_type' => sanitize_text_field($data['doc_type']),
                'settings' => $data['settings']
            ]);
        }
    }

    public static function get_pub_templates() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_pub_templates ORDER BY created_at DESC");
    }

    public static function get_pub_template($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_templates WHERE id = %d", $id));
    }

    public static function generate_pub_document($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_pub_documents';

        // Generate Unique Serial: PUB-YYYY-XXXXX
        $year = date('Y');
        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table");
        $serial = 'PUB-' . $year . '-' . str_pad(($last_id + 1), 5, '0', STR_PAD_LEFT);

        $res = $wpdb->insert($table, [
            'template_id' => intval($data['template_id'] ?? 0),
            'serial_number' => $serial,
            'title' => sanitize_text_field($data['title']),
            'content' => $data['content'],
            'options' => json_encode($data['options'] ?? []),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);

        return $res ? $wpdb->insert_id : false;
    }

    public static function get_pub_documents($args = []) {
        global $wpdb;
        $where = "1=1";
        if (!empty($args['search'])) {
            $where .= $wpdb->prepare(" AND (d.title LIKE %s OR d.serial_number LIKE %s)", '%' . $wpdb->esc_like($args['search']) . '%', '%' . $wpdb->esc_like($args['search']) . '%');
        }
        return $wpdb->get_results("
            SELECT d.*, u.display_name as creator_name
            FROM {$wpdb->prefix}sm_pub_documents d
            LEFT JOIN {$wpdb->prefix}users u ON d.created_by = u.ID
            WHERE $where
            ORDER BY d.created_at DESC
        ");
    }

    public static function get_pub_document_by_serial($serial) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_documents WHERE serial_number = %s", $serial));
    }

    public static function increment_pub_download($id, $format) {
        global $wpdb;
        return $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}sm_pub_documents SET download_count = download_count + 1, last_format = %s WHERE id = %d",
            $format, $id
        ));
    }
}
