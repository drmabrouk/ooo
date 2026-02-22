<?php if (!defined('ABSPATH')) exit; ?>
<?php
$dues = SM_Finance::calculate_member_dues($member->id);
$history = SM_Finance::get_payment_history($member->id);
?>

<div class="sm-member-finance-tab" dir="rtl">
    <!-- Financial Overview Cards -->
    <div class="sm-card-grid" style="margin-bottom: 30px;">
        <div class="sm-stat-card" style="border-right: 5px solid #e53e3e;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); font-weight: 700;">إجمالي المستحق حالياً (Due)</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #e53e3e;"><?php echo number_format($dues['balance'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #27ae60;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); font-weight: 700;">إجمالي المدفوعات المسجلة</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #27ae60;"><?php echo number_format($dues['total_paid'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
        <div class="sm-stat-card" style="border-right: 5px solid #111F35;">
            <div style="font-size: 0.85em; color: var(--sm-text-gray); font-weight: 700;">المستحق الكلي (Total Owed)</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #111F35;"><?php echo number_format($dues['total_owed'], 2); ?> <span style="font-size: 0.5em;">ج.م</span></div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <div>
            <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; font-weight: 800;"><span class="dashicons dashicons-warning" style="color:#e53e3e;"></span> البنود المستحقة والمديونيات</h4>

            <?php
            $in_grace = false;
            $current_month = (int)date('n');
            if ($current_month >= 1 && $current_month <= 3) {
                foreach ($dues['breakdown'] as $item) {
                    if (strpos($item['item'], 'تجديد عضوية') !== false && $item['penalty'] == 0) {
                        $in_grace = true;
                        break;
                    }
                }
            }
            if ($in_grace): ?>
                <div style="background: #ebf8ff; color: #2b6cb0; padding: 15px; border-radius: 8px; border: 1px solid #bee3f8; margin-bottom: 15px; font-size: 13px;">
                    <span class="dashicons dashicons-info" style="font-size: 18px;"></span> أنت حالياً في <strong>فترة السماح</strong> لتجديد العضوية. يمكنك التجديد الآن بدون أي غرامات تأخير.
                </div>
            <?php endif; ?>

            <div class="sm-table-container" style="margin: 0; border: 1px solid #eee; border-radius: 12px; overflow: hidden;">
                <table class="sm-table" style="font-size: 13px;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th>البند</th>
                            <th>قيمة الخدمة</th>
                            <th>الغرامة (Fine)</th>
                            <th>المستحق</th>
                            <?php if (current_user_can('sm_manage_finance')): ?>
                            <th>إجراء</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dues['breakdown'])): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 30px; color: #38a169; font-weight:700;">لا توجد مديونيات مستحقة. الحساب خالص.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dues['breakdown'] as $item):
                                $is_late = $item['penalty'] > 0;
                            ?>
                            <tr <?php echo $is_late ? 'style="background: #fff5f5;"' : ''; ?>>
                                <td style="font-weight: 600;">
                                    <?php echo $item['item']; ?>
                                    <?php if ($is_late): ?>
                                        <div style="color: #e53e3e; font-size: 10px;">+ مضاف غرامة تأخير</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($item['amount'], 2); ?></td>
                                <td style="color:#e53e3e;"><?php echo number_format($item['penalty'], 2); ?></td>
                                <td style="font-weight:900; color:#e53e3e;"><?php echo number_format($item['total'], 2); ?></td>
                                <?php if (current_user_can('sm_manage_finance')): ?>
                                <td style="text-align: center;">
                                    <button type="button" class="sm-btn" style="height: 25px; padding: 0 10px; font-size: 10px; width: auto; background: #2c3e50;"
                                        onclick="smSelectForPayment(<?php echo $item['total']; ?>, '<?php echo esc_js($item['item']); ?>')">سداد</button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (current_user_can('sm_manage_finance')): ?>
            <div id="sm-payment-section" style="margin-top: 25px; background: #fffaf0; border: 1px solid #feebc8; padding: 20px; border-radius: 12px;">
                <h5 style="margin: 0 0 15px 0; color: #744210; font-weight: 800;"><span class="dashicons dashicons-plus-alt" style="color:#27ae60;"></span> تسجيل تحصيل مبلغ</h5>
                <form id="record-payment-form">
                    <input type="hidden" name="member_id" value="<?php echo $member->id; ?>">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                        <div>
                            <label class="sm-label" style="font-size:11px;">المبلغ المحصل:</label>
                            <input type="number" name="amount" class="sm-input" value="<?php echo $dues['balance']; ?>" step="0.01" required>
                        </div>
                        <div>
                            <label class="sm-label" style="font-size:11px;">تاريخ السداد:</label>
                            <input type="date" name="payment_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label class="sm-label" style="font-size:11px;">النوع:</label>
                            <select name="payment_type" class="sm-select">
                                <option value="membership">اشتراك عضوية</option>
                                <option value="license">ترخيص مزاولة</option>
                                <option value="facility">ترخيص منشأة</option>
                                <option value="penalty">غرامة</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div>
                            <label class="sm-label" style="font-size:11px;">للسنة (اختياري):</label>
                            <input type="number" name="target_year" class="sm-input" value="<?php echo date('Y'); ?>">
                        </div>
                        <div style="grid-column: span 2;">
                            <label class="sm-label" style="font-size:11px;">البيان / تفاصيل العملية:</label>
                            <input type="text" name="details_ar" class="sm-input" placeholder="مثال: سداد اشتراك عضوية 2024">
                        </div>
                    </div>
                    <button type="button" onclick="smSubmitPayment(this)" class="sm-btn" style="background:#27ae60; height: 45px; font-weight: 700; width: 100%;">تأكيد الاستلام وحفظ العملية</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; font-weight: 800;"><span class="dashicons dashicons-media-spreadsheet" style="color:var(--sm-primary-color);"></span> سجل العمليات المالية السابقة</h4>
            <div style="max-height: 700px; overflow-y: auto; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                <?php if (empty($history)): ?>
                    <div style="text-align:center; padding: 50px; color: #94a3b8;">لا توجد عمليات سابقة مسجلة.</div>
                <?php else: ?>
                    <table class="sm-table" style="font-size: 12px;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th>التاريخ</th>
                                <th>البيان</th>
                                <th>المبلغ</th>
                                <th>الفاتورة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $p): ?>
                            <tr>
                                <td><?php echo $p->payment_date; ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?php echo esc_html($p->details_ar ?: $p->payment_type); ?></div>
                                    <div style="font-size: 9px; color: #94a3b8;">كود: <?php echo $p->digital_invoice_code; ?></div>
                                </td>
                                <td style="font-weight:900; color:#27ae60;"><?php echo number_format($p->amount, 2); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_invoice&payment_id='.$p->id); ?>" target="_blank" class="sm-btn" style="height:26px; padding:0 10px; font-size:10px; width:auto; background:#111F35; display:flex; align-items:center; gap:5px; text-decoration:none;"><span class="dashicons dashicons-pdf" style="font-size:14px;"></span> تحميل</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function smSelectForPayment(amount, reason) {
    const form = document.getElementById('record-payment-form');
    if (!form) return;
    form.querySelector('[name="amount"]').value = amount;
    form.querySelector('[name="details_ar"]').value = reason;

    // Auto-detect type
    const select = form.querySelector('[name="payment_type"]');
    if (reason.includes('عضوية')) select.value = 'membership';
    else if (reason.includes('تصريح') || reason.includes('مزاولة')) select.value = 'license';
    else if (reason.includes('منشأة')) select.value = 'facility';

    document.getElementById('sm-payment-section').scrollIntoView({ behavior: 'smooth' });
}
</script>
