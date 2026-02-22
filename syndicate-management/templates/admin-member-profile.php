<?php if (!defined('ABSPATH')) exit;

$member_id = intval($_GET['member_id'] ?? 0);
$member = SM_DB::get_member_by_id($member_id);

if (!$member) {
    echo '<div class="error"><p>العضو غير موجود.</p></div>';
    return;
}

$user = wp_get_current_user();
$is_sys_manager = in_array('sm_system_admin', (array)$user->roles);
$is_syndicate_admin = in_array('sm_syndicate_admin', (array)$user->roles);
$is_syndicate_staff = in_array('sm_syndicate_member', (array)$user->roles);

// IDOR CHECK: Restricted users can only see their own profile
if ($is_syndicate_staff && !current_user_can('sm_manage_members')) {
    if ($member->wp_user_id != $user->ID) {
        echo '<div class="error" style="padding:20px; background:#fff5f5; color:#c53030; border-radius:8px; border:1px solid #feb2b2;"><h4>⚠️ عذراً، لا تملك صلاحية الوصول لهذا الملف.</h4><p>لا يمكنك استعراض بيانات الأعضاء الآخرين.</p></div>';
        return;
    }
}

// GEOGRAPHIC ACCESS CHECK
if ($is_syndicate_admin) {
    $my_gov = get_user_meta($user->ID, 'sm_governorate', true);
    if ($my_gov && $member->governorate !== $my_gov) {
        echo '<div class="error" style="padding:20px; background:#fff5f5; color:#c53030; border-radius:8px; border:1px solid #feb2b2;"><h4>⚠️ عذراً، لا تملك صلاحية الوصول لهذا الملف.</h4><p>هذا العضو يتبع لمحافظة أخرى غير المسجلة في حسابك.</p></div>';
        return;
    }
}

$grades = SM_Settings::get_professional_grades();
$specs = SM_Settings::get_specializations();
$govs = SM_Settings::get_governorates();
$statuses = SM_Settings::get_membership_statuses();
$finance = SM_Finance::calculate_member_dues($member->id);
$acc_status = SM_Finance::get_member_status($member->id);
?>

