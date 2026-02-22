<?php

class SM_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function hide_admin_bar_for_non_admins($show) {
        if (!current_user_can('administrator')) {
            return false;
        }
        return $show;
    }

    private function can_manage_user($target_user_id) {
        if (current_user_can('sm_full_access') || current_user_can('manage_options')) return true;

        $current_user = wp_get_current_user();
        $target_user = get_userdata($target_user_id);
        if (!$target_user) return false;

        // Syndicate Admins can only manage Syndicate Members
        if (in_array('sm_syndicate_admin', (array)$current_user->roles)) {
            // Cannot manage System Admins
            if (in_array('sm_system_admin', (array)$target_user->roles)) return false;
            // Cannot manage other Syndicate Admins
            if (in_array('sm_syndicate_admin', (array)$target_user->roles)) return false;

            // Must be in the same governorate
            $my_gov = get_user_meta($current_user->ID, 'sm_governorate', true);
            $target_gov = get_user_meta($target_user_id, 'sm_governorate', true);
            if ($my_gov && $target_gov && $my_gov !== $target_gov) return false;

            return true;
        }

        return false;
    }

    private function can_access_member($member_id) {
        if (current_user_can('sm_full_access') || current_user_can('manage_options')) return true;

        $member = SM_DB::get_member_by_id($member_id);
        if (!$member) return false;

        $user = wp_get_current_user();

        // Members can access their own record
        if (in_array('sm_syndicate_member', (array)$user->roles) && $member->wp_user_id == $user->ID) {
            return true;
        }

        // Syndicate Admins check governorate
        if (in_array('sm_syndicate_admin', (array)$user->roles)) {
            $my_gov = get_user_meta($user->ID, 'sm_governorate', true);
            if ($my_gov && $member->governorate !== $my_gov) {
                return false;
            }
            return true;
        }

        // Syndicate Members check governorate
        if (in_array('sm_syndicate_member', (array)$user->roles)) {
             $my_gov = get_user_meta($user->ID, 'sm_governorate', true);
             if ($my_gov && $member->governorate !== $my_gov) {
                 return false;
             }
             return true;
        }

        return false;
    }

    public function restrict_admin_access() {
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'sm_account_status', true);
            if ($status === 'restricted') {
                wp_logout();
                wp_redirect(home_url('/sm-login?login=failed'));
                exit;
            }
        }

        if (is_admin() && !defined('DOING_AJAX') && !current_user_can('manage_options')) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_style($this->plugin_name, SM_PLUGIN_URL . 'assets/css/sm-public.css', array('dashicons'), $this->version, 'all');

        $appearance = SM_Settings::get_appearance();
        $custom_css = "
            :root {
                --sm-primary-color: {$appearance['primary_color']};
                --sm-secondary-color: {$appearance['secondary_color']};
                --sm-accent-color: {$appearance['accent_color']};
                --sm-dark-color: {$appearance['dark_color']};
                --sm-radius: {$appearance['border_radius']};
            }
            .sm-content-wrapper, .sm-admin-dashboard, .sm-container,
            .sm-content-wrapper *:not(.dashicons), .sm-admin-dashboard *:not(.dashicons), .sm-container *:not(.dashicons) {
                font-family: 'Rubik', sans-serif !important;
            }
            .sm-admin-dashboard { font-size: {$appearance['font_size']}; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_filter('authenticate', array($this, 'custom_authenticate'), 20, 3);
        add_filter('auth_cookie_expiration', array($this, 'custom_auth_cookie_expiration'), 10, 3);
    }

    public function custom_auth_cookie_expiration($expiration, $user_id, $remember) {
        if ($remember) {
            return 30 * DAY_IN_SECONDS; // 30 days
        }
        return $expiration;
    }

    public function custom_authenticate($user, $username, $password) {
        if (empty($username) || empty($password)) return $user;

        // If already authenticated by standard means, return
        if ($user instanceof WP_User) return $user;

        // 1. Check for Syndicate Admin/Member ID Code (meta)
        $code_query = new WP_User_Query(array(
            'meta_query' => array(
                array('key' => 'sm_syndicateMemberIdAttr', 'value' => $username)
            ),
            'number' => 1
        ));
        $found = $code_query->get_results();
        if (!empty($found)) {
            $u = $found[0];
            if (wp_check_password($password, $u->user_pass, $u->ID)) return $u;
        }

        // 2. Check for National ID in sm_members table (if user_login is different)
        global $wpdb;
        $member_wp_id = $wpdb->get_var($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}sm_members WHERE national_id = %s", $username));
        if ($member_wp_id) {
            $u = get_userdata($member_wp_id);
            if ($u && wp_check_password($password, $u->user_pass, $u->ID)) return $u;
        }

        return $user;
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/sm-admin'));
            exit;
        }
        $syndicate = SM_Settings::get_syndicate_info();
        $output = '<div class="sm-login-container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 20px; background: #fff;">';
        $output .= '<div class="sm-login-box" style="width: 100%; max-width: 400px; background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f1f5f9;" dir="rtl">';

        $output .= '<div style="background: #ffffff; padding: 30px 20px; text-align: center; border-bottom: 1px solid #f1f5f9;">';
        $output .= '<h2 style="margin: 0; font-weight: 800; color: #111F35; font-size: 1.4em; letter-spacing: -0.5px;">'.esc_html($syndicate['syndicate_name']).'</h2>';
        $output .= '<p style="margin: 5px 0 0 0; color: #64748b; font-size: 0.85em;">بوابة تسجيل الدخول الذكية</p>';
        $output .= '</div>';

        $output .= '<div style="padding: 30px 30px;">';
        if (isset($_GET['login']) && $_GET['login'] == 'failed') {
            $output .= '<div style="background: #fff5f5; color: #c53030; padding: 10px; border-radius: 8px; border: 1px solid #feb2b2; margin-bottom: 20px; font-size: 0.85em; text-align: center; font-weight: 600;">⚠️ بيانات الدخول غير صحيحة</div>';
        }

        $output .= '<style>
            #sm_login_form p { margin-bottom: 15px; }
            #sm_login_form label { display: none; }
            #sm_login_form input[type="text"], #sm_login_form input[type="password"] {
                width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px;
                background: #fcfcfc; font-size: 14px; transition: 0.3s; font-family: "Rubik", sans-serif;
            }
            #sm_login_form input:focus { border-color: var(--sm-primary-color); outline: none; background: #fff; }
            #sm_login_form .login-remember { display: flex; align-items: center; gap: 8px; font-size: 0.8em; color: #64748b; margin-top: -5px; }
            #sm_login_form input[type="submit"] {
                width: 100%; padding: 14px; background: #111F35; color: #fff; border: none;
                border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s;
            }
            #sm_login_form input[type="submit"]:hover { background: var(--sm-primary-color); }
            .sm-login-footer-links { margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
            .sm-footer-btn { text-decoration: none; padding: 10px; border-radius: 8px; font-size: 12px; font-weight: 600; text-align: center; transition: 0.2s; border: 1px solid #e2e8f0; color: #4a5568; }
            .sm-footer-btn:hover { background: #f8fafc; }
            .sm-footer-btn-primary { background: #111F35; color: white !important; border: none; }
            .sm-footer-btn-primary:hover { background: var(--sm-primary-color); }
        </style>';

        $args = array(
            'echo' => false,
            'redirect' => home_url('/sm-admin'),
            'form_id' => 'sm_login_form',
            'label_remember' => 'تذكرني',
            'label_log_in' => 'دخول النظام',
            'remember' => true
        );
        $form = wp_login_form($args);

        // Inject placeholders
        $form = str_replace('name="log"', 'name="log" placeholder="الرقم القومي أو اسم المستخدم"', $form);
        $form = str_replace('name="pwd"', 'name="pwd" placeholder="كلمة المرور"', $form);

        $output .= $form;

        $output .= '<div class="sm-login-footer-links">';
        $output .= '<a href="javascript:void(0)" onclick="smToggleRegistration()" class="sm-footer-btn sm-footer-btn-primary">حساب جديد</a>';
        $output .= '<a href="javascript:void(0)" onclick="smToggleActivation()" class="sm-footer-btn">تفعيل حساب</a>';
        $output .= '<a href="javascript:void(0)" onclick="smToggleRecovery()" style="grid-column: span 2; color: #64748b; font-size: 12px; text-decoration: none; text-align: center; margin-top: 10px;">نسيت كلمة المرور؟</a>';
        $output .= '</div>';

        // Recovery Modal
        $output .= '<div id="sm-recovery-modal" class="sm-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; padding:20px;">';
        $output .= '<div class="sm-modal-content" style="background:white; width:100%; max-width:400px; padding:35px; border-radius:20px; position:relative;">';
        $output .= '<button onclick="smToggleRecovery()" style="position:absolute; top:20px; left:20px; border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>';
        $output .= '<h3 style="margin-top:0; margin-bottom:25px; text-align:center; font-weight:800;">استعادة كلمة المرور</h3>';
        $output .= '<div id="recovery-step-1">';
        $output .= '<p style="font-size:14px; color:#64748b; margin-bottom:20px; line-height:1.6;">أدخل الرقم القومي الخاص بك للتحقق وإرسال رمز الاستعادة.</p>';
        $output .= '<div class="sm-form-group" style="margin-bottom:20px;"><label class="sm-label">الرقم القومي:</label><input type="text" id="rec_national_id" class="sm-input" placeholder="14 رقم" maxlength="14" style="width:100%;"></div>';
        $output .= '<button onclick="smRequestOTP()" class="sm-btn" style="width:100%;">إرسال رمز التحقق</button>';
        $output .= '</div>';
        $output .= '<div id="recovery-step-2" style="display:none;">';
        $output .= '<p style="font-size:13px; color:#38a169; margin-bottom:15px;">تم إرسال الرمز بنجاح. يرجى التحقق من بريدك.</p>';
        $output .= '<input type="text" id="rec_otp" class="sm-input" placeholder="رمز التحقق (6 أرقام)" style="margin-bottom:10px; width:100%;">';
        $output .= '<input type="password" id="rec_new_pass" class="sm-input" placeholder="كلمة المرور الجديدة" style="margin-bottom:20px; width:100%;">';
        $output .= '<button onclick="smResetPassword()" class="sm-btn" style="width:100%;">تغيير كلمة المرور</button>';
        $output .= '</div>';
        $output .= '</div></div>';

        // Registration Modal (Membership Request)
        $output .= '<div id="sm-registration-modal" class="sm-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; padding:20px;">';
        $output .= '<div class="sm-modal-content" style="background:white; width:100%; max-width:600px; max-height: 90vh; overflow-y: auto; padding:40px; border-radius:24px; position:relative;">';
        $output .= '<button onclick="smToggleRegistration()" style="position:absolute; top:20px; left:20px; border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>';
        $output .= '<div style="text-align:center; margin-bottom:30px;"><h3 style="margin:0; font-weight:900; font-size:1.5em; color:var(--sm-dark-color);">طلب عضوية جديدة</h3><p style="color:#64748b; font-size:13px; margin-top:5px;">يرجى ملء كافة البيانات بدقة للمراجعة</p></div>';
        $output .= '<form id="sm-membership-request-form">';
        $output .= '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">';
        $output .= '<div class="sm-form-group" style="grid-column: span 2;"><label class="sm-label">الاسم الرباعي الكامل:</label><input name="name" type="text" class="sm-input" required placeholder="أدخل اسمك كما هو في البطاقة"></div>';
        $output .= '<div class="sm-form-group"><label class="sm-label">الرقم القومي (14 رقم):</label><input name="national_id" type="text" class="sm-input" required maxlength="14" placeholder="299..."></div>';
        $output .= '<div class="sm-form-group"><label class="sm-label">المحافظة:</label><select name="governorate" class="sm-select" required><option value="">-- اختر --</option>';
        foreach(SM_Settings::get_governorates() as $k=>$v) $output .= "<option value='$k'>$v</option>";
        $output .= '</select></div>';
        $output .= '<div class="sm-form-group"><label class="sm-label">الدرجة الوظيفية:</label><select name="professional_grade" class="sm-select">';
        foreach(SM_Settings::get_professional_grades() as $k=>$v) $output .= "<option value='$k'>$v</option>";
        $output .= '</select></div>';
        $output .= '<div class="sm-form-group"><label class="sm-label">التخصص:</label><select name="specialization" class="sm-select">';
        foreach(SM_Settings::get_specializations() as $k=>$v) $output .= "<option value='$k'>$v</option>";
        $output .= '</select></div>';
        $output .= '<div class="sm-form-group"><label class="sm-label">رقم الهاتف:</label><input name="phone" type="text" class="sm-input" required placeholder="01..."></div>';
        $output .= '<div class="sm-form-group"><label class="sm-label">البريد الإلكتروني:</label><input name="email" type="email" class="sm-input" required placeholder="example@mail.com"></div>';
        $output .= '<div class="sm-form-group" style="grid-column: span 2;"><label class="sm-label">ملاحظات إضافية:</label><textarea name="notes" class="sm-textarea" rows="2"></textarea></div>';
        $output .= '</div>';
        $output .= '<button type="submit" class="sm-btn" style="width:100%; margin-top:25px; padding:15px;">إرسال طلب العضوية</button>';
        $output .= '</form>';
        $output .= '</div></div>';

        // Activation Modal (3-Step Sequential Workflow)
        $output .= '<div id="sm-activation-modal" class="sm-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; padding:20px;">';
        $output .= '<div class="sm-modal-content" style="background:white; width:100%; max-width:450px; padding:40px; border-radius:24px; position:relative;">';
        $output .= '<button onclick="smToggleActivation()" style="position:absolute; top:20px; left:20px; border:none; background:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>';
        $output .= '<div style="text-align:center; margin-bottom:30px;"><h3 style="margin:0; font-weight:900;">تفعيل الحساب الرقمي</h3><p style="color:#64748b; font-size:13px; margin-top:5px;">خطوات بسيطة للوصول لخدماتك الإلكترونية</p></div>';

        // Step 1: Verification
        $output .= '<div id="activation-step-1">';
        $output .= '<div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;"><span style="width:30px; height:30px; background:var(--sm-primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">1</span><span style="width:30px; height:30px; background:#edf2f7; color:#718096; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">2</span><span style="width:30px; height:30px; background:#edf2f7; color:#718096; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">3</span></div>';
        $output .= '<p style="font-size:14px; color:#4a5568; margin-bottom:20px; text-align:center;">المرحلة الأولى: التحقق من الهوية بالسجلات</p>';
        $output .= '<div class="sm-form-group" style="margin-bottom:15px;"><input type="text" id="act_national_id" class="sm-input" placeholder="الرقم القومي (14 رقم)" style="width:100%;"></div>';
        $output .= '<div class="sm-form-group" style="margin-bottom:15px;"><input type="text" id="act_mem_no" class="sm-input" placeholder="رقم القيد النقابي" style="width:100%;"></div>';
        $output .= '<button onclick="smActivateStep1()" class="sm-btn" style="width:100%;">تحقق وانتقل للخطوة التالية</button>';
        $output .= '</div>';

        // Step 2: Contact Confirmation
        $output .= '<div id="activation-step-2" style="display:none;">';
        $output .= '<div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;"><span style="width:30px; height:30px; background:#38a169; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">✓</span><span style="width:30px; height:30px; background:var(--sm-primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">2</span><span style="width:30px; height:30px; background:#edf2f7; color:#718096; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">3</span></div>';
        $output .= '<p style="font-size:14px; color:#4a5568; margin-bottom:20px; text-align:center;">المرحلة الثانية: تأكيد بيانات التواصل</p>';
        $output .= '<div class="sm-form-group" style="margin-bottom:15px;"><input type="email" id="act_email" class="sm-input" placeholder="البريد الإلكتروني المعتمد" style="width:100%;"></div>';
        $output .= '<div class="sm-form-group" style="margin-bottom:15px;"><input type="text" id="act_phone" class="sm-input" placeholder="رقم الهاتف الحالي" style="width:100%;"></div>';
        $output .= '<button onclick="smActivateStep2()" class="sm-btn" style="width:100%;">تأكيد البيانات</button>';
        $output .= '</div>';

        // Step 3: Account Completion
        $output .= '<div id="activation-step-3" style="display:none;">';
        $output .= '<div style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;"><span style="width:30px; height:30px; background:#38a169; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">✓</span><span style="width:30px; height:30px; background:#38a169; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">✓</span><span style="width:30px; height:30px; background:var(--sm-primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold;">3</span></div>';
        $output .= '<p style="font-size:14px; color:#4a5568; margin-bottom:20px; text-align:center;">المرحلة الثالثة: تعيين كلمة المرور</p>';
        $output .= '<div class="sm-form-group" style="margin-bottom:20px;"><input type="password" id="act_pass" class="sm-input" placeholder="كلمة المرور (10 خانات على الأقل)" style="width:100%;"></div>';
        $output .= '<button onclick="smActivateFinal()" class="sm-btn" style="width:100%;">إكمال التنشيط والدخول</button>';
        $output .= '</div>';
        $output .= '</div></div>';

        $output .= '<script>
        function smToggleRecovery() {
            const m = document.getElementById("sm-recovery-modal");
            m.style.display = m.style.display === "none" ? "flex" : "none";
        }
        function smToggleActivation() {
            const m = document.getElementById("sm-activation-modal");
            m.style.display = m.style.display === "none" ? "flex" : "none";
            document.getElementById("activation-step-1").style.display = "block";
            document.getElementById("activation-step-2").style.display = "none";
        }
        function smToggleRegistration() {
            const m = document.getElementById("sm-registration-modal");
            m.style.display = m.style.display === "none" ? "flex" : "none";
        }
        function smRequestOTP() {
            const nid = document.getElementById("rec_national_id").value;
            const fd = new FormData(); fd.append("action", "sm_forgot_password_otp"); fd.append("national_id", nid);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) {
                    document.getElementById("recovery-step-1").style.display="none";
                    document.getElementById("recovery-step-2").style.display="block";
                } else alert(res.data);
            });
        }
        function smActivateStep2() {
            const email = document.getElementById("act_email").value;
            const phone = document.getElementById("act_phone").value;
            if(!/^\S+@\S+\.\S+$/.test(email)) return alert("يرجى إدخال بريد إلكتروني صحيح");
            if(phone.length < 10) return alert("يرجى إدخال رقم هاتف صحيح");
            document.getElementById("activation-step-2").style.display="none";
            document.getElementById("activation-step-3").style.display="block";
        }
        function smResetPassword() {
            const nid = document.getElementById("rec_national_id").value;
            const otp = document.getElementById("rec_otp").value;
            const pass = document.getElementById("rec_new_pass").value;
            const fd = new FormData(); fd.append("action", "sm_reset_password_otp");
            fd.append("national_id", nid); fd.append("otp", otp); fd.append("new_password", pass);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) { alert(res.data); location.reload(); } else alert(res.data);
            });
        }
        function smActivateStep1() {
            const nid = document.getElementById("act_national_id").value;
            const mem = document.getElementById("act_mem_no").value;
            if(!/^[0-9]{14}$/.test(nid)) return alert("يرجى إدخال رقم قومي صحيح (14 رقم)");
            const fd = new FormData(); fd.append("action", "sm_activate_account_step1");
            fd.append("national_id", nid); fd.append("membership_number", mem);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) {
                    document.getElementById("activation-step-1").style.display="none";
                    document.getElementById("activation-step-2").style.display="block";
                } else alert(res.data);
            });
        }
        function smActivateFinal() {
            const nid = document.getElementById("act_national_id").value;
            const mem = document.getElementById("act_mem_no").value;
            const email = document.getElementById("act_email").value;
            const phone = document.getElementById("act_phone").value;
            const pass = document.getElementById("act_pass").value;
            if(!/^\S+@\S+\.\S+$/.test(email)) return alert("يرجى إدخال بريد إلكتروني صحيح");
            if(pass.length < 10) return alert("كلمة المرور يجب أن تكون 10 أحرف على الأقل");
            const fd = new FormData(); fd.append("action", "sm_activate_account_final");
            fd.append("national_id", nid); fd.append("membership_number", mem);
            fd.append("email", email); fd.append("phone", phone); fd.append("password", pass);
            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) { alert(res.data); location.reload(); } else alert(res.data);
            });
        }

        document.getElementById("sm-membership-request-form")?.addEventListener("submit", function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fd.append("action", "sm_submit_membership_request");
            fd.append("nonce", "'.wp_create_nonce("sm_registration_nonce").'");

            const nid = fd.get("national_id");
            if(!/^[0-9]{14}$/.test(nid)) return alert("يرجى إدخال رقم قومي صحيح (14 رقم)");

            fetch("'.admin_url('admin-ajax.php').'", {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) {
                    alert("تم إرسال طلبك بنجاح. سيتم مراجعته من قبل الإدارة وسيتم تفعيل حسابك فور الموافقة.");
                    smToggleRegistration();
                } else alert(res.data);
            });
        });
        </script>';

        $output .= '</div>'; // End padding
        $output .= '</div>'; // End box
        $output .= '</div>'; // End container
        return $output;
    }

    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';

        $is_admin = in_array('administrator', $roles) || current_user_can('sm_manage_system');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_syndicate_admin = in_array('sm_syndicate_admin', $roles);
        $is_syndicate_member = in_array('sm_syndicate_member', $roles);

        // Fetch data
        $stats = SM_DB::get_statistics();

        ob_start();
        include SM_PLUGIN_DIR . 'templates/public-admin-panel.php';
        return ob_get_clean();
    }

    public function login_failed($username) {
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function log_successful_login($user_login, $user) {
        SM_Logger::log('تسجيل دخول', "المستخدم: $user_login");
    }

    public function ajax_get_member() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $member = SM_DB::get_member_by_national_id($national_id);
        if ($member) {
            if (!$this->can_access_member($member->id)) wp_send_json_error('Access denied');
            wp_send_json_success($member);
        } else {
            wp_send_json_error('Member not found');
        }
    }

    public function ajax_search_members() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        $query = sanitize_text_field($_POST['query']);
        $members = SM_DB::get_members(array('search' => $query));
        wp_send_json_success($members);
    }

    public function ajax_refresh_dashboard() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(array('stats' => SM_DB::get_statistics()));
    }

    public function ajax_update_member_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_photo_action', 'sm_photo_nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('member_photo', 0);
        if (is_wp_error($attachment_id)) wp_send_json_error($attachment_id->get_error_message());

        $photo_url = wp_get_attachment_url($attachment_id);
        $member_id = intval($_POST['member_id']);
        SM_DB::update_member_photo($member_id, $photo_url);
        wp_send_json_success(array('photo_url' => $photo_url));
    }

    public function ajax_add_staff() {
        if (!current_user_can('sm_manage_users') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_syndicateMemberAction')) wp_send_json_error('Security check failed');

        $username = sanitize_user($_POST['user_login']);
        $email = sanitize_email($_POST['user_email']);
        $display_name = sanitize_text_field($_POST['display_name']);
        $role = sanitize_text_field($_POST['role']);

        if (empty($username)) wp_send_json_error('اسم المستخدم مطلوب');
        if (empty($email)) wp_send_json_error('البريد الإلكتروني مطلوب');
        if (empty($display_name)) wp_send_json_error('الاسم الكامل مطلوب');
        if (empty($role)) wp_send_json_error('الدور مطلوب');

        if (username_exists($username)) wp_send_json_error('اسم المستخدم موجود مسبقاً');
        if (email_exists($email)) wp_send_json_error('البريد الإلكتروني مسجل لمستخدم آخر');

        if (!empty($_POST['user_pass'])) {
            $pass = $_POST['user_pass'];
        } else {
            $digits = '';
            for ($i = 0; $i < 10; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $pass = 'IRS' . $digits;
        }

        // Prevent role escalation
        if ($role === 'sm_system_admin' && !current_user_can('sm_full_access') && !current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions to assign this role');
        }

        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => $display_name,
            'user_pass' => $pass,
            'role' => $role
        ));

        if (is_wp_error($user_id)) wp_send_json_error($user_id->get_error_message());

        update_user_meta($user_id, 'sm_temp_pass', $pass);
        update_user_meta($user_id, 'sm_syndicateMemberIdAttr', sanitize_text_field($_POST['officer_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'sm_account_status', 'active');

        $gov = sanitize_text_field($_POST['governorate'] ?? '');
        if (in_array('sm_syndicate_admin', (array)wp_get_current_user()->roles)) {
            $gov = get_user_meta(get_current_user_id(), 'sm_governorate', true);
        }
        update_user_meta($user_id, 'sm_governorate', $gov);

        // If role is member, ensure entry in sm_members table for sync
        if ($role === 'sm_syndicate_member') {
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_members WHERE national_id = %s OR wp_user_id = %d", $_POST['officer_id'], $user_id));
            if (!$exists) {
                SM_DB::add_member([
                    'national_id' => sanitize_text_field($_POST['officer_id']),
                    'name' => sanitize_text_field($_POST['display_name']),
                    'email' => $email,
                    'phone' => sanitize_text_field($_POST['phone']),
                    'governorate' => $gov,
                    'wp_user_id' => $user_id
                ]);
            }
        }

        SM_Logger::log('إضافة مستخدم', "الاسم: {$_POST['display_name']} الرتبة: $role");
        wp_send_json_success($user_id);
    }

    public function ajax_delete_staff() {
        if (!current_user_can('sm_manage_users') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_syndicateMemberAction')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['user_id']);
        if ($user_id === get_current_user_id()) wp_send_json_error('Cannot delete yourself');
        if (!$this->can_manage_user($user_id)) wp_send_json_error('Access denied');

        wp_delete_user($user_id);
        wp_send_json_success('Deleted');
    }

    public function ajax_update_staff() {
        if (!current_user_can('sm_manage_users') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['sm_nonce'], 'sm_syndicateMemberAction')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_officer_id']);
        if (!$this->can_manage_user($user_id)) wp_send_json_error('Access denied');

        $role = sanitize_text_field($_POST['role']);

        // Prevent role escalation
        if ($role === 'sm_system_admin' && !current_user_can('sm_full_access') && !current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions to assign this role');
        }

        $user_data = array('ID' => $user_id, 'display_name' => sanitize_text_field($_POST['display_name']), 'user_email' => sanitize_email($_POST['user_email']));
        if (!empty($_POST['user_pass'])) {
            $user_data['user_pass'] = $_POST['user_pass'];
            update_user_meta($user_id, 'sm_temp_pass', $_POST['user_pass']);
        }
        wp_update_user($user_data);

        $u = new WP_User($user_id);
        $u->set_role($role);

        update_user_meta($user_id, 'sm_syndicateMemberIdAttr', sanitize_text_field($_POST['officer_id']));
        update_user_meta($user_id, 'sm_phone', sanitize_text_field($_POST['phone']));

        $gov = sanitize_text_field($_POST['governorate'] ?? '');
        if (!in_array('sm_syndicate_admin', (array)wp_get_current_user()->roles)) {
            if (isset($_POST['governorate'])) {
                update_user_meta($user_id, 'sm_governorate', $gov);
            }
        } else {
            $gov = get_user_meta($user_id, 'sm_governorate', true);
        }

        update_user_meta($user_id, 'sm_account_status', sanitize_text_field($_POST['account_status']));

        // Sync to sm_members if it's a member
        if ($role === 'sm_syndicate_member') {
            global $wpdb;
            $wpdb->update("{$wpdb->prefix}sm_members", [
                'name' => sanitize_text_field($_POST['display_name']),
                'email' => sanitize_email($_POST['user_email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'governorate' => $gov
            ], ['wp_user_id' => $user_id]);
        }

        SM_Logger::log('تحديث مستخدم', "الاسم: {$_POST['display_name']}");
        wp_send_json_success('Updated');
    }

    public function ajax_add_member() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_add_member', 'sm_nonce');
        $res = SM_DB::add_member($_POST);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
        else wp_send_json_success($res);
    }

    public function ajax_update_member() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_add_member', 'sm_nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        SM_DB::update_member($member_id, $_POST);
        wp_send_json_success('Updated');
    }

    public function ajax_delete_member() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_delete_member', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        SM_DB::delete_member($member_id);
        wp_send_json_success('Deleted');
    }

    public function ajax_update_license() {
        if (!current_user_can('sm_manage_licenses')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_add_member', 'nonce');
        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');
        SM_DB::update_member($member_id, [
            'license_number' => sanitize_text_field($_POST['license_number']),
            'license_issue_date' => sanitize_text_field($_POST['license_issue_date']),
            'license_expiration_date' => sanitize_text_field($_POST['license_expiration_date'])
        ]);

        // Archive License in Vault
        SM_DB::add_document([
            'member_id' => $member_id,
            'category' => 'licenses',
            'title' => "تصريح مزاولة مهنة رقم " . $_POST['license_number'],
            'file_url' => admin_url('admin-ajax.php?action=sm_print_license&member_id=' . $member_id),
            'file_type' => 'application/pdf'
        ]);

        SM_Logger::log('تحديث ترخيص مزاولة', "العضو ID: $member_id");
        wp_send_json_success();
    }

    public function ajax_update_facility() {
        if (!current_user_can('sm_manage_licenses')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_add_member', 'nonce');
        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');
        SM_DB::update_member($member_id, [
            'facility_name' => sanitize_text_field($_POST['facility_name']),
            'facility_number' => sanitize_text_field($_POST['facility_number']),
            'facility_category' => sanitize_text_field($_POST['facility_category']),
            'facility_license_issue_date' => sanitize_text_field($_POST['facility_license_issue_date']),
            'facility_license_expiration_date' => sanitize_text_field($_POST['facility_license_expiration_date']),
            'facility_address' => sanitize_textarea_field($_POST['facility_address'])
        ]);

        // Archive Facility License in Vault
        SM_DB::add_document([
            'member_id' => $member_id,
            'category' => 'licenses',
            'title' => "ترخيص منشأة: " . $_POST['facility_name'],
            'file_url' => admin_url('admin-ajax.php?action=sm_print_facility&member_id=' . $member_id),
            'file_type' => 'application/pdf'
        ]);

        SM_Logger::log('تحديث منشأة', "العضو ID: $member_id");
        wp_send_json_success();
    }

    public function ajax_record_payment() {
        if (!current_user_can('sm_manage_finance')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_finance_action', 'nonce');
        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');
        if (SM_Finance::record_payment($_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to record payment');
    }

    public function ajax_delete_transaction() {
        if (!current_user_can('sm_full_access') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        global $wpdb;
        $id = intval($_POST['transaction_id']);
        $wpdb->delete("{$wpdb->prefix}sm_payments", ['id' => $id]);
        SM_Logger::log('حذف عملية مالية', "تم حذف العملية رقم #$id بواسطة مدير النظام");
        wp_send_json_success();
    }

    public function ajax_delete_gov_data() {
        if (!current_user_can('manage_options') && !current_user_can('sm_full_access')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        global $wpdb;
        $gov = sanitize_text_field($_POST['governorate']);
        if (!$gov) wp_send_json_error('محافظة غير محددة');

        // 1. Get member IDs for this gov
        $member_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_members WHERE governorate = %s", $gov));
        if (empty($member_ids)) wp_send_json_success('لا توجد بيانات لهذه المحافظة');

        // 2. Delete WP Users
        $wp_user_ids = $wpdb->get_col($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}sm_members WHERE governorate = %s AND wp_user_id IS NOT NULL", $gov));
        if (!empty($wp_user_ids)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            foreach ($wp_user_ids as $uid) wp_delete_user($uid);
        }

        // 3. Delete payments
        $ids_str = implode(',', array_map('intval', $member_ids));
        $wpdb->query("DELETE FROM {$wpdb->prefix}sm_payments WHERE member_id IN ($ids_str)");

        // 4. Delete members
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sm_members WHERE governorate = %s", $gov));

        SM_Logger::log('حذف بيانات محافظة', "تم مسح كافة بيانات محافظة: $gov");
        wp_send_json_success();
    }

    public function ajax_merge_gov_data() {
        if (!current_user_can('manage_options') && !current_user_can('sm_full_access')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        $gov = sanitize_text_field($_POST['governorate']);
        if (empty($_FILES['backup_file']['tmp_name'])) wp_send_json_error('الملف غير موجود');

        $json = file_get_contents($_FILES['backup_file']['tmp_name']);
        $data = json_decode($json, true);
        if (!$data || !isset($data['members'])) wp_send_json_error('تنسيق الملف غير صحيح');

        $success = 0; $skipped = 0;
        foreach ($data['members'] as $row) {
            // Only merge members belonging to the TARGET governorate if specified in the row,
            // OR force them to the target governorate.
            // Requirement says "data for a single governorate only"
            if ($row['governorate'] !== $gov) {
                $skipped++;
                continue;
            }

            if (SM_DB::member_exists($row['national_id'])) {
                $skipped++;
                continue;
            }

            // Clean data for insertion
            unset($row['id']);

            // Re-create WP User if needed
            $digits = ''; for ($i = 0; $i < 10; $i++) $digits .= mt_rand(0, 9);
            $temp_pass = 'IRS' . $digits;
            $wp_user_id = wp_insert_user([
                'user_login' => $row['national_id'],
                'user_email' => $row['email'] ?: $row['national_id'] . '@irseg.org',
                'display_name' => $row['name'],
                'user_pass' => $temp_pass,
                'role' => 'sm_syndicate_member'
            ]);

            if (!is_wp_error($wp_user_id)) {
                $row['wp_user_id'] = $wp_user_id;
                update_user_meta($wp_user_id, 'sm_temp_pass', $temp_pass);
                update_user_meta($wp_user_id, 'sm_governorate', $gov);
            }

            global $wpdb;
            if ($wpdb->insert("{$wpdb->prefix}sm_members", $row)) $success++;
            else $skipped++;
        }

        SM_Logger::log('دمج بيانات محافظة', "تم دمج $success عضواً لمحافظة $gov (تخطى $skipped)");
        wp_send_json_success("تم بنجاح دمج $success عضواً وتجاهل $skipped عضواً مسجلين مسبقاً.");
    }

    public function ajax_reset_system() {
        if (!current_user_can('manage_options') && !current_user_can('sm_full_access')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        $password = $_POST['admin_password'] ?? '';
        $current_user = wp_get_current_user();
        if (!wp_check_password($password, $current_user->user_pass, $current_user->ID)) {
            wp_send_json_error('كلمة المرور غير صحيحة. يرجى إدخال كلمة مرور مدير النظام للمتابعة.');
        }

        global $wpdb;
        $tables = [
            'sm_members', 'sm_payments', 'sm_logs', 'sm_messages',
            'sm_surveys', 'sm_survey_responses', 'sm_update_requests'
        ];

        // 1. Delete WordPress Users associated with members
        $member_wp_ids = $wpdb->get_col("SELECT wp_user_id FROM {$wpdb->prefix}sm_members WHERE wp_user_id IS NOT NULL");
        if (!empty($member_wp_ids)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            foreach ($member_wp_ids as $uid) {
                wp_delete_user($uid);
            }
        }

        // 2. Truncate Tables
        foreach ($tables as $t) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}$t");
        }

        // 3. Reset sequences
        delete_option('sm_invoice_sequence_' . date('Y'));

        SM_Logger::log('إعادة تهيئة النظام', "تم مسح كافة البيانات وتصفير النظام بالكامل");
        wp_send_json_success();
    }

    public function ajax_rollback_log() {
        if (!current_user_can('manage_options') && !current_user_can('sm_full_access')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        $log_id = intval($_POST['log_id']);
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_logs WHERE id = %d", $log_id));

        if (!$log || strpos($log->details, 'ROLLBACK_DATA:') !== 0) {
            wp_send_json_error('لا توجد بيانات استعادة لهذه العملية');
        }

        $json = str_replace('ROLLBACK_DATA:', '', $log->details);
        $rollback_info = json_decode($json, true);

        if (!$rollback_info || !isset($rollback_info['table'])) {
            wp_send_json_error('تنسيق بيانات الاستعادة غير صحيح');
        }

        $table = $rollback_info['table'];
        $data = $rollback_info['data'];

        if ($table === 'members') {
            // Re-insert into sm_members
            $wp_user_id = $data['wp_user_id'] ?? null;

            // Check if user login already exists
            if (!empty($data['national_id']) && username_exists($data['national_id'])) {
                wp_send_json_error('لا يمكن الاستعادة: اسم المستخدم (الرقم القومي) موجود بالفعل');
            }

            // Re-create WP User if it was deleted
            if ($wp_user_id && !get_userdata($wp_user_id)) {
                $digits = ''; for ($i = 0; $i < 10; $i++) $digits .= mt_rand(0, 9);
                $temp_pass = 'IRS' . $digits;
                $wp_user_id = wp_insert_user([
                    'user_login' => $data['national_id'],
                    'user_email' => $data['email'] ?: $data['national_id'] . '@irseg.org',
                    'display_name' => $data['name'],
                    'user_pass' => $temp_pass,
                    'role' => 'sm_syndicate_member'
                ]);
                if (is_wp_error($wp_user_id)) wp_send_json_error($wp_user_id->get_error_message());
                update_user_meta($wp_user_id, 'sm_temp_pass', $temp_pass);
                if (!empty($data['governorate'])) {
                    update_user_meta($wp_user_id, 'sm_governorate', $data['governorate']);
                }
            }

            unset($data['id']);
            $data['wp_user_id'] = $wp_user_id;

            $res = $wpdb->insert("{$wpdb->prefix}sm_members", $data);
            if ($res) {
                SM_Logger::log('استعادة بيانات', "تم استعادة العضو: " . $data['name']);
                wp_send_json_success();
            } else {
                wp_send_json_error('فشل في إدراج البيانات في قاعدة البيانات: ' . $wpdb->last_error);
            }
        }

        wp_send_json_error('نوع الاستعادة غير مدعوم حالياً');
    }

    public function ajax_add_survey() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        $id = SM_DB::add_survey($_POST['title'], $_POST['questions'], $_POST['recipients'], get_current_user_id());
        wp_send_json_success($id);
    }

    public function ajax_cancel_survey() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_surveys", ['status' => 'cancelled'], ['id' => intval($_POST['id'])]);
        wp_send_json_success();
    }

    public function ajax_submit_survey_response() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_survey_action', 'nonce');
        SM_DB::save_survey_response(intval($_POST['survey_id']), get_current_user_id(), json_decode(stripslashes($_POST['responses']), true));
        wp_send_json_success();
    }

    public function ajax_get_survey_results() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(SM_DB::get_survey_results(intval($_GET['id'])));
    }

    public function ajax_delete_log() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}sm_logs", ['id' => intval($_POST['log_id'])]);
        wp_send_json_success();
    }

    public function ajax_clear_all_logs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}sm_logs");
        wp_send_json_success();
    }

    public function ajax_get_user_role() {
        if (!current_user_can('sm_manage_users') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $user_id = intval($_GET['user_id']);
        $user = get_userdata($user_id);
        if ($user) {
            $role = !empty($user->roles) ? $user->roles[0] : '';
            wp_send_json_success(['role' => $role]);
        }
        wp_send_json_error('User not found');
    }

    public function ajax_update_member_account() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'sm_nonce');

        $member_id = intval($_POST['member_id']);
        $wp_user_id = intval($_POST['wp_user_id']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        // Update email in WP User and SM Members table
        $user_data = ['ID' => $wp_user_id, 'user_email' => $email];
        if (!empty($password)) {
            $user_data['user_pass'] = $password;
        }

        $res = wp_update_user($user_data);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

        // Handle role change (only for full admins)
        if (!empty($role) && (current_user_can('sm_full_access') || current_user_can('manage_options'))) {
            $user = new WP_User($wp_user_id);
            $user->set_role($role);
        }

        // Sync email to members table
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_members", ['email' => $email], ['id' => $member_id]);

        SM_Logger::log('تحديث حساب عضو', "تم تحديث بيانات الحساب للعضو ID: $member_id");
        wp_send_json_success();
    }

    public function ajax_add_service() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        $res = SM_DB::add_service($_POST);
        if ($res) wp_send_json_success();
        else wp_send_json_error('Failed to add service');
    }

    public function ajax_update_service() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        $id = intval($_POST['id']);
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'fees' => floatval($_POST['fees']),
            'status' => sanitize_text_field($_POST['status']),
            'selected_profile_fields' => $_POST['selected_profile_fields'] ?? ''
        ];
        if (SM_DB::update_service($id, $data)) wp_send_json_success();
        else wp_send_json_error('Failed to update service');
    }

    public function ajax_delete_service() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');
        if (SM_DB::delete_service(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete service');
    }

    public function ajax_submit_service_request() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_service_action', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        $res = SM_DB::submit_service_request($_POST);
        if ($res) {
            SM_Logger::log('طلب خدمة رقمية', "العضو ID: $member_id طلب خدمة ID: {$_POST['service_id']}");
            wp_send_json_success();
        } else wp_send_json_error('Failed to submit request');
    }

    public function ajax_process_service_request() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_service_requests WHERE id = %d", $id));
        if (!$req) wp_send_json_error('Request not found');

        $res = SM_DB::update_service_request_status($id, $status);
        if ($res) {
             if ($status === 'approved') {
                 // Record in finance if fees > 0
                 $service = $wpdb->get_row($wpdb->prepare("SELECT fees, name FROM {$wpdb->prefix}sm_services WHERE id = %d", $req->service_id));
                 if ($service && $service->fees > 0) {
                      SM_Finance::record_payment([
                          'member_id' => $req->member_id,
                          'amount' => $service->fees,
                          'payment_type' => 'other',
                          'payment_date' => current_time('Y-m-d'),
                          'details_ar' => 'رسوم خدمة: ' . $service->name,
                          'notes' => 'طلب رقم #' . $id
                      ]);
                 }

                 // Archive Issued Document in Vault
                 SM_DB::add_document([
                     'member_id' => $req->member_id,
                     'category' => 'certificates',
                     'title' => $service->name . " - طلب رقم #" . $id,
                     'file_url' => admin_url('admin-ajax.php?action=sm_print_service_request&id=' . $id),
                     'file_type' => 'application/pdf'
                 ]);
             }
             wp_send_json_success();
        } else wp_send_json_error('Failed to process request');
    }

    public function ajax_export_survey_results() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $id = intval($_GET['id']);
        $results = SM_DB::get_survey_results($id);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="survey-'.$id.'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Question', 'Answer', 'Count']);
        foreach ($results as $r) {
            foreach ($r['answers'] as $ans => $count) {
                fputcsv($out, [$r['question'], $ans, $count]);
            }
        }
        fclose($out);
        exit;
    }

    public function handle_form_submission() {
        if (isset($_POST['sm_import_members_csv'])) {
            $this->handle_member_csv_import();
        }
        if (isset($_POST['sm_import_staffs_csv'])) {
            $this->handle_staff_csv_import();
        }
        if (isset($_POST['sm_save_appearance'])) {
            check_admin_referer('sm_admin_action', 'sm_admin_nonce');
            $data = SM_Settings::get_appearance();
            foreach ($data as $k => $v) {
                if (isset($_POST[$k])) $data[$k] = sanitize_text_field($_POST[$k]);
            }
            SM_Settings::save_appearance($data);
            wp_redirect(add_query_arg('sm_tab', 'global-settings', wp_get_referer()));
            exit;
        }
        if (isset($_POST['sm_save_labels'])) {
            check_admin_referer('sm_admin_action', 'sm_admin_nonce');
            $labels = SM_Settings::get_labels();
            foreach ($labels as $k => $v) {
                if (isset($_POST[$k])) $labels[$k] = sanitize_text_field($_POST[$k]);
            }
            SM_Settings::save_labels($labels);
            wp_redirect(add_query_arg('sm_tab', 'global-settings', wp_get_referer()));
            exit;
        }
    }

    private function handle_member_csv_import() {
        if (!current_user_can('sm_manage_members')) return;
        check_admin_referer('sm_admin_action', 'sm_admin_nonce');

        if (empty($_FILES['member_csv_file']['tmp_name'])) return;

        $handle = fopen($_FILES['member_csv_file']['tmp_name'], 'r');
        if (!$handle) return;

        $results = ['total' => 0, 'success' => 0, 'warning' => 0, 'error' => 0];

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $results['total']++;
            if (count($data) < 2) { $results['error']++; continue; }

            $member_data = [
                'national_id' => sanitize_text_field($data[0]),
                'name' => sanitize_text_field($data[1]),
                'professional_grade' => sanitize_text_field($data[2] ?? ''),
                'specialization' => sanitize_text_field($data[3] ?? ''),
                'governorate' => sanitize_text_field($data[4] ?? ''),
                'phone' => sanitize_text_field($data[5] ?? ''),
                'email' => sanitize_email($data[6] ?? '')
            ];

            $res = SM_DB::add_member($member_data);
            if (is_wp_error($res)) {
                $results['error']++;
            } else {
                $results['success']++;
            }
        }
        fclose($handle);

        set_transient('sm_import_results_' . get_current_user_id(), $results, 3600);
        wp_redirect(add_query_arg('sm_tab', 'members', wp_get_referer()));
        exit;
    }

    private function handle_staff_csv_import() {
        if (!current_user_can('sm_manage_users')) return;
        check_admin_referer('sm_admin_action', 'sm_admin_nonce');

        if (empty($_FILES['csv_file']['tmp_name'])) return;

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) return;

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 4) continue;

            $username = sanitize_user($data[0]);
            $email = sanitize_email($data[1]);
            $name = sanitize_text_field($data[2]);
            $officer_id = sanitize_text_field($data[3]);
            $role_label = sanitize_text_field($data[4] ?? 'عضو نقابة');
            $phone = sanitize_text_field($data[5] ?? '');
            if (!empty($data[6])) {
                $pass = $data[6];
            } else {
                $digits = '';
                for ($i = 0; $i < 10; $i++) {
                    $digits .= mt_rand(0, 9);
                }
                $pass = 'IRS' . $digits;
            }

            $role = 'sm_syndicate_member';
            if (strpos($role_label, 'مدير') !== false) $role = 'sm_system_admin';
            elseif (strpos($role_label, 'مسؤول') !== false) $role = 'sm_syndicate_admin';

            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_email' => $email ?: $username . '@irseg.org',
                'display_name' => $name,
                'user_pass' => $pass,
                'role' => $role
            ]);

            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'sm_temp_pass', $pass);
                update_user_meta($user_id, 'sm_syndicateMemberIdAttr', $officer_id);
                update_user_meta($user_id, 'sm_phone', $phone);
            }
        }
        fclose($handle);

        wp_redirect(add_query_arg('sm_tab', 'staff', wp_get_referer()));
        exit;
    }

    public function ajax_get_counts() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $stats = SM_DB::get_statistics();
        wp_send_json_success([
            'pending_reports' => SM_DB::get_pending_reports_count()
        ]);
    }

    public function ajax_bulk_delete_users() {
        if (!current_user_can('sm_manage_users')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'sm_syndicateMemberAction')) wp_send_json_error('Security check failed');

        $ids = explode(',', $_POST['user_ids']);
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id === get_current_user_id()) continue;
            if (!$this->can_manage_user($id)) continue;
            wp_delete_user($id);
        }
        wp_send_json_success();
    }

    public function ajax_send_message() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_message_action', 'nonce');

        $sender_id = get_current_user_id();
        $member_id = intval($_POST['member_id'] ?? 0);

        if (!$member_id) {
            // Try to find member_id from current user if they are a member
            global $wpdb;
            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", $sender_id));
            if ($member_by_wp) $member_id = $member_by_wp->id;
        }

        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        $member = SM_DB::get_member_by_id($member_id);
        if (!$member) wp_send_json_error('Invalid member context');

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $governorate = $member->governorate;

        $file_url = null;
        if (!empty($_FILES['message_file']['name'])) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['message_file']['type'], $allowed_types)) {
                wp_send_json_error('نوع الملف غير مسموح به. يسمح فقط بملفات PDF والصور.');
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('message_file', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        SM_DB::send_message($sender_id, $receiver_id, $message, $member_id, $file_url, $governorate);
        wp_send_json_success();
    }

    public function ajax_get_conversation() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_message_action', 'nonce');

        $member_id = intval($_POST['member_id'] ?? 0);
        if (!$member_id) {
            $sender_id = get_current_user_id();
            global $wpdb;
            $member_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_members WHERE wp_user_id = %d", $sender_id));
            if ($member_by_wp) $member_id = $member_by_wp->id;
        }

        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        wp_send_json_success(SM_DB::get_ticket_messages($member_id));
    }

    public function ajax_get_conversations() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_message_action', 'nonce');

        $user = wp_get_current_user();
        $gov = get_user_meta($user->ID, 'sm_governorate', true);
        $has_full_access = current_user_can('sm_full_access') || current_user_can('manage_options');

        if (!$gov && !$has_full_access) wp_send_json_error('No governorate assigned');

        if (in_array('sm_syndicate_member', (array)$user->roles)) {
             // Members see officials of their governorate
             $officials = SM_DB::get_governorate_officials($gov);
             $data = [];
             foreach($officials as $o) {
                 $data[] = [
                     'official' => [
                         'ID' => $o->ID,
                         'display_name' => $o->display_name,
                         'avatar' => get_avatar_url($o->ID)
                     ]
                 ];
             }
             wp_send_json_success(['type' => 'member_view', 'officials' => $data]);
        } else {
             // Officials see members' tickets
             // If System Admin/WP Admin, pass null to see all governorates
             $target_gov = $has_full_access ? null : $gov;
             $conversations = SM_DB::get_governorate_conversations($target_gov);
             foreach($conversations as &$c) {
                 $c['member']->avatar = $c['member']->photo_url ?: get_avatar_url($c['member']->wp_user_id ?: 0);
             }
             wp_send_json_success(['type' => 'official_view', 'conversations' => $conversations]);
        }
    }

    public function ajax_mark_read() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_message_action', 'nonce');
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}sm_messages", ['is_read' => 1], ['receiver_id' => get_current_user_id(), 'sender_id' => intval($_POST['other_user_id'])]);
        wp_send_json_success();
    }

    public function ajax_get_member_finance_html() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $member_id = intval($_GET['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        $dues = SM_Finance::calculate_member_dues($member_id);
        $history = SM_Finance::get_payment_history($member_id);
        ob_start();
        include SM_PLUGIN_DIR . 'templates/modal-finance-details.php';
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    public function ajax_print_license() {
        if (!current_user_can('sm_print_reports')) wp_die('Unauthorized');
        $member_id = intval($_GET['member_id'] ?? 0);
        if (!$this->can_access_member($member_id)) wp_die('Access denied');
        include SM_PLUGIN_DIR . 'templates/print-practice-license.php';
        exit;
    }

    public function ajax_print_facility() {
        if (!current_user_can('sm_print_reports')) wp_die('Unauthorized');
        $member_id = intval($_GET['member_id'] ?? 0);
        if (!$this->can_access_member($member_id)) wp_die('Access denied');
        include SM_PLUGIN_DIR . 'templates/print-facility-license.php';
        exit;
    }

    public function ajax_print_invoice() {
        if (!current_user_can('sm_manage_finance')) {
            // Check if member is viewing their own invoice
            $payment_id = intval($_GET['payment_id'] ?? 0);
            global $wpdb;
            $pmt = $wpdb->get_row($wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}sm_payments WHERE id = %d", $payment_id));
            if (!$pmt || !$this->can_access_member($pmt->member_id)) wp_die('Unauthorized');
        }
        include SM_PLUGIN_DIR . 'templates/print-invoice.php';
        exit;
    }

    public function ajax_print_service_request() {
        $id = intval($_GET['id']);
        global $wpdb;
        $req = $wpdb->get_row($wpdb->prepare("SELECT member_id, status FROM {$wpdb->prefix}sm_service_requests WHERE id = %d", $id));
        if (!$req) wp_die('Request not found');

        if (!$this->can_access_member($req->member_id)) wp_die('Unauthorized');
        if ($req->status !== 'approved' && !current_user_can('sm_manage_members')) wp_die('Access denied');

        include SM_PLUGIN_DIR . 'templates/print-service-request.php';
        exit;
    }

    public function handle_print() {
        if (!current_user_can('sm_print_reports')) wp_die('Unauthorized');

        $type = sanitize_text_field($_GET['print_type'] ?? '');
        $member_id = intval($_GET['member_id'] ?? 0);

        if ($member_id && !$this->can_access_member($member_id)) wp_die('Access denied');

        switch($type) {
            case 'id_card':
                include SM_PLUGIN_DIR . 'templates/print-id-cards.php';
                break;
            case 'credentials':
                include SM_PLUGIN_DIR . 'templates/print-member-credentials.php';
                break;
            default:
                wp_die('Invalid print type');
        }
        exit;
    }

    public function ajax_submit_update_request_ajax() {
        if (!is_user_logged_in()) wp_send_json_error('يجب تسجيل الدخول');
        check_ajax_referer('sm_update_request', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('لا تملك صلاحية تعديل هذا العضو');

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'national_id' => sanitize_text_field($_POST['national_id']),
            'professional_grade' => sanitize_text_field($_POST['professional_grade']),
            'specialization' => sanitize_text_field($_POST['specialization']),
            'governorate' => sanitize_text_field($_POST['governorate']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $res = SM_DB::add_update_request($member_id, $data);
        if ($res) {
            wp_send_json_success();
        } else {
            wp_send_json_error('فشل في إرسال الطلب');
        }
    }

    public function ajax_process_update_request_ajax() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_update_request', 'nonce');

        $request_id = intval($_POST['request_id']);
        $status = sanitize_text_field($_POST['status']); // 'approved' or 'rejected'

        if (SM_DB::process_update_request($request_id, $status)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('فشل في معالجة الطلب');
        }
    }

    public function ajax_submit_membership_request() {
        check_ajax_referer('sm_registration_nonce', 'nonce');

        global $wpdb;
        $nid = sanitize_text_field($_POST['national_id']);

        // Check if already exists in members or requests
        if (SM_DB::member_exists($nid)) {
            wp_send_json_error('عذراً، هذا الرقم القومي مسجل مسبقاً في النظام.');
        }

        $exists_request = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sm_membership_requests WHERE national_id = %s", $nid));
        if ($exists_request) {
            wp_send_json_error('عذراً، يوجد طلب عضوية قيد المراجعة بهذا الرقم القومي.');
        }

        $res = $wpdb->insert("{$wpdb->prefix}sm_membership_requests", [
            'national_id' => $nid,
            'name' => sanitize_text_field($_POST['name']),
            'governorate' => sanitize_text_field($_POST['governorate']),
            'professional_grade' => sanitize_text_field($_POST['professional_grade']),
            'specialization' => sanitize_text_field($_POST['specialization']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);

        if ($res) wp_send_json_success();
        else wp_send_json_error('فشل في إرسال الطلب، يرجى المحاولة لاحقاً.');
    }

    public function ajax_process_membership_request() {
        if (!current_user_can('sm_manage_members')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_admin_action', 'nonce');

        $request_id = intval($_POST['request_id']);
        $status = sanitize_text_field($_POST['status']); // 'approved' or 'rejected'

        global $wpdb;
        $req = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_membership_requests WHERE id = %d", $request_id));
        if (!$req) wp_send_json_error('Request not found');

        if ($status === 'approved') {
            // Move to main members table
            $member_data = (array)$req;
            unset($member_data['id'], $member_data['status'], $member_data['processed_by'], $member_data['created_at']);

            $member_id = SM_DB::add_member($member_data);
            if (is_wp_error($member_id)) wp_send_json_error($member_id->get_error_message());
        }

        $wpdb->update("{$wpdb->prefix}sm_membership_requests", [
            'status' => $status,
            'processed_by' => get_current_user_id()
        ], ['id' => $request_id]);

        SM_Logger::log('معالجة طلب عضوية', "تم {$status} طلب العضوية للرقم القومي: {$req->national_id}");
        wp_send_json_success();
    }

    public function ajax_forgot_password_otp() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $member = SM_DB::get_member_by_national_id($national_id);
        if (!$member || !$member->wp_user_id) {
            wp_send_json_error('الرقم القومي غير مسجل في النظام');
        }

        $user = get_userdata($member->wp_user_id);
        $otp = sprintf("%06d", mt_rand(1, 999999));

        update_user_meta($user->ID, 'sm_recovery_otp', $otp);
        update_user_meta($user->ID, 'sm_recovery_otp_time', time());
        update_user_meta($user->ID, 'sm_recovery_otp_used', 0);

        $syndicate = SM_Settings::get_syndicate_info();
        $subject = "رمز استعادة كلمة المرور - " . $syndicate['syndicate_name'];
        $message = "عزيزي العضو " . $member->name . ",\n\n";
        $message .= "رمز التحقق الخاص بك هو: " . $otp . "\n";
        $message .= "هذا الرمز صالح لمدة 10 دقائق فقط ولمرة واحدة.\n\n";
        $message .= "إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.\n";

        wp_mail($member->email, $subject, $message);

        wp_send_json_success('تم إرسال رمز التحقق إلى بريدك الإلكتروني المسجل');
    }

    public function ajax_reset_password_otp() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';

        $member = SM_DB::get_member_by_national_id($national_id);
        if (!$member || !$member->wp_user_id) wp_send_json_error('بيانات غير صحيحة');

        $user_id = $member->wp_user_id;
        $saved_otp = get_user_meta($user_id, 'sm_recovery_otp', true);
        $otp_time = get_user_meta($user_id, 'sm_recovery_otp_time', true);
        $otp_used = get_user_meta($user_id, 'sm_recovery_otp_used', true);

        if ($otp_used || $saved_otp !== $otp || (time() - $otp_time) > 600) {
            update_user_meta($user_id, 'sm_recovery_otp_used', 1); // Mark as attempt made
            wp_send_json_error('رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        if (strlen($new_pass) < 10 || !preg_match('/^[a-zA-Z0-9]+$/', $new_pass)) {
            wp_send_json_error('كلمة المرور يجب أن تكون 10 أحرف على الأقل وتتكون من حروف وأرقام فقط بدون رموز');
        }

        wp_set_password($new_pass, $user_id);
        update_user_meta($user_id, 'sm_recovery_otp_used', 1);

        wp_send_json_success('تمت إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول');
    }

    public function ajax_activate_account_step1() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $membership_number = sanitize_text_field($_POST['membership_number'] ?? '');

        $member = SM_DB::get_member_by_national_id($national_id);
        if (!$member) wp_send_json_error('الرقم القومي غير موجود في السجلات.');

        if ($member->membership_number !== $membership_number) {
            wp_send_json_error('بيانات التحقق غير صحيحة، يرجى مراجعة رقم العضوية.');
        }

        wp_send_json_success('تم التحقق بنجاح. يرجى إكمال بيانات الحساب');
    }

    public function ajax_get_template_ajax() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        $type = sanitize_text_field($_POST['type']);
        $template = SM_Notifications::get_template($type);
        if ($template) wp_send_json_success($template);
        else wp_send_json_error('Template not found');
    }

    public function ajax_activate_account_final() {
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $membership_number = sanitize_text_field($_POST['membership_number'] ?? '');
        $new_email = sanitize_email($_POST['email'] ?? '');
        $new_phone = sanitize_text_field($_POST['phone'] ?? '');
        $new_pass = $_POST['password'] ?? '';

        $member = SM_DB::get_member_by_national_id($national_id);
        if (!$member || $member->membership_number !== $membership_number) {
            wp_send_json_error('فشل التحقق من الهوية');
        }

        if (strlen($new_pass) < 10 || !preg_match('/^[a-zA-Z0-9]+$/', $new_pass)) {
            wp_send_json_error('كلمة المرور يجب أن تكون 10 أحرف على الأقل وتتكون من حروف وأرقام فقط');
        }

        if (!is_email($new_email)) wp_send_json_error('بريد إلكتروني غير صحيح');

        // Update member record
        SM_DB::update_member($member->id, ['email' => $new_email, 'phone' => $new_phone]);

        // Update WP User
        if ($member->wp_user_id) {
            wp_update_user([
                'ID' => $member->wp_user_id,
                'user_email' => $new_email,
                'user_pass' => $new_pass
            ]);
            update_user_meta($member->wp_user_id, 'sm_phone', $new_phone);
            delete_user_meta($member->wp_user_id, 'sm_temp_pass');
        }

        wp_send_json_success('تم تفعيل الحساب بنجاح. يمكنك الآن تسجيل الدخول');

        // Send Welcome Notification
        SM_Notifications::send_template_notification($member->id, 'welcome_activation');
    }

    public function ajax_upload_document() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_document_action', 'nonce');

        $member_id = intval($_POST['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        if (empty($_FILES['document_file']['name'])) wp_send_json_error('No file uploaded');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('document_file', 0);
        if (is_wp_error($attachment_id)) wp_send_json_error($attachment_id->get_error_message());

        $file_url = wp_get_attachment_url($attachment_id);
        $file_type = get_post_mime_type($attachment_id);

        $doc_id = SM_DB::add_document([
            'member_id' => $member_id,
            'category' => sanitize_text_field($_POST['category']),
            'title' => sanitize_text_field($_POST['title']),
            'file_url' => $file_url,
            'file_type' => $file_type
        ]);

        if ($doc_id) {
            wp_send_json_success(['doc_id' => $doc_id]);
        } else {
            global $wpdb;
            wp_send_json_error('Failed to save document info: ' . $wpdb->last_error);
        }
    }

    public function ajax_get_documents() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $member_id = intval($_GET['member_id']);
        if (!$this->can_access_member($member_id)) wp_send_json_error('Access denied');

        $args = [
            'category' => $_GET['category'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        wp_send_json_success(SM_DB::get_member_documents($member_id, $args));
    }

    public function ajax_delete_document() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_document_action', 'nonce');

        $doc_id = intval($_POST['doc_id']);
        global $wpdb;
        $doc = $wpdb->get_row($wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}sm_documents WHERE id = %d", $doc_id));
        if (!$doc || !$this->can_access_member($doc->member_id)) wp_send_json_error('Access denied');

        if (SM_DB::delete_document($doc_id)) wp_send_json_success();
        else wp_send_json_error('Delete failed');
    }

    public function ajax_get_document_logs() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $doc_id = intval($_GET['doc_id']);

        global $wpdb;
        $doc = $wpdb->get_row($wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}sm_documents WHERE id = %d", $doc_id));
        if (!$doc || !$this->can_access_member($doc->member_id)) wp_send_json_error('Access denied');

        wp_send_json_success(SM_DB::get_document_logs($doc_id));
    }

    public function ajax_log_document_view() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $doc_id = intval($_POST['doc_id']);

        global $wpdb;
        $doc = $wpdb->get_row($wpdb->prepare("SELECT member_id FROM {$wpdb->prefix}sm_documents WHERE id = %d", $doc_id));
        if (!$doc || !$this->can_access_member($doc->member_id)) wp_send_json_error('Access denied');

        SM_DB::log_document_action($doc_id, 'view');
        wp_send_json_success();
    }

    // Publishing Center
    public function ajax_get_pub_template() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        $id = intval($_GET['id']);
        global $wpdb;
        $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_templates WHERE id = %d", $id));
        if ($template) wp_send_json_success($template);
        else wp_send_json_error('Template not found');
    }

    public function ajax_save_pub_template() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_pub_action', 'nonce');
        $id = SM_DB::save_pub_template($_POST);
        if ($id) wp_send_json_success($id);
        else wp_send_json_error('Failed to save template');
    }

    public function ajax_generate_pub_doc() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_pub_action', 'nonce');

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'content' => wp_kses_post($_POST['content']),
            'doc_type' => 'document',
            'format' => sanitize_text_field($_POST['format']),
            'options' => [
                'header' => !empty($_POST['header']),
                'footer' => !empty($_POST['footer']),
                'qr' => !empty($_POST['qr']),
                'frame' => !empty($_POST['frame'])
            ]
        ];

        $doc_id = SM_DB::generate_pub_document($data);
        if ($doc_id) {
            wp_send_json_success(['url' => admin_url('admin-ajax.php?action=sm_print_pub_doc&id=' . $doc_id . '&format=' . $data['format'])]);
        } else {
            wp_send_json_error('Failed to generate document');
        }
    }

    public function ajax_print_pub_doc() {
        $id = intval($_GET['id']);
        $format = $_GET['format'] ?? 'pdf';

        global $wpdb;
        $doc = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_pub_documents WHERE id = %d", $id));
        if (!$doc) wp_die('Document not found');

        // Increment download count
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sm_pub_documents SET download_count = download_count + 1 WHERE id = %d", $id));

        if ($format === 'image') {
            // Simplified image output for demo (would normally use a renderer)
            header('Content-Type: text/html; charset=UTF-8');
            echo "<html><body style='margin:0; padding:40px; background:#f0f0f0; display:flex; justify-content:center;'>";
            echo "<div id='doc-capture' style='background:white; width:800px; min-height:1000px; padding:60px; box-shadow:0 0 20px rgba(0,0,0,0.1); font-family:Arial;'>";
            echo $doc->content;
            echo "</div></body></html>";
            exit;
        }

        // PDF Output
        include SM_PLUGIN_DIR . 'templates/print-pub-document.php';
        exit;
    }

    public function ajax_save_pub_identity() {
        if (!current_user_can('sm_manage_system')) wp_send_json_error('Unauthorized');
        check_ajax_referer('sm_pub_action', 'nonce');

        update_option('sm_pub_stamp_url', esc_url_raw($_POST['stamp']));
        update_option('sm_pub_footer_statement', sanitize_textarea_field($_POST['footer']));
        update_option('sm_pub_color_primary', sanitize_hex_color($_POST['p_color']));
        update_option('sm_pub_color_secondary', sanitize_hex_color($_POST['s_color']));

        $syndicate = SM_Settings::get_syndicate_info();
        $syndicate['syndicate_logo'] = esc_url_raw($_POST['logo']);
        SM_Settings::save_syndicate_info($syndicate);

        wp_send_json_success();
    }
}
