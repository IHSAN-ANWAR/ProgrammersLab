<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/admin_layout.php';
send_security_headers();
require_admin();

$conn = get_db();

// ── AJAX / POST actions ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    session_init();
    csrf_verify();

    $action = $_POST['action'] ?? '';

    // ── ADD ──────────────────────────────────────────────────
    if ($action === 'add') {
        $title        = trim($_POST['title']        ?? '');
        $job_type     = trim($_POST['job_type']     ?? '');
        $badge_type   = trim($_POST['badge_type']   ?? 'internship');
        $duration     = trim($_POST['duration']     ?? '');
        $location     = trim($_POST['location']     ?? 'Onsite — Rawalpindi');
        $salary_label = trim($_POST['salary_label'] ?? '');
        $description  = trim($_POST['description']  ?? '');
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $sort_order   = (int)($_POST['sort_order']  ?? 0);

        if (!$title || !$job_type) {
            echo json_encode(['success' => false, 'message' => 'Title and Job Type are required.']);
            exit;
        }

        $stmt = $conn->prepare(
            "INSERT INTO job_openings
             (title, job_type, badge_type, duration, location, salary_label, description, is_featured, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('sssssssii', $title, $job_type, $badge_type, $duration, $location, $salary_label, $description, $is_featured, $sort_order);
        $ok = $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();
        log_activity($conn, 'ADD_JOB_OPENING', "Added: $title");
        echo json_encode(['success' => $ok, 'id' => $newId]);

    // ── EDIT ─────────────────────────────────────────────────
    } elseif ($action === 'edit') {
        $id           = (int)($_POST['id']          ?? 0);
        $title        = trim($_POST['title']        ?? '');
        $job_type     = trim($_POST['job_type']     ?? '');
        $badge_type   = trim($_POST['badge_type']   ?? 'internship');
        $duration     = trim($_POST['duration']     ?? '');
        $location     = trim($_POST['location']     ?? '');
        $salary_label = trim($_POST['salary_label'] ?? '');
        $description  = trim($_POST['description']  ?? '');
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $sort_order   = (int)($_POST['sort_order']  ?? 0);
        $is_active    = isset($_POST['is_active'])  ? 1 : 0;

        if (!$id || !$title || !$job_type) {
            echo json_encode(['success' => false, 'message' => 'ID, Title and Job Type are required.']);
            exit;
        }

        $stmt = $conn->prepare(
            "UPDATE job_openings SET
             title=?, job_type=?, badge_type=?, duration=?, location=?,
             salary_label=?, description=?, is_featured=?, sort_order=?, is_active=?
             WHERE id=?"
        );
        $stmt->bind_param('ssssssssiii', $title, $job_type, $badge_type, $duration, $location,
                          $salary_label, $description, $is_featured, $sort_order, $is_active, $id);
        $ok = $stmt->execute();
        $stmt->close();
        log_activity($conn, 'EDIT_JOB_OPENING', "Edited id=$id: $title");
        echo json_encode(['success' => $ok]);

    // ── DELETE ────────────────────────────────────────────────
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM job_openings WHERE id=?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            log_activity($conn, 'DELETE_JOB_OPENING', "Deleted id=$id");
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }

    // ── TOGGLE ACTIVE ─────────────────────────────────────────
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("UPDATE job_openings SET is_active = 1 - is_active WHERE id=?");
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => $ok]);
        } else {
            echo json_encode(['success' => false]);
        }

    // ── GET single ────────────────────────────────────────────
    } elseif ($action === 'get') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM job_openings WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row  = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => (bool)$row, 'job' => $row]);
    }

    $conn->close();
    exit;
}

// ── Page data ─────────────────────────────────────────────────
$jobs  = $conn->query("SELECT * FROM job_openings ORDER BY sort_order ASC, id DESC");
$total = (int)$conn->query("SELECT COUNT(*) AS c FROM job_openings")->fetch_assoc()['c'];
$active_count = (int)$conn->query("SELECT COUNT(*) AS c FROM job_openings WHERE is_active=1")->fetch_assoc()['c'];
log_activity($conn, 'VIEW_JOB_OPENINGS', 'Viewed job openings list');
$conn->close();

$csrf = csrf_token();
admin_head('Job Openings', 'job_openings');
?>

