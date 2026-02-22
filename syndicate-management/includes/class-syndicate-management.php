<?php

class Syndicate_Management {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'syndicate-management';
        $this->version = SM_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SM_PLUGIN_DIR . 'includes/class-sm-loader.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-db.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-settings.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-finance.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-logger.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-notifications.php';
        require_once SM_PLUGIN_DIR . 'admin/class-sm-admin.php';
        require_once SM_PLUGIN_DIR . 'public/class-sm-public.php';
        $this->loader = new SM_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new SM_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new SM_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('admin_init', $plugin_public, 'restrict_admin_access');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_login_failed', $plugin_public, 'login_failed');
        $this->loader->add_action('wp_login', $plugin_public, 'log_successful_login', 10, 2);
        $this->loader->add_action('wp_ajax_sm_get_member', $plugin_public, 'ajax_get_member');
        $this->loader->add_action('wp_ajax_sm_search_members', $plugin_public, 'ajax_search_members');
        $this->loader->add_action('wp_ajax_sm_refresh_dashboard', $plugin_public, 'ajax_refresh_dashboard');
        $this->loader->add_action('wp_ajax_sm_update_member_photo', $plugin_public, 'ajax_update_member_photo');
        $this->loader->add_action('wp_ajax_sm_send_message_ajax', $plugin_public, 'ajax_send_message');
        $this->loader->add_action('wp_ajax_sm_get_conversation_ajax', $plugin_public, 'ajax_get_conversation');
        $this->loader->add_action('wp_ajax_sm_get_conversations_ajax', $plugin_public, 'ajax_get_conversations');
        $this->loader->add_action('wp_ajax_sm_mark_read', $plugin_public, 'ajax_mark_read');
        $this->loader->add_action('wp_ajax_sm_print', $plugin_public, 'handle_print');
        $this->loader->add_action('wp_ajax_sm_add_member_ajax', $plugin_public, 'ajax_add_member');
        $this->loader->add_action('wp_ajax_sm_update_member_ajax', $plugin_public, 'ajax_update_member');
        $this->loader->add_action('wp_ajax_sm_delete_member_ajax', $plugin_public, 'ajax_delete_member');
        $this->loader->add_action('wp_ajax_sm_get_counts_ajax', $plugin_public, 'ajax_get_counts');
        $this->loader->add_action('wp_ajax_sm_add_staff_ajax', $plugin_public, 'ajax_add_staff');
        $this->loader->add_action('wp_ajax_sm_update_staff_ajax', $plugin_public, 'ajax_update_staff');
        $this->loader->add_action('wp_ajax_sm_delete_staff_ajax', $plugin_public, 'ajax_delete_staff');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_users_ajax', $plugin_public, 'ajax_bulk_delete_users');
        $this->loader->add_action('wp_ajax_sm_add_survey', $plugin_public, 'ajax_add_survey');
        $this->loader->add_action('wp_ajax_sm_cancel_survey', $plugin_public, 'ajax_cancel_survey');
        $this->loader->add_action('wp_ajax_sm_submit_survey_response', $plugin_public, 'ajax_submit_survey_response');
        $this->loader->add_action('wp_ajax_sm_get_survey_results', $plugin_public, 'ajax_get_survey_results');
        $this->loader->add_action('wp_ajax_sm_export_survey_results', $plugin_public, 'ajax_export_survey_results');
        $this->loader->add_action('wp_ajax_sm_record_payment_ajax', $plugin_public, 'ajax_record_payment');
        $this->loader->add_action('wp_ajax_sm_delete_transaction_ajax', $plugin_public, 'ajax_delete_transaction');
        $this->loader->add_action('wp_ajax_sm_delete_gov_data_ajax', $plugin_public, 'ajax_delete_gov_data');
        $this->loader->add_action('wp_ajax_sm_merge_gov_data_ajax', $plugin_public, 'ajax_merge_gov_data');
        $this->loader->add_action('wp_ajax_sm_reset_system_ajax', $plugin_public, 'ajax_reset_system');
        $this->loader->add_action('wp_ajax_sm_rollback_log_ajax', $plugin_public, 'ajax_rollback_log');
        $this->loader->add_action('wp_ajax_sm_delete_log', $plugin_public, 'ajax_delete_log');
        $this->loader->add_action('wp_ajax_sm_clear_all_logs', $plugin_public, 'ajax_clear_all_logs');
        $this->loader->add_action('wp_ajax_sm_get_user_role', $plugin_public, 'ajax_get_user_role');
        $this->loader->add_action('wp_ajax_sm_update_member_account_ajax', $plugin_public, 'ajax_update_member_account');
        $this->loader->add_action('wp_ajax_sm_add_service', $plugin_public, 'ajax_add_service');
        $this->loader->add_action('wp_ajax_sm_update_service', $plugin_public, 'ajax_update_service');
        $this->loader->add_action('wp_ajax_sm_delete_service', $plugin_public, 'ajax_delete_service');
        $this->loader->add_action('wp_ajax_sm_submit_service_request', $plugin_public, 'ajax_submit_service_request');
        $this->loader->add_action('wp_ajax_sm_process_service_request', $plugin_public, 'ajax_process_service_request');
        $this->loader->add_action('wp_ajax_sm_get_member_finance_html', $plugin_public, 'ajax_get_member_finance_html');
        $this->loader->add_action('wp_ajax_sm_update_license_ajax', $plugin_public, 'ajax_update_license');
        $this->loader->add_action('wp_ajax_sm_print_license', $plugin_public, 'ajax_print_license');
        $this->loader->add_action('wp_ajax_sm_update_facility_ajax', $plugin_public, 'ajax_update_facility');
        $this->loader->add_action('wp_ajax_sm_print_facility', $plugin_public, 'ajax_print_facility');
        $this->loader->add_action('wp_ajax_sm_print_invoice', $plugin_public, 'ajax_print_invoice');
        $this->loader->add_action('wp_ajax_sm_print_service_request', $plugin_public, 'ajax_print_service_request');
        $this->loader->add_action('wp_ajax_sm_submit_update_request_ajax', $plugin_public, 'ajax_submit_update_request_ajax');
        $this->loader->add_action('wp_ajax_sm_process_update_request_ajax', $plugin_public, 'ajax_process_update_request_ajax');
        $this->loader->add_action('wp_ajax_nopriv_sm_submit_membership_request', $plugin_public, 'ajax_submit_membership_request');
        $this->loader->add_action('wp_ajax_sm_process_membership_request', $plugin_public, 'ajax_process_membership_request');
        $this->loader->add_action('wp_ajax_nopriv_sm_forgot_password_otp', $plugin_public, 'ajax_forgot_password_otp');
        $this->loader->add_action('wp_ajax_nopriv_sm_reset_password_otp', $plugin_public, 'ajax_reset_password_otp');
        $this->loader->add_action('wp_ajax_nopriv_sm_activate_account_step1', $plugin_public, 'ajax_activate_account_step1');
        $this->loader->add_action('wp_ajax_nopriv_sm_activate_account_final', $plugin_public, 'ajax_activate_account_final');
        $this->loader->add_action('wp_ajax_sm_get_template_ajax', $plugin_public, 'ajax_get_template_ajax');
        $this->loader->add_action('wp_ajax_sm_upload_document', $plugin_public, 'ajax_upload_document');
        $this->loader->add_action('wp_ajax_sm_get_documents', $plugin_public, 'ajax_get_documents');
        $this->loader->add_action('wp_ajax_sm_delete_document', $plugin_public, 'ajax_delete_document');
        $this->loader->add_action('wp_ajax_sm_get_document_logs', $plugin_public, 'ajax_get_document_logs');
        $this->loader->add_action('wp_ajax_sm_log_document_view', $plugin_public, 'ajax_log_document_view');
        $this->loader->add_action('wp_ajax_sm_get_pub_template', $plugin_public, 'ajax_get_pub_template');
        $this->loader->add_action('wp_ajax_sm_generate_pub_doc', $plugin_public, 'ajax_generate_pub_doc');
        $this->loader->add_action('wp_ajax_sm_print_pub_doc', $plugin_public, 'ajax_print_pub_doc');
        $this->loader->add_action('wp_ajax_sm_save_pub_identity', $plugin_public, 'ajax_save_pub_identity');
        $this->loader->add_action('wp_ajax_sm_save_pub_template', $plugin_public, 'ajax_save_pub_template');
        $this->loader->add_action('sm_daily_maintenance', 'SM_DB', 'delete_expired_messages');
        $this->loader->add_action('sm_daily_maintenance', 'SM_Notifications', 'run_daily_checks');
    }

    public function run() {
        add_action('plugins_loaded', array($this, 'check_version_updates'));
        $this->loader->add_action('init', $this, 'schedule_maintenance_cron');
        $this->loader->run();
    }

    public function schedule_maintenance_cron() {
        if (function_exists('wp_next_scheduled') && !wp_next_scheduled('sm_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'sm_daily_maintenance');
        }
    }

    public function check_version_updates() {
        $db_version = get_option('sm_plugin_version', '1.0.0');
        if (version_compare($db_version, SM_VERSION, '<')) {
            require_once SM_PLUGIN_DIR . 'includes/class-sm-activator.php';
            SM_Activator::activate();
            update_option('sm_plugin_version', SM_VERSION);
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
