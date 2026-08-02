<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// ── AJAX: status update ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $allowed = ['pending','approved','rejected','in_progress','completed'];
        if (!$id || !in_array($status, $allowed)) {
            echo json_encode(['success'=>false,'message'=>'Invalid input.']); exit;
        }
        $stmt = $conn->prepare("UPDATE enroll SET enrollment_status=? WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok, 'message'=> $ok ? 'Status updated.' : $stmt->error]);
        exit;
    }

    if ($_POST['action'] === 'add_student') {
        $fullName   = trim($_POST['full_name']   ?? '');
        $fatherName = trim($_POST['father_name'] ?? '');
        $email      = trim($_POST['email']       ?? '');
        $phone      = trim($_POST['phone']       ?? '');
        $gender     = trim($_POST['gender']      ?? '');
        $cnic       = trim($_POST['cnic_number'] ?? '');
        $course     = trim($_POST['course_interest'] ?? '');
        $studyMode  = trim($_POST['study_mode']  ?? '');
        $notes      = trim($_POST['notes']       ?? '');
        $status     = trim($_POST['enrollment_status'] ?? 'approved');

        if (!$fullName || !$phone || !$course) {
            echo json_encode(['success'=>false,'message'=>'Name, phone and course are required.']); exit;
        }
        if ($cnic && !preg_match('/^\d{5}-\d{7}-\d$/', $cnic)) {
            echo json_encode(['success'=>false,'message'=>'Invalid CNIC format. Use: XXXXX-XXXXXXX-X']); exit;
        }
        $stmt = $conn->prepare(
            "INSERT INTO enroll (full_name,father_name,email,phone,gender,cnic_number,
             course_interest,study_mode,notes,enrollment_status,added_by)
             VALUES (?,?,?,?,?,?,?,?,?,'approved','admin')"
        );
        $stmt->bind_param('sssssssss',$fullName,$fatherName,$email,$phone,$gender,$cnic,$course,$studyMode,$notes);
        $ok = $stmt->execute();
        echo json_encode(['success'=>$ok,'id'=>$conn->insert_id,'message'=>$ok?'Student added.':$stmt->error]);
        exit;
    }
    exit;
}

// ── Load data ────────────────────────────────────────────────
$filter = $_GET['status'] ?? 'all';
$allowed_filters = ['all','pending','approved','rejected','in_progress','completed'];
if (!in_array($filter, $allowed_filters)) $filter = 'all';

$sql = "SELECT * FROM enroll";
if ($filter !== 'all') {
    if ($filter === 'pending') {
        // pending includes NULL and empty enrollment_status
        $sql .= " WHERE (enrollment_status = 'pending' OR enrollment_status IS NULL OR enrollment_status = '')";
    } else {
        $sql .= " WHERE enrollment_status = '" . $conn->real_escape_string($filter) . "'";
    }
}
$sql .= " ORDER BY id DESC";
$enrollments = $conn->query($sql);

// Counts per status — treat NULL/empty as pending
$counts = [];
$cr = $conn->query("SELECT IFNULL(NULLIF(enrollment_status,''),'pending') as st, COUNT(*) as c FROM enroll GROUP BY st");
while ($r = $cr->fetch_assoc()) $counts[$r['st']] = (int)$r['c'];
$counts['all'] = array_sum($counts);

// Courses for add modal dropdown
$courses_res = $conn->query("SELECT name, category FROM courses WHERE is_active=1 ORDER BY category, sort_order, name");
$courses_grouped = [];
while ($c = $courses_res->fetch_assoc()) $courses_grouped[$c['category']][] = $c['name'];

log_activity($conn, 'VIEW_ENROLLMENTS', 'Viewed enrollments');
$conn->close();
$csrf = csrf_token();

admin_head('Enrollments', 'enrollments');
?>

<div class="pl-page-header">
    <div>
        <h1>Enrollments</h1>
        <div class="pl-breadcrumb">Manage → <span>Student Enrollments</span></div>
    </div>
    <button onclick="openAddModal()" style="background:#f07b14;border:none;border-radius:10px;padding:10px 22px;font-weight:700;color:#fff;cursor:pointer;">
        <i class="fas fa-plus me-2"></i>Add Student
    </button>
