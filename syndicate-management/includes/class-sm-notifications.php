<?php

class SM_Notifications {

    public static function get_template($type) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_notification_templates WHERE template_type = %s",
            $type
        ));
    }

    public static function save_template($data) {
        global $wpdb;
        return $wpdb->replace(
            "{$wpdb->prefix}sm_notification_templates",
            array(
                'template_type' => sanitize_text_field($data['template_type']),
                'subject' => sanitize_text_field($data['subject']),
                'body' => sanitize_textarea_field($data['body']),
                'days_before' => intval($data['days_before']),
                'is_enabled' => isset($data['is_enabled']) ? 1 : 0
            )
        );
    }

    public static function send_template_notification($member_id, $type, $extra_placeholders = []) {
        $template = self::get_template($type);
        if (!$template || !$template->is_enabled) return false;

        $member = SM_DB::get_member_by_id($member_id);
        if (!$member || empty($member->email)) return false;

        $subject = $template->subject;
        $body = $template->body;

        $placeholders = array_merge([
            '{member_name}' => $member->name,
            '{national_id}' => $member->national_id,
            '{membership_number}' => $member->membership_number,
            '{governorate}' => SM_Settings::get_governorates()[$member->governorate] ?? $member->governorate,
            '{year}' => date('Y'),
        ], $extra_placeholders);

        foreach ($placeholders as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
            $body = str_replace($search, $replace, $body);
        }

        $email_settings = get_option('sm_email_design_settings', [
            'header_bg' => '#111F35',
            'header_text' => '#ffffff',
            'footer_text' => '#64748b',
            'accent_color' => '#F63049'
        ]);

        $syndicate = SM_Settings::get_syndicate_info();

        $html_message = self::wrap_in_template($subject, $body, $email_settings, $syndicate);

        // Professional Sender info
        add_filter('wp_mail_from', function() { return 'no-reply@irseg.org'; });
        add_filter('wp_mail_from_name', function() use ($syndicate) { return $syndicate['syndicate_name']; });

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($member->email, $subject, $html_message, $headers);

        self::log_notification($member_id, $type, $member->email, $subject, $sent ? 'success' : 'failed');

        return $sent;
    }

    private static function wrap_in_template($subject, $body, $design, $syndicate) {
        $logo_html = !empty($syndicate['syndicate_logo']) ? '<img src="'.esc_url($syndicate['syndicate_logo']).'" style="max-height:80px; margin-bottom:15px; display:inline-block;">' : '';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <style>
                body { margin: 0; padding: 0; background-color: #f6f9fc; }
                .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 15px; overflow: hidden; border: 1px solid #e1e8ed; }
                .header { background-color: <?php echo $design['header_bg']; ?>; color: <?php echo $design['header_text']; ?>; padding: 40px 20px; text-align: center; }
                .content { padding: 40px; line-height: 1.7; color: #1a202c; font-size: 16px; text-align: right; }
                .footer { background-color: #f8fafc; padding: 25px; text-align: center; font-size: 12px; color: <?php echo $design['footer_text']; ?>; border-top: 1px solid #edf2f7; }
                .btn { display: inline-block; padding: 12px 30px; background-color: <?php echo $design['accent_color']; ?>; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <?php echo $logo_html; ?>
                    <h1 style="margin: 0; font-size: 22px; font-weight: 800;"><?php echo esc_html($syndicate['syndicate_name']); ?></h1>
                </div>
                <div class="content">
                    <h2 style="color: <?php echo $design['accent_color']; ?>; margin-top: 0;"><?php echo esc_html($subject); ?></h2>
                    <div style="white-space: pre-line;">
                        <?php echo esc_html($body); ?>
                    </div>
                </div>
                <div class="footer">
                    <p style="margin: 0 0 10px 0; font-weight: 700;"><?php echo esc_html($syndicate['syndicate_name']); ?></p>
                    <p style="margin: 5px 0;"><?php echo esc_html($syndicate['address']); ?></p>
                    <p style="margin: 5px 0;">هاتف: <?php echo esc_html($syndicate['phone']); ?> | بريد: <?php echo esc_html($syndicate['email']); ?></p>
                    <p style="margin: 15px 0 0 0; opacity: 0.8;">هذه رسالة تلقائية، يرجى عدم الرد عليها مباشرة.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private static function log_notification($member_id, $type, $email, $subject, $status) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}sm_notification_logs", [
            'member_id' => $member_id,
            'notification_type' => $type,
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => $status,
            'sent_at' => current_time('mysql')
        ]);
    }

    public static function run_daily_checks() {
        self::check_membership_renewals();
        self::check_license_expirations();
        self::check_payment_dues();
    }

    private static function check_membership_renewals() {
        $template = self::get_template('membership_renewal');
        if (!$template || !$template->is_enabled) return;

        global $wpdb;
        $current_year = date('Y');
        // Members who haven't paid for current year
        $members = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}sm_members WHERE last_paid_membership_year < %d",
            $current_year
        ));

        foreach ($members as $m) {
            // Avoid duplicate emails in the same month for same type?
            // Maybe just check if we logged one in the last 20 days.
            if (!self::already_notified($m->id, 'membership_renewal', 25)) {
                self::send_template_notification($m->id, 'membership_renewal', ['{year}' => $current_year]);
            }
        }
    }

    private static function check_license_expirations() {
        $types = ['license_practice', 'license_facility'];
        global $wpdb;

        foreach ($types as $type) {
            $template = self::get_template($type);
            if (!$template || !$template->is_enabled) continue;

            $days = $template->days_before;
            $target_date = date('Y-m-d', strtotime("+$days days"));

            $field = ($type === 'license_practice') ? 'license_expiration_date' : 'facility_license_expiration_date';

            $members = $wpdb->get_results($wpdb->prepare(
                "SELECT id, $field as expiry, facility_name FROM {$wpdb->prefix}sm_members WHERE $field = %s",
                $target_date
            ));

            foreach ($members as $m) {
                if (!self::already_notified($m->id, $type, 5)) {
                    self::send_template_notification($m->id, $type, [
                        '{expiry_date}' => $m->expiry,
                        '{facility_name}' => $m->facility_name ?? ''
                    ]);
                }
            }
        }
    }

    private static function check_payment_dues() {
        $template = self::get_template('payment_reminder');
        if (!$template || !$template->is_enabled) return;

        $members = SM_DB::get_members(['limit' => -1]);
        foreach ($members as $m) {
            $dues = SM_Finance::calculate_member_dues($m->id);
            if ($dues['balance'] > 500) { // Only remind if debt is significant
                if (!self::already_notified($m->id, 'payment_reminder', 30)) {
                    self::send_template_notification($m->id, 'payment_reminder', ['{balance}' => $dues['balance']]);
                }
            }
        }
    }

    private static function already_notified($member_id, $type, $days_limit) {
        global $wpdb;
        $last_sent = $wpdb->get_var($wpdb->prepare(
            "SELECT sent_at FROM {$wpdb->prefix}sm_notification_logs WHERE member_id = %d AND notification_type = %s ORDER BY sent_at DESC LIMIT 1",
            $member_id, $type
        ));
        if (!$last_sent) return false;
        return (strtotime($last_sent) > strtotime("-$days_limit days"));
    }

    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, m.name as member_name
             FROM {$wpdb->prefix}sm_notification_logs l
             LEFT JOIN {$wpdb->prefix}sm_members m ON l.member_id = m.id
             ORDER BY l.sent_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
    }
}
