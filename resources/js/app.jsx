import axios from 'axios';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const tabs = [
  { id: 'upload', label: 'Upload' },
  { id: 'rules', label: 'Mapping Rules' },
  { id: 'review', label: 'Review Rows' },
  { id: 'reconcile', label: 'Reconcile' },
  { id: 'exports', label: 'Exports' },
];

function App() {
  const [activeTab, setActiveTab] = useState('upload');
  const [batchId, setBatchId] = useState('');
  const [notice, setNotice] = useState(null);

  const showNotice = useCallback((type, message) => {
    setNotice({ type, message });
  }, []);

  return (
    <main className="app-shell">
      <AppHeader batchId={batchId} setBatchId={setBatchId} />

      {notice ? (
        <div className={`notice notice-${notice.type}`} role="status">
          {notice.message}
          <button className="link-button" type="button" onClick={() => setNotice(null)}>
            Dismiss
          </button>
        </div>
      ) : null}

      <nav className="tab-list" aria-label="Weekly analysis workflow">
        {tabs.map((tab) => (
          <button
            className={activeTab === tab.id ? 'tab-button tab-button-active' : 'tab-button'}
            key={tab.id}
            type="button"
            onClick={() => setActiveTab(tab.id)}
          >
            {tab.label}
          </button>
        ))}
      </nav>

      {activeTab === 'upload' ? <UploadScreen /> : null}
      {activeTab === 'rules' ? <MappingRulesScreen showNotice={showNotice} /> : null}
      {activeTab === 'review' ? (
        <ReviewRowsScreen batchId={batchId} showNotice={showNotice} />
      ) : null}
      {activeTab === 'reconcile' ? (
        <ReconciliationScreen batchId={batchId} showNotice={showNotice} />
      ) : null}
      {activeTab === 'exports' ? <ExportsScreen batchId={batchId} showNotice={showNotice} /> : null}
    </main>
  );
}

function AppHeader({ batchId, setBatchId }) {
  return (
    <section className="hero-card">
      <div>
        <p className="eyebrow">Weekly Sales Automation</p>
        <h1>Sales analysis built for Traverse exports</h1>
        <p className="hero-copy">
          Review mappings, reconcile totals, and generate Excel outputs from one weekly workflow.
        </p>
      </div>

      <label className="batch-card">
        <span>Active Import Batch ID</span>
        <input
          min="1"
          placeholder="Example: 1"
          type="number"
          value={batchId}
          onChange={(event) => setBatchId(event.target.value)}
        />
      </label>
    </section>
  );
}

function UploadScreen() {
  const workbookTypes = [
    'Sales Analysis',
    'Income Statement',
    'Total Sales Report',
    'Weekly Meter Report',
    'Open Orders',
    'PTD Orders',
  ];

  return (
    <section className="panel-grid">
      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Step 1</p>
            <h2>Upload Weekly Workbooks</h2>
          </div>
          <span className="status-pill status-warning">Backend upload endpoint pending</span>
        </div>
        <p>
          The parser and workbook validation command are ready. Persisted browser uploads will be wired when
          the import-storage endpoint is added; until then, validate files from the terminal.
        </p>
        <div className="file-grid">
          {workbookTypes.map((type) => (
            <label className="file-card" key={type}>
              <span>{type}</span>
              <input disabled type="file" />
            </label>
          ))}
        </div>
      </article>
    </section>
  );
}

function MappingRulesScreen({ showNotice }) {
  const [rules, setRules] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({
    name: '',
    product_category_id: '',
    source_type: 'sales_analysis',
    match_field: 'item_id',
    match_operator: 'starts_with',
    pattern: '',
    target_bucket: 'rhp',
    priority: 100,
    is_active: true,
  });

  const loadRules = useCallback(async () => {
    setLoading(true);
    try {
      const [rulesResponse, categoriesResponse] = await Promise.all([
        axios.get('/api/mapping-rules'),
        axios.get('/api/product-categories'),
      ]);
      setRules(rulesResponse.data.data ?? []);
      setCategories(categoriesResponse.data.data ?? []);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load mapping rules.'));
    } finally {
      setLoading(false);
    }
  }, [showNotice]);

  useEffect(() => {
    loadRules();
  }, [loadRules]);

  async function submitRule(event) {
    event.preventDefault();
    try {
      await axios.post('/api/mapping-rules', {
        ...form,
        product_category_id: form.product_category_id || null,
        priority: Number(form.priority),
      });
      showNotice('success', 'Mapping rule created.');
      setForm((current) => ({ ...current, name: '', pattern: '' }));
      await loadRules();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to create mapping rule.'));
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
          <button className="secondary-button" type="button" onClick={loadRules}>
            Refresh
          </button>
        </div>
        {loading ? <p>Loading rules...</p> : <RulesTable rules={rules} />}
      </article>

      <article className="panel">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Create</p>
            <h2>New Rule</h2>
          </div>
        </div>
        <form className="stacked-form" onSubmit={submitRule}>
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
          <div className="form-row">
            <label>
              Field
              <select
                value={form.match_field}
                onChange={(event) => setForm({ ...form, match_field: event.target.value })}
              >
                {['item_id', 'description', 'customer_name', 'sales_rep_id', 'country'].map((field) => (
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
          </div>
          <label>
            Pattern
            <input
              required
              value={form.pattern}
              onChange={(event) => setForm({ ...form, pattern: event.target.value })}
            />
          </label>
          <div className="form-row">
            <label>
              Bucket
              <select
                value={form.target_bucket}
                onChange={(event) => setForm({ ...form, target_bucket: event.target.value })}
              >
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
          <button className="primary-button" type="submit">
            Create Rule
          </button>
        </form>
      </article>
    </section>
  );
}

function RulesTable({ rules }) {
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
            <tr key={rule.id}>
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