</div>

<!-- Status filter tabs -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
<?php
$tabs = [
    'all'         => ['All',         '#6366f1', 'fas fa-list'],
    'pending'     => ['Pending',     '#f59e0b', 'fas fa-clock'],
    'approved'    => ['Approved',    '#10b981', 'fas fa-check'],
    'in_progress' => ['In Progress', '#0094d9', 'fas fa-spinner'],
    'completed'   => ['Completed',   '#8b5cf6', 'fas fa-graduation-cap'],
    'rejected'    => ['Rejected',    '#ef4444', 'fas fa-times'],
];
foreach ($tabs as $key => [$label, $color, $icon]):
    $active = $filter === $key;
    $cnt    = $counts[$key] ?? 0;
?>
<a href="?status=<?= $key ?>" style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:50px;font-size:13px;font-weight:700;text-decoration:none;
    background:<?= $active ? $color : 'rgba(0,0,0,0.04)' ?>;
    color:<?= $active ? '#fff' : '#6b7280' ?>;
    border:2px solid <?= $active ? $color : 'transparent' ?>;">
    <i class="<?= $icon ?>"></i> <?= $label ?>
    <span style="background:<?= $active ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.08)' ?>;padding:1px 7px;border-radius:20px;font-size:11px;"><?= $cnt ?></span>
</a>
<?php endforeach; ?>
</div>

