<?php

class SM_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Migration: Rename old tables if they exist
        self::migrate_tables();
        self::migrate_settings();

        $sql = "";

        // Members Table
        $table_name = $wpdb->prefix . 'sm_members';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            national_id varchar(14) NOT NULL,
            member_code tinytext,
            name tinytext NOT NULL,
            gender enum('male', 'female') DEFAULT 'male',
            professional_grade tinytext,
            specialization tinytext,
            academic_degree enum('bachelor', 'master', 'doctorate'),
            governorate tinytext,
            membership_number tinytext,
            membership_start_date date,
            membership_expiration_date date,
            membership_status tinytext,
            license_number tinytext,
            license_issue_date date,
            license_expiration_date date,
            facility_number tinytext,
            facility_name tinytext,
            facility_license_issue_date date,
            facility_license_expiration_date date,
            facility_address text,
            sub_syndicate tinytext,
            facility_category enum('A', 'B', 'C') DEFAULT 'C',
            last_paid_membership_year int DEFAULT 0,
            last_paid_license_year int DEFAULT 0,
            email tinytext,
            phone tinytext,
            alt_phone tinytext,
            notes text,
            photo_url text,
            wp_user_id bigint(20),
            officer_id bigint(20),
            registration_date date,
            sort_order int DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY national_id (national_id),
            KEY wp_user_id (wp_user_id),
            KEY officer_id (officer_id)
        ) $charset_collate;\n";


        // Messages Table
        $table_name = $wpdb->prefix . 'sm_messages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            member_id mediumint(9),
            message text NOT NULL,
            file_url text,
            governorate varchar(50),
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY member_id (member_id),
            KEY governorate (governorate)
        ) $charset_collate;\n";

        // Logs Table
        $table_name = $wpdb->prefix . 'sm_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action tinytext NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        // Surveys Table
        $table_name = $wpdb->prefix . 'sm_surveys';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title tinytext NOT NULL,
            questions text NOT NULL,
            recipients tinytext NOT NULL,
            status enum('active', 'completed', 'cancelled') DEFAULT 'active',
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY created_by (created_by)
        ) $charset_collate;\n";

        // Survey Responses Table
        $table_name = $wpdb->prefix . 'sm_survey_responses';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            survey_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            responses text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY survey_id (survey_id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        // Payments Table
        $table_name = $wpdb->prefix . 'sm_payments';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_type enum('membership', 'license', 'facility', 'other', 'penalty') NOT NULL,
            payment_date date NOT NULL,
            target_year int,
            digital_invoice_code varchar(50),
            paper_invoice_code varchar(50),
            details_ar text,
            notes text,
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY created_by (created_by)
        ) $charset_collate;\n";

        // Update Requests Table
        $table_name = $wpdb->prefix . 'sm_update_requests';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            requested_data text NOT NULL,
            status enum('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            processed_at datetime,
            processed_by bigint(20),
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Digital Services Table
        $table_name = $wpdb->prefix . 'sm_services';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            description text,
            fees decimal(10,2) DEFAULT 0,
            required_fields text,
            selected_profile_fields text,
            status enum('active', 'suspended') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Service Requests Table
        $table_name = $wpdb->prefix . 'sm_service_requests';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_id mediumint(9) NOT NULL,
            member_id mediumint(9) NOT NULL,
            request_data text NOT NULL,
            fees_paid decimal(10,2) DEFAULT 0,
            status enum('pending', 'processing', 'approved', 'rejected') DEFAULT 'pending',
            processed_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY service_id (service_id),
            KEY member_id (member_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Membership Requests Table
        $table_name = $wpdb->prefix . 'sm_membership_requests';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            national_id varchar(14) NOT NULL,
            name tinytext NOT NULL,
            gender enum('male', 'female') DEFAULT 'male',
            professional_grade tinytext,
            specialization tinytext,
            academic_degree tinytext,
            governorate tinytext,
            phone tinytext,
            email tinytext,
            notes text,
            status enum('pending', 'approved', 'rejected') DEFAULT 'pending',
            processed_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY national_id (national_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Notification Templates Table
        $table_name = $wpdb->prefix . 'sm_notification_templates';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            body text NOT NULL,
            days_before int DEFAULT 0,
            is_enabled tinyint(1) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_type (template_type)
        ) $charset_collate;\n";

        // Notification Logs Table
        $table_name = $wpdb->prefix . 'sm_notification_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9),
            notification_type varchar(50),
            recipient_email varchar(100),
            subject varchar(255),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20),
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY sent_at (sent_at)
        ) $charset_collate;\n";

        // Documents Table
        $table_name = $wpdb->prefix . 'sm_documents';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            category enum('licenses', 'certificates', 'receipts', 'other') NOT NULL,
            title varchar(255) NOT NULL,
            file_url text NOT NULL,
            file_type varchar(50),
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY category (category)
        ) $charset_collate;\n";

        // Document Logs Table
        $table_name = $wpdb->prefix . 'sm_document_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            document_id mediumint(9) NOT NULL,
            action varchar(50) NOT NULL,
            user_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY document_id (document_id)
        ) $charset_collate;\n";

        // Publishing Center Templates
        $table_name = $wpdb->prefix . 'sm_pub_templates';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            doc_type varchar(50) DEFAULT 'other',
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Publishing Center Generated Documents
        $table_name = $wpdb->prefix . 'sm_pub_documents';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_id mediumint(9),
            serial_number varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            created_by bigint(20),
            download_count int DEFAULT 0,
            last_format varchar(20),
            options text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY serial_number (serial_number)
        ) $charset_collate;\n";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        self::setup_roles();
        self::seed_notification_templates();
        self::seed_publishing_templates();
    }

    private static function seed_publishing_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_pub_templates';

        $templates = [
            'experience_cert' => [
                'title' => 'شهادة خبرة معتمدة',
                'doc_type' => 'certificate',
                'content' => '<div style="text-align:center;"><p>تشهد النقابة العامة بأن السيد العضو / <strong>{MEMBER_NAME}</strong></p><p>المقيد برقم قيد: {MEMBERSHIP_NO} ومحافظة: {GOVERNORATE}</p><p>قد اجتاز كافة المتطلبات المهنية المقررة ويعتبر ممارساً معتمداً في تخصصه.</p></div>'
            ],
            'official_report' => [
                'title' => 'تقرير فني رسمي',
                'doc_type' => 'report',
                'content' => '<h3>موضوع التقرير: ....................</h3><p>بناءً على المعاينة الفنية والمهنية لعضو النقابة {MEMBER_NAME}، نفيد بالآتي:</p><ul><li>أولاً: .............</li><li>ثانياً: .............</li></ul>'
            ],
            'internal_memo' => [
                'title' => 'مذكرة عرض داخلية',
                'doc_type' => 'memo',
                'content' => '<h3>مذكرة عرض إلى: السيد مدير عام النقابة</h3><p>بشأن: .........................</p><p>بالإشارة إلى الطلب المقدم من {MEMBER_NAME}، نحيط سيادتكم علماً بـ .............</p><p style="text-align:left;">وتفضلوا بقبول فائق الاحترام،،</p>'
            ]
        ];

        foreach ($templates as $key => $data) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE title = %s", $data['title']));
            if (!$exists) {
                $wpdb->insert($table, [
                    'title' => $data['title'],
                    'doc_type' => $data['doc_type'],
                    'content' => $data['content']
                ]);
            }
        }
    }

    private static function seed_notification_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_notification_templates';
        $templates = [
            'membership_renewal' => [
                'subject' => 'تذكير: تجديد عضوية النقابة',
                'body' => "عزيزي العضو {member_name}،\n\nنود تذكيركم بقرب موعد تجديد عضويتكم السنوية لعام {year}.\nيرجى السداد لتجنب الغرامات.\n\nشكراً لكم.",
                'days_before' => 30
            ],
            'license_practice' => [
                'subject' => 'تنبيه: انتهاء تصريح مزاولة المهنة',
                'body' => "عزيزي العضو {member_name}،\n\nنحيطكم علماً بأن تصريح مزاولة المهنة الخاص بكم سينتهي في {expiry_date}.\nيرجى البدء في إجراءات التجديد.\n\nتحياتنا.",
                'days_before' => 30
            ],
            'license_facility' => [
                'subject' => 'تنبيه: انتهاء ترخيص المنشأة',
                'body' => "عزيزي العضو {member_name}،\n\nنحيطكم علماً بأن ترخيص المنشأة {facility_name} سينتهي في {expiry_date}.\nيرجى مراجعة النقابة للتجديد.\n\nشكراً لكم.",
                'days_before' => 30
            ],
            'payment_reminder' => [
                'subject' => 'إشعار: مستحقات مالية متأخرة',
                'body' => "عزيزي العضو {member_name}،\n\nيوجد مبالغ مستحقة على حسابكم بقيمة {balance} ج.م.\nنرجو السداد في أقرب وقت ممكن.\n\nإدارة النقابة.",
                'days_before' => 0
            ],
            'welcome_activation' => [
                'subject' => 'مرحباً بك في المنصة الرقمية لنقابتك',
                'body' => "أهلاً بك يا {member_name}،\n\nتم تفعيل حسابك بنجاح في المنصة الرقمية.\nيمكنك الآن الاستفادة من كافة الخدمات الإلكترونية.\n\nرقم عضويتك: {membership_number}",
                'days_before' => 0
            ],
            'admin_alert' => [
                'subject' => 'تنبيه إداري من النقابة',
                'body' => "عزيزي العضو {member_name}،\n\n{alert_message}\n\nشكراً لكم.",
                'days_before' => 0
            ]
        ];

        foreach ($templates as $type => $data) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE template_type = %s", $type));
            if (!$exists) {
                $wpdb->insert($table, [
                    'template_type' => $type,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'days_before' => $data['days_before'],
                    'is_enabled' => 1
                ]);
            }
        }
    }

    private static function migrate_settings() {
        $old_info = get_option('sm_syndicate_info');
        if ($old_info && !get_option('sm_syndicate_info')) {
            // Rename syndicate fields to syndicate fields
            if (isset($old_info['syndicate_name'])) {
                $old_info['syndicate_name'] = $old_info['syndicate_name'];
            }
            if (isset($old_info['syndicate_logo'])) {
                $old_info['syndicate_logo'] = $old_info['syndicate_logo'];
            }
            if (isset($old_info['syndicate_officer_name'])) {
                $old_info['syndicate_officer_name'] = $old_info['syndicate_officer_name'];
            }
            update_option('sm_syndicate_info', $old_info);
        }
    }

    private static function migrate_tables() {
        global $wpdb;
        // Migration from School version (sm_students -> sm_members)
        $mappings = array(
            'sm_students' => 'sm_members'
        );
        foreach ($mappings as $old => $new) {
            $old_table = $wpdb->prefix . $old;
            $new_table = $wpdb->prefix . $new;
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !$wpdb->get_var("SHOW TABLES LIKE '$new_table'")) {
                $wpdb->query("RENAME TABLE $old_table TO $new_table");
            }
        }

        $members_table = $wpdb->prefix . 'sm_members';
        if ($wpdb->get_var("SHOW TABLES LIKE '$members_table'")) {
            // Ensure column names are updated from legacy 'student' to 'member'
            $column_renames = [
                'student_code' => 'member_code'
            ];
            foreach ($column_renames as $old_col => $new_col) {
                $col_exists = $wpdb->get_results("SHOW COLUMNS FROM $members_table LIKE '$old_col'");
                if (!empty($col_exists)) {
                    $wpdb->query("ALTER TABLE $members_table CHANGE $old_col $new_col tinytext");
                }
            }
        }

        // Rename old column names to new ones in all relevant tables
        $tables_to_fix = array('sm_messages', 'sm_members');
        foreach ($tables_to_fix as $table) {
            $full_table = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$full_table'")) {
                // Fix Member ID to Member ID
                $col_member = $wpdb->get_results("SHOW COLUMNS FROM $full_table LIKE 'member_id'");
                if (!empty($col_member)) {
                    $wpdb->query("ALTER TABLE $full_table CHANGE member_id member_id mediumint(9)");
                }

                // Fix Officer ID / Syndicate Member ID to Officer ID
                $col_officer = $wpdb->get_results("SHOW COLUMNS FROM $full_table LIKE 'officer_id'");
                if (!empty($col_officer)) {
                    $wpdb->query("ALTER TABLE $full_table CHANGE officer_id officer_id bigint(20)");
                }

                $col_syndicate_member = $wpdb->get_results("SHOW COLUMNS FROM $full_table LIKE 'syndicate_member_id'");
                if (!empty($col_syndicate_member)) {
                    $wpdb->query("ALTER TABLE $full_table CHANGE syndicate_member_id officer_id bigint(20)");
                }
            }
        }
    }

    private static function setup_roles() {
        $sm_capabilities = array(
            'read' => true,
            'manage_options' => true,
            'sm_manage_system' => true,
            'sm_manage_users' => true,
            'sm_manage_members' => true,
            'sm_manage_finance' => true,
            'sm_manage_licenses' => true,
            'sm_print_reports' => true,
            'sm_full_access' => true
        );

        // 1. System Manager (مدير النظام)
        if (!get_role('sm_system_admin')) {
            add_role('sm_system_admin', 'مدير النظام', $sm_capabilities);
        } else {
            $role = get_role('sm_system_admin');
            foreach ($sm_capabilities as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }

        // Ensure WordPress Administrator has all SM capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($sm_capabilities as $cap => $grant) {
                $admin_role->add_cap($cap, $grant);
            }
        }

        // Add specific caps for Digital Archive
        $admin_role->add_cap('sm_manage_archive', true);
        if (get_role('sm_system_admin')) get_role('sm_system_admin')->add_cap('sm_manage_archive', true);
        if (get_role('sm_syndicate_admin')) get_role('sm_syndicate_admin')->add_cap('sm_manage_archive', true);

        // 2. Syndicate Administrator (مسؤول نقابة)
        $syndicate_admin_caps = array(
            'read' => true,
            'sm_manage_system' => true,
            'sm_manage_members' => true,
            'sm_manage_finance' => true,
            'sm_manage_licenses' => true,
            'sm_print_reports' => true
        );
        if (!get_role('sm_syndicate_admin')) {
            add_role('sm_syndicate_admin', 'مسؤول نقابة', $syndicate_admin_caps);
        } else {
            $role = get_role('sm_syndicate_admin');
            foreach ($syndicate_admin_caps as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }

        // 3. Syndicate Member (عضو نقابة) - Restricted to personal profile
        if (!get_role('sm_syndicate_member')) {
            add_role('sm_syndicate_member', 'عضو نقابة', array('read' => true));
        }

        self::migrate_user_roles();
        self::sync_missing_member_accounts();
        self::create_pages();
    }

    private static function create_pages() {
        $pages = array(
            'sm-login' => array(
                'title' => 'تسجيل الدخول للنظام',
                'content' => '[sm_login]'
            ),
            'sm-admin' => array(
                'title' => 'لوحة الإدارة النقابية',
                'content' => '[sm_admin]'
            )
        );

        foreach ($pages as $slug => $data) {
            $existing = get_page_by_path($slug);
            if (!$existing) {
                wp_insert_post(array(
                    'post_title'    => $data['title'],
                    'post_content'  => $data['content'],
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $slug
                ));
            }
        }
    }

    private static function sync_missing_member_accounts() {
        global $wpdb;
        $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_members WHERE wp_user_id IS NULL OR wp_user_id = 0");
        foreach ($members as $m) {
            $digits = '';
            for ($i = 0; $i < 10; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $temp_pass = 'IRS' . $digits;
            $user_id = wp_insert_user([
                'user_login' => $m->national_id,
                'user_email' => $m->email ?: $m->national_id . '@irseg.org',
                'display_name' => $m->name,
                'user_pass' => $temp_pass,
                'role' => 'sm_syndicate_member'
            ]);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'sm_temp_pass', $temp_pass);
                if (!empty($m->governorate)) {
                    update_user_meta($user_id, 'sm_governorate', $m->governorate);
                }
                $wpdb->update("{$wpdb->prefix}sm_members", ['wp_user_id' => $user_id], ['id' => $m->id]);
            }
        }
    }

    private static function migrate_user_roles() {
        $role_migration = array(
            'sm_system_admin'       => 'sm_system_admin',
            'sm_officer'            => 'sm_syndicate_admin',
            'sm_syndicate_admin'    => 'sm_syndicate_admin',
            'sm_syndicate_member'   => 'sm_syndicate_member',
            'sm_member'             => 'sm_syndicate_member',
            'sm_parent'             => 'sm_syndicate_member',
            'sm_principal'          => 'sm_syndicate_admin',
            'school_admin'          => 'sm_syndicate_admin',
            'sm_school_admin'       => 'sm_syndicate_admin',
            'sm_student'            => 'sm_syndicate_member'
        );

        foreach ($role_migration as $old => $new) {
            $users = get_users(array('role' => $old));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user->add_role($new);
                    $user->remove_role($old);
                }
            }
        }
    }
}
