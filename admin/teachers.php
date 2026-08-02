<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// ── AJAX Handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {

        case 'add':
        case 'edit':
            $full_name = trim($_POST['full_name'] ?? '');
            $username  = trim($_POST['username']  ?? '');
            $email     = trim($_POST['email']     ?? '');
            $phone     = trim($_POST['phone']     ?? '');
            $subject   = trim($_POST['subject']   ?? '');
            $bio       = trim($_POST['bio']       ?? '');
            $is_active = (int)($_POST['is_active'] ?? 1);
            $password  = trim($_POST['password']  ?? '');

            if (!$full_name || !$username) {
                echo json_encode(['success'=>false,'message'=>'Name and username are required.']); exit;
            }
            if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
                echo json_encode(['success'=>false,'message'=>'Username: only letters, numbers, underscore. 3-30 chars.']); exit;
            }

            if ($_POST['action'] === 'add') {
                if (!$password || strlen($password) < 6) {
                    echo json_encode(['success'=>false,'message'=>'Password minimum 6 characters required.']); exit;
                }
                // Check duplicate username
                $chk = $conn->prepare("SELECT id FROM teacher_users WHERE username=?");
                $chk->bind_param('s', $username);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    echo json_encode(['success'=>false,'message'=>'Username already taken.']); exit;
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "INSERT INTO teacher_users (full_name,username,password_hash,email,phone,subject,bio,is_active,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?)"
                );
                $admin = $_SESSION['admin_username'] ?? 'admin';
                $stmt->bind_param('sssssssss', $full_name,$username,$hash,$email,$phone,$subject,$bio,$is_active,$admin);
            } else {
                $id = (int)($_POST['id'] ?? 0);
                if ($password && strlen($password) < 6) {
                    echo json_encode(['success'=>false,'message'=>'Password minimum 6 characters.']); exit;
                }
                // Check duplicate username (exclude self)
                $chk = $conn->prepare("SELECT id FROM teacher_users WHERE username=? AND id!=?");
                $chk->bind_param('si', $username, $id);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    echo json_encode(['success'=>false,'message'=>'Username already taken.']); exit;
                }
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare(
                        "UPDATE teacher_users SET full_name=?,username=?,password_hash=?,email=?,phone=?,subject=?,bio=?,is_active=? WHERE id=?"
                    );
                    $stmt->bind_param('sssssssii', $full_name,$username,$hash,$email,$phone,$subject,$bio,$is_active,$id);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE teacher_users SET full_name=?,username=?,email=?,phone=?,subject=?,bio=?,is_active=? WHERE id=?"
                    );
                    $stmt->bind_param('ssssssii', $full_name,$username,$email,$phone,$subject,$bio,$is_active,$id);
                }
            }
            $ok = $stmt->execute();
            log_activity($conn, $_POST['action']==='add'?'ADD_TEACHER':'EDIT_TEACHER', "Teacher: $username");
            echo json_encode(['success'=>$ok,'id'=>$conn->insert_id,'message'=>$ok?'Saved.':$stmt->error]);
            exit;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM teacher_users WHERE id=?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            log_activity($conn, 'DELETE_TEACHER', "Teacher ID: $id");
            echo json_encode(['success'=>$ok]);
            exit;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID.']); exit; }
            // Prepared statement — consistent with rest of file (Issue #5)
            $cur = $conn->prepare("SELECT is_active FROM teacher_users WHERE id=?");
            $cur->bind_param('i', $id);
            $cur->execute();
            $row = $cur->get_result()->fetch_assoc();
            $cur->close();
            if (!$row) { echo json_encode(['success'=>false,'message'=>'Teacher not found.']); exit; }
            $new_state = $row['is_active'] ? 0 : 1;
            $tog = $conn->prepare("UPDATE teacher_users SET is_active=? WHERE id=?");
            $tog->bind_param('ii', $new_state, $id);
            $tog->execute();
            $tog->close();
            echo json_encode(['success'=>true,'is_active'=>$new_state]);
            exit;
    }
    exit;
}

// ── Load data ─────────────────────────────────────────────────
$teachers = $conn->query(
    "SELECT t.*, 
        (SELECT COUNT(*) FROM teacher_assignments ta WHERE ta.teacher_id=t.id) as student_count
     FROM teacher_users t ORDER BY t.created_at DESC"
);
log_activity($conn, 'VIEW_TEACHERS', 'Viewed teachers list');
$conn->close();

admin_head('Teachers', 'teachers');
?>

<div class="pl-page-header">
    <div>
        <h1>Teachers</h1>
        <div class="pl-breadcrumb">Manage → <span>Teacher Accounts</span></div>
    </div>
    <button onclick="openModal()" style="background:#f07b14;border:none;border-radius:10px;padding:10px 22px;font-weight:700;color:#fff;cursor:pointer;">
        <i class="fas fa-plus me-2"></i>Add Teacher
    </button>
