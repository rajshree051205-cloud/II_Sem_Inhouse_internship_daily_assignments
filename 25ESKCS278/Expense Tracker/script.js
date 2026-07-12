
const API = 'process.php';

let trendChart = null;
let categoryChart = null;
let currentExpenses = [];
let deleteTargetId = null;

const $ = (id) => document.getElementById(id);

document.addEventListener('DOMContentLoaded', () => {
  setDefaultDate();
  loadCategories();
  loadStats();
  loadExpenses();

  $('expenseForm').addEventListener('submit', handleSaveExpense);
  $('budgetForm').addEventListener('submit', handleSaveBudget);
  $('confirmDeleteBtn').addEventListener('click', handleConfirmDelete);
  $('resetFilters').addEventListener('click', resetFilters);
  $('exportBtn').addEventListener('click', exportCSV);

  $('searchInput').addEventListener('input', debounce(loadExpenses, 350));
  $('filterCategory').addEventListener('change', loadExpenses);
  $('filterDateFrom').addEventListener('change', loadExpenses);
  $('filterDateTo').addEventListener('change', loadExpenses);
  $('sortBy').addEventListener('change', loadExpenses);
});


function setDefaultDate() {
  $('expense_date').value = new Date().toISOString().split('T')[0];
}

function debounce(fn, delay) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}

function money(n) {
  return '₹' + Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function showToast(message, type = 'success') {
  const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
  const el = document.createElement('div');
  el.className = `app-toast ${type}`;
  el.innerHTML = `<i class="bi ${icon}"></i><div>${message}</div>`;
  $('toastStack').appendChild(el);
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transition = 'opacity .3s ease';
    setTimeout(() => el.remove(), 300);
  }, 3200);
}

async function api(action, params = {}, method = 'GET') {
  let url = `${API}?action=${action}`;
  let options = { method };

  if (method === 'GET') {
    const qs = new URLSearchParams(params).toString();
    if (qs) url += '&' + qs;
  } else {
    const body = new URLSearchParams({ action, ...params });
    options.body = body;
  }

  const res = await fetch(url, options);
  return res.json();
}


// Colors used if a category briefly shows up before the server has
// assigned it a permanent color (e.g. right after typing a brand new one).
const FALLBACK_PALETTE = ['#2563EB', '#16A34A', '#D4A72C', '#9333EA', '#E23B5C', '#0891B2', '#E8823D', '#4F46E5', '#0D9488', '#DB2777'];

function fallbackColorFor(name) {
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return FALLBACK_PALETTE[Math.abs(hash) % FALLBACK_PALETTE.length];
}

function categoryMeta(name) {
  return CATEGORY_META[name] || { icon: 'bi-tag', color: fallbackColorFor(name) };
}

async function loadCategories() {
  const res = await api('list_categories', {}, 'GET');
  if (!res.success) return;

  CATEGORY_META = {};
  res.data.forEach(c => { CATEGORY_META[c.name] = { icon: c.icon, color: c.color }; });

  // Filter dropdown
  const filterSelect = $('filterCategory');
  const currentFilterVal = filterSelect.value;
  filterSelect.innerHTML = '<option value="all">All categories</option>' +
    res.data.map(c => `<option value="${escapeHtml(c.name)}">${escapeHtml(c.name)}</option>`).join('');
  filterSelect.value = currentFilterVal || 'all';

  // Add/edit form datalist (suggestions only — user can still type anything new)
  $('categoryList').innerHTML = res.data.map(c => `<option value="${escapeHtml(c.name)}"></option>`).join('');
}

/*Load table*/

async function loadExpenses() {
  const params = {
    search: $('searchInput').value.trim(),
    category: $('filterCategory').value,
    date_from: $('filterDateFrom').value,
    date_to: $('filterDateTo').value,
    sort: $('sortBy').value,
  };

  const tbody = $('expenseTableBody');
  tbody.innerHTML = `<tr><td colspan="6"><div class="skeleton" style="height:20px;margin:6px 0;"></div></td></tr>`.repeat(4);

  const res = await api('list', params, 'GET');
  if (!res.success) {
    showToast(res.message, 'error');
    return;
  }

  currentExpenses = res.data;
  renderTable(currentExpenses);
}

