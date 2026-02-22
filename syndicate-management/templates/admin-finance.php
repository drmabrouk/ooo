<?php if (!defined('ABSPATH')) exit;

$stats = SM_Finance::get_financial_stats();
$search = isset($_GET['member_search']) ? sanitize_text_field($_GET['member_search']) : '';
$members = SM_DB::get_members(['search' => $search]);

$members_with_balance = [];
foreach ($members as $m) {
    $dues = SM_Finance::calculate_member_dues($m->id);
    if ($dues['balance'] > 0 || !empty($search)) {
        $m->finance = $dues;
        $members_with_balance[] = $m;
    }
}
?>

<div class="sm-finance-registry" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0;">إدارة الاستحقاقات المالية</h3>
        <div style="display:flex; gap:10px;">
             <button onclick="location.reload()" class="sm-btn sm-btn-outline" style="width:auto;"><span class="dashicons dashicons-update"></span> تحديث البيانات</button>
        </div>
    </div>

    <!-- Overall Metrics -->
    <div class="sm-card-grid" style="margin-bottom: 30px;">
        <div class="sm-stat-card" style="border-right: 5px solid #27ae60;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 5px; font-weight: 700;">إجمالي المبالغ المحصلة</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #27ae60;"><?php echo number_format($stats['total_paid'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #e67e22;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 5px; font-weight: 700;">إجمالي المستحقات المتأخرة</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #e67e22;"><?php echo number_format($stats['total_balance'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #e53e3e;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 5px; font-weight: 700;">إجمالي الغرامات المقررة</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #e53e3e;"><?php echo number_format($stats['total_penalty'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #111F35;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 5px; font-weight: 700;">القيمة الإجمالية للمطالبات</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #111F35;"><?php echo number_format($stats['total_owed'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <form method="get" style="display: flex; gap: 15px; align-items: flex-end;">
            <input type="hidden" name="sm_tab" value="finance">
            <div style="flex: 1;">
                <label class="sm-label">البحث عن عضو (الاسم أو الرقم القومي):</label>
                <input type="text" name="member_search" class="sm-input" value="<?php echo esc_attr($search); ?>" placeholder="أدخل بيانات العضو لتدقيق حسابه المالي...">
            </div>
            <button type="submit" class="sm-btn" style="width: auto; height: 42px;">بحث وتدقيق</button>
            <?php if ($search): ?>
                <a href="<?php echo remove_query_arg(['member_search']); ?>" class="sm-btn sm-btn-outline" style="width: auto; height: 42px; text-decoration:none; display:flex; align-items:center;">إلغاء البحث</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Members Balance Table -->
    <div class="sm-table-container">
        <table class="sm-table">
            <thead>
                <tr>
                    <th>العضو</th>
                    <th>الرقم القومي</th>
                    <th>المستحق</th>
                    <th>المسدد</th>
                    <th>المتبقي</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members_with_balance)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px; color: #718096;">لا توجد مديونيات قائمة بناءً على معايير البحث.</td></tr>
                <?php else: ?>
                    <?php foreach ($members_with_balance as $m): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--sm-dark-color);"><?php echo esc_html($m->name); ?></div>
                                <div style="font-size: 11px; color: #718096;"><?php echo esc_html($m->membership_number); ?></div>
                            </td>
                            <td style="font-family: monospace;"><?php echo esc_html($m->national_id); ?></td>
                            <td style="font-weight: 600;"><?php echo number_format($m->finance['total_owed'], 2); ?></td>
                            <td style="color: #38a169; font-weight: 600;"><?php echo number_format($m->finance['total_paid'], 2); ?></td>
                            <td style="color: #e53e3e; font-weight: 800;"><?php echo number_format($m->finance['balance'], 2); ?></td>
                            <td>
                                <?php if ($m->finance['balance'] <= 0): ?>
                                    <span class="sm-badge sm-badge-low" style="background:#def7ec; color:#03543f;">خالص</span>
                                <?php else: ?>
                                    <span class="sm-badge sm-badge-high">مدين</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="smOpenFinanceModal(<?php echo $m->id; ?>)" class="sm-btn" style="height: 30px; font-size: 11px; width: auto; background: #111F35;">تفاصيل / سداد</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

