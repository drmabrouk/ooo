<?php if (!defined('ABSPATH')) exit;

global $wpdb;
$members = SM_DB::get_members(['limit' => -1]);

$stats = [
    'total' => 0,
    'cat_a' => 0,
    'cat_b' => 0,
    'cat_c' => 0,
    'expired' => 0
];

$current_date = date('Y-m-d');

foreach ($members as $m) {
    if (!empty($m->facility_number)) {
        $stats['total']++;
        switch($m->facility_category) {
            case 'A': $stats['cat_a']++; break;
            case 'B': $stats['cat_b']++; break;
            case 'C': $stats['cat_c']++; break;
        }
        if ($m->facility_license_expiration_date < $current_date) {
            $stats['expired']++;
        }
    }
}

$search = isset($_GET['facility_search']) ? sanitize_text_field($_GET['facility_search']) : '';
$registry = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sm_members WHERE facility_number != '' AND (facility_name LIKE %s OR national_id LIKE %s OR facility_number LIKE %s) ORDER BY facility_license_expiration_date ASC",
    '%' . $wpdb->esc_like($search) . '%',
    '%' . $wpdb->esc_like($search) . '%',
    '%' . $wpdb->esc_like($search) . '%'
));
?>

<div class="sm-facility-licenses" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0;">إدارة تراخيص المنشآت</h3>
        <button onclick="smOpenFacilityModal()" class="sm-btn" style="width:auto;">+ تسجيل / تجديد منشأة</button>
    </div>

    <div class="sm-card-grid" style="margin-bottom: 30px;">
        <div class="sm-stat-card" style="border-right: 5px solid var(--sm-dark-color);">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">إجمالي المنشآت</div>
            <div style="font-size: 2em; font-weight: 900;"><?php echo $stats['total']; ?></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #27ae60;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">فئة A</div>
            <div style="font-size: 2em; font-weight: 900; color: #27ae60;"><?php echo $stats['cat_a']; ?></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #3498db;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">فئة B</div>
            <div style="font-size: 2em; font-weight: 900; color: #3498db;"><?php echo $stats['cat_b']; ?></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #e53e3e;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">منتهي</div>
            <div style="font-size: 2em; font-weight: 900; color: #e53e3e;"><?php echo $stats['expired']; ?></div>
        </div>
    </div>

    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="sm_tab" value="facility-licenses">
            <div style="flex: 1;">
                <label class="sm-label">بحث في سجل المنشآت:</label>
                <input type="text" name="facility_search" class="sm-input" value="<?php echo esc_attr($search); ?>" placeholder="اسم المنشأة، المالك، أو رقم الترخيص...">
            </div>
            <button type="submit" class="sm-btn" style="width: auto;">بحث</button>
        </form>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>المنشأة / المالك</th>
                    <th>رقم الترخيص</th>
                    <th>الفئة</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registry as $m):
                    $is_expired = $m->facility_license_expiration_date < $current_date;
                ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($m->facility_name); ?></div>
                        <div style="font-size: 11px; color: #718096;">المالك: <?php echo esc_html($m->name); ?></div>
                    </td>
                    <td style="font-weight: 800;"><?php echo esc_html($m->facility_number); ?></td>
                    <td><span class="sm-badge sm-badge-low" style="background:#edf2f7; color:#2d3748;">فئة <?php echo esc_html($m->facility_category); ?></span></td>
                    <td><?php echo esc_html($m->facility_license_expiration_date); ?></td>
                    <td>
                        <?php if ($is_expired): ?>
                            <span class="sm-badge sm-badge-high">منتهي</span>
                        <?php else: ?>
                            <span class="sm-badge sm-badge-low" style="background:#def7ec; color:#03543f;">ساري</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:8px;">
                            <button onclick="smEditFacility(<?php echo $m->id; ?>)" class="sm-btn sm-btn-outline" style="height:28px; font-size:11px; width:auto; padding: 0 10px;">تعديل</button>
                            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_facility&member_id='.$m->id); ?>" target="_blank" class="sm-btn" style="height:28px; font-size:11px; width:auto; background:#111F35; padding: 0 10px; display:flex; align-items:center;">طباعة</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Facility Modal -->