function renderTable(rows) {
  const tbody = $('expenseTableBody');
  const empty = $('emptyState');

  if (!rows.length) {
    tbody.innerHTML = '';
    empty.classList.remove('d-none');
    $('rowCountLabel').textContent = '';
    return;
  }
  empty.classList.add('d-none');

  tbody.innerHTML = rows.map(rowHtml).join('');
  $('rowCountLabel').textContent = `Showing ${rows.length} expense${rows.length !== 1 ? 's' : ''}`;

  // wire up action buttons
  tbody.querySelectorAll('.edit-btn').forEach(btn =>
    btn.addEventListener('click', () => openEditModal(btn.dataset.id)));
  tbody.querySelectorAll('.delete-btn').forEach(btn =>
    btn.addEventListener('click', () => openDeleteModal(btn.dataset.id, btn.dataset.title)));
}

function rowHtml(exp) {
  const meta = categoryMeta(exp.category);
  return `
    <tr>
      <td>
        <div style="font-weight:600">${escapeHtml(exp.title)}</div>
        ${exp.notes ? `<div style="font-size:12px;color:var(--muted)">${escapeHtml(exp.notes)}</div>` : ''}
      </td>
      <td>
        <span class="cat-chip" style="background:${meta.color}1A;color:${meta.color}">
          <i class="bi ${meta.icon}"></i> ${escapeHtml(exp.category)}
        </span>
      </td>
      <td class="mono" style="font-size:13px;color:var(--muted)">${formatDate(exp.expense_date)}</td>
      <td><span class="pay-chip">${escapeHtml(exp.payment_method)}</span></td>
      <td class="text-end amount-cell">-${money(exp.amount)}</td>
      <td>
        <div class="row-actions justify-content-end">
          <button class="icon-btn edit-btn" data-id="${exp.id}" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="icon-btn danger delete-btn" data-id="${exp.id}" data-title="${escapeHtml(exp.title)}" title="Delete"><i class="bi bi-trash3"></i></button>
        </div>
      </td>
    </tr>`;
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function resetFilters() {
  $('searchInput').value = '';
  $('filterCategory').value = 'all';
  $('filterDateFrom').value = '';
  $('filterDateTo').value = '';
  $('sortBy').value = 'date_desc';
  loadExpenses();
}


function openAddModal() {
  $('expenseForm').reset();
  $('expenseId').value = '';
  $('expenseModalTitle').textContent = 'Add Expense';
  $('saveExpenseLabel').textContent = 'Save Expense';
  setDefaultDate();
}

async function openEditModal(id) {
  const res = await api('get_one', { id }, 'GET');
  if (!res.success) { showToast(res.message, 'error'); return; }

  const exp = res.data;
  $('expenseId').value = exp.id;
  $('title').value = exp.title;
  $('amount').value = exp.amount;
  $('category').value = exp.category;
  $('payment_method').value = exp.payment_method;
  $('expense_date').value = exp.expense_date;
  $('notes').value = exp.notes || '';

  $('expenseModalTitle').textContent = 'Edit Expense';
  $('saveExpenseLabel').textContent = 'Update Expense';

  new bootstrap.Modal($('expenseModal')).show();
}

async function handleSaveExpense(e) {
  e.preventDefault();
  const btn = $('saveExpenseBtn');
  const originalLabel = $('saveExpenseLabel').textContent;
  btn.disabled = true;
  $('saveExpenseLabel').innerHTML = `<span class="spin"></span>`;

  const id = $('expenseId').value;
  const payload = {
    title: $('title').value.trim(),
    amount: $('amount').value,
    category: $('category').value,
    payment_method: $('payment_method').value,
    expense_date: $('expense_date').value,
    notes: $('notes').value.trim(),
  };
  if (id) payload.id = id;

  const res = await api(id ? 'update' : 'add', payload, 'POST');

  btn.disabled = false;
  $('saveExpenseLabel').textContent = originalLabel;

  if (!res.success) {
    showToast(res.message, 'error');
    return;
  }

  bootstrap.Modal.getInstance($('expenseModal')).hide();
  showToast(res.message, 'success');
  loadCategories();
  loadExpenses();
  loadStats();
}


function openDeleteModal(id, title) {
  deleteTargetId = id;
  $('deleteExpenseTitle').textContent = `"${title}" will be permanently removed.`;
  new bootstrap.Modal($('deleteModal')).show();
}

async function handleConfirmDelete() {
  if (!deleteTargetId) return;
  const btn = $('confirmDeleteBtn');
  btn.disabled = true;
  btn.innerHTML = `<span class="spin"></span>`;

  const res = await api('delete', { id: deleteTargetId }, 'POST');

  btn.disabled = false;
  btn.textContent = 'Yes, delete it';

  bootstrap.Modal.getInstance($('deleteModal')).hide();

  if (!res.success) {
    showToast(res.message, 'error');
    return;
  }
  showToast(res.message, 'success');
  loadExpenses();
  loadStats();
}


async function loadStats() {
  const res = await api('stats', {}, 'GET');
  if (!res.success) return;

  const d = res.data;
  $('kpiMonth').textContent = money(d.month_total);
  $('kpiTotal').textContent = money(d.total);
  $('kpiTopCategory').textContent = d.top_category || '—';

  updateBudgetRing(d.month_total, d.budget);
  renderTrendChart(d.trend);
  renderCategoryChart(d.by_category);

  if (d.budget > 0) $('monthly_limit').value = d.budget;
}

function updateBudgetRing(spent, budget) {
  const circumference = 163; // 2 * π * 26, rounded
  const pct = budget > 0 ? Math.min(spent / budget, 1) : 0;
  const offset = circumference - pct * circumference;

  $('budgetRing').setAttribute('stroke-dashoffset', offset);
  $('budgetPct').textContent = Math.round(pct * 100) + '%';
  $('budgetLabel').textContent = `${money(spent)} / ${money(budget)}`;

  const ring = $('budgetRing');
  ring.style.stroke = pct >= 1 ? '#E23B5C' : (pct >= 0.8 ? '#D4A72C' : '#0FA968');
}

function renderTrendChart(trend) {
  const labels = trend.map(t => {
    const [y, m] = t.ym.split('-');
    return new Date(y, m - 1).toLocaleDateString('en-IN', { month: 'short' });
  });
  const values = trend.map(t => parseFloat(t.total));

  if (trendChart) trendChart.destroy();
  trendChart = new Chart($('trendChart'), {
    type: 'line',
    data: {
      labels,
      datasets: [{
        data: values,
        borderColor: '#131C31',
        backgroundColor: 'rgba(19,28,49,0.06)',
        borderWidth: 2.5,
        fill: true,
        tension: 0.35,
        pointRadius: 4,
        pointBackgroundColor: '#D4A72C',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { callback: v => '₹' + v }, grid: { color: '#F0F1F3' } },
        x: { grid: { display: false } }
      }
    }
  });
}

function renderCategoryChart(byCategory) {
  const labels = byCategory.map(c => c.category);
  const values = byCategory.map(c => parseFloat(c.total));
  const colors = byCategory.map(c => categoryMeta(c.category).color);

  if (categoryChart) categoryChart.destroy();

  if (!labels.length) {
    categoryChart = null;
    const ctx = $('categoryChart').getContext('2d');
    ctx.clearRect(0, 0, $('categoryChart').width, $('categoryChart').height);
    return;
  }

  categoryChart = new Chart($('categoryChart'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
      cutout: '68%',
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
      }
    }
  });
}


async function handleSaveBudget(e) {
  e.preventDefault();
  const res = await api('set_budget', { monthly_limit: $('monthly_limit').value }, 'POST');
  if (!res.success) { showToast(res.message, 'error'); return; }

  bootstrap.Modal.getInstance($('budgetModal')).hide();
  showToast(res.message, 'success');
  loadStats();
}


function exportCSV() {
  if (!currentExpenses.length) {
    showToast('No expenses to export.', 'error');
    return;
  }
  const headers = ['Title', 'Amount', 'Category', 'Payment Method', 'Date', 'Notes'];
  const rows = currentExpenses.map(e => [
    e.title, e.amount, e.category, e.payment_method, e.expense_date, (e.notes || '').replace(/,/g, ';')
  ]);
  const csv = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');

  const blob = new Blob([csv], { type: 'text/csv' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `expenses_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
}