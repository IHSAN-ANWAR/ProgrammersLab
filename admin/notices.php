<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'add':
        case 'edit':
            $title       = trim($_POST['title']       ?? '');
            $message     = trim($_POST['message']     ?? '');
            $badge       = trim($_POST['badge']       ?? '');
            $badge_color = trim($_POST['badge_color'] ?? '#f07b14');
            $btn_text    = trim($_POST['btn_text']    ?? '');
            $btn_url     = trim($_POST['btn_url']     ?? '');
            $is_active   = (int)($_POST['is_active']  ?? 0);

            if (!$title || !$message) {
                echo json_encode(['success' => false, 'message' => 'Title and message required.']);
                exit;
            }

            // Handle image upload
            $image = trim($_POST['existing_image'] ?? '');
            if (isset($_FILES['notice_image']) && $_FILES['notice_image']['error'] === UPLOAD_ERR_OK) {
                $file     = $_FILES['notice_image'];
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mime     = $finfo->file($file['tmp_name']);
                $allowed  = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
                if (!isset($allowed[$mime])) {
                    echo json_encode(['success'=>false,'message'=>'Invalid image type. Use JPG, PNG, GIF or WebP.']); exit;
                }
                if ($file['size'] > 2 * 1024 * 1024) {
                    echo json_encode(['success'=>false,'message'=>'Image too large. Max 2MB.']); exit;
                }
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0750, true);
                $fname = 'notice_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $fname)) {
                    $image = $fname;
                }
            }

            if ($_POST['action'] === 'add') {
                if ($is_active) $conn->query("UPDATE notices SET is_active = 0");
                $stmt = $conn->prepare("INSERT INTO notices (title, message, badge, badge_color, btn_text, btn_url, image, is_active) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param('sssssssi', $title, $message, $badge, $badge_color, $btn_text, $btn_url, $image, $is_active);
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if ($is_active) $conn->query("UPDATE notices SET is_active = 0 WHERE id != $id");
                $stmt = $conn->prepare("UPDATE notices SET title=?, message=?, badge=?, badge_color=?, btn_text=?, btn_url=?, image=?, is_active=? WHERE id=?");
                $stmt->bind_param('sssssssii', $title, $message, $badge, $badge_color, $btn_text, $btn_url, $image, $is_active, $id);
            }
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok, 'id' => $conn->insert_id, 'message' => $ok ? 'Saved.' : $stmt->error]);
            exit;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            // Only one active at a time
            $conn->query("UPDATE notices SET is_active = 0");
            $row = $conn->query("SELECT is_active FROM notices WHERE id = $id")->fetch_assoc();
            $new = $row['is_active'] ? 0 : 1;
            if ($new) $conn->query("UPDATE notices SET is_active = 1 WHERE id = $id");
            echo json_encode(['success' => true, 'is_active' => $new]);
            exit;

        case 'delete':
            $id   = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
            $stmt->bind_param('i', $id);
            $ok   = $stmt->execute();
            echo json_encode(['success' => $ok]);
            exit;
    }
}

$notices = $conn->query("SELECT *, IFNULL(image,'') AS image FROM notices ORDER BY id DESC");
log_activity($conn, 'VIEW_NOTICES', 'Viewed notices management');
$conn->close();

admin_head('Notices', 'notices');
?>

<div class="pl-page-header">
    <div>
        <h1>Site Notices</h1>
        <div class="pl-breadcrumb">Visitor ko popup notice dikhao — admin se control karo</div>
    </div>
    <button onclick="openModal()" style="background:#f07b14;border:none;border-radius:10px;padding:10px 22px;font-weight:700;color:#fff;cursor:pointer;">
        <i class="fas fa-plus me-2"></i>New Notice
    </button>
</div>

