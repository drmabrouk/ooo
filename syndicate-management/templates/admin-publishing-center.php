<?php if (!defined('ABSPATH')) exit; ?>
<?php
$user = wp_get_current_user();
$templates = SM_DB::get_pub_templates();
$generated = SM_DB::get_pub_documents();
$syndicate = SM_Settings::get_syndicate_info();
$stamp_url = get_option('sm_pub_stamp_url');
?>

<!-- Include Google Fonts for Publishing -->
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Rubik:wght@400;700;900&display=swap" rel="stylesheet">
<!-- HTML2Canvas for Image Export -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<div class="sm-publishing-center" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <div>
            <h2 style="margin:0; font-weight: 900; color: #111F35; font-size: 1.8em;">مركز الطباعة والنشر الرقمي</h2>
            <p style="margin: 5px 0 0 0; color: #718096; font-size: 0.9em;">إدارة القوالب الرسمية، التقارير، والشهادات المعتمدة</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button onclick="smOpenInternalTab('create-document', this)" class="sm-btn" style="width: auto; background: #111F35; border-radius: 10px; padding: 0 25px;">+ إنشاء مستند جديد</button>
            <button onclick="smOpenInternalTab('approved-templates', this)" class="sm-btn sm-btn-outline" style="width: auto; border-radius: 10px;">المكتبة الرسمية</button>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #edf2f7; padding-bottom: 0;">
        <button class="sm-tab-nav-btn sm-active" onclick="smOpenInternalTab('create-document', this)">
            <span class="dashicons dashicons-edit"></span> التحرير والإنشاء
        </button>
        <button class="sm-tab-nav-btn" onclick="smOpenInternalTab('approved-templates', this)">
            <span class="dashicons dashicons-layout"></span> القوالب المعتمدة
        </button>
        <button class="sm-tab-nav-btn" onclick="smOpenInternalTab('document-logs', this)">
            <span class="dashicons dashicons-media-spreadsheet"></span> سجل المنشورات
        </button>
        <button class="sm-tab-nav-btn" onclick="smOpenInternalTab('pub-settings', this)">
            <span class="dashicons dashicons-admin-generic"></span> إعدادات الهوية
        </button>
    </div>

    <!-- TAB: CREATE DOCUMENT -->
    <div id="create-document" class="sm-internal-tab">
        <div style="display: grid; grid-template-columns: 1fr 380px; gap: 30px;">

            <!-- EDITOR COLUMN -->
            <div style="background: #fff; padding: 35px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow);">
                <div style="margin-bottom: 25px;">
                    <label class="sm-label" style="font-weight: 800; color: #111F35;">عنوان المستند الرسمي:</label>
                    <input type="text" id="pub_doc_title" class="sm-input" placeholder="مثال: شهادة خبرة، تقرير لجنة فرعية..." style="font-size: 1.1em; border-width: 2px;">
                </div>

                <!-- TOOLBAR -->
                <div id="pub-editor-toolbar" style="background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px 12px 0 0; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; border-bottom: none;">

                    <!-- FONT SELECT -->
                    <select onchange="smExecCommand('fontName', this.value)" class="sm-select" style="width: 130px; height: 36px; font-size: 12px;">
                        <option value="Cairo">Cairo (Body)</option>
                        <option value="Amiri">Amiri (Titles)</option>
                        <option value="Tahoma">Tahoma</option>
                        <option value="Arial">Arial</option>
                    </select>

                    <select onchange="smExecCommand('fontSize', this.value)" class="sm-select" style="width: 90px; height: 36px; font-size: 12px;">
                        <option value="3">عادي</option>
                        <option value="4">متوسط</option>
                        <option value="5">كبير</option>
                        <option value="6">كبير جداً</option>
                    </select>

                    <div style="height: 24px; width: 1px; background: #cbd5e0; margin: 0 5px;"></div>

                    <button onclick="smExecCommand('bold')" class="editor-tool-btn" title="عريض"><span class="dashicons dashicons-editor-bold"></span></button>
                    <button onclick="smExecCommand('italic')" class="editor-tool-btn" title="مائل"><span class="dashicons dashicons-editor-italic"></span></button>
                    <button onclick="smExecCommand('underline')" class="editor-tool-btn" title="تحته خط"><span class="dashicons dashicons-editor-underline"></span></button>

                    <div style="height: 24px; width: 1px; background: #cbd5e0; margin: 0 5px;"></div>

                    <button onclick="smExecCommand('justifyRight')" class="editor-tool-btn"><span class="dashicons dashicons-editor-alignright"></span></button>
                    <button onclick="smExecCommand('justifyCenter')" class="editor-tool-btn"><span class="dashicons dashicons-editor-aligncenter"></span></button>
                    <button onclick="smExecCommand('justifyLeft')" class="editor-tool-btn"><span class="dashicons dashicons-editor-alignleft"></span></button>

                    <div style="height: 24px; width: 1px; background: #cbd5e0; margin: 0 5px;"></div>

                    <!-- COLOR PICKERS -->
                    <div style="display: flex; gap: 5px; align-items: center;">
                        <button onclick="smExecCommand('foreColor', '#111F35')" class="color-dot" style="background: #111F35;" title="Navy Blue"></button>
                        <button onclick="smExecCommand('foreColor', '#718096')" class="color-dot" style="background: #718096;" title="Gray"></button>
                        <input type="color" onchange="smExecCommand('foreColor', this.value)" style="width:24px; height:24px; padding:0; border:none; background:none; cursor:pointer;">
                    </div>
                </div>

                <!-- THE EDITOR CANVAS -->
                <div id="pub-document-editor" contenteditable="true" style="min-height: 600px; padding: 60px; border: 2px solid #e2e8f0; border-radius: 0 0 12px 12px; background: #fff; line-height: 1.8; font-family: 'Cairo', sans-serif; outline: none; position: relative;">
                    <!-- Placeholder logic via CSS -->
                    <div style="text-align: center; margin-bottom: 50px;">
                        <h2 style="font-family: 'Amiri', serif; font-size: 2.2em; color: #111F35;">ابدأ كتابة محتوى المستند الرسمي</h2>
                        <p style="color: #718096;">استخدم أدوات التنسيق والقوالب الجاهزة للحصول على أفضل نتيجة</p>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; justify-content: flex-end; gap: 15px;">
                    <button onclick="smGenerateDocument('pdf')" class="sm-btn" style="width:auto; background: #111F35; padding: 0 35px;"><span class="dashicons dashicons-pdf"></span> توليد وحفظ PDF</button>
                    <button onclick="smGenerateDocument('image')" class="sm-btn" style="width:auto; background: #27ae60; padding: 0 35px;"><span class="dashicons dashicons-format-image"></span> تصدير صورة (HQ)</button>
                </div>
            </div>

            <!-- SIDEBAR CONTROLS -->
            <div style="display: flex; flex-direction: column; gap: 20px;">

                <!-- TEMPLATE SELECTION -->
                <div class="sm-sidebar-card">
                    <h4 class="card-title"><span class="dashicons dashicons-layout"></span> مكتبة القوالب الذكية</h4>

                    <div class="template-category">
                        <h5>شهادات وتقارير</h5>
                        <select id="pub_template_select" class="sm-select" style="margin-bottom: 12px;">
                            <option value="">-- اختر قالباً --</option>
                            <?php foreach($templates as $t): ?>
                                <option value="<?php echo $t->id; ?>"><?php echo esc_html($t->title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="smLoadTemplateToEditor()" class="sm-btn sm-btn-outline" style="font-size: 12px; height: 36px;">تطبيق القالب المختار</button>
                    </div>

                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                        <h5>حقول ديناميكية</h5>
                        <p style="font-size: 11px; color: #718096; margin-bottom: 10px;">انقر للإدراج في مكان المؤشر:</p>
                        <div class="placeholder-grid">
                            <button onclick="smInsertPlaceholder('{MEMBER_NAME}')" class="placeholder-tag">اسم العضو</button>
                            <button onclick="smInsertPlaceholder('{NATIONAL_ID}')" class="placeholder-tag">الرقم القومي</button>
                            <button onclick="smInsertPlaceholder('{MEMBERSHIP_NO}')" class="placeholder-tag">رقم القيد</button>
                            <button onclick="smInsertPlaceholder('{SERIAL_NO}')" class="placeholder-tag">رقم المرجع</button>
                            <button onclick="smInsertPlaceholder('{DATE_NOW}')" class="placeholder-tag">التاريخ</button>
                            <button onclick="smInsertPlaceholder('{GOVERNORATE}')" class="placeholder-tag">المحافظة</button>
                        </div>
                    </div>
                </div>

                <!-- DOCUMENT OPTIONS -->
                <div class="sm-sidebar-card" style="background: #f8fafc;">
                    <h4 class="card-title"><span class="dashicons dashicons-admin-tools"></span> خيارات الهوية الرسمية</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <label class="option-check"><input type="checkbox" id="pub_include_header" checked> تضمين الترويسة واللوجو</label>
                        <label class="option-check"><input type="checkbox" id="pub_include_footer" checked> تضمين التذييل والختم</label>
                        <label class="option-check"><input type="checkbox" id="pub_include_qr" checked> إدراج كود التحقق (QR)</label>
                        <label class="option-check"><input type="checkbox" id="pub_include_frame" checked> إطار رسمي للشهادات</label>
                    </div>
                </div>

                <!-- PREVIEW BOX -->
                <div class="sm-sidebar-card">
                    <h4 class="card-title"><span class="dashicons dashicons-visibility"></span> معاينة الختم المعتمد</h4>
                    <div style="text-align: center; background: #fff; padding: 20px; border: 1px solid #edf2f7; border-radius: 10px;">
                        <?php if ($stamp_url): ?>
                            <img src="<?php echo esc_url($stamp_url); ?>" style="max-width: 150px; opacity: 0.8; filter: grayscale(20%);">
                        <?php else: ?>
                            <div style="height: 100px; display: flex; align-items: center; justify-content: center; color: #cbd5e0; font-size: 12px;">لم يتم رفع ختم رسمي</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- TAB: APPROVED TEMPLATES -->
    <div id="approved-templates" class="sm-internal-tab" style="display: none;">
        <div style="background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin: 0; color: #111F35;">إدارة القوالب الرسمية المعتمدة</h3>
                <button onclick="smCreateNewTemplate()" class="sm-btn" style="width: auto; background: #27ae60;">+ إضافة قالب جديد للمكتبة</button>
            </div>
            <div class="sm-table-container">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>اسم القالب</th>
                            <th>نوع المستند</th>
                            <th>حالة الاستخدام</th>
                            <th>آخر تحديث</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($templates as $t): ?>
                            <tr>
                                <td style="font-weight: 800; color: #111F35;"><?php echo esc_html($t->title); ?></td>
                                <td><span class="sm-badge" style="background: #edf2f7; color: #2d3748;"><?php echo $t->doc_type; ?></span></td>
                                <td><span class="sm-badge" style="background: #c6f6d5; color: #22543d;">معتمد</span></td>
                                <td style="font-size: 11px;"><?php echo $t->created_at; ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick='smEditTemplate(<?php echo json_encode($t); ?>)' class="sm-btn sm-btn-outline" style="width:auto; height:32px; font-size:11px;">تعديل</button>
                                        <button onclick="smDeleteTemplate(<?php echo $t->id; ?>)" class="sm-btn sm-btn-outline" style="width:auto; height:32px; font-size:11px; color: #e53e3e; border-color: #feb2b2;">حذف</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: DOCUMENT LOGS -->
    <div id="document-logs" class="sm-internal-tab" style="display: none;">
        <div style="background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin: 0; color: #111F35;">سجل المستندات الصادرة</h3>
                <input type="text" id="pub_log_search" placeholder="بحث بالرقم المسلسل أو العنوان..." class="sm-input" style="width: 350px;" oninput="smFilterLogs()">
            </div>
            <div class="sm-table-container">
                <table class="sm-table" id="pub-logs-table">
                    <thead>
                        <tr>
                            <th>الرقم المسلسل</th>
                            <th>عنوان المستند</th>
                            <th>تاريخ الإصدار</th>
                            <th>المحرر</th>
                            <th>التحميلات</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($generated as $d): ?>
                            <tr>
                                <td style="font-family: 'Rubik', sans-serif; font-weight: 900; color: #111F35; font-size: 1.1em;"><?php echo $d->serial_number; ?></td>
                                <td style="font-weight: 700;"><?php echo esc_html($d->title); ?></td>
                                <td style="font-size: 11px;"><?php echo $d->created_at; ?></td>
                                <td><?php echo esc_html($d->creator_name); ?></td>
                                <td><span class="sm-badge" style="background:#f7fafc; color:#2d3748; border: 1px solid #e2e8f0;"><?php echo $d->download_count; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick="smDownloadGenerated(<?php echo $d->id; ?>, 'pdf')" class="sm-btn" style="width:auto; height:30px; font-size:11px; background:#111F35;">PDF</button>
                                        <button onclick="smDownloadGenerated(<?php echo $d->id; ?>, 'image')" class="sm-btn" style="width:auto; height:30px; font-size:11px; background:#27ae60;">IMG</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB: IDENTITY SETTINGS -->
    <div id="pub-settings" class="sm-internal-tab" style="display: none;">
        <div style="max-width: 900px; background: #fff; padding: 40px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow);">
            <h3 style="margin-top: 0; border-bottom: 2px solid #f0f4f8; padding-bottom: 15px; margin-bottom: 30px; color: #111F35;">إعدادات الهوية المؤسسية للمنشورات</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div class="sm-form-group">
                    <label class="sm-label" style="font-weight: 700;">الشعار الرسمي الملون (Header):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="pub_id_logo" class="sm-input" value="<?php echo esc_attr($syndicate['syndicate_logo']); ?>">
                        <button onclick="smOpenMediaUploader('pub_id_logo')" class="sm-btn sm-btn-outline" style="width:auto; background: #edf2f7;">اختيار</button>
                    </div>
                    <p style="font-size: 11px; color: #718096; margin-top: 5px;">يفضل استخدام صيغة PNG بخلفية شفافة</p>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label" style="font-weight: 700;">الختم الرسمي للمؤسسة (Digital Seal):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="pub_id_stamp" class="sm-input" value="<?php echo esc_attr($stamp_url); ?>">
                        <button onclick="smOpenMediaUploader('pub_id_stamp')" class="sm-btn sm-btn-outline" style="width:auto; background: #edf2f7;">اختيار</button>
                    </div>
                    <p style="font-size: 11px; color: #718096; margin-top: 5px;">سيتم دمجه آلياً في أسفل المستندات الرسمية</p>
                </div>

                <div class="sm-form-group" style="grid-column: span 2;">
                    <label class="sm-label" style="font-weight: 700;">بيان الاعتماد الرسمي (Footer Statement):</label>
                    <textarea id="pub_id_footer_statement" class="sm-input" rows="3" style="font-size: 14px;"><?php echo esc_textarea(get_option('sm_pub_footer_statement', 'يعتبر هذا المستند رسمياً وصادراً من المنصة الإلكترونية للنقابة، ويمكن التحقق من صحته عبر رمز الاستجابة السريع المرفق.')); ?></textarea>
                </div>

                <div class="sm-form-group">
                    <label class="sm-label" style="font-weight: 700;">لون الهوية الأساسي (Navy):</label>
                    <input type="color" id="pub_id_color_primary" class="sm-input" value="#111F35" style="height: 45px; padding: 5px;">
                </div>

                <div class="sm-form-group">
                    <label class="sm-label" style="font-weight: 700;">لون الهوية الثانوي (Accent):</label>
                    <input type="color" id="pub_id_color_secondary" class="sm-input" value="#718096" style="height: 45px; padding: 5px;">
                </div>
            </div>

            <div style="margin-top: 40px; border-top: 1px solid #edf2f7; padding-top: 25px; text-align: left;">
                <button onclick="smSavePubIdentity()" class="sm-btn" style="width: auto; padding: 0 50px; height: 50px; font-weight: 800; font-size: 1.1em;">حفظ التغييرات وتعميم الهوية</button>
            </div>
        </div>
    </div>

</div>

<style>
.sm-tab-nav-btn {
    padding: 15px 30px; border: none; background: none; cursor: pointer; font-weight: 700; color: #718096;
    border-bottom: 3px solid transparent; transition: 0.3s; font-size: 15px; display: flex; align-items: center; gap: 10px;
}
.sm-tab-nav-btn:hover { color: #111F35; background: #f8fafc; }
.sm-tab-nav-btn.sm-active { color: #111F35; border-bottom-color: #111F35; background: #f8fafc; }
.sm-tab-nav-btn .dashicons { font-size: 20px; width: 20px; height: 20px; }

.sm-sidebar-card { background: #fff; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
.card-title { margin: 0 0 20px 0; font-size: 1.1em; font-weight: 800; color: #111F35; border-bottom: 1px solid #f0f4f8; padding-bottom: 12px; display: flex; align-items: center; gap: 10px; }
.card-title .dashicons { color: #111F35; }

.editor-tool-btn {
    width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; color: #4a5568;
    transition: 0.2s;
}
.editor-tool-btn:hover { background: #edf2f7; color: #111F35; border-color: #111F35; }

.color-dot { width: 24px; height: 24px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 1px #e2e8f0; cursor: pointer; }

.placeholder-tag {
    font-size: 11px; background: #edf2f7; border: 1px solid #cbd5e0; border-radius: 6px;
    padding: 6px 10px; cursor: pointer; transition: 0.2s; font-weight: 700; color: #2d3748;
}
.placeholder-tag:hover { background: #111F35; color: #fff; border-color: #111F35; }
.placeholder-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

.option-check { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #4a5568; cursor: pointer; }
.option-check input { width: 18px; height: 18px; cursor: pointer; }

/* Custom Frame Preview Simulation */
#pub-document-editor[data-frame="true"] {
    border: 15px double #111F35 !important;
    padding: 40px !important;
}
</style>

<script>
function smExecCommand(cmd, val = null) {
    document.execCommand(cmd, false, val);
    document.getElementById('pub-document-editor').focus();
}

function smInsertPlaceholder(text) {
    document.execCommand('insertText', false, text);
}

document.getElementById('pub_include_frame').addEventListener('change', function() {
    document.getElementById('pub-document-editor').setAttribute('data-frame', this.checked);
});

function smLoadTemplateToEditor() {
    const id = document.getElementById('pub_template_select').value;
    if (!id) return;

    fetch(ajaxurl + '?action=sm_get_pub_template&id=' + id)
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('pub_doc_title').value = res.data.title;
            document.getElementById('pub-document-editor').innerHTML = res.data.content;
            smShowNotification('تم تحميل القالب من المكتبة الرسمية');
        }
    });
}

async function smGenerateDocument(format) {
    const title = document.getElementById('pub_doc_title').value;
    const content = document.getElementById('pub-document-editor').innerHTML;

    if (!title) return alert('يرجى إدخال عنوان للمستند أولاً');

    if (format === 'image') {
        smShowNotification('جاري معالجة الصورة عالية الجودة...');
        const editor = document.getElementById('pub-document-editor');
        const canvas = await html2canvas(editor, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff'
        });
        const link = document.createElement('a');
        link.download = title + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        smShowNotification('تم تصدير الصورة بنجاح');
        // Still save to logs
    }

    const fd = new FormData();
    fd.append('action', 'sm_generate_pub_doc');
    fd.append('title', title);
    fd.append('content', content);
    fd.append('format', format);
    fd.append('header', document.getElementById('pub_include_header').checked ? 1 : 0);
    fd.append('footer', document.getElementById('pub_include_footer').checked ? 1 : 0);
    fd.append('qr', document.getElementById('pub_include_qr').checked ? 1 : 0);
    fd.append('frame', document.getElementById('pub_include_frame').checked ? 1 : 0);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (format === 'pdf') window.open(res.data.url, '_blank');
            location.reload();
        } else {
            alert('خطأ: ' + res.data);
        }
    });
}

function smDownloadGenerated(id, format) {
    window.open(ajaxurl + '?action=sm_print_pub_doc&id=' + id + '&format=' + format, '_blank');
}

function smSavePubIdentity() {
    const logo = document.getElementById('pub_id_logo').value;
    const stamp = document.getElementById('pub_id_stamp').value;
    const footer = document.getElementById('pub_id_footer_statement').value;
    const pColor = document.getElementById('pub_id_color_primary').value;
    const sColor = document.getElementById('pub_id_color_secondary').value;

    const fd = new FormData();
    fd.append('action', 'sm_save_pub_identity');
    fd.append('logo', logo);
    fd.append('stamp', stamp);
    fd.append('footer', footer);
    fd.append('p_color', pColor);
    fd.append('s_color', sColor);
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) smShowNotification('تم حفظ وتحديث الهوية المؤسسية بنجاح');
    });
}