</div>

<!-- Teachers Grid -->
<div class="row g-3" id="teachersGrid">
<?php if ($teachers && $teachers->num_rows > 0):
    while ($t = $teachers->fetch_assoc()):
        $initials = strtoupper(implode('', array_map(fn($w)=>$w[0]??'', array_slice(explode(' ', trim($t['full_name'])), 0, 2))));
?>
<div class="col-md-6 col-xl-4" id="tcard-<?= $t['id'] ?>">
    <div style="background:#fff;border-radius:16px;border:1.5px solid <?= $t['is_active'] ? '#eef0f4' : '#fecaca' ?>;padding:22px;transition:box-shadow .2s;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <!-- Avatar -->
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#f07b14,#d96a00);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:800;flex-shrink:0;">
                <?= h($initials) ?>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:15px;font-weight:800;color:#0d1b2a;"><?= h($t['full_name']) ?></div>
                <div style="font-size:12.5px;color:#6b7280;">@<?= h($t['username']) ?></div>
                <?php if ($t['subject']): ?>
                <div style="margin-top:5px;">
                    <span style="background:rgba(240,123,20,.1);color:#c05c00;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;">
                        <?= h($t['subject']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <!-- Status toggle -->
            <button onclick="toggleTeacher(<?= $t['id'] ?>, this)"
                data-active="<?= $t['is_active'] ?>"
                style="padding:4px 12px;border:none;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;flex-shrink:0;
                    background:<?= $t['is_active'] ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.1)' ?>;
                    color:<?= $t['is_active'] ? '#059669' : '#dc2626' ?>;">
                <?= $t['is_active'] ? '● Active' : '○ Inactive' ?>
            </button>
        </div>

        <!-- Info rows -->
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f3f4f6;display:flex;flex-direction:column;gap:7px;">
            <?php if ($t['email']): ?>
            <div style="font-size:12.5px;color:#6b7280;display:flex;align-items:center;gap:7px;">
                <i class="fas fa-envelope" style="width:14px;color:#9ca3af;"></i><?= h($t['email']) ?>
            </div>
            <?php endif; ?>
            <?php if ($t['phone']): ?>
            <div style="font-size:12.5px;color:#6b7280;display:flex;align-items:center;gap:7px;">
                <i class="fas fa-phone" style="width:14px;color:#9ca3af;"></i><?= h($t['phone']) ?>
            </div>
            <?php endif; ?>
            <div style="font-size:12.5px;color:#6b7280;display:flex;align-items:center;gap:7px;">
                <i class="fas fa-users" style="width:14px;color:#9ca3af;"></i>
                <strong style="color:#0d1b2a;"><?= (int)$t['student_count'] ?></strong>&nbsp;students assigned
            </div>
            <div style="font-size:11.5px;color:#9ca3af;">
                Added: <?= date('d M Y', strtotime($t['created_at'])) ?>
            </div>
        </div>

        <!-- Actions -->
        <div style="margin-top:14px;display:flex;gap:8px;">
            <button onclick='editTeacher(<?= json_encode($t) ?>)'
                style="flex:1;padding:9px;background:rgba(0,148,217,.08);color:#0094d9;border:none;border-radius:9px;font-size:12.5px;font-weight:700;cursor:pointer;">
                <i class="fas fa-edit me-1"></i>Edit
            </button>
            <a href="teacher_assignments.php?teacher_id=<?= $t['id'] ?>"
                style="flex:1;padding:9px;background:rgba(139,92,246,.08);color:#6d28d9;border:none;border-radius:9px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;text-align:center;">
                <i class="fas fa-link me-1"></i>Assign Students
            </a>
            <button onclick="deleteTeacher(<?= $t['id'] ?>)"
                style="width:36px;height:36px;background:rgba(239,68,68,.08);color:#ef4444;border:none;border-radius:9px;cursor:pointer;">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
<?php endwhile;
else: ?>
<div class="col-12">
    <div class="pl-empty">
        <i class="fas fa-chalkboard-teacher"></i>
        <p>No teachers yet. Add your first teacher!</p>
    </div>
