import axios from 'axios';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function UploadIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M12 15V4" />
      <path d="M7 9l5-5 5 5" />
      <path d="M5 15v4h14v-4" />
    </svg>
  );
}

function RulesIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M5 6h14" />
      <path d="M5 12h10" />
      <path d="M5 18h6" />
      <path d="M17 14l2 2 3-4" />
    </svg>
  );
}

function ReviewIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M6 4h9l3 3v13H6z" />
      <path d="M14 4v4h4" />
      <path d="M9 12h6" />
      <path d="M9 16h4" />
    </svg>
  );
}

function ReconcileIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M7 7h10" />
      <path d="M17 7l-3-3" />
      <path d="M17 7l-3 3" />
      <path d="M17 17H7" />
      <path d="M7 17l3-3" />
      <path d="M7 17l3 3" />
    </svg>
  );
}

function ExportIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M6 4h9l3 3v13H6z" />
      <path d="M14 4v4h4" />
      <path d="M12 11v6" />
      <path d="M9 14l3 3 3-3" />
    </svg>
  );
}

function SettingsIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M12 8a4 4 0 100 8 4 4 0 000-8z" />
      <path d="M4 12h2" />
      <path d="M18 12h2" />
      <path d="M12 4v2" />
      <path d="M12 18v2" />
      <path d="M6.3 6.3l1.4 1.4" />
      <path d="M16.3 16.3l1.4 1.4" />
      <path d="M17.7 6.3l-1.4 1.4" />
      <path d="M7.7 16.3l-1.4 1.4" />
    </svg>
  );
}

const themes = [
  { id: 'dark', label: 'Dark' },
  { id: 'light', label: 'Light' },
  { id: 'midnight', label: 'Midnight' },
  { id: 'ocean', label: 'Ocean' },
  { id: 'ember', label: 'Ember' },
];

const workflowTabs = [
  { id: 'upload', label: 'Upload', icon: UploadIcon },
  { id: 'rules', label: 'Mapping Rules', icon: RulesIcon },
  { id: 'review', label: 'Review Rows', icon: ReviewIcon },
  { id: 'reconcile', label: 'Reconcile', icon: ReconcileIcon },
  { id: 'exports', label: 'Exports', icon: ExportIcon },
];

function App() {
  const [activeTab, setActiveTab] = useState('upload');
  const [batchId, setBatchId] = useState('');
  const [notice, setNotice] = useState(null);
  const [user, setUser] = useState(null);
  const [authLoading, setAuthLoading] = useState(true);
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);

  const showNotice = useCallback((type, message) => {
    setNotice({ type, message });
  }, []);

  useEffect(() => {
    axios
      .get('/api/me')
      .then((response) => setUser(response.data.data))
      .catch(() => setUser(null))
      .finally(() => setAuthLoading(false));
  }, []);

  useEffect(() => {
    if (!notice) {
      return undefined;
    }

    const timeout = window.setTimeout(() => setNotice(null), 3000);

    return () => window.clearTimeout(timeout);
  }, [notice]);

  async function handleLogout() {
    try {
      await axios.post('/api/logout');
      setUser(null);
      setNotice(null);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to sign out.'));
    }
  }

  async function handleThemeChange(theme) {
    const previousUser = user;

    setUser({ ...user, theme });

    try {
      const response = await axios.patch('/api/me/theme', { theme });
      setUser(response.data.data);
      showNotice('success', 'Theme updated.');
    } catch (error) {
      setUser(previousUser);
      showNotice('error', messageFromError(error, 'Unable to update theme.'));
    }
  }

  if (authLoading) {
    return <div className="auth-loading">Loading...</div>;
  }

  if (!user) {
    return <LoginScreen onLogin={setUser} />;
  }

  return (
    <main className={`app-shell theme-${user.theme ?? 'dark'}`}>
      <AppHeader
        batchId={batchId}
        isSidebarCollapsed={isSidebarCollapsed}
        setBatchId={setBatchId}
        user={user}
        onLogout={handleLogout}
        onToggleSidebar={() => setIsSidebarCollapsed((current) => !current)}
      />

      {notice ? <FloatingAlert notice={notice} onClose={() => setNotice(null)} /> : null}

      <div className={isSidebarCollapsed ? 'app-frame app-frame-collapsed' : 'app-frame'}>
        <aside className={isSidebarCollapsed ? 'sidebar sidebar-collapsed' : 'sidebar'} aria-label="Primary navigation">
          <div className="sidebar-title">Workflow</div>
          <nav className="sidebar-nav" aria-label="Weekly analysis workflow">
            {workflowTabs.map((tab) => {
              const Icon = tab.icon;

              return (
                <button
                  className={activeTab === tab.id ? 'sidebar-link sidebar-link-active' : 'sidebar-link'}
                  key={tab.id}
                  type="button"
                  onClick={() => {
                    setActiveTab(tab.id);
                    setIsSettingsOpen(false);
                  }}
                >
                  <span className="sidebar-link-icon" aria-hidden="true">
                    <Icon />
                  </span>
                  <span className="sidebar-link-label">{tab.label}</span>
                </button>
              );
            })}
            <div className="settings-nav-item">
              <button
                aria-expanded={isSettingsOpen}
                className={isSettingsOpen ? 'sidebar-link sidebar-link-active' : 'sidebar-link'}
                type="button"
                onClick={() => setIsSettingsOpen((current) => !current)}
              >
                <span className="sidebar-link-icon" aria-hidden="true">
                  <SettingsIcon />
                </span>
                <span className="sidebar-link-label">Settings</span>
              </button>
              {isSettingsOpen ? (
                <SettingsPopup selectedTheme={user.theme ?? 'dark'} onThemeChange={handleThemeChange} />
              ) : null}
            </div>
          </nav>
        </aside>

        <section className="workspace">
          {activeTab === 'upload' ? (
            <UploadScreen batchId={batchId} setBatchId={setBatchId} showNotice={showNotice} />
          ) : null}
          {activeTab === 'rules' ? <MappingRulesScreen showNotice={showNotice} /> : null}
          {activeTab === 'review' ? (
            <ReviewRowsScreen batchId={batchId} showNotice={showNotice} />
          ) : null}
          {activeTab === 'reconcile' ? (
            <ReconciliationScreen batchId={batchId} showNotice={showNotice} />
          ) : null}
          {activeTab === 'exports' ? <ExportsScreen batchId={batchId} showNotice={showNotice} /> : null}
        </section>
      </div>
    </main>
  );
}