<!-- Add/Edit Modal -->
<div id="noticeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:18px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;">
        <div style="padding:24px 28px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
            <h4 id="modalTitle" style="margin:0;font-weight:800;color:#0d1b2a;">New Notice</h4>
            <button onclick="closeModal()" style="border:none;background:#f5f5f5;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;">✕</button>
        </div>
        <div style="padding:24px 28px;">
            <input type="hidden" id="nId">

            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Notice Title *</label>
                <input type="text" id="nTitle" class="form-control" placeholder="e.g. New Batch Starting Soon!">
            </div>

            <!-- Image Upload -->
            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Notice Image <small style="color:#9ca3af;">(optional)</small></label>
                <div style="display:flex;align-items:center;gap:12px;">
                    <label style="flex:1;display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px dashed #e5e7eb;border-radius:10px;cursor:pointer;background:#fafafa;">
                        <i class="fas fa-image" style="color:#9ca3af;"></i>
                        <span id="nImgLabel" style="font-size:13px;color:#9ca3af;">Choose image (JPG/PNG, max 2MB)</span>
                        <input type="file" id="nImage" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                    </label>
                    <div id="nImgPreview" style="display:none;width:56px;height:56px;border-radius:10px;overflow:hidden;border:1.5px solid #e5e7eb;flex-shrink:0;">
                        <img id="nImgThumb" src="" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <button id="nImgClear" onclick="clearImage()" style="display:none;padding:6px 10px;background:rgba(239,68,68,.1);color:#ef4444;border:none;border-radius:8px;cursor:pointer;font-size:12px;" title="Remove image">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <input type="hidden" id="nExistingImage">
            </div>            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Message *</label>
                <textarea id="nMessage" class="form-control" rows="3" placeholder="e.g. New batch for Web Development starts July 25. Seats are limited — enroll now!"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Badge Text <small style="color:#9ca3af;">(optional)</small></label>
                    <input type="text" id="nBadge" class="form-control" placeholder="e.g. 🔥 New Batch">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Badge Color</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="color" id="nBadgeColor" value="#f07b14" style="width:44px;height:38px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;padding:2px;">
                        <span id="nBadgeColorHex" style="font-size:13px;color:#6b7280;">#f07b14</span>
                    </div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Button Text <small style="color:#9ca3af;">(optional)</small></label>
                    <input type="text" id="nBtnText" class="form-control" placeholder="e.g. Enroll Now">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Button URL</label>
                    <input type="text" id="nBtnUrl" class="form-control" placeholder="e.g. enroll-form.html">
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:14px;font-weight:600;color:#374151;">
                    <input type="checkbox" id="nActive" style="width:18px;height:18px;accent-color:#f07b14;">
                    Show this notice to visitors (activate)
                </label>
                <p style="font-size:12px;color:#9ca3af;margin:4px 0 0 28px;">Only one notice can be active at a time.</p>
            </div>

            <!-- Live Preview -->
            <div style="border:1.5px dashed #e5e7eb;border-radius:12px;padding:16px;margin-bottom:20px;background:#fafafa;">
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9ca3af;margin:0 0 10px;">Live Preview</p>
                <div id="previewBox" style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 16px rgba(0,0,0,0.08);">
                    <span id="prevBadge" style="display:none;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;margin-bottom:10px;display:inline-block;"></span>
                    <h5 id="prevTitle" style="font-size:16px;font-weight:800;color:#0d1b2a;margin:8px 0 6px;"></h5>
                    <p id="prevMsg" style="font-size:13.5px;color:#6b7280;margin:0 0 12px;line-height:1.6;"></p>
                    <a id="prevBtn" href="#" style="display:none;padding:8px 18px;background:#f07b14;color:#fff;border-radius:50px;font-size:13px;font-weight:700;text-decoration:none;">Enroll Now</a>
                </div>
            </div>

            <div id="modalMsg" style="font-size:13px;margin-bottom:12px;display:none;"></div>
            <div style="display:flex;gap:10px;">
                <button onclick="saveNotice()" style="flex:1;padding:12px;background:#f07b14;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;">Save Notice</button>
                <button onclick="closeModal()" style="flex:1;padding:12px;background:#f0f0f0;color:#374151;border:none;border-radius:10px;font-weight:700;cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Notices Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-bell me-2" style="color:#f07b14"></i>All Notices</h5>
    </div>
    <div class="pl-card-body p-0">
        <table class="pl-table" id="noticesTable">
            <thead>
                <tr><th>#</th><th>Title</th><th>Message</th><th>Badge</th><th>Button</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $statusColors = ['1'=>['bg'=>'rgba(16,185,129,0.1)','c'=>'#065f46'], '0'=>['bg'=>'rgba(239,68,68,0.1)','c'=>'#b91c1c']];
            if ($notices && $notices->num_rows > 0):
                while ($n = $notices->fetch_assoc()): ?>
            <tr id="nrow-<?= $n['id'] ?>">
                <td style="color:#9ca3af;font-size:12px;"><?= $n['id'] ?></td>
                <td><strong><?= h($n['title']) ?></strong></td>
                <td style="font-size:12.5px;color:#6b7280;max-width:220px;"><?= h(mb_substr($n['message'], 0, 60)) ?>…</td>
                <td>
                    <?php if ($n['badge']): ?>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= h($n['badge_color']) ?>22;color:<?= h($n['badge_color']) ?>;"><?= h($n['badge']) ?></span>
                    <?php else: ?><span style="color:#d1d5db;">—</span><?php endif; ?>
                </td>
                <td style="font-size:12.5px;"><?= $n['btn_text'] ? h($n['btn_text']) : '<span style="color:#d1d5db;">—</span>' ?></td>
                <td>
                    <button onclick="toggleNotice(<?= $n['id'] ?>, this)" data-active="<?= $n['is_active'] ?>"
                        style="padding:4px 14px;border:none;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;
                               background:<?= $statusColors[$n['is_active']]['bg'] ?>;color:<?= $statusColors[$n['is_active']]['c'] ?>;">
                        <?= $n['is_active'] ? '● Active' : '○ Inactive' ?>
                    </button>
                </td>
                <td>
                    <button onclick='editNotice(<?= json_encode($n) ?>)' style="padding:5px 12px;background:rgba(0,148,217,0.1);color:#0094d9;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;margin-right:4px;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="deleteNotice(<?= $n['id'] ?>)" style="padding:5px 12px;background:rgba(239,68,68,0.1);color:#ef4444;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile;
            else: ?>
            <tr><td colspan="7" style="text-align:center;padding:48px;color:#9ca3af;"><i class="fas fa-bell-slash" style="font-size:28px;display:block;margin-bottom:12px;opacity:0.3;"></i>No notices yet. Create one!</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Image picker
