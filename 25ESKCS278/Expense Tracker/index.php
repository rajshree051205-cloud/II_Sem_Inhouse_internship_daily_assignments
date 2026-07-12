<?php
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ledger — Expense Tracker</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="toast-stack" id="toastStack"></div>

<div class="app-shell">

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">L</div>
      <div class="brand-text">
        <div class="name">Ledger</div>
        <div class="tag">Expense Tracker</div>
      </div>
    </div>

    <div class="nav-section-label">Overview</div>
    <a class="side-link active"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a class="side-link" onclick="document.getElementById('tableSection').scrollIntoView({behavior:'smooth'})"><i class="bi bi-receipt"></i> All Expenses</a>

    <div class="nav-section-label">Manage</div>
    <a class="side-link" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="openAddModal()"><i class="bi bi-plus-circle"></i> Add Expense</a>
    <a class="side-link" data-bs-toggle="modal" data-bs-target="#budgetModal"><i class="bi bi-piggy-bank"></i> Set Budget</a>
    <a class="side-link" id="exportBtn"><i class="bi bi-download"></i> Export CSV</a>

    <div class="sidebar-footer">
      <div class="mono"><?= date('D, d M Y') ?></div>
      <div>Signed in as <strong style="color:#fff">Guest</strong></div>
    </div>
  </aside>

  <main class="main">

    <div class="topbar">
      <button class="btn btn-outline-soft mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1>Good day 👋</h1>
        <div class="sub">Here's where your money went this month.</div>
      </div>
      <button class="btn-mint" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="openAddModal()">
        <i class="bi bi-plus-lg"></i> Add Expense
      </button>
    </div>

    <div class="kpi-row" id="kpiRow">
      <div class="kpi-card">
        <div class="kpi-icon" style="background:var(--coral-soft);color:var(--coral)"><i class="bi bi-wallet2"></i></div>
        <div class="kpi-label">This month</div>
        <div class="kpi-value" id="kpiMonth">₹0.00</div>
        <div class="kpi-trend" id="kpiMonthTrend">&nbsp;</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background:var(--emerald-soft);color:var(--emerald)"><i class="bi bi-stack"></i></div>
        <div class="kpi-label">All-time total</div>
        <div class="kpi-value" id="kpiTotal">₹0.00</div>
        <div class="kpi-trend">&nbsp;</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon" style="background:var(--gold-soft);color:var(--gold)"><i class="bi bi-tags"></i></div>
        <div class="kpi-label">Top category</div>
        <div class="kpi-value" id="kpiTopCategory" style="font-size:18px">—</div>
        <div class="kpi-trend">&nbsp;</div>
      </div>

      <div class="ring-card">
        <div class="ring-wrap">
          <svg viewBox="0 0 64 64" width="64" height="64">
            <circle class="ring-bg" cx="32" cy="32" r="26"></circle>
            <circle class="ring-fg" id="budgetRing" cx="32" cy="32" r="26" stroke-dasharray="163" stroke-dashoffset="163"></circle>
          </svg>
          <div class="ring-pct" id="budgetPct">0%</div>
        </div>
        <div>
          <div style="font-size:12px;color:var(--muted-2);text-transform:uppercase;letter-spacing:.04em;font-weight:600;">Budget used</div>
          <div class="mono" id="budgetLabel" style="font-size:15px;font-weight:700;margin-top:3px;">₹0 / ₹0</div>
          <a href="#" data-bs-toggle="modal" data-bs-target="#budgetModal" style="font-size:12px;color:var(--gold);">Edit budget →</a>
        </div>
      </div>
    </div>

    <div class="charts-row">
      <div class="panel">
        <div class="panel-title">Spending trend</div>
        <div class="panel-sub">Last 6 months</div>
        <canvas id="trendChart" height="90"></canvas>
      </div>
      <div class="panel">
        <div class="panel-title">By category</div>
        <div class="panel-sub">Current month breakdown</div>
        <canvas id="categoryChart" height="90"></canvas>
      </div>
    </div>

    <div class="filters-bar" id="tableSection">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="form-control" id="searchInput" placeholder="Search expenses or notes...">
      </div>
      <select class="form-select" id="filterCategory" style="max-width:180px">
        <option value="all">All categories</option>
        <!-- options populated dynamically from the database in script.js -->
      </select>
      <input type="date" class="form-control" id="filterDateFrom" style="max-width:160px" title="From date">
      <input type="date" class="form-control" id="filterDateTo" style="max-width:160px" title="To date">
      <select class="form-select" id="sortBy" style="max-width:170px">
        <option value="date_desc">Newest first</option>
        <option value="date_asc">Oldest first</option>
        <option value="amount_desc">Amount: High → Low</option>
        <option value="amount_asc">Amount: Low → High</option>
      </select>
      <button class="btn btn-outline-soft" id="resetFilters"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
    </div>

    <div class="table-panel">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Expense</th>
              <th>Category</th>
              <th>Date</th>
              <th>Payment</th>
              <th class="text-end">Amount</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="expenseTableBody">
            <tr><td colspan="6"><div class="skeleton" style="height:20px;margin:6px 0;"></div></td></tr>
          </tbody>
        </table>
      </div>
      <div id="emptyState" class="empty-state d-none">
        <i class="bi bi-inbox"></i>
        <div style="font-weight:600;color:var(--text)">No expenses found</div>
        <div style="font-size:13px;">Try adjusting your filters, or add a new expense.</div>
      </div>
    </div>

    <div class="text-center text-muted mt-3" style="font-size:12px;" id="rowCountLabel"></div>

  </main>