</div>
<?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div id="teacherModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;padding:16px;overflow-y:auto;">
<div style="background:#fff;border-radius:18px;width:100%;max-width:520px;margin:auto;">
    <div style="padding:22px 26px 18px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <h4 id="tModalTitle" style="margin:0;font-weight:800;color:#0d1b2a;">Add Teacher</h4>
        <button onclick="closeModal()" style="border:none;background:#f5f5f5;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:16px;">✕</button>
    </div>
    <div style="padding:22px 26px;">
        <input type="hidden" id="tId">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div style="grid-column:1/-1;">
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Full Name *</label>
                <input type="text" id="tName" class="form-control" placeholder="Enter full name">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Username * <small style="color:#9ca3af;">(login ID)</small></label>
                <input type="text" id="tUsername" class="form-control" placeholder="Enter username">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Password <span id="tPassLabel" style="color:#9ca3af;">(min 6 chars)</span></label>
                <div style="position:relative;">
                    <input type="password" id="tPassword" class="form-control" placeholder="Set password">
                    <button type="button" onclick="togglePass()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);border:none;background:none;color:#9ca3af;cursor:pointer;">
                        <i class="fas fa-eye" id="tPassEye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Subject / Expertise</label>
                <input type="text" id="tSubject" class="form-control" placeholder="Enter subject or expertise">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Email</label>
                <input type="email" id="tEmail" class="form-control" placeholder="Enter email address">
            </div>
            <div>
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Phone</label>
                <input type="text" id="tPhone" class="form-control" placeholder="Enter phone number">
            </div>
            <div style="grid-column:1/-1;">
                <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Bio <small style="color:#9ca3af;">(optional)</small></label>
                <textarea id="tBio" class="form-control" rows="2" placeholder="Enter a short bio"></textarea>
            </div>
            <div style="grid-column:1/-1;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;font-weight:600;color:#374151;">
                    <input type="checkbox" id="tActive" style="width:16px;height:16px;accent-color:#10b981;" checked>
                    Active (teacher can login)
                </label>
            </div>
        </div>

        <!-- Credentials preview box — Issue #11: mask password, add copy -->
        <div id="credBox" style="display:none;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:14px 18px;margin-top:16px;">
            <div style="font-size:12px;font-weight:700;color:#15803d;margin-bottom:8px;">
                <i class="fas fa-key me-1"></i>Login Credentials — Share with Teacher
            </div>
            <div style="font-family:monospace;font-size:13px;color:#166534;line-height:2.2;">
                <div>Portal URL: <strong><?= isset($_SERVER['HTTP_HOST']) ? htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].'/pl/teacher-login.php') : 'teacher-login.php' ?></strong></div>
                <div style="display:flex;align-items:center;gap:8px;">
                    Username: <strong id="credUser">—</strong>
                    <button type="button" onclick="copyCredText('credUser')" style="padding:2px 8px;border:1px solid #bbf7d0;border-radius:6px;background:#dcfce7;color:#15803d;font-size:11px;cursor:pointer;">Copy</button>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    Password:
                    <span id="credPassMask" style="letter-spacing:3px;">••••••••</span>
                    <button type="button" onclick="toggleCredPass()" style="padding:2px 8px;border:1px solid #bbf7d0;border-radius:6px;background:#dcfce7;color:#15803d;font-size:11px;cursor:pointer;" id="credRevealBtn">
                        <i class="fas fa-eye"></i> Show
                    </button>
                    <span id="credPass" style="display:none;"></span>
                    <button type="button" onclick="copyCredText('credPass')" style="padding:2px 8px;border:1px solid #bbf7d0;border-radius:6px;background:#dcfce7;color:#15803d;font-size:11px;cursor:pointer;">Copy</button>
                </div>
            </div>
            <div style="font-size:11.5px;color:#166534;margin-top:8px;opacity:.7;">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Copy these now — password won't be shown again after this modal closes.
            </div>
        </div>

        <div id="tMsg" style="display:none;padding:10px 14px;border-radius:8px;font-size:13px;margin-top:14px;"></div>

        <div style="display:flex;gap:10px;margin-top:18px;">
            <button onclick="saveTeacher()" style="flex:1;padding:12px;background:#f07b14;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                <i class="fas fa-save me-1"></i>Save Teacher
            </button>
            <button onclick="closeModal()" style="flex:1;padding:12px;background:#f0f0f0;color:#374151;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">Cancel</button>
        </div>
    </div>
</div>
</div>

