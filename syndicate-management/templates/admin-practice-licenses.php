<?php if (!defined('ABSPATH')) exit;

global $wpdb;
$members = SM_DB::get_members(['limit' => -1]);

$stats = [
    'total' => 0,
    'expired' => 0,
    'expiring_soon' => 0,
    'active' => 0
];

$current_date = date('Y-m-d');
$soon_date = date('Y-m-d', strtotime('+30 days'));

foreach ($members as $m) {
    if (!empty($m->license_number)) {
        $stats['total']++;
        if ($m->license_expiration_date < $current_date) {
            $stats['expired']++;
        } elseif ($m->license_expiration_date <= $soon_date) {
            $stats['expiring_soon']++;
        } else {
            $stats['active']++;
        }
    }
}

$search = isset($_GET['license_search']) ? sanitize_text_field($_GET['license_search']) : '';
$registry = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sm_members WHERE license_number != '' AND (name LIKE %s OR national_id LIKE %s OR license_number LIKE %s) ORDER BY license_expiration_date ASC",
    '%' . $wpdb->esc_like($search) . '%',
    '%' . $wpdb->esc_like($search) . '%',
    '%' . $wpdb->esc_like($search) . '%'
));
?>

<div class="sm-practice-licenses" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0;">إدارة تصاريح مزاولة المهنة</h3>
        <button onclick="smOpenLicenseIssuanceModal()" class="sm-btn" style="width:auto;">+ إصدار / تجديد تصريح</button>
    </div>

    <div class="sm-card-grid" style="margin-bottom: 30px;">
        <div class="sm-stat-card">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">إجمالي التراخيص</div>
            <div style="font-size: 2em; font-weight: 900; color: var(--sm-dark-color);"><?php echo $stats['total']; ?></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #27ae60;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">تراخيص سارية</div>
            <div style="font-size: 2em; font-weight: 900; color: #27ae60;"><?php echo $stats['active']; ?></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #e67e22;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">تنتهي قريباً (30 يوم)</div>
            <div style="font-size: 2em; font-weight: 900; color: #e67e22;"><?php echo $stats['expiring_soon']; ?></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #e53e3e;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray);">تراخيص منتهية</div>
            <div style="font-size: 2em; font-weight: 900; color: #e53e3e;"><?php echo $stats['expired']; ?></div>
        </div>
    </div>

    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="sm_tab" value="practice-licenses">
            <div style="flex: 1;">
                <label class="sm-label">بحث في سجل التراخيص:</label>
                <input type="text" name="license_search" class="sm-input" value="<?php echo esc_attr($search); ?>" placeholder="الاسم، الرقم القومي، أو رقم الترخيص...">
            </div>
            <button type="submit" class="sm-btn" style="width: auto;">بحث</button>
        </form>
    </div>

    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>العضو</th>
                    <th>رقم الترخيص</th>
                    <th>تاريخ الإصدار</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registry as $m):
                    $is_expired = $m->license_expiration_date < $current_date;
                    $is_soon = !$is_expired && $m->license_expiration_date <= $soon_date;
                ?>
                <tr>
                    <td>
                        <div style="font-weight: 700;"><?php echo esc_html($m->name); ?></div>
                        <div style="font-size: 11px; color: #718096;"><?php echo esc_html($m->national_id); ?></div>
                    </td>
                    <td style="font-weight: 800; color: var(--sm-primary-color);"><?php echo esc_html($m->license_number); ?></td>
                    <td><?php echo esc_html($m->license_issue_date); ?></td>
                    <td><?php echo esc_html($m->license_expiration_date); ?></td>
                    <td>
                        <?php if ($is_expired): ?>
                            <span class="sm-badge sm-badge-high">منتهي</span>
                        <?php elseif ($is_soon): ?>
                            <span class="sm-badge sm-badge-medium">ينتهي قريباً</span>
                        <?php else: ?>
                            <span class="sm-badge sm-badge-low" style="background:#def7ec; color:#03543f;">ساري</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:8px;">
                            <button onclick="smEditLicense(<?php echo $m->id; ?>)" class="sm-btn sm-btn-outline" style="height:28px; font-size:11px; width:auto; padding: 0 10px;">تعديل</button>
                            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_license&member_id='.$m->id); ?>" target="_blank" class="sm-btn" style="height:28px; font-size:11px; width:auto; background:#111F35; padding: 0 10px; display:flex; align-items:center;">طباعة</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- License Issuance Modal -->
<div id="sm-license-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 600px;">
        <div class="sm-modal-header">
            <h3>إصدار / تجديد تصريح مزاولة المهنة</h3>
            <button class="sm-modal-close" onclick="document.getElementById('sm-license-modal').style.display='none'">&times;</button>
        </div>
        <form id="sm-license-form" style="padding: 20px;">
            <div class="sm-form-group">
                <label class="sm-label">اختر العضو:</label>
                <select name="member_id" class="sm-select" id="license_member_select" required>
                    <option value="">-- ابحث واختر العضو --</option>
                    <?php foreach ($members as $m) echo "<option value='{$m->id}' data-license='{$m->license_number}' data-issue='{$m->license_issue_date}' data-expiry='{$m->license_expiration_date}'>{$m->name} ({$m->national_id})</option>"; ?>
                </select>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div class="sm-form-group">
                    <label class="sm-label">رقم الترخيص:</label>
                    <input type="text" name="license_number" id="lic_num" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تاريخ الإصدار:</label>
                    <input type="date" name="license_issue_date" id="lic_issue" class="sm-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تاريخ الانتهاء:</label>
                    <input type="date" name="license_expiration_date" id="lic_expiry" class="sm-input" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                </div>
            </div>
            <button type="submit" class="sm-btn" style="margin-top: 25px;">حفظ بيانات الترخيص</button>
        </form>
    </div>
</div>

<script>
function smOpenLicenseIssuanceModal() {
    document.getElementById('sm-license-form').reset();
    document.getElementById('sm-license-modal').style.display = 'flex';
    document.getElementById('lic_issue').value = '<?php echo date('Y-m-d'); ?>';
    smCalculateExpiry('lic_issue', 'lic_expiry');
}

function smCalculateExpiry(startId, endId) {
    const startDate = document.getElementById(startId).value;
    if (startDate) {
        const date = new Date(startDate);
        date.setFullYear(date.getFullYear() + 1);
        document.getElementById(endId).value = date.toISOString().split('T')[0];
    }
}

document.getElementById('lic_issue').addEventListener('change', function() {
    smCalculateExpiry('lic_issue', 'lic_expiry');
});

document.getElementById('license_member_select').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        document.getElementById('lic_num').value = opt.dataset.license || '';
        document.getElementById('lic_issue').value = opt.dataset.issue || '<?php echo date('Y-m-d'); ?>';

        if (opt.dataset.expiry) {
            document.getElementById('lic_expiry').value = opt.dataset.expiry;
        } else {
            smCalculateExpiry('lic_issue', 'lic_expiry');
        }
    }
});

window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'new') {
        smOpenLicenseIssuanceModal();
    }
});

function smEditLicense(memberId) {
    const select = document.getElementById('license_member_select');
    select.value = memberId;
    select.dispatchEvent(new Event('change'));
    document.getElementById('sm-license-modal').style.display = 'flex';
}

document.getElementById('sm-license-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'sm_update_license_ajax');
    formData.append('nonce', '<?php echo wp_create_nonce("sm_add_member"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ بيانات الترخيص بنجاح');
            setTimeout(() => location.reload(), 500);
        } else {
            smShowNotification('خطأ: ' + res.data, true);
        }
    });
});
</script>