<!-- Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-user-graduate me-2" style="color:#10b981"></i>
            <?= $filter === 'all' ? 'All Enrollments' : ucfirst(str_replace('_',' ',$filter)) ?>
        </h5>
        <span style="font-size:13px;color:#9ca3af;"><?= $enrollments->num_rows ?> record(s)</span>
    </div>
    <div class="pl-card-body p-0">
    <?php if ($enrollments->num_rows > 0): ?>
    <table class="pl-table">
        <thead><tr>
            <th>Student</th><th>CNIC</th><th>Course</th>
            <th>Mode</th><th>Source</th><th>Status</th><th>Date</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php while ($row = $enrollments->fetch_assoc()):
            // Normalize status — live server may have NULL or empty
            $st = trim($row['enrollment_status'] ?? '');
            if (!$st || $st === '0') $st = 'pending';

            $stColor = ['pending'=>'#f59e0b','approved'=>'#10b981','rejected'=>'#ef4444','in_progress'=>'#0094d9','completed'=>'#8b5cf6'];
            $stBg    = ['pending'=>'rgba(245,158,11,.12)','approved'=>'rgba(16,185,129,.12)','rejected'=>'rgba(239,68,68,.12)','in_progress'=>'rgba(0,148,217,.12)','completed'=>'rgba(139,92,246,.12)'];
            $stIcon  = ['pending'=>'fa-clock','approved'=>'fa-check-circle','rejected'=>'fa-times-circle','in_progress'=>'fa-play-circle','completed'=>'fa-graduation-cap'];
            $stLabel = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','in_progress'=>'In Progress','completed'=>'Completed'];

            // Fallback for unknown status values
            if (!array_key_exists($st, $stColor)) {
                $st = 'pending';
            }
        ?>
        <tr id="erow-<?= $row['id'] ?>">
            <td>
                <div style="display:flex;align-items:center;gap:9px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700;flex-shrink:0;">
                        <?= strtoupper(substr(h($row['full_name']),0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13.5px;"><?= h($row['full_name']) ?></div>
                        <div style="font-size:11.5px;color:#9ca3af;"><?= h($row['phone']) ?></div>
                    </div>
                </div>
            </td>
            <td style="font-family:monospace;font-size:12px;color:#374151;">
                <?= $row['cnic_number'] ? h($row['cnic_number']) : '<span style="color:#d1d5db;">—</span>' ?>
            </td>
            <td><span class="pl-badge pl-badge-green" style="font-size:11.5px;"><?= h(mb_substr($row['course_interest'],0,24)) ?></span></td>
            <td style="font-size:12px;color:#6b7280;"><?= h($row['study_mode'] ?: '—') ?></td>
            <td>
                <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:5px;
                    background:<?= $row['added_by']==='admin' ? 'rgba(99,102,241,.1)' : 'rgba(16,185,129,.1)' ?>;
                    color:<?= $row['added_by']==='admin' ? '#6366f1' : '#10b981' ?>;">
                    <i class="fas <?= $row['added_by']==='admin' ? 'fa-user-shield' : 'fa-globe' ?>"></i>
                    <?= $row['added_by']==='admin' ? 'Admin' : 'Online' ?>
                </span>
            </td>

            <!-- Status badge -->
            <td id="st-cell-<?= $row['id'] ?>">
                <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;
                    background:<?= $stBg[$st] ?? $stBg['pending'] ?>;
                    color:<?= $stColor[$st] ?? $stColor['pending'] ?>;">
                    <i class="fas <?= $stIcon[$st] ?? 'fa-clock' ?>"></i>
                    <?= $stLabel[$st] ?? ucfirst($st) ?>
                </span>
            </td>

            <td style="color:#9ca3af;font-size:12px;white-space:nowrap;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>

            <!-- Action buttons: simple forward flow -->
            <td id="ac-cell-<?= $row['id'] ?>">
                <div style="display:flex;align-items:center;gap:5px;flex-wrap:nowrap;">
                    <?php if ($st === 'pending'): ?>
                        <button onclick="updateStatus(<?= $row['id'] ?>, 'approved', this)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button onclick="updateStatus(<?= $row['id'] ?>, 'rejected', this)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    <?php elseif ($st === 'approved'): ?>
                        <button onclick="updateStatus(<?= $row['id'] ?>, 'in_progress', this)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#0094d9;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-play"></i> In Progress
                        </button>
                    <?php elseif ($st === 'in_progress'): ?>
                        <button onclick="updateStatus(<?= $row['id'] ?>, 'completed', this)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#8b5cf6;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-graduation-cap"></i> Complete
                        </button>
                    <?php elseif ($st === 'completed'): ?>
                        <span style="font-size:12px;color:#8b5cf6;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                            <i class="fas fa-check-double"></i> Done
                        </span>
                    <?php elseif ($st === 'rejected'): ?>
                        <button onclick="updateStatus(<?= $row['id'] ?>, 'pending', this)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                            <i class="fas fa-redo"></i> Restore
                        </button>
                    <?php endif; ?>
                    <button class="pl-btn-icon pl-btn-view" onclick="viewEnrollment(<?= $row['id'] ?>)" title="View"><i class="fas fa-eye"></i></button>
                    <button class="pl-btn-icon pl-btn-del" onclick="deleteEnrollment(<?= $row['id'] ?>, this)" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="pl-empty"><i class="fas fa-user-graduate"></i><p>No enrollments found</p></div>
    <?php endif; ?>
    </div>
</div>

<!-- Add Student Modal -->
<div id="addStudentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;overflow-y:auto;padding:20px;">
<div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:600px;margin:auto;">
    <h3 style="margin:0 0 6px;font-weight:800;color:#0d1b2a;"><i class="fas fa-user-plus me-2" style="color:#f07b14;"></i>Add Student Manually</h3>
    <p style="font-size:13px;color:#6b7280;margin:0 0 24px;">Add existing students from paper records to the system.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Full Name *</label>
            <input type="text" id="as_name" class="form-control" placeholder="Student full name">
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Father's Name</label>
            <input type="text" id="as_father" class="form-control" placeholder="Father's name">
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Phone *</label>
            <input type="text" id="as_phone" class="form-control" placeholder="03XX-XXXXXXX">
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Email</label>
            <input type="email" id="as_email" class="form-control" placeholder="email@example.com">
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">CNIC Number</label>
            <input type="text" id="as_cnic" class="form-control" placeholder="12345-6789012-3" maxlength="15">
            <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Format: XXXXX-XXXXXXX-X</div>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Gender</label>
            <select id="as_gender" class="form-control">
                <option value="">— Select —</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Prefer not to say">Other</option>
            </select>
        </div>
        <div style="grid-column:1/-1;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Course *</label>
            <select id="as_course" class="form-control">
                <option value="">— Select Course —</option>
                <?php foreach($courses_grouped as $cat => $cnames): ?>
                <optgroup label="<?= h($cat) ?>">
                    <?php foreach($cnames as $cn): ?>
                    <option value="<?= h($cn) ?>"><?= h($cn) ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Study Mode</label>
            <select id="as_mode" class="form-control">
                <option value="">— Select —</option>
                <option value="Onsite">Onsite</option>
                <option value="Online">Online</option>
                <option value="Hybrid">Hybrid</option>
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Initial Status</label>
            <select id="as_status" class="form-control">
                <option value="approved">Approved</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
            </select>
        </div>
        <div style="grid-column:1/-1;">
            <label style="font-size:12px;font-weight:700;color:#374151;display:block;margin-bottom:5px;">Notes (optional)</label>
            <textarea id="as_notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
        </div>
    </div>

    <div id="asMsg" style="display:none;margin-top:14px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>

    <div style="display:flex;gap:10px;margin-top:20px;">
        <button onclick="saveStudent()" style="flex:1;padding:12px;background:#f07b14;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
            <i class="fas fa-save me-1"></i> Save Student
        </button>
        <button onclick="closeAddModal()" style="flex:1;padding:12px;background:#f0f0f0;color:#374151;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">Cancel</button>
    </div>
</div>
</div>

<!-- View Detail Modal -->
<div class="modal fade" id="enrollmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-graduate me-2" style="color:#10b981"></i>Enrollment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="enrollmentContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#10b981"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

// ── Status update inline ─────────────────────────────────────
function updateStatus(id, newStatus, btn) {
    const stColor = {pending:'#f59e0b',approved:'#10b981',rejected:'#ef4444',in_progress:'#0094d9',completed:'#8b5cf6'};
    const stBg    = {pending:'rgba(245,158,11,.12)',approved:'rgba(16,185,129,.12)',rejected:'rgba(239,68,68,.12)',in_progress:'rgba(0,148,217,.12)',completed:'rgba(139,92,246,.12)'};
    const stIcon  = {pending:'fa-clock',approved:'fa-check-circle',rejected:'fa-times-circle',in_progress:'fa-play-circle',completed:'fa-graduation-cap'};
    const stLabel = {pending:'Pending',approved:'Approved',rejected:'Rejected',in_progress:'In Progress',completed:'Completed'};

    btn.disabled = true;
    btn.style.opacity = '0.5';

    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id',     id);
    fd.append('status', newStatus);

    fetch('enrollments.php', {method:'POST', body:fd})
        .then(function(r){ return r.text(); })
        .then(function(text){
            var data;
            try { data = JSON.parse(text); } catch(e) {
                btn.disabled = false; btn.style.opacity = '1';
                alert('Server error. Check console.'); console.error(text); return;
            }
            if (data.success) {
                // 1. Update status badge
                var cell = document.getElementById('st-cell-' + id);
                if (cell) {
                    cell.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;'
                        + 'background:' + (stBg[newStatus]||stBg.pending) + ';'
                        + 'color:' + (stColor[newStatus]||stColor.pending) + ';">'
                        + '<i class="fas ' + (stIcon[newStatus]||'fa-clock') + '"></i> '
                        + (stLabel[newStatus]||newStatus)
                        + '</span>';
                }

                // 2. Update action buttons in-place (no reload)
                var row = document.getElementById('erow-' + id);
                if (row) {
                    var actionCell = document.getElementById('ac-cell-' + id);
                    if (actionCell) {
                        var btns = '';
                        if (newStatus === 'pending') {
                            btns = '<button onclick="updateStatus('+id+',\'approved\',this)" style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#10b981;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-check"></i> Approve</button>'
                                 + '<button onclick="updateStatus('+id+',\'rejected\',this)" style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-times"></i> Reject</button>';
                        } else if (newStatus === 'approved') {
                            btns = '<button onclick="updateStatus('+id+',\'in_progress\',this)" style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#0094d9;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-play"></i> In Progress</button>';
                        } else if (newStatus === 'in_progress') {
                            btns = '<button onclick="updateStatus('+id+',\'completed\',this)" style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#8b5cf6;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-graduation-cap"></i> Complete</button>';
                        } else if (newStatus === 'completed') {
                            btns = '<span style="font-size:12px;color:#8b5cf6;font-weight:700;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-check-double"></i> Done</span>';
                        } else if (newStatus === 'rejected') {
                            btns = '<button onclick="updateStatus('+id+',\'pending\',this)" style="display:inline-flex;align-items:center;gap:4px;padding:6px 13px;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;"><i class="fas fa-redo"></i> Restore</button>';
                        }
                        btns += '<button class="pl-btn-icon pl-btn-view" onclick="viewEnrollment('+id+')" title="View"><i class="fas fa-eye"></i></button>'
                              + '<button class="pl-btn-icon pl-btn-del" onclick="deleteEnrollment('+id+', this)" title="Delete"><i class="fas fa-trash"></i></button>';
                        actionCell.innerHTML = '<div style="display:flex;align-items:center;gap:5px;flex-wrap:nowrap;">' + btns + '</div>';
                    }
                }

                // 3. Flash row green briefly
                var rowEl = document.getElementById('erow-' + id);
                if (rowEl) {
                    rowEl.style.transition = 'background .3s';
                    rowEl.style.background = 'rgba(16,185,129,.06)';
                    setTimeout(function(){ rowEl.style.background = ''; }, 1200);
                }

            } else {
                btn.disabled = false;
                btn.style.opacity = '1';
                alert('Error: ' + (data.message || 'Update failed'));
            }
        })
        .catch(function(e){
            btn.disabled = false;
            btn.style.opacity = '1';
            alert('Network error. Please try again.');
        });
}

// ── Add Student Modal ────────────────────────────────────────
function openAddModal() {
    document.getElementById('addStudentModal').style.display = 'flex';
    document.getElementById('asMsg').style.display = 'none';
    ['as_name','as_father','as_phone','as_email','as_cnic','as_notes'].forEach(id=>{
        document.getElementById(id).value = '';
    });
    document.getElementById('as_gender').value = '';
    document.getElementById('as_mode').value   = '';
    document.getElementById('as_status').value = 'approved';
    document.getElementById('as_course').value = '';
}
function closeAddModal() {
    document.getElementById('addStudentModal').style.display = 'none';
}
document.getElementById('addStudentModal').addEventListener('click', function(e){
    if(e.target===this) closeAddModal();
});

// CNIC auto-format
document.getElementById('as_cnic').addEventListener('input', function(){
    let v = this.value.replace(/[^0-9]/g,'');
    if(v.length>5)  v=v.slice(0,5) +'-'+v.slice(5);
    if(v.length>13) v=v.slice(0,13)+'-'+v.slice(13);
    if(v.length>15) v=v.slice(0,15);
    this.value=v;
});

function saveStudent() {
    const name   = document.getElementById('as_name').value.trim();
    const phone  = document.getElementById('as_phone').value.trim();
    const course = document.getElementById('as_course').value.trim();
    const cnic   = document.getElementById('as_cnic').value.trim();
    const msg    = document.getElementById('asMsg');

    if (!name)   { showAsMsg('Name is required.','#ef4444'); return; }
    if (!phone)  { showAsMsg('Phone is required.','#ef4444'); return; }
    if (!course) { showAsMsg('Please select a course.','#ef4444'); return; }
    if (cnic && !/^\d{5}-\d{7}-\d$/.test(cnic)) {
        showAsMsg('Invalid CNIC format. Use: XXXXX-XXXXXXX-X','#ef4444'); return;
    }

    const fd = new FormData();
    fd.append('action',            'add_student');
    fd.append('full_name',         name);
    fd.append('father_name',       document.getElementById('as_father').value.trim());
    fd.append('email',             document.getElementById('as_email').value.trim());
    fd.append('phone',             phone);
    fd.append('gender',            document.getElementById('as_gender').value);
    fd.append('cnic_number',       cnic);
    fd.append('course_interest',   course);
    fd.append('study_mode',        document.getElementById('as_mode').value);
    fd.append('enrollment_status', document.getElementById('as_status').value);
    fd.append('notes',             document.getElementById('as_notes').value.trim());

    fetch('enrollments.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(data=>{
            if(data.success){
                showAsMsg('Student added successfully!','#10b981');
                setTimeout(()=>{ closeAddModal(); location.reload(); },900);
            } else {
                showAsMsg(data.message||'Error saving.','#ef4444');
            }
        })
        .catch(()=>showAsMsg('Network error.','#ef4444'));
}

function showAsMsg(text,color){
    const el=document.getElementById('asMsg');
    el.textContent=text;
    el.style.color=color;
    el.style.background=color+'15';
    el.style.border='1px solid '+color+'40';
    el.style.display='block';
}

// ── View detail ──────────────────────────────────────────────
function esc(str){const d=document.createElement('div');d.appendChild(document.createTextNode(str??''));return d.innerHTML;}
function viewEnrollment(id){
    const modal=new bootstrap.Modal(document.getElementById('enrollmentModal'));
    document.getElementById('enrollmentContent').innerHTML='<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:#10b981"></i></div>';
    modal.show();
    fetch('get_enrollment.php?id='+id)
        .then(r=>r.json())
        .then(data=>{
            if(!data.success){alert(data.message);return;}
            const e=data.enrollment;
            const stLabel={'pending':'Pending','approved':'Approved','rejected':'Rejected','in_progress':'In Progress','completed':'Completed'};
            const stIcon={'pending':'fa-clock','approved':'fa-check-circle','rejected':'fa-times-circle','in_progress':'fa-play-circle','completed':'fa-graduation-cap'};
            const stColor={'pending':'#f59e0b','approved':'#10b981','rejected':'#ef4444','in_progress':'#0094d9','completed':'#8b5cf6'};
            const st=e.enrollment_status||'pending';
            const row=(lbl,val)=>val?`<div class="detail-row"><span class="detail-label">${lbl}</span><span class="detail-value">${esc(val)}</span></div>`:'';
            document.getElementById('enrollmentContent').innerHTML=`
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
                    <div>
                        ${row('Full Name',e.full_name)}
                        ${row('Father Name',e.father_name)}
                        ${row('Email',e.email)}
                        ${row('Phone',e.phone)}
                        ${row('Gender',e.gender)}
                        ${row('CNIC',e.cnic_number)}
                    </div>
                    <div>
                        ${row('Course',e.course_interest)}
                        ${row('Study Mode',e.study_mode)}
                        ${row('Added By',e.added_by)}
                        <div class="detail-row"><span class="detail-label">Status</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;background:${stColor[st]}18;color:${stColor[st]};"><i class="fas ${stIcon[st]||'fa-clock'}"></i> ${stLabel[st]||st}</span>
                        </div>
                        <div class="detail-row"><span class="detail-label">Enrolled On</span><span class="detail-value" style="color:#9ca3af;">${esc(e.created_at)}</span></div>
                    </div>
                </div>
                ${e.notes?`<div class="detail-row" style="flex-direction:column;gap:6px;margin-top:4px;"><span class="detail-label">Notes</span><div style="background:#f9fafb;border-radius:8px;padding:12px;font-size:13.5px;">${esc(e.notes)}</div></div>`:''}
                ${e.previous_experience?`<div class="detail-row" style="flex-direction:column;gap:6px;"><span class="detail-label">Experience</span><div style="background:#f9fafb;border-radius:8px;padding:12px;font-size:13.5px;">${esc(e.previous_experience)}</div></div>`:''}
                ${e.passport_photo?`<div class="detail-row"><span class="detail-label">Photo</span><a href="../uploads/${esc(e.passport_photo)}" target="_blank" style="color:#f07b14;font-weight:600;"><i class="fas fa-image me-1"></i>View</a></div>`:''}
                ${e.cnic_file?`<div class="detail-row"><span class="detail-label">CNIC File</span><a href="../uploads/${esc(e.cnic_file)}" target="_blank" style="color:#f07b14;font-weight:600;"><i class="fas fa-id-card me-1"></i>View</a></div>`:''}
            `;
        });
}

// ── Delete ───────────────────────────────────────────────────
function deleteEnrollment(id,btn){
    if(!confirm('Delete this enrollment?'))return;
    fetch('delete_enrollment.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id+'&csrf_token='+encodeURIComponent(CSRF)
    })
    .then(r=>r.json())
    .then(data=>{
        if(data.success){
            const row=btn.closest('tr');
            row.style.transition='opacity 0.3s';
            row.style.opacity='0';
            setTimeout(()=>row.remove(),300);
        } else alert(data.message);
    });
}
</script>

<?php admin_foot(); ?>