</div>

<div class="modal fade" id="expenseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="expenseForm">
        <div class="modal-header">
          <h5 class="modal-title" id="expenseModalTitle">Add Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="expenseId" name="id">

          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Grocery shopping" required maxlength="150">
          </div>

          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Amount</label>
              <div class="amount-input-group">
                <span class="currency">₹</span>
                <input type="number" class="form-control" id="amount" name="amount" placeholder="0.00" step="0.01" min="0.01" required>
              </div>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Date</label>
              <input type="date" class="form-control" id="expense_date" name="expense_date" required>
            </div>
          </div>

          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Category</label>
              <input type="text" class="form-control" id="category" name="category"
                     list="categoryList" placeholder="Pick or type your own" required maxlength="50" autocomplete="off">
              <datalist id="categoryList"><!-- populated dynamically in script.js --></datalist>
              <div class="form-text" style="font-size:11.5px;">Not in the list? Just type a new one — it'll be saved.</div>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Payment method</label>
              <input type="text" class="form-control" id="payment_method" name="payment_method"
                     list="paymentList" placeholder="Pick or type your own" required maxlength="50" autocomplete="off">
              <datalist id="paymentList">
                <?php foreach ($PAYMENT_METHODS as $pm): ?>
                  <option value="<?= htmlspecialchars($pm) ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
          </div>

          <div class="mb-1">
            <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="255" placeholder="Any extra detail..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-mint" id="saveExpenseBtn">
            <span id="saveExpenseLabel">Save Expense</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center pt-4">
        <div style="width:56px;height:56px;border-radius:50%;background:var(--coral-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
          <i class="bi bi-trash3" style="font-size:22px;color:var(--coral)"></i>
        </div>
        <h5 class="fw-bold mb-1">Delete this expense?</h5>
        <p class="text-muted" style="font-size:13.5px" id="deleteExpenseTitle">This action can't be undone.</p>
      </div>
      <div class="modal-footer justify-content-center border-top-0 pb-4">
        <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-danger-soft" id="confirmDeleteBtn">Yes, delete it</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="budgetModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="budgetForm">
        <div class="modal-header">
          <h5 class="modal-title">Set monthly budget</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Budget for <?= date('F Y') ?></label>
          <div class="amount-input-group">
            <span class="currency">₹</span>
            <input type="number" class="form-control" id="monthly_limit" name="monthly_limit" step="0.01" min="1" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-soft" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-mint">Save Budget</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
  let CATEGORY_META = {};
</script>
<script src="script.js"></script>
</body>
</html>