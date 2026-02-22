<?php

class SM_Settings {

    public static function get_appearance() {
        $default = array(
            'primary_color' => '#F63049',
            'secondary_color' => '#D02752',
            'accent_color' => '#8A244B',
            'dark_color' => '#111F35',
            'bg_color' => '#ffffff',
            'sidebar_bg_color' => '#f8fafc',
            'font_color' => '#111F35',
            'border_color' => '#e2e8f0',
            'btn_color' => '#111F35',
            'font_size' => '15px',
            'font_weight' => '400',
            'line_spacing' => '1.5',
            'border_radius' => '12px',
            'table_style' => 'modern',
            'button_style' => 'flat'
        );
        return wp_parse_args(get_option('sm_appearance', array()), $default);
    }

    public static function get_labels() {
        $default = array(
            'tab_summary' => 'لوحة المعلومات',
            'tab_members' => 'إدارة الأعضاء',
            'tab_finance' => 'الاستحقاقات المالية',
            'tab_financial_logs' => 'سجل العمليات المالية',
            'tab_practice_licenses' => 'تصاريح مزاولة المهنة',
            'tab_facility_licenses' => 'تراخيص المنشآت',
            'tab_staffs' => 'إدارة مستخدمي النظام',
            'tab_printing' => 'مركز الطباعة والنشر الرقمي',
            'tab_surveys' => 'استطلاعات الرأي',
            'tab_global_settings' => 'إعدادات النظام',
            'tab_update_requests' => 'طلبات التحديث',
            'tab_my_profile' => 'ملفي الشخصي'
        );
        return wp_parse_args(get_option('sm_labels', array()), $default);
    }

    public static function save_labels($labels) {
        update_option('sm_labels', $labels);
    }

    public static function save_appearance($data) {
        update_option('sm_appearance', $data);
    }

    public static function get_notifications() {
        $default = array(
            'email_subject' => 'إشعار من النقابة بخصوص العضو: {member_name}',
            'email_template' => "تحية طيبة، نود إخطاركم بخصوص العضو: {member_name}\nالتفاصيل: {details}",
            'whatsapp_template' => "تنبيه من النقابة بخصوص العضو {member_name}. تفاصيل: {details}.",
            'internal_template' => "إشعار نظام بخصوص العضو {member_name}."
        );
        return get_option('sm_notification_settings', $default);
    }

    public static function save_notifications($data) {
        update_option('sm_notification_settings', $data);
    }

    public static function get_syndicate_info() {
        $default = array(
            'syndicate_name' => 'نقابتي النموذجية',
            'syndicate_officer_name' => 'أحمد علي',
            'syndicate_logo' => '',
            'address' => 'الرياض، المملكة العربية السعودية',
            'email' => 'info@syndicate.edu',
            'phone' => '0123456789',
            'map_link' => '',
            'extra_details' => ''
        );
        return get_option('sm_syndicate_info', $default);
    }

    public static function save_syndicate_info($data) {
        update_option('sm_syndicate_info', $data);
    }

    public static function format_grade_name($grade, $section = '', $format = 'full') {
        if (empty($grade)) return '---';
        $grade_num = str_replace('الدرجة المهنية ', '', $grade);
        if ($format === 'short') {
            return trim($grade_num . ' ' . $section);
        }
        $output = 'الدرجة المهنية ' . $grade_num;
        if (!empty($section)) {
            $output .= ' شعبة ' . $section;
        }
        return $output;
    }

    public static function get_retention_settings() {
        $default = array(
            'message_retention_days' => 90
        );
        return get_option('sm_retention_settings', $default);
    }

    public static function save_retention_settings($data) {
        update_option('sm_retention_settings', $data);
    }

    public static function record_backup_download() {
        update_option('sm_last_backup_download', current_time('mysql'));
    }

    public static function record_backup_import() {
        update_option('sm_last_backup_import', current_time('mysql'));
    }

    public static function get_last_backup_info() {
        return array(
            'export' => get_option('sm_last_backup_download', 'لم يتم التصدير مسبقاً'),
            'import' => get_option('sm_last_backup_import', 'لم يتم الاستيراد مسبقاً')
        );
    }