document.getElementById('nImage').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    document.getElementById('nImgLabel').textContent = file.name;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('nImgThumb').src = e.target.result;
        document.getElementById('nImgPreview').style.display = 'block';
        document.getElementById('nImgClear').style.display   = 'block';
    };
    reader.readAsDataURL(file);
    updatePreview();
});

function clearImage() {
    document.getElementById('nImage').value        = '';
    document.getElementById('nImgLabel').textContent = 'Choose image (JPG/PNG, max 2MB)';
    document.getElementById('nImgPreview').style.display = 'none';
    document.getElementById('nImgClear').style.display   = 'none';
    document.getElementById('nExistingImage').value = '';
    updatePreview();
}

// Live preview update
function updatePreview() {
    const title = document.getElementById('nTitle').value;
    const msg   = document.getElementById('nMessage').value;
    const badge = document.getElementById('nBadge').value;
    const color = document.getElementById('nBadgeColor').value;
    const btnT  = document.getElementById('nBtnText').value;
    const btnU  = document.getElementById('nBtnUrl').value;
    const imgSrc = document.getElementById('nImgThumb').src;
    const hasImg = document.getElementById('nImgPreview').style.display !== 'none';

    // Image in preview
    let prevImgEl = document.getElementById('prevImgEl');
    if (hasImg && imgSrc) {
        if (!prevImgEl) {
            prevImgEl = document.createElement('img');
            prevImgEl.id = 'prevImgEl';
            prevImgEl.style = 'width:100%;border-radius:10px;margin-bottom:12px;max-height:140px;object-fit:cover;display:block;';
            document.getElementById('previewBox').prepend(prevImgEl);
        }
        prevImgEl.src = imgSrc;
    } else if (prevImgEl) {
        prevImgEl.remove();
    }

    document.getElementById('prevTitle').textContent = title || 'Notice Title';
    document.getElementById('prevMsg').textContent   = msg   || 'Notice message will appear here...';

    const prevBadge = document.getElementById('prevBadge');
    if (badge) {
        prevBadge.textContent   = badge;
        prevBadge.style.display = 'inline-block';
        prevBadge.style.background = color + '22';
        prevBadge.style.color   = color;
    } else {
        prevBadge.style.display = 'none';
    }

    const prevBtn = document.getElementById('prevBtn');
    if (btnT) {
        prevBtn.textContent     = btnT;
        prevBtn.href            = btnU || '#';
        prevBtn.style.display   = 'inline-block';
        prevBtn.style.background = color;
    } else {
        prevBtn.style.display = 'none';
    }

    document.getElementById('nBadgeColorHex').textContent = color;
}