function LoginScreen({ onLogin }) {
  const [form, setForm] = useState({
    email: 'sjavonitalla@wagnermeters.com',
    password: '',
  });
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  async function submitLogin(event) {
    event.preventDefault();
    setSubmitting(true);
    setError('');

    try {
      const response = await axios.post('/api/login', form);
      onLogin(response.data.data);
    } catch (loginError) {
      setError(messageFromError(loginError, 'Please check your email and password.'));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="login-page">
      <section className="login-card">
        <div className="login-brand">
          <img className="login-logo" src="/images/white-logo.png" alt="Wagner Meters" />
        </div>
        {error ? <div className="login-error">{error}</div> : null}
        <form className="login-form" onSubmit={submitLogin}>
          <label>
            <span className="login-label-text">Email <span>*</span></span>
            <input
              autoComplete="email"
              required
              type="email"
              value={form.email}
              onChange={(event) => setForm({ ...form, email: event.target.value })}
            />
          </label>
          <label>
            <span className="login-label-text">Password <span>*</span></span>
            <input
              autoComplete="current-password"
              required
              type="password"
              value={form.password}
              onChange={(event) => setForm({ ...form, password: event.target.value })}
            />
          </label>
          <button className="login-button" disabled={submitting} type="submit">
            {submitting ? 'Signing in...' : 'Sign in'}
          </button>
        </form>
      </section>
    </main>
  );
}

function FloatingAlert({ notice, onClose }) {
  return (
    <div className={`notice notice-${notice.type}`} role="alert">
      <span className="notice-message">{notice.message}</span>
      <button className="notice-close" type="button" aria-label="Close alert" onClick={onClose}>
        ×
      </button>
    </div>
  );
}

function AppHeader({ batchId, isSidebarCollapsed, setBatchId, user, onLogout, onToggleSidebar }) {
  const userName = [user.first_name, user.last_name].filter(Boolean).join(' ') || user.name;
  const initials = userName
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  return (
    <header className="top-nav">
      <div className="top-nav-left">
        <button
          className="menu-button"
          type="button"
          aria-label={isSidebarCollapsed ? 'Expand navigation' : 'Collapse navigation'}
          aria-pressed={isSidebarCollapsed}
          onClick={onToggleSidebar}
        >
          <span />
          <span />
          <span />
        </button>
        <strong>Analysis Report</strong>
      </div>

      <img className="top-nav-logo" src="/images/white-logo.png" alt="Wagner Meters" />

      <div className="top-nav-right">
        <label className="batch-field">
          <span>Batch ID</span>
          <input
            min="1"
            placeholder="1"
            type="number"
            value={batchId}
            onChange={(event) => setBatchId(event.target.value)}
          />
        </label>
        <div className="user-chip" title={userName}>
          <span className="avatar">{initials}</span>
          <button className="link-button" type="button" onClick={onLogout}>
            Sign out
          </button>
        </div>
      </div>
    </header>
  );
}

function UploadScreen({ batchId, setBatchId, showNotice }) {
  const [selectedFiles, setSelectedFiles] = useState({});
  const [uploadMessage, setUploadMessage] = useState('');
  const [uploading, setUploading] = useState(false);
  const workbookTypes = [
    { key: 'sales_analysis', label: 'Sales Analysis' },
    { key: 'income_statement', label: 'Income Statement' },
    { key: 'total_sales_report', label: 'Total Sales Report' },
    { key: 'weekly_meter_report', label: 'Weekly Meter Report' },
    { key: 'open_orders', label: 'Open Orders' },
    { key: 'ptd_orders', label: 'PTD Orders' },
  ];
  const hasFiles = Object.values(selectedFiles).some(Boolean);

  async function handleUploadClick() {
    if (!hasFiles) {
      setUploadMessage('Choose at least one workbook before importing.');
      return;
    }

    const formData = new FormData();

    Object.entries(selectedFiles).forEach(([type, file]) => {
      if (file) {
        formData.append(type, file);
      }
    });

    if (batchId) {
      formData.append('import_batch_id', batchId);
    }

    setUploading(true);
    setUploadMessage('');

    try {
      const response = await axios.post('/api/workbook-imports', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      const result = response.data.data;
      const nextBatchId = String(result.import_batch.id);

      setBatchId(nextBatchId);
      setUploadMessage(`Imported selected files into batch #${nextBatchId}.`);
      showNotice('success', `Imported ${Object.keys(result.summary).length} workbook type(s).`);
    } catch (error) {
      setUploadMessage(messageFromError(error, 'Unable to upload and import the selected files.'));
    } finally {
      setUploading(false);
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Step 1</p>
            <h2>Upload Weekly Workbooks</h2>
          </div>
          <span className="status-pill status-success">Partial uploads ready</span>
        </div>
        <p>
          Choose one file, several files, or the full weekly set. The app will create a new batch automatically
          unless you enter an existing Active Import Batch ID above.
        </p>
        {uploadMessage ? <div className="upload-message">{uploadMessage}</div> : null}
        <div className="file-grid">
          {workbookTypes.map((type) => (
            <label className="file-card" key={type.key}>
              <span>{type.label}</span>
              <input
                accept=".xlsx"
                type="file"
                onChange={(event) => {
                  setSelectedFiles({
                    ...selectedFiles,
                    [type.key]: event.target.files?.[0] ?? null,
                  });
                }}
              />
            </label>
          ))}
        </div>
        <div className="upload-actions">
          <button className="primary-button" disabled={uploading} type="button" onClick={handleUploadClick}>
            {uploading ? 'Importing...' : 'Upload / Import Selected Files'}
          </button>
        </div>
      </article>
    </section>
  );
}

function MappingRulesScreen({ showNotice }) {
  const emptyRuleForm = {
    name: '',
    product_category_id: '',
    source_type: 'sales_analysis',
    match_field: 'item_id',
    match_operator: 'starts_with',
    pattern: '',
    target_bucket: 'rhp',
    priority: 100,
    is_active: true,
  };
  const [rules, setRules] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedRule, setSelectedRule] = useState(null);
  const [editForm, setEditForm] = useState(emptyRuleForm);
  const [createForm, setCreateForm] = useState(emptyRuleForm);
  const [isCreateOpen, setIsCreateOpen] = useState(false);

  const loadRules = useCallback(async () => {
    setLoading(true);
    try {
      const [rulesResponse, categoriesResponse] = await Promise.all([
        axios.get('/api/mapping-rules'),
        axios.get('/api/product-categories'),
      ]);
      const nextRules = rulesResponse.data.data ?? [];

      setRules(nextRules);
      setCategories(categoriesResponse.data.data ?? []);
      setSelectedRule((current) => {
        if (!current) {
          return null;
        }

        return nextRules.find((rule) => rule.id === current.id) ?? null;
      });
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load mapping rules.'));
    } finally {
      setLoading(false);
    }
  }, [showNotice]);

  useEffect(() => {
    loadRules();
  }, [loadRules]);

  useEffect(() => {
    if (selectedRule) {
      setEditForm(ruleToForm(selectedRule));
    }
  }, [selectedRule]);

  function rulePayload(form) {
    return {
      ...form,
      product_category_id: form.product_category_id || null,
      target_bucket: form.target_bucket || null,
      priority: Number(form.priority),
      is_active: Boolean(form.is_active),
    };
  }

  async function submitCreateRule(event) {
    event.preventDefault();

    try {
      const response = await axios.post('/api/mapping-rules', rulePayload(createForm));

      showNotice('success', 'Mapping rule created.');
      setCreateForm(emptyRuleForm);
      setIsCreateOpen(false);
      await loadRules();
      setSelectedRule(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to create mapping rule.'));
    }
  }

  async function submitEditRule(event) {
    event.preventDefault();

    if (!selectedRule) {
      return;
    }

    try {
      const response = await axios.patch(`/api/mapping-rules/${selectedRule.id}`, rulePayload(editForm));

      showNotice('success', 'Mapping rule updated.');
      await loadRules();
      setSelectedRule(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update mapping rule.'));
    }
  }

  async function deleteSelectedRule() {
    if (!selectedRule || !window.confirm(`Delete rule "${selectedRule.name}"?`)) {
      return;
    }

    try {
      await axios.delete(`/api/mapping-rules/${selectedRule.id}`);
      showNotice('success', 'Mapping rule deleted.');
      setSelectedRule(null);
      await loadRules();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete mapping rule.'));
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Rules</p>
            <h2>Configured Mapping Rules</h2>
          </div>
          <div className="button-row">
            <button className="secondary-button" type="button" onClick={loadRules}>
              Refresh
            </button>
            <button className="primary-button" type="button" onClick={() => setIsCreateOpen(true)}>
              Create Rule
            </button>
          </div>
        </div>
        {loading ? (
          <p>Loading rules...</p>
        ) : (
          <RulesTable rules={rules} selectedRule={selectedRule} onSelectRule={setSelectedRule} />
        )}
      </article>

      <article className="panel">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Details</p>
            <h2>{selectedRule ? 'Edit Mapping Rule' : 'Select a Rule'}</h2>
          </div>
        </div>

        {selectedRule ? (
          <RuleForm
            categories={categories}
            form={editForm}
            primaryLabel="Update Rule"
            setForm={setEditForm}
            onSubmit={submitEditRule}
          >
            <button className="danger-button" type="button" onClick={deleteSelectedRule}>
              Delete Rule
            </button>
          </RuleForm>
        ) : (
          <div className="empty-state">
            <strong>No mapping rule selected</strong>
            <p>Click a mapping rule from the table on the left to view, edit, or delete it.</p>
          </div>
        )}
      </article>

      {isCreateOpen ? (
        <Modal title="Create Mapping Rule" onClose={() => setIsCreateOpen(false)}>
          <RuleForm
            categories={categories}
            form={createForm}
            primaryLabel="Create Rule"
            setForm={setCreateForm}
            onSubmit={submitCreateRule}
          />
        </Modal>
      ) : null}

      <CategoryManager categories={categories} loadRules={loadRules} showNotice={showNotice} />
    </section>
  );
}

function ruleToForm(rule) {
  return {
    name: rule.name ?? '',
    product_category_id: rule.product_category_id ? String(rule.product_category_id) : '',
    source_type: rule.source_type ?? 'sales_analysis',
    match_field: rule.match_field ?? 'item_id',
    match_operator: rule.match_operator ?? 'starts_with',
    pattern: rule.pattern ?? '',
    target_bucket: rule.target_bucket ?? 'rhp',
    priority: rule.priority ?? 100,
    is_active: Boolean(rule.is_active),
  };
}

function RuleForm({ categories, children, form, primaryLabel, setForm, onSubmit }) {
  return (
    <form className="stacked-form" onSubmit={onSubmit}>
      <label>
        Rule Name
        <input
          required
          value={form.name}
          onChange={(event) => setForm({ ...form, name: event.target.value })}
        />
      </label>
      <label>
        Category
        <select
          value={form.product_category_id}
          onChange={(event) => setForm({ ...form, product_category_id: event.target.value })}
        >
          <option value="">No category</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </select>
      </label>
      <label>
        Source Type
        <input
          required
          value={form.source_type}
          onChange={(event) => setForm({ ...form, source_type: event.target.value })}
        />
      </label>
      <div className="rule-match-row">
        <label>
          Field
          <select
            value={form.match_field}
            onChange={(event) => setForm({ ...form, match_field: event.target.value })}
          >
            {['item_id', 'description', 'customer_id', 'customer_name', 'invoice_number', 'sales_rep_id', 'country', 'bill_to_state'].map((field) => (
              <option key={field} value={field}>
                {field}
              </option>
            ))}
          </select>
        </label>
        <label>
          Operator
          <select
            value={form.match_operator}
            onChange={(event) => setForm({ ...form, match_operator: event.target.value })}
          >
            {['exact', 'starts_with', 'ends_with', 'contains', 'regex'].map((operator) => (
              <option key={operator} value={operator}>
                {operator}
              </option>
            ))}
          </select>
        </label>
        <label>
          Pattern
          <input
            required
            value={form.pattern}
            onChange={(event) => setForm({ ...form, pattern: event.target.value })}
          />
        </label>
      </div>
      <div className="form-row">
        <label>
          Bucket
          <select
            value={form.target_bucket}
            onChange={(event) => setForm({ ...form, target_bucket: event.target.value })}
          >
            <option value="">None</option>
            <option value="rhp">RHP</option>
            <option value="parts_tsd">Parts & TSD</option>
            <option value="raw">Raw</option>
          </select>
        </label>
        <label>
          Priority
          <input
            min="1"
            type="number"
            value={form.priority}
            onChange={(event) => setForm({ ...form, priority: event.target.value })}
          />
        </label>
      </div>
      <label className="checkbox-label">
        <input
          checked={form.is_active}
          type="checkbox"
          onChange={(event) => setForm({ ...form, is_active: event.target.checked })}
        />
        Active
      </label>
      <div className="form-actions">
        <button className="primary-button" type="submit">
          {primaryLabel}
        </button>
        {children}
      </div>
    </form>
  );
}

function Modal({ children, title, onClose }) {
  return (
    <div className="modal-backdrop" role="presentation">
      <section className="modal-card" role="dialog" aria-modal="true" aria-label={title}>
        <div className="section-heading">
          <div>
            <p className="eyebrow">Create</p>
            <h2>{title}</h2>
          </div>
          <button className="secondary-button" type="button" onClick={onClose}>
            Close
          </button>
        </div>
        {children}
      </section>
    </div>
  );
}

function CategoryManager({ categories, loadRules, showNotice }) {
  const emptyForm = {
    name: '',
    sales_analysis_bucket: 'rhp',
    total_sales_row_label: '',
    weekly_meter_row_label: '',
    sort_order: 100,
    is_active: true,
  };
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState(null);

  function editCategory(category) {
    setEditingId(category.id);
    setForm({
      name: category.name ?? '',
      sales_analysis_bucket: category.sales_analysis_bucket ?? '',
      total_sales_row_label: category.total_sales_row_label ?? '',
      weekly_meter_row_label: category.weekly_meter_row_label ?? '',
      sort_order: category.sort_order ?? 100,
      is_active: Boolean(category.is_active),
    });
  }

  function resetCategoryForm() {
    setEditingId(null);
    setForm(emptyForm);
  }

  async function submitCategory(event) {
    event.preventDefault();

    const payload = {
      ...form,
      sort_order: Number(form.sort_order),
      sales_analysis_bucket: form.sales_analysis_bucket || null,
      total_sales_row_label: form.total_sales_row_label || null,
      weekly_meter_row_label: form.weekly_meter_row_label || null,
    };

    try {
      if (editingId) {
        await axios.patch(`/api/product-categories/${editingId}`, payload);
        showNotice('success', 'Category updated.');
      } else {
        await axios.post('/api/product-categories', payload);
        showNotice('success', 'Category created.');
      }

      resetCategoryForm();
      await loadRules();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to save category.'));
    }
  }

  async function deleteCategory(category) {
    if (!window.confirm(`Delete category "${category.name}"?`)) {
      return;
    }

    try {
      await axios.delete(`/api/product-categories/${category.id}`);
      showNotice('success', 'Category deleted.');
      await loadRules();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete category.'));
    }
  }

  return (
    <article className="panel panel-span">
      <div className="section-heading">
        <div>
          <p className="eyebrow">Categories</p>
          <h2>Category CRUD</h2>
        </div>
        {editingId ? (
          <button className="secondary-button" type="button" onClick={resetCategoryForm}>
            Cancel Edit
          </button>
        ) : null}
      </div>

      <form className="category-form" onSubmit={submitCategory}>
        <label>
          Name
          <input
            required
            value={form.name}
            onChange={(event) => setForm({ ...form, name: event.target.value })}
          />
        </label>
        <label>
          Bucket
          <select
            value={form.sales_analysis_bucket}
            onChange={(event) => setForm({ ...form, sales_analysis_bucket: event.target.value })}
          >
            <option value="">None</option>
            <option value="rhp">RHP</option>
            <option value="parts_tsd">Parts & TSD</option>
            <option value="raw">Raw</option>
          </select>
        </label>
        <label>
          Total Sales Label
          <input
            value={form.total_sales_row_label}
            onChange={(event) => setForm({ ...form, total_sales_row_label: event.target.value })}
          />
        </label>
        <label>
          Weekly Meter Label
          <input
            value={form.weekly_meter_row_label}
            onChange={(event) => setForm({ ...form, weekly_meter_row_label: event.target.value })}
          />
        </label>
        <label>
          Sort Order
          <input
            min="0"
            type="number"
            value={form.sort_order}
            onChange={(event) => setForm({ ...form, sort_order: event.target.value })}
          />
        </label>
        <label className="checkbox-label">
          <input
            checked={form.is_active}
            type="checkbox"
            onChange={(event) => setForm({ ...form, is_active: event.target.checked })}
          />
          Active
        </label>
        <button className="primary-button" type="submit">
          {editingId ? 'Update Category' : 'Create Category'}
        </button>
      </form>

      <CategoryTable categories={categories} deleteCategory={deleteCategory} editCategory={editCategory} />
    </article>
  );
}

function CategoryTable({ categories, editCategory, deleteCategory }) {
  if (categories.length === 0) {
    return <p>No categories found.</p>;
  }

  return (
    <div className="table-wrap table-section">
      <table>
        <thead>
          <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Bucket</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {categories.map((category) => (
            <tr key={category.id}>
              <td>{category.code}</td>
              <td>{category.name}</td>
              <td>{category.sales_analysis_bucket ?? 'None'}</td>
              <td>{category.is_active ? 'Active' : 'Inactive'}</td>
              <td>
                <div className="button-row">
                  <button className="secondary-button" type="button" onClick={() => editCategory(category)}>
                    Edit
                  </button>
                  <button className="danger-button" type="button" onClick={() => deleteCategory(category)}>
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RulesTable({ rules, selectedRule, onSelectRule }) {
  if (rules.length === 0) {
    return <p>No mapping rules found.</p>;
  }

  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Priority</th>
            <th>Name</th>
            <th>Match</th>
            <th>Category</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {rules.map((rule) => (
            <tr
              className={selectedRule?.id === rule.id ? 'clickable-row selected-row' : 'clickable-row'}
              key={rule.id}
              onClick={() => onSelectRule(rule)}
            >
              <td>{rule.priority}</td>
              <td>{rule.name}</td>
              <td>
                {rule.match_field} {rule.match_operator} {rule.pattern}
              </td>
              <td>{rule.product_category?.name ?? 'Unassigned'}</td>
              <td>{rule.is_active ? 'Active' : 'Inactive'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function ReviewRowsScreen({ batchId, showNotice }) {
  const [rows, setRows] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [selectedBucket, setSelectedBucket] = useState('rhp');

  const canLoad = Boolean(batchId);

  const loadRows = useCallback(async () => {
    if (!canLoad) {
      return;
    }
    setLoading(true);
    try {
      const [rowsResponse, categoriesResponse] = await Promise.all([
        axios.get(`/api/import-batches/${batchId}/unmatched-sales-rows`),
        axios.get('/api/product-categories'),
      ]);
      setRows(rowsResponse.data.data ?? []);
      setCategories(categoriesResponse.data.data ?? []);
      setSelectedCategory((current) => current || String((categoriesResponse.data.data ?? [])[0]?.id ?? ''));
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load unmatched rows.'));
    } finally {
      setLoading(false);
    }
  }, [batchId, canLoad, showNotice]);

  useEffect(() => {
    loadRows();
  }, [loadRows]);

  async function classifyBatch() {
    try {
      const response = await axios.post(`/api/import-batches/${batchId}/classify-sales-rows`);
      const summary = response.data.data;
      showNotice('success', `Classified ${summary.total} rows: ${summary.matched} matched, ${summary.unmatched} unmatched.`);
      await loadRows();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to classify rows.'));
    }
  }

  async function resolveRow(row) {
    try {
      await axios.patch(`/api/sales-rows/${row.id}/classification`, {
        product_category_id: Number(selectedCategory),
        source_bucket: selectedBucket,
        notes: 'Resolved from weekly analysis UI.',
      });
      showNotice('success', `Resolved row ${row.source_row_number}.`);
      await loadRows();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to resolve row.'));
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Review</p>
            <h2>Unmatched Sales Rows</h2>
          </div>
          <div className="button-row">
            <button className="secondary-button" disabled={!canLoad} type="button" onClick={loadRows}>
              Refresh
            </button>
            <button className="primary-button" disabled={!canLoad} type="button" onClick={classifyBatch}>
              Run Rules
            </button>
          </div>
        </div>
        {!canLoad ? <p>Enter an active import batch ID to review unmatched rows.</p> : null}
        {loading ? <p>Loading unmatched rows...</p> : null}
        {canLoad && !loading ? (
          <>
            <div className="review-controls">
              <label>
                Resolve As
                <select value={selectedCategory} onChange={(event) => setSelectedCategory(event.target.value)}>
                  {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                Bucket
                <select value={selectedBucket} onChange={(event) => setSelectedBucket(event.target.value)}>
                  <option value="rhp">RHP</option>
                  <option value="parts_tsd">Parts & TSD</option>
                  <option value="raw">Raw</option>
                </select>
              </label>
            </div>
            <RowsTable rows={rows} resolveRow={resolveRow} />
          </>
        ) : null}
      </article>
    </section>
  );
}

function RowsTable({ rows, resolveRow }) {
  if (rows.length === 0) {
    return <p>No unmatched rows found.</p>;
  }

  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Row</th>
            <th>Item</th>
            <th>Description</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.id}>
              <td>{row.source_row_number}</td>
              <td>{row.item_id}</td>
              <td>{row.description}</td>
              <td>{row.customer_name}</td>
              <td>{currency(row.amount)}</td>
              <td>
                <button className="secondary-button" type="button" onClick={() => resolveRow(row)}>
                  Resolve
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function ReconciliationScreen({ batchId, showNotice }) {
  const [result, setResult] = useState(null);
  const [totals, setTotals] = useState([]);
  const [fees, setFees] = useState([]);
  const [feeForm, setFeeForm] = useState({ marketplace: 'Amazon', amount: '', description: '' });

  const canLoad = Boolean(batchId);

  const loadData = useCallback(async () => {
    if (!canLoad) {
      return;
    }
    try {
      const [reconciliationResponse, totalsResponse, feesResponse] = await Promise.all([
        axios.get(`/api/import-batches/${batchId}/reconciliation`),
        axios.get(`/api/import-batches/${batchId}/report-totals`),
        axios.get(`/api/import-batches/${batchId}/marketplace-fees`),
      ]);
      setResult(reconciliationResponse.data.data);
      setTotals(totalsResponse.data.data ?? []);
      setFees(feesResponse.data.data ?? []);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load reconciliation data.'));
    }
  }, [batchId, canLoad, showNotice]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  async function runReconciliation() {
    try {
      const response = await axios.post(`/api/import-batches/${batchId}/reconciliation`, { tolerance: 0.01 });
      setResult(response.data.data.reconciliation);
      setTotals(response.data.data.report_totals ?? []);
      showNotice('success', 'Reconciliation calculated.');
      await loadData();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to run reconciliation.'));
    }
  }

  async function addFee(event) {
    event.preventDefault();
    try {
      await axios.post(`/api/import-batches/${batchId}/marketplace-fees`, {
        ...feeForm,
        amount: Number(feeForm.amount),
      });
      setFeeForm({ marketplace: 'Amazon', amount: '', description: '' });
      showNotice('success', 'Marketplace fee added.');
      await loadData();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to add marketplace fee.'));
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Reconciliation</p>
            <h2>Balance Sales Analysis to Income Statement</h2>
          </div>
          <button className="primary-button" disabled={!canLoad} type="button" onClick={runReconciliation}>
            Run Reconciliation
          </button>
        </div>
        {!canLoad ? <p>Enter an active import batch ID to run reconciliation.</p> : null}
        {result ? <SummaryCards result={result} /> : null}
      </article>

      <article className="panel">
        <h2>Marketplace Fees</h2>
        <form className="stacked-form" onSubmit={addFee}>
          <label>
            Marketplace
            <select
              value={feeForm.marketplace}
              onChange={(event) => setFeeForm({ ...feeForm, marketplace: event.target.value })}
            >
              <option value="Amazon">Amazon</option>
              <option value="Walmart">Walmart</option>
            </select>
          </label>
          <label>
            Amount
            <input
              required
              step="0.01"
              type="number"
              value={feeForm.amount}
              onChange={(event) => setFeeForm({ ...feeForm, amount: event.target.value })}
            />
          </label>
          <label>
            Description
            <input
              value={feeForm.description}
              onChange={(event) => setFeeForm({ ...feeForm, description: event.target.value })}
            />
          </label>
          <button className="secondary-button" disabled={!canLoad} type="submit">
            Add Fee
          </button>
        </form>
        <SimpleList items={fees.map((fee) => `${fee.marketplace}: ${currency(fee.amount)}`)} />
      </article>

      <article className="panel">
        <h2>Report Metrics</h2>
        <SimpleList items={totals.map((total) => `${total.metric_key}: ${currency(total.amount)}`)} />
      </article>
    </section>
  );
}

function SummaryCards({ result }) {
  const cards = [
    ['Sales Analysis', result.sales_analysis_total],
    ['Income Statement', result.income_statement_total],
    ['Marketplace Fees', result.marketplace_fee_total],
    ['Adjusted IS', result.adjusted_income_statement_total],
    ['Difference', result.difference],
  ];

  return (
    <div className="summary-grid">
      {cards.map(([label, value]) => (
        <div className="summary-card" key={label}>
          <span>{label}</span>
          <strong>{currency(value)}</strong>
        </div>
      ))}
      <div className="summary-card">
        <span>Status</span>
        <strong>{result.is_balanced ? 'Balanced' : 'Needs Review'}</strong>
      </div>
    </div>
  );
}

function ExportsScreen({ batchId, showNotice }) {
  const [reports, setReports] = useState([]);
  const canLoad = Boolean(batchId);

  const loadReports = useCallback(async () => {
    if (!canLoad) {
      return;
    }
    try {
      const response = await axios.get(`/api/import-batches/${batchId}/generated-reports`);
      setReports(response.data.data ?? []);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load generated reports.'));
    }
  }, [batchId, canLoad, showNotice]);

  useEffect(() => {
    loadReports();
  }, [loadReports]);

  async function generateReports() {
    try {
      const response = await axios.post(`/api/import-batches/${batchId}/generated-reports`);
      setReports(response.data.data ?? []);
      showNotice('success', 'Report export finished.');
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to generate reports.'));
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Exports</p>
            <h2>Generated Excel Reports</h2>
          </div>
          <div className="button-row">
            <button className="secondary-button" disabled={!canLoad} type="button" onClick={loadReports}>
              Refresh
            </button>
            <button className="primary-button" disabled={!canLoad} type="button" onClick={generateReports}>
              Generate Reports
            </button>
          </div>
        </div>
        {!canLoad ? <p>Enter an active import batch ID to generate reports.</p> : null}
        <ReportsTable reports={reports} />
      </article>
    </section>
  );
}

function SettingsPopup({ selectedTheme, onThemeChange }) {
  return (
    <div className="settings-popup">
      <div className="settings-section">
        <h3>Theme</h3>
        <div className="theme-grid">
          {themes.map((theme) => (
            <button
              className={selectedTheme === theme.id ? 'theme-card theme-card-active' : 'theme-card'}
              key={theme.id}
              type="button"
              onClick={() => onThemeChange(theme.id)}
            >
              <span className={`theme-preview theme-preview-${theme.id}`} aria-hidden="true" />
              <strong>{theme.label}</strong>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

function ReportsTable({ reports }) {
  if (reports.length === 0) {
    return <p>No generated reports yet.</p>;
  }

  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Report</th>
            <th>Status</th>
            <th>File</th>
            <th>Download</th>
          </tr>
        </thead>
        <tbody>
          {reports.map((report) => (
            <tr key={report.id ?? report.report_type}>
              <td>{report.report_type}</td>
              <td>{report.status}</td>
              <td>{report.file_name ?? 'n/a'}</td>
              <td>
                {report.status === 'completed' ? (
                  <a className="secondary-button" href={`/api/generated-reports/${report.id}/download`}>
                    Download
                  </a>
                ) : (
                  'Unavailable'
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function SimpleList({ items }) {
  const normalizedItems = useMemo(() => items.filter(Boolean), [items]);

  if (normalizedItems.length === 0) {
    return <p>No records yet.</p>;
  }

  return (
    <ul className="simple-list">
      {normalizedItems.map((item) => (
        <li key={item}>{item}</li>
      ))}
    </ul>
  );
}

function currency(value) {
  const numberValue = Number(value ?? 0);

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(numberValue);
}

function messageFromError(error, fallback) {
  return error?.response?.data?.message ?? fallback;
}

createRoot(document.getElementById('app')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