<div id="sm-facility-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 700px;">
        <div class="sm-modal-header">
            <h3>تسجيل / تجديد بيانات المنشأة</h3>
            <button class="sm-modal-close" onclick="document.getElementById('sm-facility-modal').style.display='none'">&times;</button>
        </div>
        <form id="sm-facility-form" style="padding: 20px;">
            <div class="sm-form-group">
                <label class="sm-label">المالك (العضو):</label>
                <select name="member_id" class="sm-select" id="facility_owner_select" required>
                    <option value="">-- ابحث واختر المالك --</option>
                    <?php foreach ($members as $m) echo "<option value='{$m->id}' data-fname='{$m->facility_name}' data-fnum='{$m->facility_number}' data-fcat='{$m->facility_category}' data-fissue='{$m->facility_license_issue_date}' data-fexpiry='{$m->facility_license_expiration_date}' data-faddr='{$m->facility_address}'>{$m->name} ({$m->national_id})</option>"; ?>
                </select>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div class="sm-form-group">
                    <label class="sm-label">اسم المنشأة:</label>
                    <input type="text" name="facility_name" id="fac_name" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">رقم ترخيص المنشأة:</label>
                    <input type="text" name="facility_number" id="fac_num" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">فئة المنشأة:</label>
                    <select name="facility_category" id="fac_cat" class="sm-select">
                        <option value="A">فئة A (كبرى)</option>
                        <option value="B">فئة B (متوسطة)</option>
                        <option value="C">فئة C (صغرى)</option>
                    </select>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تاريخ الإصدار:</label>
                    <input type="date" name="facility_license_issue_date" id="fac_issue" class="sm-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تاريخ الانتهاء:</label>
                    <input type="date" name="facility_license_expiration_date" id="fac_expiry" class="sm-input" required>
                </div>
            </div>
            <div class="sm-form-group" style="margin-top: 15px;">
                <label class="sm-label">العنوان التفصيلي:</label>
                <textarea name="facility_address" id="fac_addr" class="sm-input" rows="2"></textarea>
            </div>
            <button type="submit" class="sm-btn" style="margin-top: 25px;">حفظ بيانات المنشأة</button>
        </form>
    </div>
</div>

<script>
function smOpenFacilityModal() {
    document.getElementById('sm-facility-form').reset();
    document.getElementById('sm-facility-modal').style.display = 'flex';
    document.getElementById('fac_issue').value = '<?php echo date('Y-m-d'); ?>';
    smCalculateFacilityExpiry();
}

function smCalculateFacilityExpiry() {
    const startDate = document.getElementById('fac_issue').value;
    if (startDate) {
        const date = new Date(startDate);
        date.setFullYear(date.getFullYear() + 1);
        document.getElementById('fac_expiry').value = date.toISOString().split('T')[0];
    }
}

document.getElementById('fac_issue').addEventListener('change', smCalculateFacilityExpiry);

document.getElementById('facility_owner_select').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('fac_name').value = opt.dataset.fname || '';
        document.getElementById('fac_num').value = opt.dataset.fnum || '';
        document.getElementById('fac_cat').value = opt.dataset.fcat || 'C';
        document.getElementById('fac_issue').value = opt.dataset.fissue || '<?php echo date('Y-m-d'); ?>';

        if (opt.dataset.fexpiry) {
            document.getElementById('fac_expiry').value = opt.dataset.fexpiry;
        } else {
            smCalculateFacilityExpiry();
        }

        document.getElementById('fac_addr').value = opt.dataset.faddr || '';
    }
});

window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'new') {
        smOpenFacilityModal();
    }
});

function smEditFacility(memberId) {
    const select = document.getElementById('facility_owner_select');
    select.value = memberId;
    select.dispatchEvent(new Event('change'));
    document.getElementById('sm-facility-modal').style.display = 'flex';
}

document.getElementById('sm-facility-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'sm_update_facility_ajax');
    formData.append('nonce', '<?php echo wp_create_nonce("sm_add_member"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ بيانات المنشأة بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            smShowNotification('خطأ: ' + res.data, true);
        }
    });
});
</script>