['nTitle','nMessage','nBadge','nBtnText','nBtnUrl'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePreview);
});
document.getElementById('nBadgeColor').addEventListener('input', updatePreview);

function openModal() {
    document.getElementById('modalTitle').textContent  = 'New Notice';
    document.getElementById('nId').value               = '';
    document.getElementById('nTitle').value            = '';
    document.getElementById('nMessage').value          = '';
    document.getElementById('nBadge').value            = '';
    document.getElementById('nBadgeColor').value       = '#f07b14';
    document.getElementById('nBtnText').value          = '';
    document.getElementById('nBtnUrl').value           = '';
    document.getElementById('nActive').checked         = true;
    document.getElementById('modalMsg').style.display  = 'none';
    clearImage();
    updatePreview();
    document.getElementById('noticeModal').style.display = 'flex';
}

function editNotice(n) {
    document.getElementById('modalTitle').textContent   = 'Edit Notice';
    document.getElementById('nId').value                = n.id;
    document.getElementById('nTitle').value             = n.title;
    document.getElementById('nMessage').value           = n.message;
    document.getElementById('nBadge').value             = n.badge  || '';
    document.getElementById('nBadgeColor').value        = n.badge_color || '#f07b14';
    document.getElementById('nBtnText').value           = n.btn_text || '';
    document.getElementById('nBtnUrl').value            = n.btn_url  || '';
    document.getElementById('nActive').checked          = n.is_active == 1;
    document.getElementById('nExistingImage').value     = n.image   || '';
    document.getElementById('modalMsg').style.display   = 'none';

    // Show existing image if present
    if (n.image) {
        document.getElementById('nImgThumb').src            = '../uploads/' + n.image;
        document.getElementById('nImgPreview').style.display = 'block';
        document.getElementById('nImgClear').style.display   = 'block';
        document.getElementById('nImgLabel').textContent     = n.image;
    } else {
        clearImage();
    }

    updatePreview();
    document.getElementById('noticeModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('noticeModal').style.display = 'none';
}

function saveNotice() {
    const id      = document.getElementById('nId').value;
    const title   = document.getElementById('nTitle').value.trim();
    const message = document.getElementById('nMessage').value.trim();
    const msg     = document.getElementById('modalMsg');

    if (!title || !message) {
        msg.textContent = 'Title and message are required.';
        msg.style.color = '#ef4444'; msg.style.display = 'block'; return;
    }

    const fd = new FormData();
    fd.append('action',         id ? 'edit' : 'add');
    if (id) fd.append('id',     id);
    fd.append('title',          title);
    fd.append('message',        message);
    fd.append('badge',          document.getElementById('nBadge').value.trim());
    fd.append('badge_color',    document.getElementById('nBadgeColor').value);
    fd.append('btn_text',       document.getElementById('nBtnText').value.trim());
    fd.append('btn_url',        document.getElementById('nBtnUrl').value.trim());
    fd.append('is_active',      document.getElementById('nActive').checked ? 1 : 0);
    fd.append('existing_image', document.getElementById('nExistingImage').value);

    // Attach new image file if selected
    const imgFile = document.getElementById('nImage').files[0];
    if (imgFile) fd.append('notice_image', imgFile);

    fetch('notices.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { closeModal(); location.reload(); }
            else { msg.textContent = data.message; msg.style.color = '#ef4444'; msg.style.display = 'block'; }
        })
        .catch(() => { msg.textContent = 'Network error.'; msg.style.color='#ef4444'; msg.style.display='block'; });
}