<div class="pl-page-header">
    <div>
        <h1>Job Openings</h1>
        <div class="pl-breadcrumb">Manage career page job cards</div>
    </div>
    <button class="btn btn-sm" onclick="openAddModal()"
        style="background:#f07b14;color:#fff;border-radius:10px;font-weight:600;font-size:13px;padding:9px 20px;border:none;">
        <i class="fas fa-plus me-1"></i> Add Opening
    </button>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(240,123,20,.1);color:#f07b14;"><i class="fas fa-briefcase"></i></div>
            <div><div class="stat-value"><?= $total ?></div><div class="stat-label">Total Openings</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(16,185,129,.1);color:#10b981;"><i class="fas fa-check-circle"></i></div>
            <div><div class="stat-value"><?= $active_count ?></div><div class="stat-label">Active / Visible</div></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:rgba(239,68,68,.1);color:#ef4444;"><i class="fas fa-eye-slash"></i></div>
            <div><div class="stat-value"><?= $total - $active_count ?></div><div class="stat-label">Hidden</div></div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="pl-card">
    <div class="pl-card-header">
        <h5><i class="fas fa-list me-2" style="color:#f07b14"></i>All Openings</h5>
        <span style="font-size:13px;color:#9ca3af;"><?= $total ?> total</span>
    </div>
    <div class="pl-card-body p-0">
        <table class="pl-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Duration / Location</th>
                    <th>Description</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="jobsTableBody">
            <?php if ($jobs && $jobs->num_rows > 0): ?>
            <?php while ($j = $jobs->fetch_assoc()): ?>
            <tr id="job-row-<?= $j['id'] ?>">
                <td style="color:#9ca3af;font-size:12px;"><?= $j['sort_order'] ?></td>
                <td><strong><?= h($j['title']) ?></strong></td>
                <td>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;
                        background:<?= $j['badge_type']==='fulltime' ? 'rgba(240,123,20,0.12)' : ($j['badge_type']==='parttime' ? 'rgba(99,102,241,0.12)' : 'rgba(16,185,129,0.12)') ?>;
                        color:<?= $j['badge_type']==='fulltime' ? '#c05c00' : ($j['badge_type']==='parttime' ? '#4f46e5' : '#065f46') ?>;">
                        <?= h($j['job_type']) ?>
                    </span>
                </td>
                <td style="font-size:13px;color:#6b7280;">
                    <?= h($j['duration'] ?: '—') ?><br>
                    <span style="font-size:11.5px;"><?= h($j['location'] ?: '—') ?></span>
                </td>
                <td style="font-size:12.5px;color:#6b7280;max-width:200px;">
                    <?= h(mb_strimwidth($j['description'] ?? '', 0, 60, '…')) ?>
                </td>
                <td>
                    <?php if ($j['is_featured']): ?>
                    <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(245,158,11,0.12);color:#92400e;">
                        <i class="fas fa-star me-1"></i>Featured
                    </span>
                    <?php else: ?>
                    <span style="color:#d1d5db;font-size:12px;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span id="status-badge-<?= $j['id'] ?>" style="padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;
                        background:<?= $j['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' ?>;
                        color:<?= $j['is_active'] ? '#065f46' : '#b91c1c' ?>;">
                        <?= $j['is_active'] ? 'Active' : 'Hidden' ?>
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <button class="pl-btn-icon pl-btn-view me-1" title="Edit" onclick="openEditModal(<?= $j['id'] ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="pl-btn-icon" title="Toggle Active/Hidden"
                        style="background:rgba(245,158,11,0.1);color:#d97706;"
                        onclick="toggleJob(<?= $j['id'] ?>)">
                        <i class="fas fa-toggle-<?= $j['is_active'] ? 'on' : 'off' ?>"></i>
                    </button>
                    <button class="pl-btn-icon pl-btn-del ms-1" title="Delete" onclick="deleteJob(<?= $j['id'] ?>, '<?= h($j['title']) ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="8" class="pl-empty"><i class="fas fa-briefcase"></i><p>No job openings yet. Click "Add Opening" to create one.</p></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== ADD / EDIT MODAL ===== -->
<div class="modal fade" id="jobModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobModalTitle">Add Job Opening</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="jobForm">
                    <input type="hidden" name="id" id="jobId">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:13px;">Job Title <span style="color:#f07b14;">*</span></label>
                            <input type="text" class="form-control" name="title" id="jobTitle" placeholder="e.g. Web Development Intern" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="jobSortOrder" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Job Type <span style="color:#f07b14;">*</span></label>
                            <input type="text" class="form-control" name="job_type" id="jobType" placeholder="e.g. Full Time, Internship, Part Time" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Badge Color</label>
                            <select class="form-select" name="badge_type" id="jobBadgeType">
                                <option value="internship">Green (Internship)</option>
                                <option value="fulltime">Orange (Full Time)</option>
                                <option value="parttime">Purple (Part Time)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Duration</label>
                            <input type="text" class="form-control" name="duration" id="jobDuration" placeholder="e.g. 3 Months, 1+ Year Experience">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Location</label>
                            <input type="text" class="form-control" name="location" id="jobLocation" placeholder="Onsite — Rawalpindi">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Salary / Stipend Label</label>
                            <input type="text" class="form-control" name="salary_label" id="jobSalaryLabel" placeholder="e.g. Paid Internship, Market Competitive">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Description</label>
                            <textarea class="form-control" name="description" id="jobDescription" rows="3"
                                placeholder="Brief description about this role..."></textarea>
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-3 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="jobFeatured">
                                <label class="form-check-label fw-semibold" style="font-size:13px;" for="jobFeatured">
                                    <i class="fas fa-star me-1" style="color:#f59e0b;"></i>Featured card
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-3 pt-2" id="activeToggleRow" style="display:none!important;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="jobActive" checked>
                                <label class="form-check-label fw-semibold" style="font-size:13px;" for="jobActive">Active (visible on site)</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f0f0f0;">
                <div id="jobFormMsg" style="font-size:13px;color:#ef4444;flex:1;"></div>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="jobSaveBtn"
                    onclick="saveJob()"
                    style="background:#f07b14;color:#fff;border:none;border-radius:8px;padding:8px 20px;font-weight:600;">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= h($csrf) ?>';
let editMode = false;

function openAddModal() {
    editMode = false;
    document.getElementById('jobModalTitle').textContent = 'Add Job Opening';
    document.getElementById('jobForm').reset();
    document.getElementById('jobId').value = '';
    document.getElementById('jobLocation').value = 'Onsite — Rawalpindi';
    document.getElementById('activeToggleRow').style.display = 'none';
    document.getElementById('jobFormMsg').textContent = '';
    new bootstrap.Modal(document.getElementById('jobModal')).show();
}

function openEditModal(id) {
    editMode = true;
    document.getElementById('jobModalTitle').textContent = 'Edit Job Opening';
    document.getElementById('jobFormMsg').textContent = '';
    document.getElementById('activeToggleRow').style.removeProperty('display');

    const fd = new FormData();
    fd.append('action', 'get');
    fd.append('id', id);
    fd.append('csrf_token', CSRF);

    fetch('job_openings.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const j = data.job;
            document.getElementById('jobId').value          = j.id;
            document.getElementById('jobTitle').value       = j.title;
            document.getElementById('jobType').value        = j.job_type;
            document.getElementById('jobBadgeType').value   = j.badge_type;
            document.getElementById('jobDuration').value    = j.duration    || '';
            document.getElementById('jobLocation').value    = j.location    || '';
            document.getElementById('jobSalaryLabel').value = j.salary_label|| '';
            document.getElementById('jobDescription').value = j.description || '';
            document.getElementById('jobSortOrder').value   = j.sort_order  || 0;
            document.getElementById('jobFeatured').checked  = j.is_featured == 1;
            document.getElementById('jobActive').checked    = j.is_active   == 1;
            new bootstrap.Modal(document.getElementById('jobModal')).show();
        });
}

