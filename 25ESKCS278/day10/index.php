<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db_connect.php';

$isAdmin = !empty($_SESSION['is_admin']);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$students = [];
if ($isAdmin) {
    $stmt     = $pdo->query('SELECT * FROM students ORDER BY created_at DESC');
    $students = $stmt->fetchAll();
}

/** Small helper to keep the markup below readable. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Deterministic accent color for an avatar initial, based on the name. */
function initials_color(string $name): string
{
    $palette = ['#4F46E5', '#0EA5A4', '#DB2777', '#D97706', '#059669', '#7C3AED'];
    $hash    = array_sum(array_map('ord', str_split($name)));
    return $palette[$hash % count($palette)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Registry — Records Management</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root {
    --ink:        #14162B;
    --ink-soft:   #5B5F79;
    --surface:    #FFFFFF;
    --canvas:     #F5F6FB;
    --brand:      #4F46E5;
    --brand-dark: #3730A3;
    --accent:     #0EA5A4;
    --line:       #E6E7F0;
    --radius:     14px;
  }

  * { box-sizing: border-box; }

  body {
    background: var(--canvas);
    color: var(--ink);
    font-family: 'Inter', system-ui, sans-serif;
    min-height: 100vh;
  }

  h1, h2, h3, .display-font {
    font-family: 'Sora', system-ui, sans-serif;
  }

  .mono { font-family: 'IBM Plex Mono', monospace; letter-spacing: 0.02em; }

  /* ---------- Top bar ---------- */
  .topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--line);
  }
  .brand-mark {
    width: 38px; height: 38px; border-radius: 10px;
    background: linear-gradient(135deg, var(--brand), var(--accent));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-family: 'Sora', sans-serif;
    flex-shrink: 0;
  }
  .brand-name { font-weight: 700; font-size: 1.05rem; letter-spacing: -0.01em; }
  .brand-sub  { font-size: 0.75rem; color: var(--ink-soft); }

  /* ---------- Hero ---------- */
  .hero {
    background: radial-gradient(1200px 400px at 15% -10%, rgba(79,70,229,0.14), transparent),
                radial-gradient(900px 380px at 100% 0%, rgba(14,165,164,0.12), transparent);
    border-bottom: 1px solid var(--line);
  }

  /* Decorative ID-card motif for the registration side */
  .id-card {
    background: linear-gradient(160deg, var(--brand-dark), var(--brand) 60%, var(--accent) 130%);
    border-radius: 20px;
    color: #fff;
    padding: 1.75rem;
    position: relative;
    overflow: hidden;
    min-height: 260px;
    box-shadow: 0 20px 40px -18px rgba(55, 48, 163, 0.55);
  }
  .id-card::before {
    content: "";
    position: absolute; inset: -40% -10% auto auto;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,0.08);
  }
  .id-card::after {
    content: "";
    position: absolute; left: -60px; bottom: -60px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(255,255,255,0.06);
  }
  .id-card .chip {
    width: 42px; height: 30px; border-radius: 6px;
    background: linear-gradient(155deg, #FDE68A, #F59E0B);
  }
  .id-card .id-label {
    font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.14em;
    color: rgba(255,255,255,0.75);
  }

  /* ---------- Cards / surfaces ---------- */
  .surface {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius);
  }

  .form-control, .form-select {
    border-color: var(--line);
    padding-block: 0.55rem;
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15);
  }
  label.form-label {
    font-size: 0.83rem; font-weight: 600; color: var(--ink-soft);
  }

  .btn-brand {
    background: var(--brand); border-color: var(--brand); color: #fff;
    font-weight: 600;
  }
  .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }

  .btn-outline-brand {
    border-color: var(--brand); color: var(--brand); font-weight: 600;
  }
  .btn-outline-brand:hover { background: var(--brand); color: #fff; }

  /* ---------- Nav pills (Register / Dashboard) ---------- */
  .nav-pills .nav-link {
    color: var(--ink-soft); font-weight: 600; border-radius: 999px;
    padding: 0.5rem 1.1rem;
  }
  .nav-pills .nav-link.active {
    background: var(--brand); color: #fff;
  }

  /* ---------- Table ---------- */
  .table-registry thead th {
    font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;
    color: var(--ink-soft); border-bottom: 1px solid var(--line);
    background: #FAFAFE;
  }
  .table-registry td { vertical-align: middle; border-color: var(--line); }
  .avatar-initial {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 0.85rem;
    flex-shrink: 0;
  }
  .badge-course {
    background: #EEF2FF; color: var(--brand-dark);
    font-weight: 600; font-size: 0.72rem;
    border-radius: 999px; padding: 0.32rem 0.65rem;
  }

  .empty-state { color: var(--ink-soft); }
</style>
</head>
<body>

<!-- ============================================================
     TOP BAR
============================================================ -->
<nav class="topbar py-3">
  <div class="container d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <div class="brand-mark">SR</div>
      <div>
        <div class="brand-name">Student Registry</div>
        <div class="brand-sub">Data collection &amp; records management</div>
      </div>
    </div>

    <?php if ($isAdmin): ?>
      <div class="d-flex align-items-center gap-3">
        <span class="badge rounded-pill text-bg-light border">
          <i class="bi bi-shield-lock-fill text-success me-1"></i> Admin session
        </span>
        <form action="process.php" method="POST" class="m-0">
          <input type="hidden" name="action" value="logout">
          <button type="submit" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-box-arrow-right me-1"></i>Log out
          </button>
        </form>
      </div>
    <?php else: ?>
      <button type="button" class="btn btn-sm btn-outline-brand" data-bs-toggle="modal" data-bs-target="#adminLoginModal">
        <i class="bi bi-shield-lock me-1"></i>Admin login
      </button>
    <?php endif; ?>
  </div>
</nav>

<!-- ============================================================
     FLASH MESSAGE
============================================================ -->
<?php if ($flash): ?>
  <div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show shadow-sm" role="alert">
      <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
      <?= e($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  </div>
<?php endif; ?>

<!-- ============================================================
     HERO + NAV PILLS
============================================================ -->
<header class="hero py-5">
  <div class="container">
    <ul class="nav nav-pills gap-2 mb-4" id="viewTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= !$isAdmin ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#pane-register" type="button">
          <i class="bi bi-pencil-square me-1"></i>Student Registration
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $isAdmin ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#pane-dashboard" type="button">
          <i class="bi bi-grid-3x3-gap me-1"></i>Admin Dashboard
        </button>
      </li>
    </ul>

    <div class="tab-content">

      <!-- ======================= REGISTRATION PANE ======================= -->
      <div class="tab-pane fade <?= !$isAdmin ? 'show active' : '' ?>" id="pane-register" role="tabpanel">
        <div class="row g-4 align-items-start">

          <!-- Decorative ID card -->
          <div class="col-lg-4 d-none d-lg-block">
            <div class="id-card">
              <div class="d-flex justify-content-between align-items-start mb-4">
                <span class="id-label">Student Registry</span>
                <i class="bi bi-mortarboard-fill fs-4 opacity-75"></i>
              </div>
              <div class="chip mb-4"></div>
              <div class="id-label mb-1">Full name</div>
              <div class="fs-5 fw-semibold mb-3">Your name here</div>
              <div class="row">
                <div class="col-6">
                  <div class="id-label mb-1">Course</div>
                  <div class="fw-semibold">—</div>
                </div>
                <div class="col-6">
                  <div class="id-label mb-1">Admission No.</div>
                  <div class="fw-semibold mono">Auto-generated</div>
                </div>
              </div>
            </div>
            <p class="text-muted small mt-3">
              Submit the form to generate your unique admission number. Records are reviewed and
              can only be corrected by an authorized administrator.
            </p>
          </div>

          <!-- Registration form -->
          <div class="col-lg-8">
            <div class="surface p-4 p-md-5 shadow-sm">
              <h2 class="h4 mb-1">Register your details</h2>
              <p class="text-muted mb-4">Fields marked <span class="text-danger">*</span> are required.</p>

              <form action="process.php" method="POST" class="row g-3" novalidate>
                <input type="hidden" name="action" value="register">

                <div class="col-md-6">
                  <label class="form-label">Full name <span class="text-danger">*</span></label>
                  <input type="text" name="full_name" class="form-control" maxlength="100" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Email address <span class="text-danger">*</span></label>
                  <input type="email" name="email" class="form-control" maxlength="150" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Phone number <span class="text-danger">*</span></label>
                  <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Date of birth <span class="text-danger">*</span></label>
                  <input type="date" name="date_of_birth" class="form-control" max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Gender <span class="text-danger">*</span></label>
                  <select name="gender" class="form-select" required>
                    <option value="" selected disabled>Select…</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Course / Program <span class="text-danger">*</span></label>
                  <input type="text" name="course" class="form-control" placeholder="e.g. B.Tech Computer Science" maxlength="100" required>
                </div>

                <div class="col-12">
                  <label class="form-label">Address</label>
                  <textarea name="address" class="form-control" rows="2" placeholder="Optional"></textarea>
                </div>

                <div class="col-12 pt-2">
                  <button type="submit" class="btn btn-brand px-4 py-2">
                    <i class="bi bi-send-check me-1"></i>Submit registration
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>

      <!-- ======================= DASHBOARD PANE ======================= -->
      <div class="tab-pane fade <?= $isAdmin ? 'show active' : '' ?>" id="pane-dashboard" role="tabpanel">

        <?php if (!$isAdmin): ?>
          <div class="surface p-5 text-center shadow-sm">
            <i class="bi bi-shield-lock display-6 text-muted mb-3 d-block"></i>
            <h2 class="h5 mb-2">This section is restricted</h2>
            <p class="text-muted mb-4">Log in as an administrator to view, edit, or remove student records.</p>
            <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#adminLoginModal">
              <i class="bi bi-shield-lock me-1"></i>Admin login
            </button>
          </div>

        <?php else: ?>
          <div class="surface shadow-sm">
            <div class="d-flex align-items-center justify-content-between p-4 pb-3">
              <div>
                <h2 class="h5 mb-1">All student records</h2>
                <p class="text-muted small mb-0"><?= count($students) ?> record(s) on file</p>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-registry mb-0">
                <thead>
                  <tr>
                    <th class="ps-4">Student</th>
                    <th>Admission No.</th>
                    <th>Contact</th>
                    <th>Date of birth</th>
                    <th>Gender</th>
                    <th>Course</th>
                    <th class="text-end pe-4">Actions</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (empty($students)): ?>
                  <tr>
                    <td colspan="7" class="text-center empty-state py-5">
                      <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                      No student records yet. Once students submit the registration form, they'll appear here.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($students as $s): ?>
                    <tr>
                      <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar-initial" style="background: <?= initials_color($s['full_name']) ?>;">
                            <?= e(mb_strtoupper(mb_substr($s['full_name'], 0, 1))) ?>
                          </div>
                          <div>
                            <div class="fw-semibold"><?= e($s['full_name']) ?></div>
                            <div class="text-muted small"><?= e($s['email']) ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="mono small"><?= e($s['admission_number']) ?></td>
                      <td><?= e($s['phone']) ?></td>
                      <td><?= e(date('d M Y', strtotime((string)$s['date_of_birth']))) ?></td>
                      <td><?= e($s['gender']) ?></td>
                      <td><span class="badge-course"><?= e($s['course']) ?></span></td>
                      <td class="text-end pe-4">
                        <div class="d-inline-flex gap-2">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal"
                            data-id="<?= e((string)$s['id']) ?>"
                            data-full_name="<?= e($s['full_name']) ?>"
                            data-email="<?= e($s['email']) ?>"
                            data-phone="<?= e($s['phone']) ?>"
                            data-dob="<?= e($s['date_of_birth']) ?>"
                            data-gender="<?= e($s['gender']) ?>"
                            data-course="<?= e($s['course']) ?>"
                            data-address="<?= e((string)$s['address']) ?>"
                            title="Edit record"
                          >
                            <i class="bi bi-pencil"></i>
                          </button>

                          <form action="process.php" method="POST"
                                onsubmit="return confirm('Delete the record for <?= e(addslashes($s['full_name'])) ?>? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e((string)$s['id']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete record">
                              <i class="bi bi-trash3"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</header>

<footer class="py-4 text-center text-muted small">
  &copy; <?= date('Y') ?> Student Registry. Built with PHP, PDO &amp; Bootstrap 5.
</footer>

<!-- ============================================================
     ADMIN LOGIN MODAL
============================================================ -->
<div class="modal fade" id="adminLoginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: var(--radius);">
      <form action="process.php" method="POST">
        <input type="hidden" name="action" value="login">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title"><i class="bi bi-shield-lock me-2"></i>Admin login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-2">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autocomplete="username">
          </div>
          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required autocomplete="current-password">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-brand px-4">Log in</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================
     EDIT STUDENT MODAL (populated via JS from data-* attributes)
============================================================ -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius: var(--radius);">
      <form action="process.php" method="POST" id="editForm">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="edit_id">

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit student record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body pt-2">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full name</label>
              <input type="text" name="full_name" id="edit_full_name" class="form-control" maxlength="100" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email address</label>
              <input type="email" name="email" id="edit_email" class="form-control" maxlength="150" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone number</label>
              <input type="tel" name="phone" id="edit_phone" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Date of birth</label>
              <input type="date" name="date_of_birth" id="edit_dob" class="form-control" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Gender</label>
              <select name="gender" id="edit_gender" class="form-select" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Course / Program</label>
              <input type="text" name="course" id="edit_course" class="form-control" maxlength="100" required>
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-brand px-4">
            <i class="bi bi-check2-circle me-1"></i>Save changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Populate the edit modal from the clicked row's data attributes.
  document.querySelectorAll('.edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('edit_id').value        = btn.dataset.id;
      document.getElementById('edit_full_name').value  = btn.dataset.full_name;
      document.getElementById('edit_email').value      = btn.dataset.email;
      document.getElementById('edit_phone').value      = btn.dataset.phone;
      document.getElementById('edit_dob').value        = btn.dataset.dob;
      document.getElementById('edit_gender').value     = btn.dataset.gender;
      document.getElementById('edit_course').value     = btn.dataset.course;
      document.getElementById('edit_address').value    = btn.dataset.address;
    });
  });
</script>
</body>
</html>