function smFilterLogs() {
    const val = document.getElementById('pub_log_search').value.toLowerCase();
    const rows = document.querySelectorAll('#pub-logs-table tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
}

function smCreateNewTemplate() {
    const title = prompt('أدخل اسم القالب الجديد:');
    if (!title) return;
    const content = document.getElementById('pub-document-editor').innerHTML;

    const fd = new FormData();
    fd.append('action', 'sm_save_pub_template');
    fd.append('title', title);
    fd.append('content', content);
    fd.append('doc_type', 'custom');
    fd.append('nonce', '<?php echo wp_create_nonce("sm_pub_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if (res.success) {
            smShowNotification('تم إضافة القالب للمكتبة بنجاح');
            location.reload();
        }
    });
}

function smDeleteTemplate(id) {
    if (!confirm('هل أنت متأكد من حذف هذا القالب من المكتبة؟')) return;
    // Logic for deletion...
}

window.smOpenInternalTab = function(tabId, element) {
    document.querySelectorAll('.sm-internal-tab').forEach(t => t.style.display = 'none');
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';

    if (element && element.classList.contains('sm-tab-nav-btn')) {
        element.parentElement.querySelectorAll('.sm-tab-nav-btn').forEach(b => b.classList.remove('sm-active'));
        element.classList.add('sm-active');
    }
}
</script>