function saveJob() {
    const form = document.getElementById('jobForm');
    const msg  = document.getElementById('jobFormMsg');
    const btn  = document.getElementById('jobSaveBtn');
    msg.textContent = '';

    const title   = form.querySelector('[name="title"]').value.trim();
    const jobType = form.querySelector('[name="job_type"]').value.trim();
    if (!title)   { msg.textContent = 'Title is required.'; return; }
    if (!jobType) { msg.textContent = 'Job Type is required.'; return; }

    const fd = new FormData(form);
    fd.set('action', editMode ? 'edit' : 'add');
    fd.set('csrf_token', CSRF);
    if (!form.querySelector('[name="is_featured"]').checked) fd.delete('is_featured');
    if (!form.querySelector('[name="is_active"]').checked)   fd.delete('is_active');

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled  = true;

    fetch('job_openings.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('jobModal')).hide();
                location.reload();
            } else {
                msg.textContent = data.message || 'Error saving. Try again.';
            }
        })
        .catch(() => { msg.textContent = 'Network error.'; })
        .finally(() => { btn.innerHTML = '<i class="fas fa-save me-1"></i> Save'; btn.disabled = false; });
}

function toggleJob(id) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fd.append('csrf_token', CSRF);
    fetch('job_openings.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
}

function deleteJob(id, title) {
    if (!confirm('Delete "' + title + '"? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fd.append('csrf_token', CSRF);
    fetch('job_openings.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('job-row-' + id);
                if (row) row.remove();
            }
        });
}
</script>

<?php admin_foot(); ?>