<script>
function openModal(editMode=false) {
    document.getElementById('tModalTitle').textContent = editMode ? 'Edit Teacher' : 'Add Teacher';
    document.getElementById('tPassLabel').textContent  = editMode ? '(leave blank to keep)' : '(min 6 chars)';
    document.getElementById('tMsg').style.display      = 'none';
    document.getElementById('credBox').style.display   = 'none';
    if (!editMode) {
        document.getElementById('tId').value       = '';
        document.getElementById('tName').value     = '';
        document.getElementById('tUsername').value = '';
        document.getElementById('tPassword').value = '';
        document.getElementById('tSubject').value  = '';
        document.getElementById('tEmail').value    = '';
        document.getElementById('tPhone').value    = '';
        document.getElementById('tBio').value      = '';
        document.getElementById('tActive').checked = true;
    }
    document.getElementById('teacherModal').style.display = 'flex';
}
function closeModal() {
    const credVisible = document.getElementById('credBox').style.display !== 'none';
    document.getElementById('teacherModal').style.display = 'none';
    if (credVisible) location.reload(); // refresh to show new teacher in list
}
function togglePass() {
    const inp = document.getElementById('tPassword');
    const eye = document.getElementById('tPassEye');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

function editTeacher(t) {
    openModal(true);
    document.getElementById('tId').value       = t.id;
    document.getElementById('tName').value     = t.full_name;
    document.getElementById('tUsername').value = t.username;
    document.getElementById('tPassword').value = '';
    document.getElementById('tSubject').value  = t.subject  || '';
    document.getElementById('tEmail').value    = t.email    || '';
    document.getElementById('tPhone').value    = t.phone    || '';
    document.getElementById('tBio').value      = t.bio      || '';
    document.getElementById('tActive').checked = t.is_active == 1;
}

function saveTeacher() {
    const id       = document.getElementById('tId').value;
    const name     = document.getElementById('tName').value.trim();
    const username = document.getElementById('tUsername').value.trim();
    const password = document.getElementById('tPassword').value.trim();
    const msg      = document.getElementById('tMsg');

    if (!name || !username) {
        showMsg('Name and username are required.', '#ef4444'); return;
    }

    const fd = new FormData();
    fd.append('action',    id ? 'edit' : 'add');
    if (id) fd.append('id', id);
    fd.append('full_name', name);
    fd.append('username',  username);
    fd.append('password',  password);
    fd.append('subject',   document.getElementById('tSubject').value.trim());
    fd.append('email',     document.getElementById('tEmail').value.trim());
    fd.append('phone',     document.getElementById('tPhone').value.trim());
    fd.append('bio',       document.getElementById('tBio').value.trim());
    fd.append('is_active', document.getElementById('tActive').checked ? 1 : 0);

    fetch('teachers.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Show credentials for new teacher — Issue #11: masked
                if (!id && password) {
                    document.getElementById('credUser').textContent = username;
                    document.getElementById('credPass').textContent = password;
                    document.getElementById('credPassMask').style.display = 'inline';
                    document.getElementById('credPass').style.display = 'none';
                    document.getElementById('credRevealBtn').innerHTML = '<i class="fas fa-eye"></i> Show';
                    document.getElementById('credBox').style.display = 'block';
                    showMsg('Teacher added! Copy credentials before closing.', '#10b981');
                    // Don't auto-close — let admin copy credentials first
                } else {
                    closeModal(); location.reload();
                }
            } else {
                showMsg(data.message || 'Error saving.', '#ef4444');
            }
        })
        .catch(() => showMsg('Network error.', '#ef4444'));
}

function showMsg(text, color) {
    const el = document.getElementById('tMsg');
    el.textContent = text;
    el.style.color = color;
    el.style.background = color + '15';
    el.style.border = '1px solid ' + color + '40';
    el.style.display = 'block';
}

// Issue #11: mask password in DOM, provide copy + toggle reveal
function toggleCredPass() {
    const mask = document.getElementById('credPassMask');
    const real = document.getElementById('credPass');
    const btn  = document.getElementById('credRevealBtn');
    if (real.style.display === 'none') {
        real.style.display = 'inline';
        mask.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
    } else {
        real.style.display = 'none';
        mask.style.display = 'inline';
        btn.innerHTML = '<i class="fas fa-eye"></i> Show';
    }
}

function copyCredText(elId) {
    const text = document.getElementById(elId).textContent.trim();
    if (!text || text === '—') return;
    navigator.clipboard?.writeText(text).then(() => {
        const tmp = document.createElement('span');
        tmp.textContent = ' ✓';
        tmp.style.color = '#15803d';
        tmp.style.fontSize = '12px';
        const btn = event.target.closest('button');
        btn.after(tmp);
        setTimeout(() => tmp.remove(), 1500);
    }).catch(() => {
        // Fallback for HTTP
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    });
}

function toggleTeacher(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fetch('teachers.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.dataset.active = data.is_active;
                btn.textContent    = data.is_active ? '● Active' : '○ Inactive';
                btn.style.background = data.is_active ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.1)';
                btn.style.color      = data.is_active ? '#059669' : '#dc2626';
            }
        });
}

function deleteTeacher(id) {
    if (!confirm('Delete this teacher? Their student assignments will also be removed.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('teachers.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) document.getElementById('tcard-' + id).remove();
        });
}

document.getElementById('teacherModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php admin_foot(); ?>