<div class="sm-member-profile-view" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="position: relative;">
                <div id="member-photo-container" style="width: 80px; height: 80px; background: #f0f4f8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; border: 3px solid var(--sm-primary-color); overflow: hidden;">
                    <?php if ($member->photo_url): ?>
                        <img src="<?php echo esc_url($member->photo_url); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        👤
                    <?php endif; ?>
                </div>
                <button onclick="smTriggerPhotoUpload()" style="position: absolute; bottom: 0; right: 0; background: var(--sm-primary-color); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <span class="dashicons dashicons-camera" style="font-size: 14px; width: 14px; height: 14px;"></span>
                </button>
                <input type="file" id="member-photo-input" style="display:none;" accept="image/*" onchange="smUploadMemberPhoto(<?php echo $member->id; ?>)">
            </div>
            <div>
                <h2 style="margin:0; color: var(--sm-dark-color);"><?php echo esc_html($member->name); ?></h2>
                <div style="display: flex; gap: 10px; margin-top: 5px;">
                    <span class="sm-badge sm-badge-low"><?php echo $grades[$member->professional_grade] ?? $member->professional_grade; ?></span>
                    <span class="sm-badge" style="background: #e2e8f0; color: #4a5568;"><?php echo $govs[$member->governorate] ?? $member->governorate; ?></span>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if ($is_member || $is_syndicate_member): ?>
                <button onclick="smOpenUpdateMemberRequestModal()" class="sm-btn" style="background: #3182ce; width: auto;"><span class="dashicons dashicons-edit"></span> طلب تحديث بياناتي</button>
            <?php elseif (!$is_member): ?>
                <button onclick="editSmMember(JSON.parse(this.dataset.member))" data-member='<?php echo esc_attr(wp_json_encode($member)); ?>' class="sm-btn" style="background: #3182ce; width: auto;"><span class="dashicons dashicons-edit"></span> تعديل البيانات</button>
            <?php endif; ?>

            <div class="sm-dropdown" style="position:relative; display:inline-block;">
                <button class="sm-btn" style="background: #111F35; width: auto;" onclick="smToggleFinanceDropdown()"><span class="dashicons dashicons-money-alt"></span> المعاملات المالية <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px;"></span></button>
                <div id="sm-finance-dropdown" style="display:none; position:absolute; left:0; top:100%; background:white; border:1px solid #eee; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.1); z-index:100; min-width:200px; padding:10px 0;">
                    <?php if (current_user_can('sm_manage_finance')): ?>
                        <a href="javascript:smOpenFinanceModal(<?php echo $member->id; ?>)" class="sm-dropdown-item"><span class="dashicons dashicons-plus"></span> تأكيد سداد دفعة</a>
                    <?php endif; ?>
                    <a href="<?php echo add_query_arg('sm_tab', 'financial-logs'); ?>&member_search=<?php echo urlencode($member->national_id); ?>" class="sm-dropdown-item"><span class="dashicons dashicons-media-spreadsheet"></span> سجل الفواتير والعمليات</a>
                </div>
            </div>

            <?php if (!$is_syndicate_staff || current_user_can('sm_print_reports')): ?>
                <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card&member_id='.$member->id); ?>" target="_blank" class="sm-btn" style="background: #27ae60; width: auto; text-decoration:none; display:flex; align-items:center; gap:8px;"><span class="dashicons dashicons-id-alt"></span> طباعة الكارنيه</a>
            <?php endif; ?>
            <?php if ($is_sys_manager): ?>
                <button onclick="deleteMember(<?php echo $member->id; ?>, '<?php echo esc_js($member->name); ?>')" class="sm-btn" style="background: #e53e3e; width: auto;"><span class="dashicons dashicons-trash"></span> حذف العضو</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('profile-info', this)"><span class="dashicons dashicons-admin-users"></span> بيانات العضوية</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('finance-management', this)"><span class="dashicons dashicons-money-alt"></span> الإدارة المالية</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('document-vault', this); smLoadDocuments();"><span class="dashicons dashicons-portfolio"></span> الأرشيف والمستندات</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('member-chat', this); setTimeout(() => selectConversation(<?php echo $member->id; ?>, '<?php echo esc_js($member->name); ?>', <?php echo $member->wp_user_id ?: 0; ?>), 100);"><span class="dashicons dashicons-email"></span> المراسلات والشكاوى</button>
    </div>

    <div id="profile-info" class="sm-internal-tab">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <div style="display: flex; flex-direction: column; gap: 30px;">
                <!-- Basic Info -->
                <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">البيانات الأساسية</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div><label class="sm-label">الرقم القومي:</label> <div class="sm-value"><?php echo esc_html($member->national_id); ?></div></div>
                    <div><label class="sm-label">كود العضوية:</label> <div class="sm-value"><?php echo esc_html($member->member_code); ?></div></div>
                    <div><label class="sm-label">التخصص:</label> <div class="sm-value"><?php echo esc_html($specs[$member->specialization] ?? $member->specialization); ?></div></div>
                    <div><label class="sm-label">الدرجة العلمية:</label> <div class="sm-value"><?php echo esc_html($member->academic_degree); ?></div></div>
                    <div><label class="sm-label">رقم الهاتف:</label> <div class="sm-value"><?php echo esc_html($member->phone); ?></div></div>
                    <div><label class="sm-label">البريد الإلكتروني:</label> <div class="sm-value"><?php echo esc_html($member->email); ?></div></div>
                    <?php if ($member->wp_user_id): ?>
                        <?php $temp_pass = get_user_meta($member->wp_user_id, 'sm_temp_pass', true); if ($temp_pass): ?>
                            <div style="grid-column: span 2; background: #fffaf0; padding: 15px; border-radius: 8px; border: 1px solid #feebc8; margin-top: 10px;">
                                <label class="sm-label" style="color: #744210;">كلمة المرور المؤقتة للنظام:</label>
                                <div style="font-family: monospace; font-size: 1.2em; font-weight: 700; color: #975a16;"><?php echo esc_html($temp_pass); ?></div>
                                <small style="color: #975a16;">* يرجى تزويد العضو بهذه الكلمة ليتمكن من الدخول لأول مرة.</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Professional Permits Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Practice License Card -->
                <div class="sm-license-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--sm-border-color); overflow: hidden; box-shadow: var(--sm-shadow);">
                    <div style="background: var(--sm-primary-color); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: #fff;">
                        <h4 style="margin: 0; font-weight: 800;"><span class="dashicons dashicons-id-alt" style="vertical-align: middle;"></span> تصريح مزاولة المهنة</h4>
                        <?php
                        $lic_valid = ($member->license_expiration_date && $member->license_expiration_date >= date('Y-m-d'));
                        $lic_badge_bg = $lic_valid ? '#38a169' : '#e53e3e';
                        if (empty($member->license_number)) $lic_badge_bg = '#718096';
                        ?>
                        <span class="sm-badge" style="background: <?php echo $lic_badge_bg; ?>; color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                            <?php echo empty($member->license_number) ? 'غير مسجل' : ($lic_valid ? 'ساري' : 'منتهي'); ?>
                        </span>
                    </div>
                    <div style="padding: 20px;">
                        <?php if (empty($member->license_number)): ?>
                            <div style="text-align: center; color: #94a3b8; padding: 20px;">
                                <span class="dashicons dashicons-warning" style="font-size: 32px; width: 32px; height: 32px;"></span>
                                <p style="margin-top: 10px; font-weight: 700;">غير مقيد بسجل تصاريح المزاولة</p>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div><label class="sm-label" style="font-size: 11px;">رقم التصريح</label><div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($member->license_number); ?></div></div>
                                <div><label class="sm-label" style="font-size: 11px;">تاريخ الإصدار</label><div style="font-weight: 700;"><?php echo esc_html($member->license_issue_date ?: '---'); ?></div></div>
                                <div style="grid-column: span 2;">
                                    <label class="sm-label" style="font-size: 11px;">تاريخ الانتهاء</label>
                                    <div style="font-weight: 800; color: <?php echo $lic_valid ? '#38a169' : '#e53e3e'; ?>; font-size: 1.1em;">
                                        <?php echo esc_html($member->license_expiration_date ?: '---'); ?>
                                        <?php if ($lic_valid): ?>
                                            <span style="font-size: 11px; font-weight: 400; margin-right: 5px;">(ينتهي خلال <?php
                                                $d1 = new DateTime(); $d2 = new DateTime($member->license_expiration_date);
                                                echo $d1->diff($d2)->days;
                                            ?> يوم)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px;">
                                <?php if (current_user_can('sm_print_reports')): ?>
                                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_license&member_id='.$member->id); ?>" target="_blank" class="sm-btn sm-btn-outline" style="height: 32px; font-size: 11px; width: auto;"><span class="dashicons dashicons-printer"></span> طباعة التصريح</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Facility License Card -->
                <div class="sm-license-card" style="background: #fff; border-radius: 12px; border: 1px solid var(--sm-border-color); overflow: hidden; box-shadow: var(--sm-shadow);">
                    <div style="background: #2c3e50; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: #fff;">
                        <h4 style="margin: 0; font-weight: 800;"><span class="dashicons dashicons-building" style="vertical-align: middle;"></span> ترخيص المنشأة</h4>
                        <?php
                        $fac_valid = ($member->facility_license_expiration_date && $member->facility_license_expiration_date >= date('Y-m-d'));
                        $fac_badge_bg = $fac_valid ? '#27ae60' : '#e53e3e';
                        if (empty($member->facility_number)) $fac_badge_bg = '#718096';
                        ?>
                        <span class="sm-badge" style="background: <?php echo $fac_badge_bg; ?>; color: #fff; border: 1px solid rgba(255,255,255,0.3);">
                            <?php echo empty($member->facility_number) ? 'غير مسجل' : ($fac_valid ? 'ساري' : 'منتهي'); ?>
                        </span>
                    </div>
                    <div style="padding: 20px;">
                        <?php if (empty($member->facility_number)): ?>
                            <div style="text-align: center; color: #94a3b8; padding: 20px;">
                                <span class="dashicons dashicons-building" style="font-size: 32px; width: 32px; height: 32px;"></span>
                                <p style="margin-top: 10px; font-weight: 700;">لم يتم تسجيل منشأة لهذا العضو</p>
                            </div>
                        <?php else: ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div style="grid-column: span 2;"><label class="sm-label" style="font-size: 11px;">اسم المنشأة</label><div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($member->facility_name); ?></div></div>
                                <div><label class="sm-label" style="font-size: 11px;">رقم الترخيص</label><div style="font-weight: 700;"><?php echo esc_html($member->facility_number); ?></div></div>
                                <div><label class="sm-label" style="font-size: 11px;">الفئة</label><div><span class="sm-badge sm-badge-low" style="background: #edf2f7; color: #2d3748;"><?php echo esc_html($member->facility_category); ?></span></div></div>
                                <div style="grid-column: span 2;">
                                    <label class="sm-label" style="font-size: 11px;">تاريخ انتهاء الترخيص</label>
                                    <div style="font-weight: 800; color: <?php echo $fac_valid ? '#38a169' : '#e53e3e'; ?>;">
                                        <?php echo esc_html($member->facility_license_expiration_date ?: '---'); ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px;">
                                <?php if (current_user_can('sm_print_reports')): ?>
                                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_facility&member_id='.$member->id); ?>" target="_blank" class="sm-btn sm-btn-outline" style="height: 32px; font-size: 11px; width: auto;"><span class="dashicons dashicons-printer"></span> طباعة الترخيص</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Financial Status -->
            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); box-shadow: var(--sm-shadow);">
                <h3 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">الوضع المالي</h3>
                <div style="text-align: center; padding: 10px 0;">
                    <div style="font-size: 0.9em; color: #718096;">إجمالي المستحق</div>
                    <div style="font-size: 2.2em; font-weight: 900; color: <?php echo $finance['balance'] > 0 ? '#e53e3e' : '#38a169'; ?>;">
                        <?php echo number_format($finance['balance'], 2); ?> ج.م
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between;"><span>المبلغ المطلوب سداده:</span> <strong><?php echo number_format($finance['total_owed'], 2); ?></strong></div>
                    <div style="display: flex; justify-content: space-between;"><span>إجمالي ما تم سداده:</span> <strong style="color:#38a169;"><?php echo number_format($finance['total_paid'], 2); ?></strong></div>
                </div>
                    <button onclick="smOpenFinanceModal(<?php echo $member->id; ?>)" class="sm-btn" style="margin-top: 20px; background: var(--sm-dark-color);">
                        <?php echo ($is_syndicate_staff && !current_user_can('sm_manage_finance')) ? 'عرض كشف الحساب' : 'إدارة المدفوعات والفواتير'; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Finance Management Tab -->
    <div id="finance-management" class="sm-internal-tab" style="display: none;">
        <?php include SM_PLUGIN_DIR . 'templates/member-finance-tab.php'; ?>
    </div>

    <!-- Document Vault Tab -->
    <div id="document-vault" class="sm-internal-tab" style="display: none;">
        <?php include SM_PLUGIN_DIR . 'templates/member-document-vault.php'; ?>
    </div>

    <!-- Communication Tab -->
    <div id="member-chat" class="sm-internal-tab" style="display: none;">
        <div style="height: 600px; border: 1px solid #eee; border-radius: 12px; overflow: hidden; background: #fff;">
            <?php
            // Reuse messaging-center but in a compact way
            include SM_PLUGIN_DIR . 'templates/messaging-center.php';
            ?>
        </div>
    </div>

    <!-- Edit Member Modal (Moved here to be functional) -->
    <div id="edit-member-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 900px;">
            <div class="sm-modal-header"><h3>تعديل بيانات العضو</h3><button class="sm-modal-close" onclick="document.getElementById('edit-member-modal').style.display='none'">&times;</button></div>
            <form id="edit-member-form">
                <?php wp_nonce_field('sm_add_member', 'sm_nonce'); ?>
                <input type="hidden" name="member_id" id="edit_member_id_hidden">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; padding:20px;">
                    <div class="sm-form-group"><label class="sm-label">الاسم الكامل:</label><input name="name" id="edit_name" type="text" class="sm-input" required></div>
                    <div class="sm-form-group"><label class="sm-label">الرقم القومي:</label><input name="national_id" id="edit_national_id" type="text" class="sm-input" required maxlength="14"></div>
                    <div class="sm-form-group"><label class="sm-label">الدرجة الوظيفية:</label><select name="professional_grade" id="edit_grade" class="sm-select"><?php foreach (SM_Settings::get_professional_grades() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="sm-form-group"><label class="sm-label">التخصص:</label><select name="specialization" id="edit_spec" class="sm-select"><?php foreach (SM_Settings::get_specializations() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="sm-form-group"><label class="sm-label">المحافظة:</label><select name="governorate" id="edit_gov" class="sm-select"><?php foreach (SM_Settings::get_governorates() as $k => $v) echo "<option value='$k'>$v</option>"; ?></select></div>
                    <div class="sm-form-group"><label class="sm-label">رقم الهاتف:</label><input name="phone" id="edit_phone" type="text" class="sm-input"></div>
                    <div class="sm-form-group"><label class="sm-label">البريد الإلكتروني:</label><input name="email" id="edit_email" type="email" class="sm-input"></div>
                    <div class="sm-form-group"><label class="sm-label">تاريخ بدء العضوية:</label><input name="membership_start_date" id="edit_mem_start" type="date" class="sm-input"></div>
                    <div class="sm-form-group"><label class="sm-label">تاريخ انتهاء العضوية:</label><input name="membership_expiration_date" id="edit_mem_expiry" type="date" class="sm-input" value="2024-12-31"></div>
                    <div class="sm-form-group" style="grid-column: span 3;"><label class="sm-label">ملاحظات:</label><textarea name="notes" id="edit_notes" class="sm-input" rows="2"></textarea></div>
                </div>
                <button type="submit" class="sm-btn">تحديث البيانات الآن</button>
            </form>
        </div>
    </div>

    <!-- Member Update Request Modal -->
    <div id="member-update-request-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 800px;">
            <div class="sm-modal-header">
                <h3>طلب تحديث بيانات العضوية</h3>
                <button class="sm-modal-close" onclick="document.getElementById('member-update-request-modal').style.display='none'">&times;</button>
            </div>
            <div style="padding: 20px; background: #fffaf0; border-bottom: 1px solid #feebc8; font-size: 13px; color: #744210;">
                <span class="dashicons dashicons-info" style="font-size: 16px;"></span> سيتم إرسال طلبك للمراجعة من قبل إدارة النقابة قبل اعتماده رسمياً في النظام.
            </div>
            <form id="member-update-request-form">
                <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 25px;">
                    <div class="sm-form-group"><label class="sm-label">الاسم الكامل:</label><input type="text" name="name" class="sm-input" value="<?php echo esc_attr($member->name); ?>" required></div>
                    <div class="sm-form-group"><label class="sm-label">الرقم القومي:</label><input type="text" name="national_id" class="sm-input" value="<?php echo esc_attr($member->national_id); ?>" required maxlength="14"></div>
                    <div class="sm-form-group"><label class="sm-label">الدرجة الوظيفية:</label><select name="professional_grade" class="sm-select"><?php foreach ($grades as $k => $v) echo "<option value='$k' ".selected($member->professional_grade, $k, false).">$v</option>"; ?></select></div>
                    <div class="sm-form-group"><label class="sm-label">التخصص:</label><select name="specialization" class="sm-select"><?php foreach ($specs as $k => $v) echo "<option value='$k' ".selected($member->specialization, $k, false).">$v</option>"; ?></select></div>
                    <div class="sm-form-group"><label class="sm-label">المحافظة:</label><select name="governorate" class="sm-select"><?php foreach ($govs as $k => $v) echo "<option value='$k' ".selected($member->governorate, $k, false).">$v</option>"; ?></select></div>
                    <div class="sm-form-group"><label class="sm-label">رقم الهاتف:</label><input type="text" name="phone" class="sm-input" value="<?php echo esc_attr($member->phone); ?>"></div>
                    <div class="sm-form-group"><label class="sm-label">البريد الإلكتروني:</label><input type="email" name="email" class="sm-input" value="<?php echo esc_attr($member->email); ?>"></div>
                    <div class="sm-form-group" style="grid-column: span 2;"><label class="sm-label">سبب التحديث / ملاحظات إضافية:</label><textarea name="notes" class="sm-input" rows="2"></textarea></div>
                </div>
                <div style="padding: 0 25px 25px;">
                    <button type="submit" class="sm-btn" style="width: 100%; height: 45px; font-weight: 700;">إرسال طلب التحديث للمراجعة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function smToggleFinanceDropdown() {
    const el = document.getElementById('sm-finance-dropdown');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function smTriggerPhotoUpload() {
    document.getElementById('member-photo-input').click();
}

function smUploadMemberPhoto(memberId) {
    const file = document.getElementById('member-photo-input').files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'sm_update_member_photo');
    formData.append('member_id', memberId);
    formData.append('member_photo', file);
    formData.append('sm_photo_nonce', '<?php echo wp_create_nonce("sm_photo_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('member-photo-container').innerHTML = `<img src="${res.data.photo_url}" style="width:100%; height:100%; object-fit:cover;">`;
            smShowNotification('تم تحديث الصورة الشخصية');
        } else {
            alert('فشل الرفع: ' + res.data);
        }
    });
}