    public static function get_professional_grades() {
        $default = array(
            'assistant_specialist' => 'أخصائي مساعد',
            'specialist' => 'أخصائي',
            'consultant' => 'استشاري',
            'expert' => 'خبير'
        );
        return get_option('sm_professional_grades', $default);
    }

    public static function save_professional_grades($grades) {
        update_option('sm_professional_grades', $grades);
    }

    public static function get_specializations() {
        $default = array(
            'injuries' => 'إصابات وتأهيل',
            'massage' => 'تدليك رياضي',
            'nutrition' => 'تغذية رياضية',
            'special_needs' => 'تأهيل ذوي الاحتياجات الخاصة'
        );
        return get_option('sm_specializations', $default);
    }

    public static function save_specializations($specs) {
        update_option('sm_specializations', $specs);
    }

    public static function get_academic_degrees() {
        return array(
            'bachelor' => 'بكالوريوس',
            'master' => 'ماجستير',
            'doctorate' => 'دكتوراه'
        );
    }

    public static function get_membership_statuses() {
        return array(
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'pending' => 'قيد الانتظار',
            'expired' => 'منتهي'
        );
    }

    public static function get_governorates() {
        return array(
            'cairo' => 'القاهرة',
            'giza' => 'الجيزة',
            'alexandria' => 'الإسكندرية',
            'monufia' => 'المنوفية',
            'dakahlia' => 'الدقهلية',
            'sharqia' => 'الشرقية',
            'beheira' => 'البحيرة',
            'qalyubia' => 'القليوبية',
            'gharbia' => 'الغربية',
            'fayoum' => 'الفيوم',
            'minya' => 'المنيا',
            'asyut' => 'أسيوط',
            'sohag' => 'سوهاج',
            'qena' => 'قنا',
            'luxor' => 'الأقصر',
            'aswan' => 'أسوان',
            'damietta' => 'دمياط',
            'port_said' => 'بورسعيد',
            'ismailia' => 'الإسماعيلية',
            'suez' => 'السويس',
            'kafr_el_sheikh' => 'كفر الشيخ',
            'matrouh' => 'مطروح',
            'red_sea' => 'البحر الأحمر',
            'new_valley' => 'الوادي الجديد',
            'north_sinai' => 'شمال سيناء',
            'south_sinai' => 'جنوب سيناء',
            'beni_suef' => 'بني سويف'
        );
    }

    public static function get_governorate_prefix($gov_key) {
        $prefixes = array(
            'cairo' => 'CAI',
            'giza' => 'GIZ',
            'alexandria' => 'ALX',
            'monufia' => 'MNF',
            'dakahlia' => 'DKH',
            'sharqia' => 'SHR',
            'beheira' => 'BEH',
            'qalyubia' => 'QAL',
            'gharbia' => 'GHA',
            'fayoum' => 'FAY',
            'minya' => 'MIN',
            'asyut' => 'ASY',
            'sohag' => 'SOH',
            'qena' => 'QEN',
            'luxor' => 'LXR',
            'aswan' => 'ASW',
            'damietta' => 'DAM',
            'port_said' => 'PSD',
            'ismailia' => 'ISM',
            'suez' => 'SUE',
            'kafr_el_sheikh' => 'KFS',
            'matrouh' => 'MAT',
            'red_sea' => 'RED',
            'new_valley' => 'NVAL',
            'north_sinai' => 'NSIN',
            'south_sinai' => 'SSIN',
            'beni_suef' => 'BSF'
        );
        return $prefixes[$gov_key] ?? 'GEN';
    }

    public static function get_finance_settings() {
        $default = array(
            'membership_new' => 480,
            'membership_renewal' => 280,
            'membership_penalty' => 50,
            'license_new' => 2500,
            'license_renewal' => 1000,
            'license_penalty' => 500,
            'facility_a' => 9000,
            'facility_b' => 6000,
            'facility_c' => 3000
        );
        return get_option('sm_finance_settings', $default);
    }

    public static function save_finance_settings($settings) {
        update_option('sm_finance_settings', $settings);
    }
}