function toggleNotice(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fetch('notices.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
}

function deleteNotice(id) {
    if (!confirm('Delete this notice?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('notices.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => { if (data.success) document.getElementById('nrow-' + id).remove(); });
}

document.getElementById('noticeModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php admin_foot(); ?>
    const title = document.getElementById('nTitle').value;
    const msg   = document.getElementById('nMessage').value;
    const badge = document.getElementById('nBadge').value;
    const color = document.getElementById('nBadgeColor').value;
    const btnT  = document.getElementById('nBtnText').value;
    const btnU  = document.getElementById('nBtnUrl').value;

    document.getElementById('prevTitle').textContent = title || 'Notice Title';
    document.getElementById('prevMsg').textContent   = msg   || 'Notice message will appear here...';

    const prevBadge = document.getElementById('prevBadge');
    if (badge) {
        prevBadge.textContent    = badge;
        prevBadge.style.display  = 'inline-block';
        prevBadge.style.background = color + '22';
        prevBadge.style.color    = color;
    } else {
        prevBadge.style.display = 'none';
    }

    const prevBtn = document.getElementById('prevBtn');
    if (btnT) {
        prevBtn.textContent  = btnT;
        prevBtn.href         = btnU || '#';
        prevBtn.style.display = 'inline-block';
        prevBtn.style.background = color;
    } else {
        prevBtn.style.display = 'none';
    }

    document.getElementById('nBadgeColorHex').textContent = color;
}

['nTitle','nMessage','nBadge','nBtnText','nBtnUrl'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePreview);
});
document.getElementById('nBadgeColor').addEventListener('input', updatePreview);

function openModal() {
    document.getElementById('modalTitle').textContent = 'New Notice';
    document.getElementById('nId').value       = '';
    document.getElementById('nTitle').value    = '';
    document.getElementById('nMessage').value  = '';
    document.getElementById('nBadge').value    = '';
    document.getElementById('nBadgeColor').value = '#f07b14';
    document.getElementById('nBtnText').value  = '';
    document.getElementById('nBtnUrl').value   = '';
    document.getElementById('nActive').checked = true;
    document.getElementById('modalMsg').style.display = 'none';
    updatePreview();
    document.getElementById('noticeModal').style.display = 'flex';
}

function editNotice(n) {
    document.getElementById('modalTitle').textContent    = 'Edit Notice';
    document.getElementById('nId').value        = n.id;
    document.getElementById('nTitle').value     = n.title;
    document.getElementById('nMessage').value   = n.message;
    document.getElementById('nBadge').value     = n.badge || '';
    document.getElementById('nBadgeColor').value = n.badge_color || '#f07b14';
    document.getElementById('nBtnText').value   = n.btn_text || '';
    document.getElementById('nBtnUrl').value    = n.btn_url || '';
    document.getElementById('nActive').checked  = n.is_active == 1;
    document.getElementById('modalMsg').style.display = 'none';
    updatePreview();
    document.getElementById('noticeModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('noticeModal').style.display = 'none';
}

function saveNotice() {
    const id      = document.getElementById('nId').value;
    const title   = document.getElementById('nTitle').value.trim();
    const message = document.getElementById('nMessage').value.trim();
    const msg     = document.getElementById('modalMsg');

    if (!title || !message) {
        msg.textContent = 'Title and message are required.';
        msg.style.color = '#ef4444';
        msg.style.display = 'block';
        return;
    }

    const fd = new FormData();
    fd.append('action',      id ? 'edit' : 'add');
    if (id) fd.append('id', id);
    fd.append('title',       title);
    fd.append('message',     message);
    fd.append('badge',       document.getElementById('nBadge').value.trim());
    fd.append('badge_color', document.getElementById('nBadgeColor').value);
    fd.append('btn_text',    document.getElementById('nBtnText').value.trim());
    fd.append('btn_url',     document.getElementById('nBtnUrl').value.trim());
    fd.append('is_active',   document.getElementById('nActive').checked ? 1 : 0);

    fetch('notices.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) { closeModal(); location.reload(); }
            else { msg.textContent = data.message; msg.style.color='#ef4444'; msg.style.display='block'; }
        });
}

function toggleNotice(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fetch('notices.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
}

function deleteNotice(id) {
    if (!confirm('Delete this notice?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('notices.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) document.getElementById('nrow-' + id).remove();
        });
}

document.getElementById('noticeModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php admin_foot(); ?>