function smOpenUpdateMemberRequestModal() {
    document.getElementById('member-update-request-modal').style.display = 'flex';
}

document.getElementById('member-update-request-form').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'sm_submit_update_request_ajax');
    formData.append('nonce', '<?php echo wp_create_nonce("sm_update_request"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم إرسال طلب التحديث بنجاح. سنقوم بمراجعته قريباً.');
            document.getElementById('member-update-request-modal').style.display = 'none';
        } else {
            alert('خطأ: ' + res.data);
        }
    });
};

function deleteMember(id, name) {
    if (!confirm('هل أنت متأكد من حذف العضو: ' + name + ' نهائياً من النظام؟ لا يمكن التراجع عن هذا الإجراء.')) return;
    const formData = new FormData();
    formData.append('action', 'sm_delete_member_ajax');
    formData.append('member_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_member"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            window.location.href = '<?php echo add_query_arg('sm_tab', 'members'); ?>';
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

window.editSmMember = function(s) {
    document.getElementById('edit_member_id_hidden').value = s.id;
    document.getElementById('edit_name').value = s.name;
    document.getElementById('edit_national_id').value = s.national_id;
    document.getElementById('edit_grade').value = s.professional_grade;
    document.getElementById('edit_spec').value = s.specialization;
    document.getElementById('edit_gov').value = s.governorate;
    document.getElementById('edit_phone').value = s.phone;
    document.getElementById('edit_email').value = s.email;
    document.getElementById('edit_mem_start').value = s.membership_start_date;
    document.getElementById('edit_mem_expiry').value = s.membership_expiration_date;
    document.getElementById('edit_notes').value = s.notes || '';
    document.getElementById('edit-member-modal').style.display = 'flex';
};

document.getElementById('edit-member-form').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'sm_update_member_ajax');
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json()).then(res => {
        if(res.success) {
            smShowNotification('تم تحديث البيانات بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            alert(res.data);
        }
    });
};

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('sm-finance-dropdown');
    const btn = document.querySelector('[onclick="smToggleFinanceDropdown()"]');
    if (dropdown && !dropdown.contains(e.target) && btn && !btn.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
</script>
