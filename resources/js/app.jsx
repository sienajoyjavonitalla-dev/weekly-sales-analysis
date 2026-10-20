import axios from 'axios';
import React, { Fragment, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { createRoot } from 'react-dom/client';
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function DashboardIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M4 4h7v9H4z" />
      <path d="M13 4h7v5h-7z" />
      <path d="M13 11h7v9h-7z" />
      <path d="M4 15h7v5H4z" />
    </svg>
  );
}

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

function CategoriesIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M5 5h6v6H5z" />
      <path d="M13 5h6v6h-6z" />
      <path d="M5 13h6v6H5z" />
      <path d="M13 13h6v6h-6z" />
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

function CalendarIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M7 4v3" />
      <path d="M17 4v3" />
      <path d="M5 6h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1z" />
      <path d="M4 10h16" />
      <path d="M8 14h2" />
      <path d="M14 14h2" />
    </svg>
  );
}

function MeterReportIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M4 19V5" />
      <path d="M4 19h16" />
      <path d="M8 16v-5" />
      <path d="M12 16V8" />
      <path d="M16 16v-3" />
      <path d="M20 16v-8" />
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

function HowToUseIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M5 4h11a2 2 0 012 2v14H7a2 2 0 01-2-2V4z" />
      <path d="M7 4v14" />
      <path d="M10 8h6" />
      <path d="M10 12h6" />
      <path d="M10 16h4" />
    </svg>
  );
}

function UsersIcon() {
  return (
    <svg aria-hidden="true" fill="none" viewBox="0 0 24 24">
      <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
      <path d="M9 11a4 4 0 100-8 4 4 0 000 8z" />
      <path d="M22 21v-2a4 4 0 00-3-3.87" />
      <path d="M16 3.13a4 4 0 010 7.75" />
    </svg>
  );
}

function ActionIcon({ name }) {
  const paths = {
    add: ['M12 5v14', 'M5 12h14'],
    back: ['M15 6l-6 6 6 6', 'M9 12h10'],
    cancel: ['M6 6l12 12', 'M18 6L6 18'],
    calendar: ['M7 4v2', 'M17 4v2', 'M5 8h14', 'M6 5h12a2 2 0 012 2v13a2 2 0 01-2 2H8a2 2 0 01-2-2V7a2 2 0 012-2z'],
    check: ['M5 13l4 4L19 7'],
    chevron: ['M6 9l6 6 6-6'],
    copy: [
      'M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1',
      'M11 9h9a2 2 0 012 2v9a2 2 0 01-2 2h-9a2 2 0 01-2-2v-9a2 2 0 012-2z',
    ],
    delete: ['M5 7h14', 'M10 11v6', 'M14 11v6', 'M8 7l1-3h6l1 3', 'M7 7l1 13h8l1-13'],
    download: ['M12 4v10', 'M8 10l4 4 4-4', 'M5 20h14'],
    edit: ['M5 19l4-1 9-9-3-3-9 9-1 4z', 'M14 6l3 3'],
    eye: ['M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z', 'M12 15a3 3 0 100-6 3 3 0 000 6z'],
    export: ['M6 4h9l3 3v13H6z', 'M14 4v4h4', 'M12 11v6', 'M9 14l3 3 3-3'],
    info: ['M12 22a10 10 0 110-20 10 10 0 010 20z', 'M12 16v-4', 'M12 8h.01'],
    login: ['M14 6h4v12h-4', 'M10 8l4 4-4 4', 'M4 12h10'],
    logout: ['M10 6H6v12h4', 'M14 8l4 4-4 4', 'M8 12h10'],
    refresh: ['M17 3v5h-5', 'M7 21v-5h5', 'M17 8a7 7 0 00-12 3', 'M7 16a7 7 0 0012-3'],
    resolve: ['M5 12l4 4L19 6', 'M5 20h14'],
    run: ['M8 5v14l11-7z'],
    save: ['M5 4h12l2 2v14H5z', 'M8 4v6h8V4', 'M8 16h8v4', 'M10 7h4'],
    upload: ['M12 15V4', 'M7 9l5-5 5 5', 'M5 15v4h14v-4'],
  };

  return (
    <svg className="button-icon" aria-hidden="true" fill="none" viewBox="0 0 24 24">
      {(paths[name] ?? paths.add).map((path) => (
        <path d={path} key={path} />
      ))}
    </svg>
  );
}

function LoadingSpinner({ className = 'button-spinner' }) {
  return (
    <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity="0.25" strokeWidth="2" />
      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" strokeLinecap="round" strokeWidth="2" />
    </svg>
  );
}

function ButtonContent({ icon, loading = false, children }) {
  return (
    <>
      {loading ? <LoadingSpinner /> : icon ? <ActionIcon name={icon} /> : null}
      <span>{children}</span>
    </>
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
  { id: 'dashboard', label: 'Dashboard', icon: DashboardIcon },
  { id: 'calendar', label: 'Calendar', icon: CalendarIcon },
  { id: 'upload', label: 'Upload', icon: UploadIcon },
  { id: 'meter-report', label: 'Meter Report', icon: MeterReportIcon },
  { id: 'categories', label: 'Categories', icon: CategoriesIcon },
  { id: 'rules', label: 'Mapping Rules', icon: RulesIcon },
  { id: 'reconcile', label: 'Reconcile', icon: ReconcileIcon },
];

const userRoleOptions = [
  { value: 'admin', label: 'Admin' },
  { value: 'analyst', label: 'Analyst' },
];

const workbookTypes = [
  { key: 'sales_analysis', label: 'Sales Analysis' },
  { key: 'income_statement', label: 'Income Statement' },
  { key: 'total_sales_report', label: 'Total Sales Report' },
  { key: 'open_orders', label: 'Open Orders' },
  { key: 'ptd_orders', label: 'PTD Orders' },
];

const weeklyMeterModelLabels = [
  'RHP 4.0 Kits',
  'RHP L6 Kits',
  'RHP 4.0 Readr/Sens Pk',
  'RHP L6 Readr/Sens Pk',
  'RHP 4.0 5Pk Sensors*',
  'RHP L6 5Pk Sensors*',
  'RHP 4.0 Misc',
  'RHP L6 Misc',
  'BT Smart Loggers',
  'RHP 4.0 Flooring Kits',
  'RHP L6 Flooring Kits',
  'True Remote Monitoring',
  'Floor Sentry',
  '5.0 RHP Prod',
  'C555 Concrete Kits',
  'C575',
  'BI 2200',
  'MMI 1100',
  'MMC205',
  'L607',
  'MMC210',
  'MMC220',
  'Orion 910',
  'Orion 920',
  'Orion 930',
  'Orion 940',
  'Orion 950',
  'Handmeter Misc',
  'L5200/L5300',
  'L601-3',
  'L610/L620',
  'L612/L622',
  'L722-L',
  'L722-S',
  'L622/L722 Kits',
  'L5300/L722',
];

const monthNames = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

const salesAnalysisBucketOptions = [
  { value: 'rhp', label: 'RHP' },
  { value: 'parts_tsd', label: 'PARTS & TSD' },
  { value: 'state', label: 'STATE' },
];

const RECONCILE_SESSION_KEY = 'weeklySalesAnalysisReconcileSession';

function markReconcileSessionActive() {
  sessionStorage.setItem(RECONCILE_SESSION_KEY, '1');
}

function clearReconcileSession() {
  sessionStorage.removeItem(RECONCILE_SESSION_KEY);
}

function isReconcileSessionActive() {
  return sessionStorage.getItem(RECONCILE_SESSION_KEY) === '1';
}

async function discardAbandonedPendingReconciles() {
  if (!isReconcileSessionActive()) {
    return 0;
  }

  clearReconcileSession();

  const response = await axios.post('/api/import-batches/discard-pending-reconciles');

  return response.data.data?.discarded_count ?? 0;
}

function salesAnalysisBucketLabel(bucket) {
  if (!bucket) {
    return null;
  }

  return salesAnalysisBucketOptions.find((option) => option.value === bucket)?.label ?? bucket;
}

// Saving a category or mapping rule re-applies it to every generated report; tell the user when that happened.
function reportsRefreshedSuffix(response) {
  const count = response?.data?.meta?.reports_refreshed ?? 0;

  if (count <= 0) {
    return '';
  }

  return ` Sales Analysis and Meter Report refreshed for ${count} ${count === 1 ? 'week' : 'weeks'}.`;
}

async function copyTextToClipboard(text) {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(text);

    return;
  }

  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.setAttribute('readonly', '');
  textarea.style.position = 'absolute';
  textarea.style.left = '-9999px';
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand('copy');
  document.body.removeChild(textarea);
}

const SALES_ANALYSIS_ROUTE_PATTERN = /^\/sales-analysis\/(\d+)\/?$/;

function parseSalesAnalysisReportId(pathname) {
  return pathname.match(SALES_ANALYSIS_ROUTE_PATTERN)?.[1] ?? null;
}

function salesAnalysisReportPath(reportId) {
  return `/sales-analysis/${reportId}`;
}

function navigateTo(path) {
  if (window.location.pathname !== path) {
    window.history.pushState({}, '', path);
  }

  window.dispatchEvent(new PopStateEvent('popstate'));
}

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [meterReportTarget, setMeterReportTarget] = useState(null);
  // Month last shown on the calendar ({ year, month } with a 0-based month), so returning to it restores the view.
  const [calendarMonth, setCalendarMonth] = useState(null);
  const [salesAnalysisReportId, setSalesAnalysisReportId] = useState(() =>
    parseSalesAnalysisReportId(window.location.pathname),
  );
  const [batchId, setBatchId] = useState('');
  const [batches, setBatches] = useState([]);
  const [batchesLoading, setBatchesLoading] = useState(false);
  const [notice, setNotice] = useState(null);
  const [user, setUser] = useState(null);
  const [authLoading, setAuthLoading] = useState(true);
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);

  const showNotice = useCallback((type, message) => {
    setNotice({ type, message });
  }, []);

  const hasBootstrappedBatchesRef = useRef(false);

  const loadBatches = useCallback(async () => {
    setBatchesLoading(true);

    try {
      const response = await axios.get('/api/import-batches');
      const nextBatches = response.data.data ?? [];
      const selectableBatches = nextBatches.filter(batchHasUploads);

      setBatches(nextBatches);
      setBatchId((current) => {
        if (current && selectableBatches.some((batch) => String(batch.id) === String(current))) {
          return current;
        }

        return selectableBatches[0] ? String(selectableBatches[0].id) : '';
      });
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load import batches.'));
    } finally {
      setBatchesLoading(false);
    }
  }, [showNotice]);

  const batchesWithUploads = useMemo(() => batches.filter(batchHasUploads), [batches]);

  useEffect(() => {
    function syncRouteFromLocation() {
      setSalesAnalysisReportId(parseSalesAnalysisReportId(window.location.pathname));
    }

    window.addEventListener('popstate', syncRouteFromLocation);

    return () => window.removeEventListener('popstate', syncRouteFromLocation);
  }, []);

  const selectTab = useCallback((tabId) => {
    if (parseSalesAnalysisReportId(window.location.pathname)) {
      navigateTo('/');
    }

    setMeterReportTarget(null);
    setActiveTab(tabId);
    setIsSettingsOpen(false);
  }, []);

  // When a Sales Analysis is opened directly (no calendar visit yet), fall back to that report's month.
  const handleSalesAnalysisLoaded = useCallback((report) => {
    const weekEnding = report?.import_batch?.week_ending;

    if (!weekEnding) {
      return;
    }

    const date = parseIsoDate(weekEnding);

    setCalendarMonth((current) => current ?? { year: date.getFullYear(), month: date.getMonth() });
  }, []);

  const openSalesAnalysisFromCalendar = useCallback((reportId) => {
    navigateTo(salesAnalysisReportPath(reportId));
  }, []);

  const openMeterReportFromCalendar = useCallback((year, month) => {
    setMeterReportTarget({ year, month });
    setActiveTab('meter-report');
  }, []);

  // Sales Analysis is opened from the calendar, so the calendar is where "Back" returns to.
  const sidebarActiveTab = salesAnalysisReportId ? 'calendar' : activeTab;
  const visibleTab = salesAnalysisReportId ? null : activeTab;

  useEffect(() => {
    if (user && user.role !== 'admin' && activeTab === 'users') {
      setActiveTab('upload');
    }
  }, [user, activeTab]);

  useEffect(() => {
    axios
      .get('/api/me')
      .then((response) => setUser(response.data.data))
      .catch(() => setUser(null))
      .finally(() => setAuthLoading(false));
  }, []);

  useEffect(() => {
    if (!user) {
      setBatches([]);
      setBatchId('');
      hasBootstrappedBatchesRef.current = false;
      return;
    }

    if (hasBootstrappedBatchesRef.current) {
      return;
    }

    hasBootstrappedBatchesRef.current = true;

    let cancelled = false;

    (async () => {
      try {
        const discardedCount = await discardAbandonedPendingReconciles();

        if (cancelled) {
          return;
        }

        await loadBatches();

        if (discardedCount > 0) {
          showNotice('success', 'Unreconciled Sales Analysis upload was discarded.');
        }
      } catch (error) {
        if (!cancelled) {
          showNotice('error', messageFromError(error, 'Unable to load import batches.'));
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [user, loadBatches, showNotice]);

  useEffect(() => {
    if (!user) {
      return;
    }

    if (['upload', 'reconcile'].includes(activeTab)) {
      loadBatches();
    }
  }, [activeTab, loadBatches, user]);

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
      setBatches([]);
      setBatchId('');
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
        isSidebarCollapsed={isSidebarCollapsed}
        user={user}
        showNotice={showNotice}
        onUserUpdated={setUser}
        onLogout={handleLogout}
        onToggleSidebar={() => setIsSidebarCollapsed((current) => !current)}
      />

      {notice ? <FloatingAlert notice={notice} onClose={() => setNotice(null)} /> : null}

      <div className={isSidebarCollapsed ? 'app-frame app-frame-collapsed' : 'app-frame'}>
        <aside className={isSidebarCollapsed ? 'sidebar sidebar-collapsed' : 'sidebar'} aria-label="Primary navigation">
          <div className="sidebar-title">Workflow</div>
          <nav className="sidebar-nav" aria-label="Weekly analysis workflow">
            <div className="sidebar-nav-main">
              {workflowTabs.map((tab) => {
                const Icon = tab.icon;

                return (
                  <button
                    className={sidebarActiveTab === tab.id ? 'sidebar-link sidebar-link-active' : 'sidebar-link'}
                    key={tab.id}
                    type="button"
                    onClick={() => selectTab(tab.id)}
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
              {user?.role === 'admin' ? (
                <button
                  className={sidebarActiveTab === 'users' ? 'sidebar-link sidebar-link-active' : 'sidebar-link'}
                  type="button"
                  onClick={() => selectTab('users')}
                >
                  <span className="sidebar-link-icon" aria-hidden="true">
                    <UsersIcon />
                  </span>
                  <span className="sidebar-link-label">Users</span>
                </button>
              ) : null}
            </div>
            <div className="sidebar-nav-footer">
              <button
                className={sidebarActiveTab === 'how-to-use' ? 'sidebar-link sidebar-link-active' : 'sidebar-link'}
                type="button"
                onClick={() => selectTab('how-to-use')}
              >
                <span className="sidebar-link-icon" aria-hidden="true">
                  <HowToUseIcon />
                </span>
                <span className="sidebar-link-label">How to Use</span>
              </button>
            </div>
          </nav>
        </aside>

        <section className="workspace">
          {salesAnalysisReportId ? (
            <SalesAnalysisReportScreen
              key={salesAnalysisReportId}
              reportId={salesAnalysisReportId}
              showNotice={showNotice}
              onLoaded={handleSalesAnalysisLoaded}
              onBack={() => {
                setActiveTab('calendar');
                navigateTo('/');
              }}
            />
          ) : null}
          {visibleTab === 'dashboard' ? (
            <DashboardScreen
              showNotice={showNotice}
              onOpenCalendar={() => selectTab('calendar')}
              onOpenMeterReport={openMeterReportFromCalendar}
            />
          ) : null}
          {visibleTab === 'calendar' ? (
            <ReportCalendarScreen
              initialMonth={calendarMonth}
              showNotice={showNotice}
              onMonthChange={setCalendarMonth}
              onOpenMeterReport={openMeterReportFromCalendar}
              onOpenSalesAnalysis={openSalesAnalysisFromCalendar}
            />
          ) : null}
          {visibleTab === 'upload' ? (
            <UploadScreen
              batchId={batchId}
              batches={batchesWithUploads}
              batchesLoading={batchesLoading}
              loadBatches={loadBatches}
              setBatchId={setBatchId}
              showNotice={showNotice}
            />
          ) : null}
          {visibleTab === 'rules' ? <MappingRulesScreen showNotice={showNotice} /> : null}
          {visibleTab === 'categories' ? <CategoriesScreen showNotice={showNotice} /> : null}
          {visibleTab === 'users' ? <UsersScreen currentUser={user} showNotice={showNotice} /> : null}
          {visibleTab === 'reconcile' ? (
            <ReconciliationScreen
              batchId={batchId}
              batches={batchesWithUploads}
              batchesLoading={batchesLoading}
              setBatchId={setBatchId}
              showNotice={showNotice}
            />
          ) : null}
          {visibleTab === 'meter-report' ? (
            <WeeklyMeterReportScreen
              initialMonth={meterReportTarget?.month}
              initialYear={meterReportTarget?.year}
              key={meterReportTarget ? `${meterReportTarget.year}-${meterReportTarget.month}` : 'current'}
              showNotice={showNotice}
              onBackToCalendar={
                meterReportTarget
                  ? () => {
                      setMeterReportTarget(null);
                      setActiveTab('calendar');
                    }
                  : undefined
              }
            />
          ) : null}
          {visibleTab === 'how-to-use' ? <HowToUseScreen /> : null}
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
            <ButtonContent icon="login" loading={submitting}>{submitting ? 'Signing in...' : 'Sign in'}</ButtonContent>
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

function BatchSelect({ batches, batchId, batchesLoading, onChange }) {
  return (
    <label className="batch-select">
      <span>Batch</span>
      <select
        disabled={batchesLoading}
        value={batchId}
        onChange={(event) => onChange(event.target.value)}
      >
        <option value="">Select a batch...</option>
        {batches.map((batch) => (
          <option key={batch.id} value={String(batch.id)}>
            {formatBatchLabel(batch)}
          </option>
        ))}
      </select>
    </label>
  );
}

function AppHeader({ isSidebarCollapsed, user, showNotice, onUserUpdated, onLogout, onToggleSidebar }) {
  const [isAccountOpen, setIsAccountOpen] = useState(false);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
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
        <strong>Sales Analysis Report</strong>
      </div>

      <img className="top-nav-logo" src="/images/white-logo.png" alt="Wagner Meters" />

      <div className="top-nav-right">
        <div className="account-menu-wrap">
          <button
            className="account-trigger"
            type="button"
            aria-label="Open account menu"
            aria-expanded={isAccountOpen}
            onClick={() => setIsAccountOpen((current) => !current)}
          >
            <Avatar initials={initials} user={user} />
          </button>
          {isAccountOpen ? (
            <div className="account-menu">
              <div className="account-menu-user">
                <strong>{userName}</strong>
                <span>{user.email}</span>
              </div>
              <button
                className="account-menu-item"
                type="button"
                onClick={() => {
                  setIsAccountOpen(false);
                  setIsProfileOpen(true);
                }}
              >
                Profile
              </button>
              <button
                className="account-menu-item account-menu-logout"
                type="button"
                onClick={() => {
                  setIsAccountOpen(false);
                  onLogout();
                }}
              >
                <ButtonContent icon="logout">Logout</ButtonContent>
              </button>
            </div>
          ) : null}
          {isProfileOpen ? (
            <ProfileModal
              initials={initials}
              user={user}
              showNotice={showNotice}
              onClose={() => setIsProfileOpen(false)}
              onUserUpdated={onUserUpdated}
            />
          ) : null}
        </div>
      </div>
    </header>
  );
}

function Avatar({ initials, user }) {
  if (user.profile_photo_url) {
    return <img className="avatar avatar-image" src={user.profile_photo_url} alt="" />;
  }

  return <span className="avatar">{initials}</span>;
}

function ProfileModal({ initials, user, showNotice, onClose, onUserUpdated }) {
  const [photoFile, setPhotoFile] = useState(null);
  const [photoPreview, setPhotoPreview] = useState(user.profile_photo_url ?? '');
  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  });
  const [isSavingPhoto, setIsSavingPhoto] = useState(false);
  const [isSavingPassword, setIsSavingPassword] = useState(false);

  function handlePhotoChange(event) {
    const file = event.target.files?.[0] ?? null;

    setPhotoFile(file);
    setPhotoPreview(file ? URL.createObjectURL(file) : user.profile_photo_url ?? '');
  }

  async function submitPhoto(event) {
    event.preventDefault();

    if (!photoFile) {
      showNotice('error', 'Choose a profile picture first.');
      return;
    }

    const formData = new FormData();
    formData.append('profile_photo', photoFile);
    setIsSavingPhoto(true);

    try {
      const response = await axios.post('/api/me/profile-photo', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      onUserUpdated(response.data.data);
      showNotice('success', 'Profile picture updated.');
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update profile picture.'));
    } finally {
      setIsSavingPhoto(false);
    }
  }

  async function submitPassword(event) {
    event.preventDefault();
    setIsSavingPassword(true);

    try {
      await axios.patch('/api/me/password', passwordForm);
      setPasswordForm({
        current_password: '',
        password: '',
        password_confirmation: '',
      });
      showNotice('success', 'Password updated.');
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update password.'));
    } finally {
      setIsSavingPassword(false);
    }
  }

  return (
    <div className="modal-backdrop" role="presentation">
      <section className="modal-card profile-modal" role="dialog" aria-modal="true" aria-label="Profile settings">
        <button className="modal-close-button" type="button" aria-label="Close" onClick={onClose}>
          <ActionIcon name="cancel" />
        </button>
        <div className="section-heading">
          <div>
            <p className="eyebrow">Profile</p>
            <h2>Account Settings</h2>
          </div>
        </div>

        <form className="profile-section" onSubmit={submitPhoto}>
          <div className="profile-picture-row">
            {photoPreview ? (
              <img className="profile-picture-preview" src={photoPreview} alt="" />
            ) : (
              <span className="profile-picture-preview profile-picture-initials">{initials}</span>
            )}
            <label>
              Profile Picture
              <input accept="image/*" type="file" onChange={handlePhotoChange} />
            </label>
          </div>
          <button className="primary-button" disabled={isSavingPhoto} type="submit">
            <ButtonContent icon="upload" loading={isSavingPhoto}>{isSavingPhoto ? 'Uploading...' : 'Update Picture'}</ButtonContent>
          </button>
        </form>

        <form className="profile-section" onSubmit={submitPassword}>
          <h3>Change Password</h3>
          <label>
            Current Password
            <input
              autoComplete="current-password"
              required
              type="password"
              value={passwordForm.current_password}
              onChange={(event) => setPasswordForm({ ...passwordForm, current_password: event.target.value })}
            />
          </label>
          <div className="form-row">
            <label>
              New Password
              <input
                autoComplete="new-password"
                minLength="8"
                required
                type="password"
                value={passwordForm.password}
                onChange={(event) => setPasswordForm({ ...passwordForm, password: event.target.value })}
              />
            </label>
            <label>
              Confirm Password
              <input
                autoComplete="new-password"
                minLength="8"
                required
                type="password"
                value={passwordForm.password_confirmation}
                onChange={(event) => setPasswordForm({ ...passwordForm, password_confirmation: event.target.value })}
              />
            </label>
          </div>
          <button className="primary-button" disabled={isSavingPassword} type="submit">
            <ButtonContent icon="save" loading={isSavingPassword}>{isSavingPassword ? 'Saving...' : 'Change Password'}</ButtonContent>
          </button>
        </form>
      </section>
    </div>
  );
}

function toIsoDateString(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function parseIsoDate(isoDate) {
  return new Date(`${isoDate}T00:00:00`);
}

function DateRangePicker({ start, end, onChange, placeholder = 'Select week range' }) {
  const containerRef = useRef(null);
  const [isOpen, setIsOpen] = useState(false);
  const [viewDate, setViewDate] = useState(() => (start ? parseIsoDate(start) : new Date()));
  const [draftStart, setDraftStart] = useState(start);
  const [draftEnd, setDraftEnd] = useState(end);

  useEffect(() => {
    setDraftStart(start);
    setDraftEnd(end);
  }, [start, end]);

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    function handlePointerDown(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false);
        setDraftStart(start);
        setDraftEnd(end);
      }
    }

    document.addEventListener('mousedown', handlePointerDown);

    return () => document.removeEventListener('mousedown', handlePointerDown);
  }, [end, isOpen, start]);

  const monthLabel = viewDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });
  const year = viewDate.getFullYear();
  const month = viewDate.getMonth();
  const firstWeekday = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const days = [];

  for (let index = 0; index < firstWeekday; index += 1) {
    days.push(null);
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    days.push(toIsoDateString(new Date(year, month, day)));
  }

  const displayValue = start && end ? formatWeekRange(start, end) : '';
  const hint = !draftStart ? 'Choose a start date' : !draftEnd ? 'Choose an end date' : 'Range selected';

  function handleDayClick(isoDate) {
    if (!draftStart || (draftStart && draftEnd)) {
      setDraftStart(isoDate);
      setDraftEnd('');
      return;
    }

    let nextStart = draftStart;
    let nextEnd = isoDate;

    if (nextEnd < nextStart) {
      [nextStart, nextEnd] = [nextEnd, nextStart];
    }

    setDraftStart(nextStart);
    setDraftEnd(nextEnd);
    onChange({ start: nextStart, end: nextEnd });
    setIsOpen(false);
  }

  function shiftMonth(offset) {
    setViewDate(new Date(year, month + offset, 1));
  }

  return (
    <div className="date-range-picker" ref={containerRef}>
      <button
        aria-expanded={isOpen}
        aria-haspopup="dialog"
        className="date-range-trigger"
        type="button"
        onClick={() => {
          setIsOpen((current) => !current);
          if (start) {
            setViewDate(parseIsoDate(start));
          }
        }}
      >
        <ActionIcon name="calendar" />
        <span className={displayValue ? 'date-range-value' : 'date-range-placeholder'}>
          {displayValue || placeholder}
        </span>
      </button>
      {isOpen ? (
        <div aria-label="Choose week range" className="date-range-popover" role="dialog">
          <div className="date-range-popover-header">
            <button aria-label="Previous month" className="date-range-nav-button" type="button" onClick={() => shiftMonth(-1)}>
              ‹
            </button>
            <span>{monthLabel}</span>
            <button aria-label="Next month" className="date-range-nav-button" type="button" onClick={() => shiftMonth(1)}>
              ›
            </button>
          </div>
          <p className="date-range-hint">{hint}</p>
          <div className="date-range-weekdays">
            {['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].map((label) => (
              <span key={label}>{label}</span>
            ))}
          </div>
          <div className="date-range-days">
            {days.map((isoDate, index) => {
              if (!isoDate) {
                return <span className="date-range-day date-range-day-empty" key={`empty-${index}`} />;
              }

              const inRange = draftStart && draftEnd && isoDate >= draftStart && isoDate <= draftEnd;
              const isStart = isoDate === draftStart;
              const isEnd = isoDate === draftEnd;
              const isToday = isoDate === toIsoDateString(new Date());

              return (
                <button
                  className={[
                    'date-range-day',
                    inRange ? 'date-range-day-in-range' : '',
                    isStart ? 'date-range-day-start' : '',
                    isEnd ? 'date-range-day-end' : '',
                    isToday ? 'date-range-day-today' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                  key={isoDate}
                  type="button"
                  onClick={() => handleDayClick(isoDate)}
                >
                  {parseIsoDate(isoDate).getDate()}
                </button>
              );
            })}
          </div>
        </div>
      ) : null}
    </div>
  );
}

function UploadScreen({ batchId, batches, batchesLoading, loadBatches, setBatchId, showNotice }) {
  const [selectedFiles, setSelectedFiles] = useState({});
  const [uploading, setUploading] = useState(false);
  const [weekRangeStart, setWeekRangeStart] = useState('');
  const [weekRangeEnd, setWeekRangeEnd] = useState('');
  const [unmatchedModal, setUnmatchedModal] = useState(null);
  const [preparingUnmatched, setPreparingUnmatched] = useState(false);
  const [includeState, setIncludeState] = useState(false);
  const [generatingReports, setGeneratingReports] = useState(false);

  const closeUnmatchedModal = useCallback(() => {
    setUnmatchedModal(null);
  }, []);

  const autoGenerateReports = useCallback(async (targetBatchId) => {
    setGeneratingReports(true);

    try {
      const reports = await requestGeneratedReports(targetBatchId, includeState);

      if (hasFailedReports(reports)) {
        showNotice(
          'error',
          `Reports for batch #${targetBatchId} were generated, but some failed. Check the Calendar for details.`,
        );
      } else {
        showNotice('success', `Reports generated for batch #${targetBatchId}. Open the Calendar to view them.`);
      }
    } catch (error) {
      showNotice(
        'error',
        messageFromError(
          error,
          `Upload succeeded but report generation failed for batch #${targetBatchId}.`,
        ),
      );
    } finally {
      setGeneratingReports(false);
    }
  }, [includeState, showNotice]);

  const openUnmatchedModal = useCallback(async (nextBatchId) => {
    setPreparingUnmatched(true);

    try {
      const data = await fetchUnmatchedModalData(nextBatchId);

      if ((data.itemGroups ?? []).length === 0) {
        showNotice('success', 'Sales Analysis uploaded successfully with no unmatched rows.');
        await autoGenerateReports(nextBatchId);

        return;
      }

      markReconcileSessionActive();
      setUnmatchedModal({
        batchId: nextBatchId,
        ...data,
      });
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load unmatched items.'));
    } finally {
      setPreparingUnmatched(false);
    }
  }, [autoGenerateReports, showNotice]);

  const selectedBatch = useMemo(
    () => batches.find((batch) => String(batch.id) === String(batchId)),
    [batches, batchId],
  );

  useEffect(() => {
    if (!weekRangeStart && !weekRangeEnd && selectedBatch) {
      setWeekRangeStart(selectedBatch.week_start ?? '');
      setWeekRangeEnd(selectedBatch.week_ending ?? '');
    }
  }, [selectedBatch, weekRangeEnd, weekRangeStart]);

  const hasFiles = Object.values(selectedFiles).some(Boolean);
  const isUploadBusy = uploading || preparingUnmatched || generatingReports;

  async function handleUploadClick() {
    if (!hasFiles) {
      showNotice('error', 'Choose at least one workbook before importing.');
      return;
    }

    if (!weekRangeStart || !weekRangeEnd) {
      showNotice('error', 'Select a week range before importing.');
      return;
    }

    if (weekRangeStart > weekRangeEnd) {
      showNotice('error', 'Week range start date must be on or before the end date.');
      return;
    }

    const formData = new FormData();

    Object.entries(selectedFiles).forEach(([type, file]) => {
      if (file) {
        formData.append(type, file);
      }
    });

    formData.append('week_start', weekRangeStart);
    formData.append('week_ending', weekRangeEnd);

    setUploading(true);

    const includedSalesAnalysis = Boolean(selectedFiles.sales_analysis);

    try {
      const response = await axios.post('/api/workbook-imports', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      const result = response.data.data;
      const nextBatchId = String(result.import_batch.id);

      setBatchId(nextBatchId);
      setSelectedFiles({});
      showNotice('success', uploadSuccessMessage(result, nextBatchId, includedSalesAnalysis));
      await loadBatches();

      if (shouldOpenUnmatchedModal(result, includedSalesAnalysis)) {
        await openUnmatchedModal(nextBatchId);
      } else if (includedSalesAnalysis) {
        await autoGenerateReports(nextBatchId);
      }
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to upload and import the selected files.'));
    } finally {
      setUploading(false);
    }
  }

  return (
    <>
      {unmatchedModal ? (
        <UnmatchedItemsModal
          batchId={unmatchedModal.batchId}
          categories={unmatchedModal.categories}
          itemGroups={unmatchedModal.itemGroups}
          loadBatches={loadBatches}
          salesAnalysisFileId={unmatchedModal.salesAnalysisFileId}
          showNotice={showNotice}
          onClose={closeUnmatchedModal}
          onResolved={autoGenerateReports}
        />
      ) : null}
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
          Choose one file, several files, or the full weekly set. Select the week range, then import to create a new
          batch automatically.
        </p>
        <div className="week-range-field">
          <span>Week range</span>
          <DateRangePicker
            end={weekRangeEnd}
            start={weekRangeStart}
            onChange={({ start, end }) => {
              setWeekRangeStart(start);
              setWeekRangeEnd(end);
            }}
          />
        </div>
        <div className="file-grid">
          {workbookTypes.map((type) => (
            <div className="file-card" key={type.key}>
              <label className="file-card-field">
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
              {type.key === 'sales_analysis' ? (
                <label className="checkbox-label file-card-option">
                  <input
                    checked={includeState}
                    disabled={isUploadBusy}
                    type="checkbox"
                    onChange={(event) => setIncludeState(event.target.checked)}
                  />
                  Include State
                </label>
              ) : null}
            </div>
          ))}
        </div>
        <div className="upload-actions">
          <button className="primary-button" disabled={isUploadBusy} type="button" onClick={handleUploadClick}>
            <ButtonContent icon="upload" loading={isUploadBusy}>
              {generatingReports
                ? 'Generating reports...'
                : uploading
                  ? 'Importing...'
                  : preparingUnmatched
                    ? 'Preparing reconcile...'
                    : 'Upload / Import Selected Files'}
            </ButtonContent>
          </button>
        </div>
      </article>

      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Uploaded Batches</p>
            <h2>Batch History</h2>
          </div>
          <button className="secondary-button" disabled={batchesLoading} type="button" onClick={loadBatches}>
            <ButtonContent icon="refresh">Refresh</ButtonContent>
          </button>
        </div>
        {batchesLoading ? <p>Loading batches...</p> : null}
        {!batchesLoading && batches.length === 0 ? <p>No batches uploaded yet.</p> : null}
        {!batchesLoading && batches.length > 0 ? (
          <UploadedBatchesTable
            batches={batches}
            loadBatches={loadBatches}
            setBatchId={setBatchId}
            showNotice={showNotice}
            onGenerateReports={autoGenerateReports}
            onUnmatchedItems={openUnmatchedModal}
          />
        ) : null}
      </article>
    </section>
    </>
  );
}

function UploadedBatchesTable({ batches, loadBatches, onGenerateReports, onUnmatchedItems, setBatchId, showNotice }) {
  const [uploadingCell, setUploadingCell] = useState('');
  const [reclassifyingBatchId, setReclassifyingBatchId] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const fileInputRefs = useRef({});
  const pagination = usePagination(batches);

  async function uploadForCell(batchId, fileType, fileTypeLabel, file) {
    const cellKey = `${batchId}-${fileType}`;
    const formData = new FormData();

    formData.append(fileType, file);
    formData.append('import_batch_id', String(batchId));

    setUploadingCell(cellKey);

    try {
      const response = await axios.post('/api/workbook-imports', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      const result = response.data.data;
      const nextBatchId = String(result.import_batch.id);

      setBatchId(nextBatchId);
      const successMessage = fileType === 'sales_analysis'
        ? uploadSuccessMessage(result, nextBatchId, true)
        : `${fileTypeLabel} imported into batch #${nextBatchId}.`;
      showNotice('success', successMessage);
      await loadBatches();

      if (fileType === 'sales_analysis') {
        if (shouldOpenUnmatchedModal(result, true)) {
          await onUnmatchedItems?.(nextBatchId);
        } else {
          await onGenerateReports?.(nextBatchId);
        }
      }
    } catch (error) {
      showNotice('error', messageFromError(error, `Unable to upload ${fileTypeLabel}.`));
    } finally {
      setUploadingCell('');
    }
  }

  async function reclassifyBatch(batchId) {
    setReclassifyingBatchId(batchId);

    try {
      const response = await axios.post(`/api/import-batches/${batchId}/classify-sales-rows`);
      const summary = response.data.data ?? {};

      showNotice(
        'success',
        `Batch #${batchId} reclassified: ${summary.matched ?? 0} matched, ${summary.unmatched ?? 0} unmatched.`,
      );
      await loadBatches();

      if ((summary.reconcilable_unmatched ?? 0) > 0) {
        await onUnmatchedItems?.(String(batchId));
      }
    } catch (error) {
      showNotice('error', messageFromError(error, `Unable to reclassify batch #${batchId}.`));
    } finally {
      setReclassifyingBatchId(null);
    }
  }

  async function confirmDelete() {
    if (!deleteTarget) {
      return;
    }

    setDeleting(true);

    try {
      await axios.delete(`/api/uploaded-files/${deleteTarget.uploadedFileId}`);
      showNotice('success', `${deleteTarget.fileTypeLabel} deleted from batch #${deleteTarget.batchId}.`);
      setDeleteTarget(null);
      await loadBatches();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete uploaded file.'));
    } finally {
      setDeleting(false);
    }
  }

  return (
    <>
      {deleteTarget ? (
        <ConfirmDialog
          confirmLabel="Delete File"
          isProcessing={deleting}
          message={`Delete ${deleteTarget.fileTypeLabel} from batch #${deleteTarget.batchId}? This removes the uploaded workbook and its imported rows.`}
          title="Delete Uploaded File"
          onCancel={() => {
            if (!deleting) {
              setDeleteTarget(null);
            }
          }}
          onConfirm={confirmDelete}
        />
      ) : null}
      <div className="table-wrap table-section">
      <table className="upload-table">
        <thead>
          <tr>
            <th>Batch ID</th>
            <th>Week Range</th>
            <th>Status</th>
            {workbookTypes.map((type) => (
              <th key={type.key}>{type.label}</th>
            ))}
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {pagination.paginatedItems.map((batch) => (
            <tr key={batch.id}>
              <td>#{batch.id}</td>
              <td>{formatWeekRange(batch.week_start, batch.week_ending)}</td>
              <td className="batch-status-text">{batch.status}</td>
              {workbookTypes.map((type) => {
                const uploaded = (batch.uploaded_files ?? []).find((file) => file.file_type === type.key);
                const status = uploaded?.status ?? 'missing';
                const cellKey = `${batch.id}-${type.key}`;
                const isUploading = uploadingCell === cellKey;
                const canUpload = status === 'missing' || status === 'validation_failed' || status === 'pending_reconcile';
                const canDownload = status === 'imported' && uploaded?.id;

                return (
                  <td key={type.key}>
                    <div className="batch-cell-status">
                      {canDownload ? (
                        <>
                          <a
                            className="batch-file-download"
                            href={`/api/uploaded-files/${uploaded.id}/download`}
                            title={`Download ${uploaded.original_name}`}
                          >
                            <ActionIcon name="download" />
                            <span className="batch-file-name">{truncateFileName(uploaded.original_name)}</span>
                          </a>
                          <button
                            aria-label={`Delete ${type.label} from batch ${batch.id}`}
                            className="batch-delete-button"
                            title={`Delete ${type.label}`}
                            type="button"
                            onClick={() =>
                              setDeleteTarget({
                                batchId: batch.id,
                                fileTypeLabel: type.label,
                                uploadedFileId: uploaded.id,
                              })
                            }
                          >
                            <ActionIcon name="delete" />
                          </button>
                        </>
                      ) : (
                        <span className={`import-status ${importStatusClass(status)}`}>
                          {formatImportStatus(status)}
                        </span>
                      )}
                      {canUpload ? (
                        <>
                          <button
                            aria-label={`Upload ${type.label} for batch ${batch.id}`}
                            className="batch-upload-button"
                            disabled={isUploading}
                            title={isUploading ? `Uploading ${type.label}...` : `Upload ${type.label}`}
                            type="button"
                            onClick={() => fileInputRefs.current[cellKey]?.click()}
                          >
                            {isUploading ? <LoadingSpinner /> : <ActionIcon name="upload" />}
                          </button>
                          <input
                            accept=".xlsx"
                            className="batch-upload-input"
                            ref={(element) => {
                              fileInputRefs.current[cellKey] = element;
                            }}
                            type="file"
                            onChange={(event) => {
                              const file = event.target.files?.[0];

                              if (file) {
                                uploadForCell(batch.id, type.key, type.label, file);
                              }

                              event.target.value = '';
                            }}
                          />
                        </>
                      ) : null}
                    </div>
                  </td>
                );
              })}
              <td>
                <button
                  className="secondary-button batch-reclassify-button"
                  disabled={!batchHasImportedSalesAnalysis(batch) || reclassifyingBatchId === batch.id}
                  title={
                    batchHasImportedSalesAnalysis(batch)
                      ? 'Re-run sales row classification for this batch'
                      : 'Import Sales Analysis before reclassifying'
                  }
                  type="button"
                  onClick={() => reclassifyBatch(batch.id)}
                >
                  {reclassifyingBatchId === batch.id ? (
                    <ButtonContent loading>Reclassifying...</ButtonContent>
                  ) : (
                    'Reclassify'
                  )}
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
    <PaginationControls {...pagination} />
    </>
  );
}

function MappingRulesScreen({ showNotice }) {
  const [rules, setRules] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedRule, setSelectedRule] = useState(null);
  const [editForm, setEditForm] = useState(emptyMappingRuleForm());
  const [createForm, setCreateForm] = useState(emptyMappingRuleForm());
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isDeleteConfirmOpen, setIsDeleteConfirmOpen] = useState(false);
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [statusFilter, setStatusFilter] = useState('active');
  const [searchQuery, setSearchQuery] = useState('');

  const filteredRules = useMemo(() => {
    const normalizedQuery = searchQuery.trim().toLowerCase();

    return rules.filter((rule) => {
      const matchesStatus =
        statusFilter === 'all' || Boolean(rule.is_active) === (statusFilter === 'active');

      if (!matchesStatus) {
        return false;
      }

      if (!normalizedQuery) {
        return true;
      }

      return matchesListSearch(normalizedQuery, [
        rule.name,
        rule.match_field,
        rule.match_operator,
        rule.pattern,
        rule.source_type,
        rule.target_bucket,
        salesAnalysisBucketLabel(rule.target_bucket),
        rule.product_category?.name,
      ]);
    });
  }, [rules, searchQuery, statusFilter]);

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

  useEffect(() => {
    if (selectedRule && !filteredRules.some((rule) => rule.id === selectedRule.id)) {
      setSelectedRule(null);
    }
  }, [filteredRules, selectedRule]);

  function rulePayload(form) {
    return mappingRulePayload(form);
  }

  async function submitCreateRule(event) {
    event.preventDefault();
    setCreating(true);

    try {
      const response = await axios.post('/api/mapping-rules', rulePayload(createForm));

      showNotice('success', `Mapping rule created.${reportsRefreshedSuffix(response)}`);
      setCreateForm(emptyMappingRuleForm());
      setIsCreateOpen(false);
      await loadRules();
      setSelectedRule(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to create mapping rule.'));
    } finally {
      setCreating(false);
    }
  }

  async function submitEditRule(event) {
    event.preventDefault();

    if (!selectedRule) {
      return;
    }

    setEditing(true);

    try {
      const response = await axios.patch(`/api/mapping-rules/${selectedRule.id}`, rulePayload(editForm));

      showNotice('success', `Mapping rule updated.${reportsRefreshedSuffix(response)}`);
      await loadRules();
      setSelectedRule(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update mapping rule.'));
    } finally {
      setEditing(false);
    }
  }

  async function confirmDeleteRule() {
    if (!selectedRule) {
      return;
    }

    setDeleting(true);

    try {
      const response = await axios.delete(`/api/mapping-rules/${selectedRule.id}`);
      showNotice('success', `Mapping rule deleted.${reportsRefreshedSuffix(response)}`);
      setSelectedRule(null);
      setIsDeleteConfirmOpen(false);
      await loadRules();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete mapping rule.'));
    } finally {
      setDeleting(false);
    }
  }

  return (
    <>
      {isDeleteConfirmOpen && selectedRule ? (
        <ConfirmDialog
          confirmLabel="Delete Rule"
          isProcessing={deleting}
          message={`Delete rule "${selectedRule.name}"? This action cannot be undone.`}
          title="Delete Mapping Rule"
          onCancel={() => {
            if (!deleting) {
              setIsDeleteConfirmOpen(false);
            }
          }}
          onConfirm={confirmDeleteRule}
        />
      ) : null}
    <section className="panel-grid">
      <article className="panel panel-legend panel-transparent">
        <p className="eyebrow">Mapping Rules</p>
        <div className="section-heading section-heading-toolbar">
          <div className="button-row">
            <ListSearchInput
              placeholder="Search rules..."
              value={searchQuery}
              onChange={setSearchQuery}
            />
            <label className="status-filter">
              Status
              <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="all">All</option>
              </select>
            </label>
          </div>
          <button className="primary-button" type="button" onClick={() => setIsCreateOpen(true)}>
            <ButtonContent icon="add">Create Rule</ButtonContent>
          </button>
        </div>
        {loading ? (
          <p>Loading rules...</p>
        ) : (
          <RulesTable
            rules={filteredRules}
            searchQuery={searchQuery}
            selectedRule={selectedRule}
            statusFilter={statusFilter}
            onSelectRule={setSelectedRule}
          />
        )}
      </article>

      <article className="panel panel-legend">
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
            isSubmitting={editing}
            primaryLabel="Update Rule"
            setForm={setEditForm}
            onSubmit={submitEditRule}
          >
            <button className="danger-button" disabled={editing} type="button" onClick={() => setIsDeleteConfirmOpen(true)}>
              <ButtonContent icon="delete">Delete Rule</ButtonContent>
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
        <Modal
          className="modal-card-rule"
          isBusy={creating}
          title="Create Mapping Rule"
          onClose={() => {
            if (!creating) {
              setIsCreateOpen(false);
            }
          }}
        >
          <RuleForm
            categories={categories}
            form={createForm}
            isSubmitting={creating}
            primaryLabel="Create Rule"
            setForm={setCreateForm}
            onSubmit={submitCreateRule}
          />
        </Modal>
      ) : null}

    </section>
    </>
  );
}

function emptyMappingRuleForm() {
  return {
    name: '',
    product_category_id: '',
    source_type: 'sales_analysis',
    match_field: 'item_id',
    match_operator: 'starts_with',
    pattern: '',
    target_bucket: '',
    priority: 100,
    is_active: true,
  };
}

function mappingRulePayload(form) {
  return {
    ...form,
    product_category_id: form.product_category_id || null,
    target_bucket: form.target_bucket || null,
    priority: Number(form.priority),
    is_active: Boolean(form.is_active),
  };
}

function ruleToForm(rule) {
  return {
    name: rule.name ?? '',
    product_category_id: rule.product_category_id ? String(rule.product_category_id) : '',
    source_type: rule.source_type ?? 'sales_analysis',
    match_field: rule.match_field ?? 'item_id',
    match_operator: rule.match_operator ?? 'starts_with',
    pattern: rule.pattern ?? '',
    target_bucket: rule.target_bucket ?? '',
    priority: rule.priority ?? 100,
    is_active: Boolean(rule.is_active),
  };
}

function RuleForm({ categories, children, form, isSubmitting = false, primaryLabel, setForm, onSubmit }) {
  const visibleCategories = useMemo(() => {
    if (! form.target_bucket) {
      return categories;
    }

    return categories.filter((category) => category.sales_analysis_bucket === form.target_bucket);
  }, [categories, form.target_bucket]);

  const submitLabel = isSubmitting
    ? (primaryLabel.startsWith('Update') ? 'Updating...' : 'Creating...')
    : primaryLabel;

  return (
    <form className="stacked-form" onSubmit={onSubmit}>
      <fieldset className="form-fieldset" disabled={isSubmitting}>
      <label>
        Rule Name
        <input
          required
          value={form.name}
          onChange={(event) => setForm({ ...form, name: event.target.value })}
        />
      </label>
      <div className="form-row">
        <label>
          Bucket
          <select
            value={form.target_bucket}
            onChange={(event) => {
              const target_bucket = event.target.value;
              const selectedCategory = categories.find(
                (category) => String(category.id) === String(form.product_category_id),
              );
              const product_category_id = !target_bucket
                || selectedCategory?.sales_analysis_bucket === target_bucket
                ? form.product_category_id
                : '';

              setForm({ ...form, target_bucket, product_category_id });
            }}
          >
            <option value="">None</option>
            {salesAnalysisBucketOptions.map((bucket) => (
              <option key={bucket.value} value={bucket.value}>
                {bucket.label}
              </option>
            ))}
          </select>
        </label>
        <label>
          Category
          <SearchableCategorySelect
            allowEmpty
            categories={visibleCategories}
            disabled={isSubmitting}
            emptyLabel="No category"
            showBucketInLabel
            value={form.product_category_id}
            onChange={(categoryId) => setForm({ ...form, product_category_id: categoryId })}
          />
        </label>
      </div>
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
      <label>
        Priority
        <input
          min="1"
          type="number"
          value={form.priority}
          onChange={(event) => setForm({ ...form, priority: event.target.value })}
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
      <div className="form-actions">
        <button className="primary-button" disabled={isSubmitting} type="submit">
          <ButtonContent
            icon={primaryLabel.startsWith('Update') ? 'save' : 'check'}
            loading={isSubmitting}
          >
            {submitLabel}
          </ButtonContent>
        </button>
        {children}
      </div>
      </fieldset>
    </form>
  );
}

function ConfirmDialog({
  title,
  message,
  confirmLabel,
  cancelLabel = 'Cancel',
  confirmIcon = 'delete',
  confirmButtonClassName = 'danger-button',
  isProcessing,
  onConfirm,
  onCancel,
}) {
  return (
    <div className="modal-backdrop" role="presentation" onClick={onCancel}>
      <section
        aria-labelledby="confirm-dialog-title"
        aria-modal="true"
        className="modal-card confirm-dialog"
        role="alertdialog"
        onClick={(event) => event.stopPropagation()}
      >
        <h2 id="confirm-dialog-title">{title}</h2>
        <p>{message}</p>
        <div className="confirm-dialog-actions">
          <button className="secondary-button" disabled={isProcessing} type="button" onClick={onCancel}>
            {cancelLabel}
          </button>
          <button className={confirmButtonClassName} disabled={isProcessing} type="button" onClick={onConfirm}>
            {confirmIcon ? (
              <ButtonContent icon={confirmIcon} loading={isProcessing}>
                {isProcessing ? 'Processing...' : confirmLabel}
              </ButtonContent>
            ) : (
              isProcessing ? 'Processing...' : confirmLabel
            )}
          </button>
        </div>
      </section>
    </div>
  );
}

function Modal({ children, title, className = '', eyebrow = 'Create', isBusy = false, onClose }) {
  return (
    <div
      className="modal-backdrop"
      role="presentation"
      onClick={() => {
        if (!isBusy) {
          onClose?.();
        }
      }}
    >
      <section
        aria-busy={isBusy}
        aria-label={title}
        className={['modal-card', className, isBusy ? 'modal-card-busy' : ''].filter(Boolean).join(' ')}
        role="dialog"
        aria-modal="true"
        onClick={(event) => event.stopPropagation()}
      >
        {isBusy ? (
          <div aria-hidden="true" className="modal-busy-overlay">
            <LoadingSpinner className="modal-spinner" />
          </div>
        ) : null}
        <button
          aria-label="Close"
          className="modal-close-button"
          disabled={isBusy}
          type="button"
          onClick={onClose}
        >
          <ActionIcon name="cancel" />
        </button>
        <div className="section-heading">
          <div>
            <p className="eyebrow">{eyebrow}</p>
            <h2>{title}</h2>
          </div>
        </div>
        {children}
      </section>
    </div>
  );
}

async function requestGeneratedReports(batchId, includeState = false) {
  const response = await axios.post(`/api/import-batches/${batchId}/generated-reports`, {
    include_state: Boolean(includeState),
  });

  return response.data.data ?? [];
}

function hasFailedReports(reports) {
  return (reports ?? []).some((report) => report?.status === 'failed');
}

function shouldOpenUnmatchedModal(result, salesAnalysisIncluded) {
  const reconcilableUnmatched = result?.summary?.classification?.reconcilable_unmatched;

  if (typeof reconcilableUnmatched === 'number') {
    return Boolean(salesAnalysisIncluded && reconcilableUnmatched > 0);
  }

  return Boolean(salesAnalysisIncluded && (result?.summary?.classification?.unmatched ?? 0) > 0);
}

function uploadSuccessMessage(result, batchId, salesAnalysisIncluded = false) {
  const workbookCount = Object.keys(result?.summary ?? {}).filter((key) => key !== 'classification').length;
  let message = `Imported ${workbookCount} workbook type(s) into batch #${batchId}.`;

  if (salesAnalysisIncluded) {
    const reconcilableUnmatched = result?.summary?.classification?.reconcilable_unmatched ?? 0;

    if (reconcilableUnmatched === 0) {
      message += ' Sales Analysis uploaded successfully with no unmatched rows.';
    }
  }

  return message;
}

async function fetchUnmatchedModalData(batchId) {
  const [groupsResponse, categoriesResponse, batchResponse] = await Promise.all([
    axios.get(`/api/import-batches/${batchId}/unmatched-item-groups`),
    axios.get('/api/product-categories'),
    axios.get(`/api/import-batches/${batchId}`),
  ]);

  const salesAnalysisFile = (batchResponse.data.data?.uploaded_files ?? []).find(
    (file) => file.file_type === 'sales_analysis',
  );

  return {
    itemGroups: groupsResponse.data.data ?? [],
    categories: categoriesResponse.data.data ?? [],
    salesAnalysisFileId: salesAnalysisFile?.id ?? null,
  };
}

function UnmatchedDetailsTrigger({ group }) {
  const triggerRef = useRef(null);
  const closeTimerRef = useRef(null);
  const [isOpen, setIsOpen] = useState(false);
  const [position, setPosition] = useState(null);

  function clearCloseTimer() {
    if (closeTimerRef.current) {
      window.clearTimeout(closeTimerRef.current);
      closeTimerRef.current = null;
    }
  }

  function scheduleClose() {
    clearCloseTimer();
    closeTimerRef.current = window.setTimeout(() => {
      setIsOpen(false);
      setPosition(null);
    }, 120);
  }

  function openPopover() {
    clearCloseTimer();

    const trigger = triggerRef.current;

    if (!trigger) {
      return;
    }

    const rect = trigger.getBoundingClientRect();
    const popoverWidth = Math.min(320, window.innerWidth * 0.8);
    const spaceBelow = window.innerHeight - rect.bottom - 16;
    const spaceAbove = rect.top - 16;
    const openAbove = spaceBelow < 180 && spaceAbove > spaceBelow;
    const left = Math.min(Math.max(16, rect.left), window.innerWidth - popoverWidth - 16);

    setPosition({
      left,
      top: openAbove ? rect.top - 8 : rect.bottom + 8,
      openAbove,
      width: popoverWidth,
    });
    setIsOpen(true);
  }

  useEffect(() => () => clearCloseTimer(), []);

  return (
    <div
      className="unmatched-details-trigger"
      onMouseEnter={openPopover}
      onMouseLeave={scheduleClose}
    >
      <button ref={triggerRef} className="secondary-button" type="button">
        Details ({group.row_count})
      </button>
      {isOpen && position
        ? createPortal(
            <div
              className="unmatched-details-popover unmatched-details-popover-fixed"
              style={{
                left: position.left,
                top: position.openAbove ? undefined : position.top,
                bottom: position.openAbove ? window.innerHeight - position.top : undefined,
                width: position.width,
                transform: position.openAbove ? 'translateY(-100%)' : undefined,
              }}
              onMouseEnter={clearCloseTimer}
              onMouseLeave={scheduleClose}
            >
              {group.rows.map((row, index) => (
                <div className="unmatched-details-row" key={`${group.item_id}-${index}`}>
                  <strong>Row {index + 1}</strong>
                  <span>Customer ID: {row.customer_id ?? '—'}</span>
                  <span>Customer: {row.customer_name ?? '—'}</span>
                  <span>Invoice: {row.invoice_number ?? '—'}</span>
                  <span>Sales Rep: {row.sales_rep_id ?? '—'}</span>
                  <span>Country: {row.country ?? '—'}</span>
                  <span>State: {row.bill_to_state ?? '—'}</span>
                  <span>Invoice Date: {row.invoice_date ?? '—'}</span>
                  <span>Qty: {row.quantity_ordered ?? '—'}</span>
                  <span>Amount: {row.amount != null ? currency(row.amount) : '—'}</span>
                </div>
              ))}
            </div>,
            document.body,
          )
        : null}
    </div>
  );
}

function UnmatchedItemsModal({
  batchId,
  categories: initialCategories,
  itemGroups: initialItemGroups,
  loadBatches,
  onClose,
  onResolved,
  salesAnalysisFileId: initialSalesAnalysisFileId,
  showNotice,
}) {
  const emptyCategoryForm = {
    name: '',
    sales_analysis_bucket: 'rhp',
    weekly_meter_row_label: '',
    sort_order: 100,
    quantity_multiplier: 1,
    is_active: true,
  };
  const showNoticeRef = useRef(showNotice);

  useEffect(() => {
    showNoticeRef.current = showNotice;
  }, [showNotice]);

  const [view, setView] = useState('unmatched');
  const [itemGroups, setItemGroups] = useState(initialItemGroups);
  const [categories, setCategories] = useState(initialCategories);
  const [resolutions, setResolutions] = useState({});
  const [noCategoryAssignments, setNoCategoryAssignments] = useState({});
  const [saving, setSaving] = useState(false);
  const [isCreateCategoryOpen, setIsCreateCategoryOpen] = useState(false);
  const [createCategoryForm, setCreateCategoryForm] = useState(emptyCategoryForm);
  const [createCategoryForItemId, setCreateCategoryForItemId] = useState('');
  const [creatingCategory, setCreatingCategory] = useState(false);
  const [salesAnalysisFileId, setSalesAnalysisFileId] = useState(initialSalesAnalysisFileId);
  const [isCloseConfirmOpen, setIsCloseConfirmOpen] = useState(false);
  const [discardingUpload, setDiscardingUpload] = useState(false);
  const [createRuleForm, setCreateRuleForm] = useState(emptyMappingRuleForm());
  const [creatingRule, setCreatingRule] = useState(false);

  const allResolved = useMemo(
    () =>
      itemGroups.length > 0 &&
      itemGroups.every((group) => noCategoryAssignments[group.item_id] || resolutions[group.item_id]),
    [itemGroups, resolutions, noCategoryAssignments],
  );

  const resolvedCount = useMemo(
    () =>
      itemGroups.filter(
        (group) => noCategoryAssignments[group.item_id] || resolutions[group.item_id],
      ).length,
    [itemGroups, resolutions, noCategoryAssignments],
  );

  const remainingCount = itemGroups.length - resolvedCount;
  const isBusy = saving || discardingUpload || creatingRule;

  function toggleNoCategoryAssignment(itemId, checked) {
    setNoCategoryAssignments((current) => ({
      ...current,
      [itemId]: checked,
    }));

    if (checked) {
      setResolutions((current) => {
        const next = { ...current };
        delete next[itemId];
        return next;
      });
    }
  }

  function openCreateCategory(itemId) {
    setCreateCategoryForItemId(itemId);
    setCreateCategoryForm(emptyCategoryForm);
    setIsCreateCategoryOpen(true);
  }

  function openCreateRuleView() {
    setCreateRuleForm(emptyMappingRuleForm());
    setView('create-rule');
  }

  function backToUnmatchedView() {
    if (creatingRule) {
      return;
    }

    setView('unmatched');
  }

  function pruneResolutionsForItemIds(nextItemIds) {
    const allowed = new Set(nextItemIds);

    setResolutions((current) => {
      const next = {};

      Object.entries(current).forEach(([itemId, value]) => {
        if (allowed.has(itemId)) {
          next[itemId] = value;
        }
      });

      return next;
    });

    setNoCategoryAssignments((current) => {
      const next = {};

      Object.entries(current).forEach(([itemId, value]) => {
        if (allowed.has(itemId)) {
          next[itemId] = value;
        }
      });

      return next;
    });
  }

  async function copyItemId(itemId) {
    try {
      await copyTextToClipboard(itemId);
      showNoticeRef.current('success', 'Item ID copied to clipboard.');
    } catch {
      showNoticeRef.current('error', 'Unable to copy item ID.');
    }
  }

  async function submitCreateCategory(event) {
    event.preventDefault();
    setCreatingCategory(true);

    try {
      const response = await axios.post('/api/product-categories', {
        name: createCategoryForm.name,
        sort_order: Number(createCategoryForm.sort_order),
        quantity_multiplier: Math.max(1, Number(createCategoryForm.quantity_multiplier) || 1),
        sales_analysis_bucket: createCategoryForm.sales_analysis_bucket || null,
        weekly_meter_row_label: createCategoryForm.weekly_meter_row_label || null,
        is_active: Boolean(createCategoryForm.is_active),
      });
      const category = response.data.data;

      setCategories((current) => [...current, category]);

      if (createCategoryForItemId) {
        setResolutions((current) => ({
          ...current,
          [createCategoryForItemId]: String(category.id),
        }));
        setNoCategoryAssignments((current) => ({
          ...current,
          [createCategoryForItemId]: false,
        }));
      }

      setIsCreateCategoryOpen(false);
      setCreateCategoryForItemId('');
      showNoticeRef.current('success', 'Category created.');
    } catch (error) {
      showNoticeRef.current('error', messageFromError(error, 'Unable to create category.'));
    } finally {
      setCreatingCategory(false);
    }
  }

  async function submitCreateRule(event) {
    event.preventDefault();
    setCreatingRule(true);

    try {
      await axios.post('/api/mapping-rules', { ...mappingRulePayload(createRuleForm), skip_report_refresh: true });
      showNoticeRef.current('success', 'Mapping rule created.');

      await axios.post(`/api/import-batches/${batchId}/classify-sales-rows`);
      const data = await fetchUnmatchedModalData(batchId);
      const nextGroups = data.itemGroups ?? [];

      setItemGroups(nextGroups);
      setCategories(data.categories ?? []);
      setSalesAnalysisFileId(data.salesAnalysisFileId ?? null);
      pruneResolutionsForItemIds(nextGroups.map((group) => group.item_id));
      setCreateRuleForm(emptyMappingRuleForm());

      if (nextGroups.length === 0) {
        clearReconcileSession();
        await loadBatches?.();
        showNoticeRef.current('success', 'All unmatched items were classified by mapping rules.');
        onClose();
        onResolved?.(batchId);
        return;
      }

      setView('unmatched');
      showNoticeRef.current(
        'success',
        `Unmatched items refreshed: ${nextGroups.length} item${nextGroups.length === 1 ? '' : 's'} remaining.`,
      );
    } catch (error) {
      const status = error?.response?.status;

      if (status === 404) {
        clearReconcileSession();
        await loadBatches?.();
        showNoticeRef.current(
          'error',
          'This Sales Analysis upload is no longer available. It may have been discarded. Please upload it again.',
        );
        onClose();
      } else {
        showNoticeRef.current('error', messageFromError(error, 'Unable to create mapping rule.'));
      }
    } finally {
      setCreatingRule(false);
    }
  }

  async function saveResolutions() {
    if (!allResolved) {
      return;
    }

    setSaving(true);

    try {
      const payload = {
        resolutions: itemGroups.map((group) => {
          if (noCategoryAssignments[group.item_id]) {
            return {
              item_id: group.item_id,
              no_category_assignment: true,
            };
          }

          return {
            item_id: group.item_id,
            product_category_id: Number(resolutions[group.item_id]),
          };
        }),
      };
      const response = await axios.post(`/api/import-batches/${batchId}/resolve-unmatched-items`, payload);
      const summary = response.data.data;

      showNoticeRef.current(
        'success',
        `Resolved ${summary.resolved_item_count} item(s), updated ${summary.updated_row_count} row(s), and created ${summary.created_or_updated_rule_count} mapping rule(s).`,
      );
      clearReconcileSession();
      await loadBatches?.();
      onClose();
      onResolved?.(batchId);
    } catch (error) {
      const status = error?.response?.status;
      const missingBatch = status === 404;

      if (missingBatch) {
        clearReconcileSession();
        await loadBatches?.();
        showNoticeRef.current(
          'error',
          'This Sales Analysis upload is no longer available. It may have been discarded. Please upload it again.',
        );
        onClose();
      } else {
        showNoticeRef.current('error', messageFromError(error, 'Unable to save unmatched item resolutions.'));
      }
    } finally {
      setSaving(false);
    }
  }

  function requestClose() {
    if (isBusy) {
      return;
    }

    setIsCloseConfirmOpen(true);
  }

  async function confirmDiscardUpload() {
    if (!salesAnalysisFileId) {
      showNoticeRef.current('error', 'Unable to discard upload because the Sales Analysis file was not found.');
      setIsCloseConfirmOpen(false);
      return;
    }

    setDiscardingUpload(true);

    try {
      await axios.delete(`/api/uploaded-files/${salesAnalysisFileId}`);
      await loadBatches?.();
      clearReconcileSession();
      showNoticeRef.current('success', 'Sales Analysis upload discarded.');
      setIsCloseConfirmOpen(false);
      onClose();
    } catch (error) {
      showNoticeRef.current('error', messageFromError(error, 'Unable to discard uploaded file.'));
    } finally {
      setDiscardingUpload(false);
    }
  }

  return (
  <>
    <div className="modal-backdrop" role="presentation">
      <section
        aria-labelledby="unmatched-items-title"
        aria-modal="true"
        className={
          view === 'create-rule'
            ? 'modal-card modal-card-rule modal-card-unmatched-rule'
            : 'modal-card modal-card-wide modal-card-unmatched'
        }
        role="dialog"
      >
        {view !== 'create-rule' ? (
          <button
            aria-label="Close"
            className="modal-close-button"
            disabled={isBusy}
            type="button"
            onClick={requestClose}
          >
            <ActionIcon name="cancel" />
          </button>
        ) : null}

        {view === 'create-rule' ? (
          <>
            <div className="section-heading unmatched-rule-heading">
              <div>
                <button
                  className="link-button unmatched-rule-back"
                  disabled={creatingRule}
                  type="button"
                  onClick={backToUnmatchedView}
                >
                  <ButtonContent icon="back">Back</ButtonContent>
                </button>
                <h2 id="unmatched-items-title">Create Mapping Rule</h2>
              </div>
            </div>
            <RuleForm
              categories={categories}
              form={createRuleForm}
              isSubmitting={creatingRule}
              primaryLabel="Create Rule"
              setForm={setCreateRuleForm}
              onSubmit={submitCreateRule}
            />
          </>
        ) : (
          <>
            <div className="section-heading unmatched-modal-heading">
              <div>
                <p className="eyebrow">Reconcile</p>
                <h2 id="unmatched-items-title">
                  Resolve Unmatched Items
                  {itemGroups.length > 0 ? (
                    <span className="unmatched-items-count">
                      {' '}
                      ({itemGroups.length} item{itemGroups.length === 1 ? '' : 's'})
                    </span>
                  ) : null}
                </h2>
              </div>
              <button
                className="secondary-button"
                disabled={isBusy}
                type="button"
                onClick={openCreateRuleView}
              >
                <ButtonContent icon="add">Add mapping rule</ButtonContent>
              </button>
            </div>
            <p className="unmatched-modal-intro">
              Assign a category to each item, or mark items that should stay on the raw sheet only with no category
              assignment. A mapping rule will be created for each resolved item.
            </p>

            {itemGroups.length === 0 ? <p>No unmatched items found.</p> : null}

            {itemGroups.length > 0 ? (
              <>
                <div className="unmatched-items-scroll table-wrap">
                  <table className="unmatched-items-table">
                    <thead>
                      <tr>
                        <th>Item ID</th>
                        <th>Description</th>
                        <th>Details</th>
                        <th>Category</th>
                      </tr>
                    </thead>
                    <tbody>
                      {itemGroups.map((group) => (
                        <tr key={group.item_id}>
                          <td>
                            <div className="unmatched-item-id-cell">
                              <span>{group.item_id}</span>
                              <button
                                aria-label={`Copy item ID ${group.item_id}`}
                                className="copy-item-id-button"
                                title="Copy item ID"
                                type="button"
                                onClick={() => copyItemId(group.item_id)}
                              >
                                <ActionIcon name="copy" />
                              </button>
                            </div>
                          </td>
                          <td>{group.description ?? '—'}</td>
                          <td>
                            <UnmatchedDetailsTrigger group={group} />
                          </td>
                          <td>
                            <div className="unmatched-category-cell">
                              <div className="unmatched-category-controls">
                                <SearchableCategorySelect
                                  categories={categories}
                                  disabled={Boolean(noCategoryAssignments[group.item_id])}
                                  showBucketInLabel
                                  value={resolutions[group.item_id] ?? ''}
                                  onChange={(categoryId) => {
                                    setNoCategoryAssignments((current) => ({
                                      ...current,
                                      [group.item_id]: false,
                                    }));
                                    setResolutions((current) => ({
                                      ...current,
                                      [group.item_id]: categoryId,
                                    }));
                                  }}
                                />
                                <button
                                  className="secondary-button"
                                  disabled={Boolean(noCategoryAssignments[group.item_id])}
                                  type="button"
                                  onClick={() => openCreateCategory(group.item_id)}
                                >
                                  <ButtonContent icon="add">Add</ButtonContent>
                                </button>
                                <label className="checkbox-label unmatched-no-category-label">
                                  <input
                                    checked={Boolean(noCategoryAssignments[group.item_id])}
                                    type="checkbox"
                                    onChange={(event) =>
                                      toggleNoCategoryAssignment(group.item_id, event.target.checked)
                                    }
                                  />
                                  <span>No category assignment</span>
                                </label>
                              </div>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                {!allResolved ? (
                  <p className="unmatched-modal-hint">
                    Resolve {remainingCount} remaining item{remainingCount === 1 ? '' : 's'} to enable Save
                    ({resolvedCount} of {itemGroups.length} done).
                  </p>
                ) : null}

                <div className="unmatched-modal-actions">
                  <button className="secondary-button" disabled={saving} type="button" onClick={requestClose}>
                    Cancel
                  </button>
                  <button
                    className="primary-button"
                    disabled={!allResolved || saving}
                    type="button"
                    onClick={saveResolutions}
                  >
                    <ButtonContent icon="save" loading={saving}>{saving ? 'Saving...' : 'Save'}</ButtonContent>
                  </button>
                </div>
              </>
            ) : null}
          </>
        )}
      </section>
    </div>
    {isCreateCategoryOpen ? (
      <Modal
        isBusy={creatingCategory}
        title="Add Category"
        onClose={() => {
          if (!creatingCategory) {
            setIsCreateCategoryOpen(false);
            setCreateCategoryForItemId('');
          }
        }}
      >
        <CategoryForm
          form={createCategoryForm}
          isSubmitting={creatingCategory}
          primaryLabel="Create Category"
          setForm={setCreateCategoryForm}
          onSubmit={submitCreateCategory}
        >
          <button
            className="secondary-button"
            disabled={creatingCategory}
            type="button"
            onClick={() => {
              setIsCreateCategoryOpen(false);
              setCreateCategoryForItemId('');
            }}
          >
            Cancel
          </button>
        </CategoryForm>
      </Modal>
    ) : null}
    {isCloseConfirmOpen ? (
      <ConfirmDialog
        cancelLabel="Go back"
        confirmButtonClassName="primary-button"
        confirmIcon={null}
        confirmLabel="Yes"
        isProcessing={discardingUpload}
        message="The file uploaded will not be saved, continue?"
        title="Discard Upload"
        onCancel={() => {
          if (!discardingUpload) {
            setIsCloseConfirmOpen(false);
          }
        }}
        onConfirm={confirmDiscardUpload}
      />
    ) : null}
  </>
  );
}

function UsersScreen({ currentUser, showNotice }) {
  const emptyForm = {
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    role: 'analyst',
    is_active: true,
  };
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedUser, setSelectedUser] = useState(null);
  const [editForm, setEditForm] = useState(emptyForm);
  const [createForm, setCreateForm] = useState(emptyForm);
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [isDeleteConfirmOpen, setIsDeleteConfirmOpen] = useState(false);
  const [statusFilter, setStatusFilter] = useState('active');
  const [roleFilter, setRoleFilter] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  const filteredUsers = useMemo(() => {
    const normalizedQuery = searchQuery.trim().toLowerCase();

    return users.filter((managedUser) => {
      const matchesStatus =
        statusFilter === 'all' || Boolean(managedUser.is_active) === (statusFilter === 'active');
      const matchesRole = roleFilter === 'all' || managedUser.role === roleFilter;

      if (!matchesStatus || !matchesRole) {
        return false;
      }

      if (!normalizedQuery) {
        return true;
      }

      return matchesListSearch(normalizedQuery, [
        managedUser.name,
        managedUser.first_name,
        managedUser.last_name,
        managedUser.email,
        managedUser.role,
      ]);
    });
  }, [users, searchQuery, statusFilter, roleFilter]);

  const loadUsers = useCallback(async () => {
    setLoading(true);

    try {
      const response = await axios.get('/api/users');
      const nextUsers = response.data.data ?? [];

      setUsers(nextUsers);
      setSelectedUser((current) => {
        if (!current) {
          return null;
        }

        return nextUsers.find((managedUser) => managedUser.id === current.id) ?? null;
      });
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load users.'));
    } finally {
      setLoading(false);
    }
  }, [showNotice]);

  useEffect(() => {
    loadUsers();
  }, [loadUsers]);

  useEffect(() => {
    if (selectedUser) {
      setEditForm(userToForm(selectedUser));
    }
  }, [selectedUser]);

  useEffect(() => {
    if (selectedUser && !filteredUsers.some((managedUser) => managedUser.id === selectedUser.id)) {
      setSelectedUser(null);
    }
  }, [filteredUsers, selectedUser]);

  function userPayload(form, { includePassword = true } = {}) {
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      role: form.role,
      is_active: Boolean(form.is_active),
    };

    if (includePassword && form.password) {
      payload.password = form.password;
    }

    return payload;
  }

  async function submitCreateUser(event) {
    event.preventDefault();
    setCreating(true);

    try {
      const response = await axios.post('/api/users', userPayload(createForm, { includePassword: true }));

      showNotice('success', 'User created.');
      setCreateForm(emptyForm);
      setIsCreateOpen(false);
      await loadUsers();
      setSelectedUser(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to create user.'));
    } finally {
      setCreating(false);
    }
  }

  async function submitEditUser(event) {
    event.preventDefault();

    if (!selectedUser) {
      return;
    }

    setEditing(true);

    try {
      const response = await axios.patch(
        `/api/users/${selectedUser.id}`,
        userPayload(editForm, { includePassword: Boolean(editForm.password) }),
      );

      showNotice('success', 'User updated.');
      await loadUsers();
      setSelectedUser(response.data.data);
      setEditForm((current) => ({ ...current, password: '' }));
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update user.'));
    } finally {
      setEditing(false);
    }
  }

  async function confirmDeleteUser() {
    if (!selectedUser || (currentUser && selectedUser.id === currentUser.id)) {
      return;
    }

    setDeleting(true);

    try {
      await axios.delete(`/api/users/${selectedUser.id}`);
      showNotice('success', 'User deleted.');
      setSelectedUser(null);
      setIsDeleteConfirmOpen(false);
      await loadUsers();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete user.'));
    } finally {
      setDeleting(false);
    }
  }

  const isEditingSelf = selectedUser && currentUser && selectedUser.id === currentUser.id;

  return (
    <>
      {isDeleteConfirmOpen && selectedUser ? (
        <ConfirmDialog
          confirmLabel="Delete User"
          isProcessing={deleting}
          message={`Delete user "${selectedUser.name}"? This action cannot be undone.`}
          title="Delete User"
          onCancel={() => {
            if (!deleting) {
              setIsDeleteConfirmOpen(false);
            }
          }}
          onConfirm={confirmDeleteUser}
        />
      ) : null}
    <section className="panel-grid">
      <article className="panel panel-legend panel-transparent">
        <p className="eyebrow">Users</p>
        <div className="section-heading section-heading-toolbar">
          <div className="button-row">
            <ListSearchInput
              placeholder="Search users..."
              value={searchQuery}
              onChange={setSearchQuery}
            />
            <label className="status-filter">
              Role
              <select value={roleFilter} onChange={(event) => setRoleFilter(event.target.value)}>
                <option value="all">All</option>
                {userRoleOptions.map((role) => (
                  <option key={role.value} value={role.value}>
                    {role.label}
                  </option>
                ))}
              </select>
            </label>
            <label className="status-filter">
              Status
              <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="all">All</option>
              </select>
            </label>
          </div>
          <button className="primary-button" type="button" onClick={() => setIsCreateOpen(true)}>
            <ButtonContent icon="add">Create User</ButtonContent>
          </button>
        </div>
        {loading ? (
          <p>Loading users...</p>
        ) : (
          <UsersTable
            roleFilter={roleFilter}
            searchQuery={searchQuery}
            selectedUser={selectedUser}
            statusFilter={statusFilter}
            users={filteredUsers}
            onSelectUser={setSelectedUser}
          />
        )}
      </article>

      <article className="panel panel-legend">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Details</p>
            <h2>{selectedUser ? 'Edit User' : 'Select a User'}</h2>
          </div>
        </div>

        {selectedUser ? (
          <UserForm
            form={editForm}
            isSelf={Boolean(isEditingSelf)}
            isSubmitting={editing || deleting}
            passwordRequired={false}
            primaryLabel="Update User"
            setForm={setEditForm}
            onSubmit={submitEditUser}
          >
            {!isEditingSelf ? (
              <button
                className="danger-button"
                disabled={editing || deleting}
                type="button"
                onClick={() => setIsDeleteConfirmOpen(true)}
              >
                <ButtonContent icon="delete">Delete User</ButtonContent>
              </button>
            ) : null}
          </UserForm>
        ) : (
          <div className="empty-state">
            <strong>No user selected</strong>
            <p>Click a user from the table on the left to view or edit them.</p>
          </div>
        )}
      </article>

      {isCreateOpen ? (
        <Modal
          isBusy={creating}
          title="Create User"
          onClose={() => {
            if (!creating) {
              setIsCreateOpen(false);
            }
          }}
        >
          <UserForm
            form={createForm}
            isSelf={false}
            isSubmitting={creating}
            passwordRequired
            primaryLabel="Create User"
            setForm={setCreateForm}
            onSubmit={submitCreateUser}
          />
        </Modal>
      ) : null}
    </section>
    </>
  );
}

function userToForm(managedUser) {
  return {
    first_name: managedUser.first_name ?? '',
    last_name: managedUser.last_name ?? '',
    email: managedUser.email ?? '',
    password: '',
    role: managedUser.role ?? 'analyst',
    is_active: Boolean(managedUser.is_active),
  };
}

function UserForm({
  children,
  form,
  isSelf = false,
  isSubmitting = false,
  passwordRequired = false,
  primaryLabel,
  setForm,
  onSubmit,
}) {
  const submitLabel = isSubmitting
    ? (primaryLabel.startsWith('Update') ? 'Updating...' : 'Creating...')
    : primaryLabel;

  return (
    <form className="stacked-form" onSubmit={onSubmit}>
      <fieldset className="form-fieldset" disabled={isSubmitting}>
        <label>
          First Name
          <input
            required
            value={form.first_name}
            onChange={(event) => setForm({ ...form, first_name: event.target.value })}
          />
        </label>
        <label>
          Last Name
          <input
            required
            value={form.last_name}
            onChange={(event) => setForm({ ...form, last_name: event.target.value })}
          />
        </label>
        <label>
          Email
          <input
            required
            type="email"
            value={form.email}
            onChange={(event) => setForm({ ...form, email: event.target.value })}
          />
        </label>
        <label>
          Role
          <select
            disabled={isSelf}
            required
            value={form.role}
            onChange={(event) => setForm({ ...form, role: event.target.value })}
          >
            {userRoleOptions.map((role) => (
              <option key={role.value} value={role.value}>
                {role.label}
              </option>
            ))}
          </select>
        </label>
        <label>
          Password{passwordRequired ? '' : ' (leave blank to keep)'}
          <input
            autoComplete="new-password"
            minLength={passwordRequired ? 8 : undefined}
            required={passwordRequired}
            type="password"
            value={form.password}
            onChange={(event) => setForm({ ...form, password: event.target.value })}
          />
        </label>
        <label className="checkbox-label">
          <input
            checked={form.is_active}
            disabled={isSelf}
            type="checkbox"
            onChange={(event) => setForm({ ...form, is_active: event.target.checked })}
          />
          Active
        </label>
        {isSelf ? (
          <p className="form-hint">You cannot deactivate, demote, or delete your own account.</p>
        ) : null}
        <div className="form-actions">
          <button className="primary-button" disabled={isSubmitting} type="submit">
            <ButtonContent
              icon={primaryLabel.startsWith('Update') ? 'save' : 'add'}
              loading={isSubmitting}
            >
              {submitLabel}
            </ButtonContent>
          </button>
          {children}
        </div>
      </fieldset>
    </form>
  );
}

function UsersTable({ users, selectedUser, statusFilter, roleFilter, searchQuery, onSelectUser }) {
  const pagination = usePagination(users);

  if (users.length === 0) {
    if (searchQuery.trim()) {
      return <p>No users match your search.</p>;
    }

    const statusLabel = statusFilter === 'all' ? '' : `${statusFilter} `;
    const roleLabel = roleFilter === 'all' ? '' : `${roleFilter} `;

    return <p>No {statusLabel}{roleLabel}users found.</p>;
  }

  return (
    <>
      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {pagination.paginatedItems.map((managedUser) => (
              <tr
                className={selectedUser?.id === managedUser.id ? 'clickable-row selected-row' : 'clickable-row'}
                key={managedUser.id}
                onClick={() => onSelectUser(managedUser)}
              >
                <td>{managedUser.name}</td>
                <td>{managedUser.email}</td>
                <td>{managedUser.role}</td>
                <td>{managedUser.is_active ? 'Active' : 'Inactive'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <PaginationControls {...pagination} />
    </>
  );
}

function formatMeterNumber(value, fractionDigits = 0) {
  if (value === null || value === undefined) {
    return '';
  }

  return Number(value).toLocaleString(undefined, {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  });
}

function formatMeterPercent(value) {
  if (value === null || value === undefined) {
    return '';
  }

  return `${Number(value).toFixed(1)}%`;
}

function formatMeterMoney(value) {
  return formatMeterNumber(value, 2);
}

function priorMeterWeekWithData(weeks, weekIndex) {
  return [...weeks]
    .filter((week) => week.has_data && week.index < weekIndex && week.raw_sales !== null && week.raw_sales !== undefined)
    .at(-1);
}

function meterWeekSalesTitle(weekValues, rowWeeks) {
  if (!weekValues?.has_data || weekValues.sales === null || weekValues.sales === undefined) {
    return undefined;
  }

  const displayed = formatMeterMoney(weekValues.sales);
  const raw = weekValues.raw_sales;

  if (weekValues.index === 1 || raw === null || raw === undefined) {
    return `Week ${weekValues.index} sales analysis total: ${displayed}`;
  }

  const priorWeek = priorMeterWeekWithData(rowWeeks, weekValues.index);

  if (!priorWeek) {
    return `Week ${weekValues.index} sales analysis total: ${displayed}`;
  }

  return [
    `${formatMeterMoney(raw)} − ${formatMeterMoney(priorWeek.raw_sales)} = ${displayed}`,
    `This week's sales analysis total minus week ${priorWeek.index}'s sales analysis total`,
  ].join('\n');
}

function meterMtdSalesTitle(row) {
  if (row.mtd_sales === null || row.mtd_sales === undefined) {
    return undefined;
  }

  const parts = row.weeks
    .filter((week) => week.has_data && week.sales !== null && week.sales !== undefined)
    .map((week) => formatMeterMoney(week.sales));

  if ((row.additional_fees_total ?? 0) !== 0 || (row.additional_fees?.length ?? 0) > 0) {
    parts.push(formatMeterMoney(row.additional_fees_total ?? 0));
  }

  if (parts.length === 0) {
    return undefined;
  }

  const feeNote = (row.additional_fees?.length ?? 0) > 0
    ? '\nIncludes additional fees'
    : '';

  return `${parts.join(' + ')} = ${formatMeterMoney(row.mtd_sales)}\nSum of weekly net sales${feeNote}`;
}

function meterAdditionalFeesTitle(row) {
  const fees = row.additional_fees ?? [];

  if (fees.length === 0) {
    return 'No additional fees';
  }

  const parts = fees.map((fee) => formatMeterMoney(fee.amount));

  return `${parts.join(' + ')} = ${formatMeterMoney(row.additional_fees_total)}\nAdditional fee line(s)`;
}

function meterTotalWeekSalesTitle(weekValues) {
  if (!weekValues?.has_data || weekValues.sales === null || weekValues.sales === undefined) {
    return undefined;
  }

  return `Sum of all model net sales for week ${weekValues.index}: ${formatMeterMoney(weekValues.sales)}`;
}

function meterTotalMtdSalesTitle(totals) {
  if (totals.mtd_sales === null || totals.mtd_sales === undefined) {
    return undefined;
  }

  const parts = totals.weeks
    .filter((week) => week.has_data && week.sales !== null && week.sales !== undefined)
    .map((week) => formatMeterMoney(week.sales));

  if ((totals.additional_fees_total ?? 0) !== 0) {
    parts.push(formatMeterMoney(totals.additional_fees_total));
  }

  if (parts.length === 0) {
    return undefined;
  }

  return `${parts.join(' + ')} = ${formatMeterMoney(totals.mtd_sales)}\nSum of weekly total net sales and additional fees`;
}

function meterTotalAdditionalFeesTitle(totals) {
  if (totals.additional_fees_total === null || totals.additional_fees_total === undefined) {
    return undefined;
  }

  return `Sum of all model additional fees: ${formatMeterMoney(totals.additional_fees_total)}`;
}

function meterPercentTitle(weekValues, mtdSales) {
  if (!weekValues?.has_data || weekValues.percent === null || weekValues.percent === undefined || !mtdSales) {
    return undefined;
  }

  return `(${formatMeterMoney(weekValues.sales)} ÷ ${formatMeterMoney(mtdSales)}) × 100 = ${formatMeterPercent(weekValues.percent)}`;
}

function meterAdditionalFeesPercentTitle(totals) {
  const percent = totals?.additional_fees_percent;
  const mtdSales = totals?.mtd_sales;

  if (percent === null || percent === undefined || !mtdSales) {
    return undefined;
  }

  return `(${formatMeterMoney(totals.additional_fees_total ?? 0)} ÷ ${formatMeterMoney(mtdSales)}) × 100 = ${formatMeterPercent(percent)}`;
}

function formatMeterFeeDisplay(total, fees) {
  if (!fees?.length) {
    return '—';
  }

  return formatMeterMoney(total ?? 0);
}

function WeeklyMeterAdditionalFeesModal({
  modelLabel,
  monthLabel,
  initialFees,
  saving,
  onClose,
  onSave,
}) {
  const [lines, setLines] = useState(() => (
    initialFees?.length
      ? initialFees.map((fee) => String(fee.amount))
      : ['']
  ));
  const [error, setError] = useState('');

  function updateLine(index, value) {
    setError('');
    setLines((current) => current.map((line, lineIndex) => (lineIndex === index ? value : line)));
  }

  function addLine() {
    setError('');
    setLines((current) => [...current, '']);
  }

  function removeLine(index) {
    setError('');
    setLines((current) => (current.length <= 1 ? [''] : current.filter((_, lineIndex) => lineIndex !== index)));
  }

  async function handleSave() {
    const fees = lines
      .map((line) => line.trim())
      .filter((line) => line !== '')
      .map((line) => ({ amount: Number(line) }));

    if (fees.some((fee) => Number.isNaN(fee.amount))) {
      setError('Enter a valid number for each amount.');
      return;
    }

    await onSave(fees);
  }

  return (
    <Modal
      className="modal-card-meter-fees"
      isBusy={saving}
      title={`Additional fees — ${modelLabel}`}
      onClose={onClose}
    >
      <p className="muted">{monthLabel}</p>
      {error ? <p className="form-error">{error}</p> : null}
      <div className="meter-fee-lines">
        {lines.map((line, index) => (
          <div className="meter-fee-line" key={`fee-line-${index}`}>
            <label>
              Amount {index + 1}
              <input
                disabled={saving}
                inputMode="decimal"
                placeholder="0.00"
                type="number"
                step="0.01"
                value={line}
                onChange={(event) => updateLine(index, event.target.value)}
              />
            </label>
            <button
              aria-label={`Remove amount ${index + 1}`}
              className="meter-fee-edit"
              disabled={saving}
              type="button"
              onClick={() => removeLine(index)}
            >
              <ActionIcon name="delete" />
            </button>
          </div>
        ))}
      </div>
      <div className="form-actions meter-fee-actions">
        <button className="secondary-button" disabled={saving} type="button" onClick={addLine}>
          <ButtonContent icon="add">Add more</ButtonContent>
        </button>
        <button className="secondary-button" disabled={saving} type="button" onClick={onClose}>
          <ButtonContent icon="cancel">Cancel</ButtonContent>
        </button>
        <button className="primary-button" disabled={saving} type="button" onClick={handleSave}>
          <ButtonContent icon="save" loading={saving}>
            {saving ? 'Saving...' : 'Save'}
          </ButtonContent>
        </button>
      </div>
    </Modal>
  );
}

function WeeklyMeterReportScreen({ initialYear, initialMonth, showNotice, onBackToCalendar }) {
  const now = new Date();
  const [year, setYear] = useState(initialYear ?? now.getFullYear());
  const [month, setMonth] = useState(initialMonth ?? now.getMonth() + 1);
  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(true);
  const [feeEditor, setFeeEditor] = useState(null);
  const [savingFees, setSavingFees] = useState(false);
  const [downloadingYear, setDownloadingYear] = useState(false);

  const loadReport = useCallback(async (nextYear, nextMonth) => {
    setLoading(true);

    try {
      const response = await axios.get('/api/weekly-meter-report', {
        params: { year: nextYear, month: nextMonth },
      });
      const data = response.data.data;
      setReport(data);

      if (Array.isArray(data.available_months) && data.available_months.length > 0 && !data.available_months.includes(nextMonth)) {
        const fallbackMonth = data.available_months[data.available_months.length - 1];
        setMonth(fallbackMonth);
        return;
      }
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load the weekly meter report.'));
    } finally {
      setLoading(false);
    }
  }, [showNotice]);

  useEffect(() => {
    loadReport(year, month);
  }, [year, month, loadReport]);

  const years = report?.years ?? [now.getFullYear() - 1, now.getFullYear()];
  const availableMonths = report?.available_months ?? [month];
  const monthLabel = `${monthNames[month - 1]} ${year}`;
  const titleColSpan = 1 + ((report?.weeks.length ?? 0) * 2) + 1 + 2;

  async function downloadYearReport() {
    setDownloadingYear(true);

    try {
      const response = await axios.get('/api/weekly-meter-report/download', {
        params: { year },
        responseType: 'blob',
      });
      const url = URL.createObjectURL(response.data);
      const link = document.createElement('a');

      link.href = url;
      link.download = `${year}-weekly-meter-report.xlsx`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (error) {
      // With a blob response type, error bodies arrive as a Blob and need to be parsed manually.
      let message = 'Unable to download the weekly meter report.';

      if (error?.response?.data instanceof Blob) {
        try {
          message = JSON.parse(await error.response.data.text()).message ?? message;
        } catch {
          // Keep the fallback message when the body is not JSON.
        }
      }

      showNotice('error', message);
    } finally {
      setDownloadingYear(false);
    }
  }

  async function saveAdditionalFees(fees) {
    if (!feeEditor) {
      return;
    }

    setSavingFees(true);

    try {
      const response = await axios.put('/api/weekly-meter-report/additional-fees', {
        year,
        month,
        model_label: feeEditor.label,
        fees,
      });
      setReport(response.data.data);
      setFeeEditor(null);
      showNotice('success', `Additional fees saved for ${feeEditor.label}.`);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to save additional fees.'));
    } finally {
      setSavingFees(false);
    }
  }

  return (
    <section className="panel-grid meter-report-grid">
      <article className="panel panel-span panel-legend meter-report-panel">
        <p className="eyebrow">Meter Report</p>
        <div className="section-heading section-heading-toolbar">
          <div>
            <h2>Handmeter / RHP Shipments</h2>
          </div>
          <div className="meter-heading-actions">
            {onBackToCalendar ? (
              <button className="secondary-button" type="button" onClick={onBackToCalendar}>
                <ButtonContent icon="back">Back to Calendar</ButtonContent>
              </button>
            ) : null}
            <button
              className="secondary-button"
              disabled={downloadingYear}
              title={`Download the ${year} Meter Report (all 12 months, one sheet per month)`}
              type="button"
              onClick={downloadYearReport}
            >
              <ButtonContent icon="download" loading={downloadingYear}>
                Download {year}
              </ButtonContent>
            </button>
            <label className="status-filter">
              Year
              <select
                value={year}
                onChange={(event) => {
                  const nextYear = Number(event.target.value);
                  setYear(nextYear);
                  if (nextYear === now.getFullYear() && month > now.getMonth() + 1) {
                    setMonth(now.getMonth() + 1);
                  }
                }}
              >
                {years.map((optionYear) => (
                  <option key={optionYear} value={optionYear}>
                    {optionYear}
                  </option>
                ))}
              </select>
            </label>
          </div>
        </div>

        <div className="meter-month-tabs" role="tablist" aria-label="Report months">
          {availableMonths.map((optionMonth) => (
            <button
              className={optionMonth === month ? 'meter-month-tab meter-month-tab-active' : 'meter-month-tab'}
              key={optionMonth}
              role="tab"
              type="button"
              aria-selected={optionMonth === month}
              onClick={() => setMonth(optionMonth)}
            >
              {monthNames[optionMonth - 1]} {year}
            </button>
          ))}
        </div>

        {loading && !report ? <p>Loading weekly meter report...</p> : null}

        {report ? (
          <div className="meter-table-wrap">
            <table className="meter-table">
              <thead>
                <tr>
                  <th className="meter-title" colSpan={titleColSpan}>
                    {report.title}
                  </th>
                </tr>
                <tr>
                  <th className="meter-corner meter-corner-top" aria-hidden="true" />
                  {report.weeks.map((week) => (
                    <th
                      className={week.index === 5 ? 'meter-week-head meter-week-5' : 'meter-week-head'}
                      colSpan={2}
                      key={`week-label-${week.index}`}
                    >
                      {week.label}
                    </th>
                  ))}
                  <th className="meter-fees-head">ADDT&apos;L FEES</th>
                  <th className="meter-total-head" colSpan={2}>Total</th>
                </tr>
                <tr>
                  <th className="meter-corner meter-corner-mid">MODEL</th>
                  {report.weeks.map((week) => (
                    <th
                      className={week.index === 5 ? 'meter-week-head meter-week-5' : 'meter-week-head'}
                      colSpan={2}
                      key={`week-range-${week.index}`}
                    >
                      {week.range}
                    </th>
                  ))}
                  <th className="meter-fees-head" aria-hidden="true" />
                  <th className="meter-total-head" colSpan={2}>Month to Date</th>
                </tr>
                <tr>
                  <th className="meter-corner meter-corner-bottom" aria-hidden="true" />
                  {report.weeks.map((week) => (
                    <Fragment key={`week-sub-${week.index}`}>
                      <th className={week.index === 5 ? 'meter-week-sub meter-week-5' : 'meter-week-sub'}># OF UNITS</th>
                      <th className={week.index === 5 ? 'meter-week-sub meter-week-5' : 'meter-week-sub'}>NET SALES $</th>
                    </Fragment>
                  ))}
                  <th className="meter-fees-sub">NET SALES $</th>
                  <th className="meter-total-sub"># OF UNITS</th>
                  <th className="meter-total-sub">NET SALES $</th>
                </tr>
              </thead>
              <tbody>
                {report.rows.map((row) => (
                  <tr key={row.label}>
                    <td className="meter-model">{row.label}</td>
                    {row.weeks.map((weekValues) => (
                      <Fragment key={`${row.label}-${weekValues.index}`}>
                        <td className="meter-num">
                          {row.tracks_units ? formatMeterNumber(weekValues.units) : ''}
                        </td>
                        <td className="meter-num meter-sales" title={meterWeekSalesTitle(weekValues, row.weeks)}>
                          {formatMeterNumber(weekValues.sales, 2)}
                        </td>
                      </Fragment>
                    ))}
                    <td className="meter-num meter-fees-cell" title={meterAdditionalFeesTitle(row)}>
                      <span>{formatMeterFeeDisplay(row.additional_fees_total, row.additional_fees)}</span>
                      <button
                        aria-label={`Edit additional fees for ${row.label}`}
                        className="meter-fee-edit"
                        type="button"
                        onClick={() => setFeeEditor(row)}
                      >
                        <ActionIcon name="edit" />
                      </button>
                    </td>
                    <td className="meter-num meter-mtd">{row.tracks_units ? formatMeterNumber(row.mtd_units) : ''}</td>
                    <td className="meter-num meter-mtd meter-sales" title={meterMtdSalesTitle(row)}>
                      {formatMeterNumber(row.mtd_sales, 2)}
                    </td>
                  </tr>
                ))}
                <tr className="meter-total-row">
                  <td>TOTAL</td>
                  {report.totals.weeks.map((weekValues) => (
                    <Fragment key={`total-${weekValues.index}`}>
                      <td className="meter-num">{formatMeterNumber(weekValues.units)}</td>
                      <td className="meter-num meter-sales" title={meterTotalWeekSalesTitle(weekValues)}>
                        {formatMeterNumber(weekValues.sales, 2)}
                      </td>
                    </Fragment>
                  ))}
                  <td className="meter-num meter-fees-cell" title={meterTotalAdditionalFeesTitle(report.totals)}>
                    {formatMeterMoney(report.totals.additional_fees_total ?? 0)}
                  </td>
                  <td className="meter-num meter-mtd">{formatMeterNumber(report.totals.mtd_units)}</td>
                  <td className="meter-num meter-mtd meter-sales" title={meterTotalMtdSalesTitle(report.totals)}>
                    {formatMeterNumber(report.totals.mtd_sales, 2)}
                  </td>
                </tr>
                <tr className="meter-percent-row">
                  <td />
                  {report.totals.weeks.map((weekValues) => (
                    <Fragment key={`percent-${weekValues.index}`}>
                      <td />
                      <td className="meter-num meter-sales" title={meterPercentTitle(weekValues, report.totals.mtd_sales)}>
                        {formatMeterPercent(weekValues.percent)}
                      </td>
                    </Fragment>
                  ))}
                  <td className="meter-num meter-sales" title={meterAdditionalFeesPercentTitle(report.totals)}>
                    {formatMeterPercent(report.totals.additional_fees_percent)}
                  </td>
                  <td />
                  <td className="meter-num meter-mtd">100%</td>
                </tr>
              </tbody>
            </table>
          </div>
        ) : null}

        {feeEditor ? (
          <WeeklyMeterAdditionalFeesModal
            initialFees={feeEditor.additional_fees}
            modelLabel={feeEditor.label}
            monthLabel={monthLabel}
            saving={savingFees}
            onClose={() => {
              if (!savingFees) {
                setFeeEditor(null);
              }
            }}
            onSave={saveAdditionalFees}
          />
        ) : null}
      </article>
    </section>
  );
}

const DASHBOARD_PALETTE = [
  'var(--primary)',
  'var(--icon)',
  'var(--success-text)',
  'var(--warning-text)',
  'var(--danger-text)',
  'var(--text-muted)',
];

const DASHBOARD_TOOLTIP_PROPS = {
  contentStyle: {
    background: 'var(--surface-2)',
    border: '1px solid var(--border-2)',
    borderRadius: 8,
    color: 'var(--text)',
    fontSize: '0.82rem',
  },
  labelStyle: { color: 'var(--text-strong)', fontWeight: 700 },
  itemStyle: { color: 'var(--text)' },
  cursor: { fill: 'var(--surface-3)', fillOpacity: 0.5 },
};

const DASHBOARD_AXIS_TICK = { fill: 'var(--text-muted)', fontSize: 12 };

function compactCurrency(value) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value ?? 0));
}

function truncateLabel(value, maxLength = 24) {
  const text = String(value ?? '');

  return text.length > maxLength ? `${text.slice(0, maxLength - 1)}…` : text;
}

function DashboardDelta({ percent }) {
  if (percent === null || percent === undefined) {
    return <small className="dashboard-delta dashboard-delta-flat">No prior month data</small>;
  }

  const direction = percent > 0 ? 'up' : percent < 0 ? 'down' : 'flat';
  const arrow = direction === 'up' ? '▲' : direction === 'down' ? '▼' : '■';

  return (
    <small className={`dashboard-delta dashboard-delta-${direction}`}>
      {arrow} {Math.abs(percent).toFixed(1)}% vs prev. month
    </small>
  );
}

function DashboardKpi({ label, value, children, tone }) {
  return (
    <div className={tone ? `summary-card dashboard-kpi dashboard-kpi-${tone}` : 'summary-card dashboard-kpi'}>
      <span>{label}</span>
      <strong>{value}</strong>
      {children}
    </div>
  );
}

function DashboardChartCard({ title, subtitle, className = '', children }) {
  return (
    <section className={`dashboard-card ${className}`.trim()}>
      <header className="dashboard-card-header">
        <h3>{title}</h3>
        {subtitle ? <small>{subtitle}</small> : null}
      </header>
      {children}
    </section>
  );
}

function DashboardEmptyChart({ message = 'No data for this month.' }) {
  return <p className="dashboard-empty">{message}</p>;
}

function DashboardHorizontalBars({ data, dataKey, valueFormatter, color = 'var(--primary)', labelWidth = 150 }) {
  if (data.length === 0) {
    return <DashboardEmptyChart />;
  }

  return (
    <ResponsiveContainer height={Math.max(180, data.length * 34 + 30)} width="100%">
      <BarChart data={data} layout="vertical" margin={{ top: 4, right: 16, bottom: 4, left: 4 }}>
        <CartesianGrid horizontal={false} stroke="var(--border)" strokeDasharray="3 3" />
        <XAxis axisLine={false} tick={DASHBOARD_AXIS_TICK} tickFormatter={valueFormatter} tickLine={false} type="number" />
        <YAxis
          axisLine={false}
          dataKey="label"
          tick={DASHBOARD_AXIS_TICK}
          tickFormatter={(value) => truncateLabel(value, Math.floor(labelWidth / 7))}
          tickLine={false}
          type="category"
          width={labelWidth}
        />
        <Tooltip {...DASHBOARD_TOOLTIP_PROPS} formatter={(value) => [valueFormatter(value), 'Amount']} />
        <Bar dataKey={dataKey} fill={color} radius={[0, 4, 4, 0]} />
      </BarChart>
    </ResponsiveContainer>
  );
}

function DashboardScreen({ showNotice, onOpenCalendar, onOpenMeterReport }) {
  const now = new Date();
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [result, setResult] = useState(null);

  const requestKey = `${year}-${month}`;
  const years = [now.getFullYear() - 1, now.getFullYear()];
  const lastMonth = year === now.getFullYear() ? now.getMonth() + 1 : 12;
  const availableMonths = Array.from({ length: lastMonth }, (_, index) => index + 1);

  useEffect(() => {
    let cancelled = false;

    axios
      .get('/api/dashboard', { params: { year, month } })
      .then((response) => {
        if (!cancelled) {
          setResult({ key: `${year}-${month}`, data: response.data.data });
        }
      })
      .catch((error) => {
        if (!cancelled) {
          setResult({ key: `${year}-${month}`, data: null });
          showNotice('error', messageFromError(error, 'Unable to load the dashboard.'));
        }
      });

    return () => {
      cancelled = true;
    };
  }, [year, month, showNotice]);

  const loading = result?.key !== requestKey;
  // Keep showing the previous month (dimmed) while the next one loads.
  const data = result?.data ?? null;
  const sales = data?.sales_analysis;
  const meter = data?.meter;
  const health = data?.health;
  const hasData = (health?.weeks_with_data ?? 0) > 0;

  const weekChartData = useMemo(
    () =>
      (data?.weeks ?? []).map((week) => ({
        name: `W${week.index}`,
        range: week.range,
        salesAnalysis: week.sales_analysis_weekly,
        meterSales: week.meter_sales,
        meterUnits: week.meter_units,
      })),
    [data],
  );

  const modelSalesData = useMemo(
    () => (meter?.top_models_by_sales ?? []).map((model) => ({ label: model.label, value: model.sales })),
    [meter],
  );
  const modelUnitsData = useMemo(
    () => (meter?.top_models_by_units ?? []).map((model) => ({ label: model.label, value: model.units ?? 0 })),
    [meter],
  );
  const categoryData = useMemo(
    () => (sales?.top_categories ?? []).map((category) => ({ label: category.name, value: category.amount })),
    [sales],
  );

  const unmatchedRows = health?.unmatched_rows ?? 0;
  const reconciledWeeks = health?.reconciled_weeks ?? 0;
  const reportCounts = health?.reports_by_status ?? {};

  return (
    <section className="panel-grid meter-report-grid">
      <article className="panel panel-span panel-legend dashboard-panel">
        <p className="eyebrow">Dashboard</p>
        <div className="section-heading section-heading-toolbar">
          <div>
            <h2>Sales Analysis &amp; Meter Report</h2>
            <p className="dashboard-subtitle">Statistics for {data?.title ?? `${monthNames[month - 1]} ${year}`}</p>
          </div>
          <div className="meter-heading-actions">
            <button className="secondary-button" type="button" onClick={onOpenCalendar}>
              <ButtonContent icon="back">Calendar</ButtonContent>
            </button>
            <button className="secondary-button" type="button" onClick={() => onOpenMeterReport(year, month)}>
              <ButtonContent>Open Meter Report</ButtonContent>
            </button>
            <label className="status-filter">
              Year
              <select
                value={year}
                onChange={(event) => {
                  const nextYear = Number(event.target.value);
                  setYear(nextYear);

                  if (nextYear === now.getFullYear() && month > now.getMonth() + 1) {
                    setMonth(now.getMonth() + 1);
                  }
                }}
              >
                {years.map((optionYear) => (
                  <option key={optionYear} value={optionYear}>
                    {optionYear}
                  </option>
                ))}
              </select>
            </label>
          </div>
        </div>

        <div className="meter-month-tabs" role="tablist" aria-label="Dashboard months">
          {availableMonths.map((optionMonth) => (
            <button
              className={optionMonth === month ? 'meter-month-tab meter-month-tab-active' : 'meter-month-tab'}
              key={optionMonth}
              role="tab"
              type="button"
              aria-selected={optionMonth === month}
              onClick={() => setMonth(optionMonth)}
            >
              {monthNames[optionMonth - 1]} {year}
            </button>
          ))}
        </div>

        {loading && !data ? <p>Loading dashboard...</p> : null}

        {data ? (
          <div className={loading ? 'dashboard-body dashboard-body-loading' : 'dashboard-body'}>
            <div className="dashboard-kpi-grid">
              <DashboardKpi label="Sales Analysis (MTD)" value={currency(sales.mtd_total)}>
                <DashboardDelta percent={sales.change_percent} />
              </DashboardKpi>
              <DashboardKpi label="Meter Sales (MTD)" value={currency(meter.mtd_sales)}>
                <DashboardDelta percent={meter.sales_change_percent} />
              </DashboardKpi>
              <DashboardKpi label="Meter Units (MTD)" value={formatMeterNumber(meter.mtd_units)}>
                <DashboardDelta percent={meter.units_change_percent} />
              </DashboardKpi>
              <DashboardKpi label="Weeks Uploaded" value={`${health.weeks_with_data} / ${health.weeks_in_month}`}>
                <small className="dashboard-delta dashboard-delta-flat">Weeks with a Sales Analysis upload</small>
              </DashboardKpi>
              <DashboardKpi label="Unmatched Rows" value={formatMeterNumber(unmatchedRows)} tone={unmatchedRows > 0 ? 'warning' : 'success'}>
                <small className="dashboard-delta dashboard-delta-flat">
                  {unmatchedRows > 0 ? 'Resolve them to include in totals' : 'All rows classified'}
                </small>
              </DashboardKpi>
              <DashboardKpi
                label="Reconciliation"
                value={reconciledWeeks > 0 ? `${health.balanced_weeks} / ${reconciledWeeks} balanced` : 'Not reconciled'}
                tone={reconciledWeeks === 0 ? undefined : health.unbalanced_weeks > 0 ? 'warning' : 'success'}
              >
                <small className="dashboard-delta dashboard-delta-flat">
                  {health.unbalanced_weeks > 0
                    ? `${health.unbalanced_weeks} ${health.unbalanced_weeks === 1 ? 'week needs' : 'weeks need'} review`
                    : `Add. fees: ${currency(meter.additional_fees_total)}`}
                </small>
              </DashboardKpi>
            </div>

            {hasData ? (
              <>
                <div className="dashboard-grid">
                  <DashboardChartCard
                    className="dashboard-card-wide"
                    subtitle="Weekly values derived from month-to-date totals"
                    title="Weekly Sales: Sales Analysis vs Meter Report"
                  >
                    <ResponsiveContainer height={280} width="100%">
                      <BarChart data={weekChartData} margin={{ top: 8, right: 16, bottom: 4, left: 8 }}>
                        <CartesianGrid stroke="var(--border)" strokeDasharray="3 3" vertical={false} />
                        <XAxis axisLine={false} dataKey="name" tick={DASHBOARD_AXIS_TICK} tickLine={false} />
                        <YAxis axisLine={false} tick={DASHBOARD_AXIS_TICK} tickFormatter={compactCurrency} tickLine={false} width={64} />
                        <Tooltip
                          {...DASHBOARD_TOOLTIP_PROPS}
                          formatter={(value) => currency(value)}
                          labelFormatter={(label, payload) => `${label}${payload?.[0]?.payload?.range ? ` (${payload[0].payload.range})` : ''}`}
                        />
                        <Legend wrapperStyle={{ color: 'var(--text-muted)', fontSize: '0.8rem' }} />
                        <Bar dataKey="salesAnalysis" fill="var(--primary)" name="Sales Analysis" radius={[4, 4, 0, 0]} />
                        <Bar dataKey="meterSales" fill="var(--icon)" name="Meter Report" radius={[4, 4, 0, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  </DashboardChartCard>

                  <DashboardChartCard subtitle="Month-to-date Sales Analysis by bucket" title="Sales by Bucket">
                    {sales.buckets.length === 0 ? (
                      <DashboardEmptyChart />
                    ) : (
                      <ResponsiveContainer height={280} width="100%">
                        <PieChart>
                          <Pie
                            data={sales.buckets}
                            dataKey="amount"
                            innerRadius={60}
                            nameKey="label"
                            outerRadius={95}
                            paddingAngle={2}
                            stroke="var(--surface)"
                          >
                            {sales.buckets.map((bucket, index) => (
                              <Cell fill={DASHBOARD_PALETTE[index % DASHBOARD_PALETTE.length]} key={bucket.bucket} />
                            ))}
                          </Pie>
                          <Tooltip {...DASHBOARD_TOOLTIP_PROPS} formatter={(value) => currency(value)} />
                          <Legend wrapperStyle={{ color: 'var(--text-muted)', fontSize: '0.8rem' }} />
                        </PieChart>
                      </ResponsiveContainer>
                    )}
                  </DashboardChartCard>

                  <DashboardChartCard subtitle="Month-to-date net sales" title="Top Meter Models by Sales">
                    <DashboardHorizontalBars
                      data={modelSalesData}
                      dataKey="value"
                      valueFormatter={compactCurrency}
                    />
                  </DashboardChartCard>

                  <DashboardChartCard subtitle="Month-to-date units" title="Top Meter Models by Units">
                    <DashboardHorizontalBars
                      color="var(--icon)"
                      data={modelUnitsData}
                      dataKey="value"
                      valueFormatter={(value) => formatMeterNumber(value)}
                    />
                  </DashboardChartCard>

                  <DashboardChartCard subtitle="Month-to-date Sales Analysis" title="Top Categories">
                    <DashboardHorizontalBars
                      data={categoryData}
                      dataKey="value"
                      labelWidth={190}
                      valueFormatter={compactCurrency}
                    />
                  </DashboardChartCard>

                  <DashboardChartCard subtitle="Units shipped per week" title="Weekly Meter Units">
                    <ResponsiveContainer height={240} width="100%">
                      <AreaChart data={weekChartData} margin={{ top: 8, right: 16, bottom: 4, left: 0 }}>
                        <defs>
                          <linearGradient id="dashboardUnitsFill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="5%" stopColor="var(--primary)" stopOpacity={0.45} />
                            <stop offset="95%" stopColor="var(--primary)" stopOpacity={0.02} />
                          </linearGradient>
                        </defs>
                        <CartesianGrid stroke="var(--border)" strokeDasharray="3 3" vertical={false} />
                        <XAxis axisLine={false} dataKey="name" tick={DASHBOARD_AXIS_TICK} tickLine={false} />
                        <YAxis axisLine={false} tick={DASHBOARD_AXIS_TICK} tickLine={false} width={48} />
                        <Tooltip {...DASHBOARD_TOOLTIP_PROPS} formatter={(value) => [formatMeterNumber(value), 'Units']} />
                        <Area
                          connectNulls
                          dataKey="meterUnits"
                          fill="url(#dashboardUnitsFill)"
                          stroke="var(--primary)"
                          strokeWidth={2}
                          type="monotone"
                        />
                      </AreaChart>
                    </ResponsiveContainer>
                  </DashboardChartCard>
                </div>

                <div className="dashboard-grid">
                  <DashboardChartCard className="dashboard-card-wide" subtitle="Latest upload per week" title="Weekly Reconciliation">
                    <div className="table-wrap">
                      <table className="dashboard-table">
                        <thead>
                          <tr>
                            <th>Week</th>
                            <th className="dashboard-num">Sales Analysis (week)</th>
                            <th className="dashboard-num">Sales Analysis (MTD)</th>
                            <th className="dashboard-num">Meter Sales</th>
                            <th className="dashboard-num">Income Statement</th>
                            <th className="dashboard-num">Difference</th>
                            <th className="dashboard-num">Unmatched</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          {data.weeks.map((week) => (
                            <tr key={week.index}>
                              <td>
                                {week.label} <span className="dashboard-muted">{week.range}</span>
                              </td>
                              <td className="dashboard-num">{week.has_data ? currency(week.sales_analysis_weekly) : '—'}</td>
                              <td className="dashboard-num">{week.has_data ? currency(week.sales_analysis_mtd) : '—'}</td>
                              <td className="dashboard-num">{week.meter_sales === null ? '—' : currency(week.meter_sales)}</td>
                              <td className="dashboard-num">
                                {week.reconciliation ? currency(week.reconciliation.income_statement_total) : '—'}
                              </td>
                              <td className="dashboard-num">
                                {week.reconciliation ? currency(week.reconciliation.difference) : '—'}
                              </td>
                              <td className="dashboard-num">{week.unmatched_rows ?? '—'}</td>
                              <td>
                                {!week.has_data ? (
                                  <span className="dashboard-badge dashboard-badge-muted">No upload</span>
                                ) : !week.reconciliation ? (
                                  <span className="dashboard-badge dashboard-badge-muted">Not reconciled</span>
                                ) : week.reconciliation.is_balanced ? (
                                  <span className="dashboard-badge dashboard-badge-success">Balanced</span>
                                ) : (
                                  <span className="dashboard-badge dashboard-badge-warning">Needs review</span>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </DashboardChartCard>

                  <DashboardChartCard subtitle="Month-to-date Sales Analysis" title="Top Customers">
                    {sales.top_customers.length === 0 ? (
                      <DashboardEmptyChart />
                    ) : (
                      <ol className="dashboard-ranking">
                        {sales.top_customers.map((customer) => (
                          <li key={customer.name}>
                            <span title={customer.name}>{customer.name}</span>
                            <strong>{currency(customer.amount)}</strong>
                          </li>
                        ))}
                      </ol>
                    )}
                    {Object.keys(reportCounts).length > 0 ? (
                      <p className="dashboard-report-counts">
                        Generated reports:{' '}
                        {Object.entries(reportCounts)
                          .map(([status, count]) => `${count} ${status}`)
                          .join(', ')}
                      </p>
                    ) : null}
                  </DashboardChartCard>
                </div>
              </>
            ) : (
              <div className="empty-state dashboard-empty-state">
                <strong>No data for this month</strong>
                <p>Upload a Sales Analysis workbook for a week in {data.title} to see statistics here.</p>
              </div>
            )}
          </div>
        ) : null}
      </article>
    </section>
  );
}

const CALENDAR_REPORT_ROWS = [
  { type: 'sales_analysis', label: 'Sales Analysis' },
  { type: 'weekly_meter_report', label: 'Meter Report' },
];

const CALENDAR_WEEKDAY_LABELS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function buildCalendarCells(viewDate) {
  const year = viewDate.getFullYear();
  const month = viewDate.getMonth();
  const leadingDays = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const cellCount = Math.ceil((leadingDays + daysInMonth) / 7) * 7;

  return Array.from({ length: cellCount }, (_, index) => {
    const date = new Date(year, month, 1 - leadingDays + index);

    return {
      iso: toIsoDateString(date),
      day: date.getDate(),
      inMonth: date.getMonth() === month,
    };
  });
}

// Clicking the month title opens a quick jump: step the year, then pick a month.
function CalendarMonthPicker({ label, viewDate, onSelect }) {
  const containerRef = useRef(null);
  const [isOpen, setIsOpen] = useState(false);
  const [pickerYear, setPickerYear] = useState(viewDate.getFullYear());
  const viewYear = viewDate.getFullYear();
  const viewMonth = viewDate.getMonth();
  const now = new Date();

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    function handlePointerDown(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        setIsOpen(false);
      }
    }

    document.addEventListener('mousedown', handlePointerDown);
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('mousedown', handlePointerDown);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen]);

  function toggleOpen() {
    if (!isOpen) {
      setPickerYear(viewYear);
    }

    setIsOpen((current) => !current);
  }

  return (
    <div className="calendar-month-picker" ref={containerRef}>
      <button
        aria-expanded={isOpen}
        aria-haspopup="dialog"
        className="calendar-month-label calendar-month-trigger"
        title="Jump to a month or year"
        type="button"
        onClick={toggleOpen}
      >
        <span aria-live="polite">{label}</span>
        <ActionIcon name="chevron" />
      </button>
      {isOpen ? (
        <div aria-label="Choose month and year" className="calendar-month-popover" role="dialog">
          <div className="calendar-month-popover-header">
            <button aria-label="Previous year" className="calendar-nav-button" type="button" onClick={() => setPickerYear((current) => current - 1)}>
              ‹
            </button>
            <strong>{pickerYear}</strong>
            <button aria-label="Next year" className="calendar-nav-button" type="button" onClick={() => setPickerYear((current) => current + 1)}>
              ›
            </button>
          </div>
          <div className="calendar-month-options">
            {monthNames.map((name, index) => {
              const isSelected = pickerYear === viewYear && index === viewMonth;
              const isCurrent = pickerYear === now.getFullYear() && index === now.getMonth();

              return (
                <button
                  aria-pressed={isSelected}
                  className={[
                    'calendar-month-option',
                    isSelected ? 'calendar-month-option-selected' : '',
                    isCurrent ? 'calendar-month-option-current' : '',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                  key={name}
                  type="button"
                  onClick={() => {
                    onSelect(pickerYear, index);
                    setIsOpen(false);
                  }}
                >
                  {name.slice(0, 3)}
                </button>
              );
            })}
          </div>
        </div>
      ) : null}
    </div>
  );
}

function calendarWeekReadyReportCount(week) {
  return week.reports.filter((report) => report.status === 'completed').length;
}

function ReportCalendarScreen({ initialMonth, showNotice, onMonthChange, onOpenMeterReport, onOpenSalesAnalysis }) {
  const [viewDate, setViewDate] = useState(() => {
    if (initialMonth) {
      return new Date(initialMonth.year, initialMonth.month, 1);
    }

    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), 1);
  });
  const [weeks, setWeeks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedWeekId, setSelectedWeekId] = useState(null);
  const [hoveredWeekId, setHoveredWeekId] = useState(null);

  const year = viewDate.getFullYear();
  const month = viewDate.getMonth();

  useEffect(() => {
    onMonthChange?.({ year, month });
  }, [year, month, onMonthChange]);

  useEffect(() => {
    let cancelled = false;

    setLoading(true);

    axios
      .get('/api/report-calendar', { params: { year, month: month + 1 } })
      .then((response) => {
        if (!cancelled) {
          setWeeks(response.data.data?.weeks ?? []);
        }
      })
      .catch((error) => {
        if (!cancelled) {
          setWeeks([]);
          showNotice('error', messageFromError(error, 'Unable to load the report calendar.'));
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [year, month, showNotice]);

  const cells = useMemo(() => buildCalendarCells(viewDate), [viewDate]);
  const selectedWeek = weeks.find((week) => week.id === selectedWeekId) ?? null;
  const todayIso = toIsoDateString(new Date());
  const monthLabel = viewDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });

  function shiftMonth(offset) {
    setHoveredWeekId(null);
    setViewDate(new Date(year, month + offset, 1));
  }

  function jumpToMonth(targetYear, targetMonth) {
    setHoveredWeekId(null);
    setViewDate(new Date(targetYear, targetMonth, 1));
  }

  function goToToday() {
    const now = new Date();

    setHoveredWeekId(null);
    setViewDate(new Date(now.getFullYear(), now.getMonth(), 1));
  }

  function weekForDay(iso) {
    return weeks.find((week) => iso >= week.week_start && iso <= week.week_ending) ?? null;
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span panel-legend report-calendar-panel">
        <p className="eyebrow">Calendar</p>
        <div className="section-heading section-heading-toolbar">
          <div>
            <h2>Weekly Reports</h2>
            <p className="calendar-subtitle">Select a highlighted week to see its reports.</p>
          </div>
          <div className="calendar-toolbar">
            <button aria-label="Previous month" className="calendar-nav-button" type="button" onClick={() => shiftMonth(-1)}>
              ‹
            </button>
            <CalendarMonthPicker label={monthLabel} viewDate={viewDate} onSelect={jumpToMonth} />
            <button aria-label="Next month" className="calendar-nav-button" type="button" onClick={() => shiftMonth(1)}>
              ›
            </button>
            <button className="secondary-button" type="button" onClick={goToToday}>
              Today
            </button>
          </div>
        </div>

        <div className="calendar-legend" aria-hidden="true">
          <span className="calendar-legend-item">
            <span className="calendar-legend-swatch calendar-legend-swatch-ready" />
            Reports generated
          </span>
          <span className="calendar-legend-item">
            <span className="calendar-legend-swatch calendar-legend-swatch-pending" />
            Uploaded, no reports yet
          </span>
        </div>

        <div className={loading ? 'calendar calendar-loading' : 'calendar'}>
          <div className="calendar-weekdays">
            {CALENDAR_WEEKDAY_LABELS.map((label) => (
              <span key={label}>{label}</span>
            ))}
          </div>
          <div className="calendar-grid">
            {cells.map((cell, index) => {
              const week = weekForDay(cell.iso);
              const classes = [
                'calendar-day',
                cell.inMonth ? '' : 'calendar-day-outside',
                cell.iso === todayIso ? 'calendar-day-today' : '',
              ];

              if (!week) {
                return (
                  <div className={classes.filter(Boolean).join(' ')} key={cell.iso}>
                    <span className="calendar-day-number">{cell.day}</span>
                  </div>
                );
              }

              const readyCount = calendarWeekReadyReportCount(week);
              const column = index % 7;
              const weekRange = formatWeekRange(week.week_start, week.week_ending);

              classes.push(
                'calendar-day-week',
                readyCount > 0 ? 'calendar-day-week-ready' : 'calendar-day-week-pending',
                cell.iso === week.week_start || column === 0 ? 'calendar-day-week-start' : '',
                cell.iso === week.week_ending || column === 6 ? 'calendar-day-week-end' : '',
                hoveredWeekId === week.id ? 'calendar-day-week-hover' : '',
              );

              return (
                <button
                  aria-label={`${weekRange}, ${readyCount} ${readyCount === 1 ? 'report' : 'reports'} generated`}
                  className={classes.filter(Boolean).join(' ')}
                  key={cell.iso}
                  title={`${weekRange} — ${readyCount > 0 ? 'click to view reports' : 'no reports generated yet'}`}
                  type="button"
                  onBlur={() => setHoveredWeekId(null)}
                  onClick={() => setSelectedWeekId(week.id)}
                  onFocus={() => setHoveredWeekId(week.id)}
                  onMouseEnter={() => setHoveredWeekId(week.id)}
                  onMouseLeave={() => setHoveredWeekId(null)}
                >
                  <span className="calendar-day-number">{cell.day}</span>
                  {cell.iso === week.week_ending ? (
                    <span className="calendar-day-badge">
                      {readyCount > 0 ? `${readyCount} ${readyCount === 1 ? 'report' : 'reports'}` : 'Uploaded'}
                    </span>
                  ) : null}
                </button>
              );
            })}
          </div>
        </div>

        {!loading && weeks.length === 0 ? (
          <p className="calendar-empty">No uploaded workbooks for {monthLabel}.</p>
        ) : null}

        {selectedWeek ? (
          <CalendarWeekModal
            week={selectedWeek}
            onClose={() => setSelectedWeekId(null)}
            onOpenMeterReport={(targetYear, targetMonth) => {
              // "Back to Calendar" should land on the month of the week that was opened.
              onMonthChange?.({ year: targetYear, month: targetMonth - 1 });
              setSelectedWeekId(null);
              onOpenMeterReport(targetYear, targetMonth);
            }}
            onOpenSalesAnalysis={(reportId) => {
              // "Back to Calendar" should land on the month of the week that was opened.
              const weekEnding = parseIsoDate(selectedWeek.week_ending);

              onMonthChange?.({ year: weekEnding.getFullYear(), month: weekEnding.getMonth() });
              setSelectedWeekId(null);
              onOpenSalesAnalysis(reportId);
            }}
          />
        ) : null}
      </article>
    </section>
  );
}

function CalendarWeekModal({ week, onClose, onOpenMeterReport, onOpenSalesAnalysis }) {
  const meterMonthLabel = `${monthNames[week.meter_month - 1]} ${week.meter_year}`;
  const hasAnyReport = calendarWeekReadyReportCount(week) > 0;

  return (
    <Modal className="modal-card-calendar" eyebrow="Week Reports" title={formatWeekRange(week.week_start, week.week_ending)} onClose={onClose}>
      <div className="table-wrap">
        <table className="calendar-reports-table">
          <thead>
            <tr>
              <th>Report</th>
              <th className="calendar-total-column">Total Sales</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            {CALENDAR_REPORT_ROWS.map((row) => {
              const report = week.reports.find((candidate) => candidate.report_type === row.type);
              const isReady = report?.status === 'completed';
              const isMeter = row.type === 'weekly_meter_report';

              return (
                <tr key={row.type}>
                  <td>{row.label}</td>
                  <td className="calendar-total-column">
                    {isReady && report.total !== null && report.total !== undefined
                      ? formatMeterMoney(report.total)
                      : '—'}
                  </td>
                  <td>
                    {isReady ? (
                      <button
                        className="secondary-button"
                        title={isMeter ? `Open the Meter Report for ${meterMonthLabel}` : undefined}
                        type="button"
                        onClick={() =>
                          isMeter
                            ? onOpenMeterReport(week.meter_year, week.meter_month)
                            : onOpenSalesAnalysis(report.id)
                        }
                      >
                        <ButtonContent icon="eye">View</ButtonContent>
                      </button>
                    ) : (
                      <span className="exports-details-unavailable">
                        {report?.status === 'failed' ? 'Failed' : 'Not generated'}
                      </span>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
      {hasAnyReport ? (
        <p className="calendar-modal-note">The Meter Report opens on {meterMonthLabel}.</p>
      ) : (
        <p className="calendar-modal-note">
          No reports have been generated for this week yet. They are generated automatically after the Sales Analysis upload.
        </p>
      )}
    </Modal>
  );
}

function CategoriesScreen({ showNotice }) {
  const emptyForm = {
    name: '',
    sales_analysis_bucket: 'rhp',
    weekly_meter_row_label: '',
    sort_order: 100,
    quantity_multiplier: 1,
    is_active: true,
  };
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [editForm, setEditForm] = useState(emptyForm);
  const [createForm, setCreateForm] = useState(emptyForm);
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isDeleteConfirmOpen, setIsDeleteConfirmOpen] = useState(false);
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [statusFilter, setStatusFilter] = useState('active');
  const [searchQuery, setSearchQuery] = useState('');

  const filteredCategories = useMemo(() => {
    const normalizedQuery = searchQuery.trim().toLowerCase();

    return categories.filter((category) => {
      const matchesStatus =
        statusFilter === 'all' || Boolean(category.is_active) === (statusFilter === 'active');

      if (!matchesStatus) {
        return false;
      }

      if (!normalizedQuery) {
        return true;
      }

      return matchesListSearch(normalizedQuery, [
        category.name,
        category.code,
        category.sales_analysis_bucket,
        category.report_family,
        category.weekly_meter_row_label,
      ]);
    });
  }, [categories, searchQuery, statusFilter]);

  const loadCategories = useCallback(async () => {
    setLoading(true);

    try {
      const response = await axios.get('/api/product-categories');
      const nextCategories = response.data.data ?? [];

      setCategories(nextCategories);
      setSelectedCategory((current) => {
        if (!current) {
          return null;
        }

        return nextCategories.find((category) => category.id === current.id) ?? null;
      });
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load categories.'));
    } finally {
      setLoading(false);
    }
  }, [showNotice]);

  useEffect(() => {
    loadCategories();
  }, [loadCategories]);

  useEffect(() => {
    if (selectedCategory) {
      setEditForm(categoryToForm(selectedCategory));
    }
  }, [selectedCategory]);

  useEffect(() => {
    if (selectedCategory && !filteredCategories.some((category) => category.id === selectedCategory.id)) {
      setSelectedCategory(null);
    }
  }, [filteredCategories, selectedCategory]);

  function categoryPayload(form) {
    return {
      name: form.name,
      sort_order: Number(form.sort_order),
      quantity_multiplier: Math.max(1, Number(form.quantity_multiplier) || 1),
      sales_analysis_bucket: form.sales_analysis_bucket || null,
      weekly_meter_row_label: form.weekly_meter_row_label || null,
      is_active: Boolean(form.is_active),
    };
  }

  async function submitCreateCategory(event) {
    event.preventDefault();
    setCreating(true);

    try {
      const response = await axios.post('/api/product-categories', categoryPayload(createForm));

      showNotice('success', 'Category created.');
      setCreateForm(emptyForm);
      setIsCreateOpen(false);
      await loadCategories();
      setSelectedCategory(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to create category.'));
    } finally {
      setCreating(false);
    }
  }

  async function submitEditCategory(event) {
    event.preventDefault();

    if (!selectedCategory) {
      return;
    }

    setEditing(true);

    try {
      const response = await axios.patch(`/api/product-categories/${selectedCategory.id}`, categoryPayload(editForm));

      showNotice('success', `Category updated.${reportsRefreshedSuffix(response)}`);
      await loadCategories();
      setSelectedCategory(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update category.'));
    } finally {
      setEditing(false);
    }
  }

  async function confirmDeleteCategory() {
    if (!selectedCategory) {
      return;
    }

    setDeleting(true);

    try {
      const response = await axios.delete(`/api/product-categories/${selectedCategory.id}`);
      showNotice('success', `Category deleted.${reportsRefreshedSuffix(response)}`);
      setSelectedCategory(null);
      setIsDeleteConfirmOpen(false);
      await loadCategories();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete category.'));
    } finally {
      setDeleting(false);
    }
  }

  return (
    <>
      {isDeleteConfirmOpen && selectedCategory ? (
        <ConfirmDialog
          confirmLabel="Delete Category"
          isProcessing={deleting}
          message={`Delete category "${selectedCategory.name}"? This action cannot be undone.`}
          title="Delete Category"
          onCancel={() => {
            if (!deleting) {
              setIsDeleteConfirmOpen(false);
            }
          }}
          onConfirm={confirmDeleteCategory}
        />
      ) : null}
    <section className="panel-grid">
      <article className="panel panel-legend panel-transparent">
        <p className="eyebrow">Categories</p>
        <div className="section-heading section-heading-toolbar">
          <div className="button-row">
            <ListSearchInput
              placeholder="Search categories..."
              value={searchQuery}
              onChange={setSearchQuery}
            />
            <label className="status-filter">
              Status
              <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="all">All</option>
              </select>
            </label>
          </div>
          <button className="primary-button" type="button" onClick={() => setIsCreateOpen(true)}>
            <ButtonContent icon="add">Create Category</ButtonContent>
          </button>
        </div>
        {loading ? (
          <p>Loading categories...</p>
        ) : (
          <CategoryTable
            categories={filteredCategories}
            searchQuery={searchQuery}
            selectedCategory={selectedCategory}
            statusFilter={statusFilter}
            onSelectCategory={setSelectedCategory}
          />
        )}
      </article>

      <article className="panel panel-legend">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Details</p>
            <h2>{selectedCategory ? 'Edit Category' : 'Select a Category'}</h2>
          </div>
        </div>

        {selectedCategory ? (
          <CategoryForm
            form={editForm}
            isSubmitting={editing}
            primaryLabel="Update Category"
            setForm={setEditForm}
            onSubmit={submitEditCategory}
          >
            <button className="danger-button" disabled={editing} type="button" onClick={() => setIsDeleteConfirmOpen(true)}>
              <ButtonContent icon="delete">Delete Category</ButtonContent>
            </button>
          </CategoryForm>
        ) : (
          <div className="empty-state">
            <strong>No category selected</strong>
            <p>Click a category from the table on the left to view, edit, or delete it.</p>
          </div>
        )}
      </article>

      {isCreateOpen ? (
        <Modal
          isBusy={creating}
          title="Create Category"
          onClose={() => {
            if (!creating) {
              setIsCreateOpen(false);
            }
          }}
        >
          <CategoryForm
            form={createForm}
            isSubmitting={creating}
            primaryLabel="Create Category"
            setForm={setCreateForm}
            onSubmit={submitCreateCategory}
          />
        </Modal>
      ) : null}
    </section>
    </>
  );
}

function categoryToForm(category) {
  return {
    name: category.name ?? '',
    sales_analysis_bucket: category.sales_analysis_bucket ?? '',
    weekly_meter_row_label: category.weekly_meter_row_label ?? '',
    sort_order: category.sort_order ?? 100,
    quantity_multiplier: category.quantity_multiplier ?? 1,
    is_active: Boolean(category.is_active),
  };
}

function CategoryForm({ children, form, isSubmitting = false, primaryLabel, setForm, onSubmit }) {
  const submitLabel = isSubmitting
    ? (primaryLabel.startsWith('Update') ? 'Updating...' : 'Creating...')
    : primaryLabel;

  return (
    <form className="stacked-form" onSubmit={onSubmit}>
      <fieldset className="form-fieldset" disabled={isSubmitting}>
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
          {salesAnalysisBucketOptions.map((bucket) => (
            <option key={bucket.value} value={bucket.value}>
              {bucket.label}
            </option>
          ))}
        </select>
      </label>
      <label>
        Weekly Meter Model
        <select
          value={form.weekly_meter_row_label}
          onChange={(event) => setForm({ ...form, weekly_meter_row_label: event.target.value })}
        >
          <option value="">None</option>
          {weeklyMeterModelLabels.map((label) => (
            <option key={label} value={label}>
              {label}
            </option>
          ))}
        </select>
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
      <label>
        Qty Multiplier
        <input
          min="1"
          type="number"
          value={form.quantity_multiplier}
          onChange={(event) => setForm({ ...form, quantity_multiplier: event.target.value })}
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
      <div className="form-actions">
        <button className="primary-button" disabled={isSubmitting} type="submit">
          <ButtonContent
            icon={primaryLabel.startsWith('Update') ? 'save' : 'add'}
            loading={isSubmitting}
          >
            {submitLabel}
          </ButtonContent>
        </button>
        {children}
      </div>
      </fieldset>
    </form>
  );
}

const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];
const DEFAULT_PAGE_SIZE = 10;

function matchesListSearch(normalizedQuery, values) {
  return values.some((value) => String(value ?? '').toLowerCase().includes(normalizedQuery));
}

function ListSearchInput({ placeholder, value, onChange }) {
  return (
    <label className="list-search">
      Search
      <input
        placeholder={placeholder}
        type="search"
        value={value}
        onChange={(event) => onChange(event.target.value)}
      />
    </label>
  );
}

function categoryOptionLabel(category, showBucketInLabel = false) {
  if (!showBucketInLabel || !category.sales_analysis_bucket) {
    return category.name;
  }

  return `${category.name} (${salesAnalysisBucketLabel(category.sales_analysis_bucket)})`;
}

function SearchableCategorySelect({
  categories,
  value,
  onChange,
  placeholder = 'Select category',
  disabled = false,
  allowEmpty = false,
  emptyLabel = 'No category',
  showBucketInLabel = false,
}) {
  const containerRef = useRef(null);
  const searchInputRef = useRef(null);
  const [isOpen, setIsOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const selectedCategory = useMemo(
    () => categories.find((category) => String(category.id) === String(value)),
    [categories, value],
  );

  const triggerLabel = useMemo(() => {
    if (!value && allowEmpty) {
      return emptyLabel;
    }

    if (selectedCategory) {
      return categoryOptionLabel(selectedCategory, showBucketInLabel);
    }

    return null;
  }, [allowEmpty, emptyLabel, selectedCategory, showBucketInLabel, value]);

  const filteredCategories = useMemo(() => {
    const normalizedQuery = searchQuery.trim().toLowerCase();

    if (!normalizedQuery) {
      return categories;
    }

    return categories.filter((category) =>
      matchesListSearch(normalizedQuery, [
        category.name,
        category.code,
        category.sales_analysis_bucket,
        category.report_family,
        category.sales_analysis_bucket ? salesAnalysisBucketLabel(category.sales_analysis_bucket) : '',
      ]),
    );
  }, [categories, searchQuery]);

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    const handlePointerDown = (event) => {
      if (!containerRef.current?.contains(event.target)) {
        setIsOpen(false);
        setSearchQuery('');
      }
    };

    const handleEscape = (event) => {
      if (event.key === 'Escape') {
        setIsOpen(false);
        setSearchQuery('');
      }
    };

    document.addEventListener('mousedown', handlePointerDown);
    document.addEventListener('keydown', handleEscape);
    searchInputRef.current?.focus();

    return () => {
      document.removeEventListener('mousedown', handlePointerDown);
      document.removeEventListener('keydown', handleEscape);
    };
  }, [isOpen]);

  function selectCategory(categoryId) {
    onChange(categoryId === '' ? '' : String(categoryId));
    setIsOpen(false);
    setSearchQuery('');
  }

  return (
    <div className="searchable-select-wrap">
      <div className="searchable-select" ref={containerRef}>
        <button
          aria-expanded={isOpen}
          aria-haspopup="listbox"
          className="searchable-select-trigger"
          disabled={disabled}
          type="button"
          onClick={() => {
            if (disabled) {
              return;
            }

            setIsOpen((current) => !current);
            if (isOpen) {
              setSearchQuery('');
            }
          }}
        >
          <span
            className={
              triggerLabel && (value || !allowEmpty)
                ? 'searchable-select-value'
                : 'searchable-select-placeholder'
            }
            title={triggerLabel ?? placeholder}
          >
            {triggerLabel ?? placeholder}
          </span>
          <ActionIcon name="chevron" />
        </button>
        {isOpen ? (
          <div className="searchable-select-menu">
            <div className="searchable-select-search">
              <input
                ref={searchInputRef}
                placeholder="Search categories..."
                type="search"
                value={searchQuery}
                onChange={(event) => setSearchQuery(event.target.value)}
              />
            </div>
            <ul className="searchable-select-options" role="listbox">
              {allowEmpty ? (
                <li role="none">
                  <button
                    className={
                      !value
                        ? 'searchable-select-option searchable-select-option-active searchable-select-option-empty'
                        : 'searchable-select-option searchable-select-option-empty'
                    }
                    role="option"
                    aria-selected={!value}
                    type="button"
                    onClick={() => selectCategory('')}
                  >
                    {emptyLabel}
                  </button>
                </li>
              ) : null}
              {filteredCategories.length === 0 ? (
                <li className="searchable-select-empty">No categories found.</li>
              ) : (
                filteredCategories.map((category) => (
                  <li key={category.id} role="none">
                    <button
                      className={
                        String(category.id) === String(value)
                          ? 'searchable-select-option searchable-select-option-active'
                          : 'searchable-select-option'
                      }
                      role="option"
                      aria-selected={String(category.id) === String(value)}
                      title={categoryOptionLabel(category, showBucketInLabel)}
                      type="button"
                      onClick={() => selectCategory(category.id)}
                    >
                      {categoryOptionLabel(category, showBucketInLabel)}
                    </button>
                  </li>
                ))
              )}
            </ul>
          </div>
        ) : null}
      </div>
      {selectedCategory?.sales_analysis_bucket && !showBucketInLabel ? (
        <span className="searchable-select-bucket">
          {salesAnalysisBucketLabel(selectedCategory.sales_analysis_bucket)}
        </span>
      ) : null}
    </div>
  );
}

function usePagination(items, defaultPageSize = DEFAULT_PAGE_SIZE) {
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(defaultPageSize);
  const totalItems = items.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / perPage));

  useEffect(() => {
    setPage(1);
  }, [perPage, totalItems]);

  useEffect(() => {
    setPage((current) => Math.min(current, totalPages));
  }, [totalPages]);

  const paginatedItems = useMemo(() => {
    const startIndex = (page - 1) * perPage;

    return items.slice(startIndex, startIndex + perPage);
  }, [items, page, perPage]);

  const rangeStart = totalItems === 0 ? 0 : (page - 1) * perPage + 1;
  const rangeEnd = Math.min(page * perPage, totalItems);

  return {
    page,
    setPage,
    perPage,
    setPerPage,
    paginatedItems,
    totalItems,
    totalPages,
    rangeStart,
    rangeEnd,
  };
}

function PaginationControls({ page, setPage, perPage, setPerPage, totalItems, totalPages, rangeStart, rangeEnd }) {
  return (
    <div className="pagination-bar">
      <label className="pagination-per-page">
        Show
        <select value={perPage} onChange={(event) => setPerPage(Number(event.target.value))}>
          {PAGE_SIZE_OPTIONS.map((size) => (
            <option key={size} value={size}>
              {size}
            </option>
          ))}
        </select>
      </label>
      <span className="pagination-summary">
        {totalItems === 0 ? 'No rows' : `Showing ${rangeStart}-${rangeEnd} of ${totalItems}`}
      </span>
      <div className="pagination-actions">
        <button
          className="secondary-button"
          disabled={page <= 1}
          type="button"
          onClick={() => setPage(page - 1)}
        >
          Previous
        </button>
        <span className="pagination-page">
          Page {page} of {totalPages}
        </span>
        <button
          className="secondary-button"
          disabled={page >= totalPages}
          type="button"
          onClick={() => setPage(page + 1)}
        >
          Next
        </button>
      </div>
    </div>
  );
}

function CategoryTable({ categories, selectedCategory, statusFilter, searchQuery, onSelectCategory }) {
  const pagination = usePagination(categories);

  if (categories.length === 0) {
    if (searchQuery.trim()) {
      return <p>No categories match your search.</p>;
    }

    return <p>No {statusFilter === 'all' ? '' : `${statusFilter} `}categories found.</p>;
  }

  return (
    <>
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Bucket</th>
            <th>Meter Model</th>
          </tr>
        </thead>
        <tbody>
          {pagination.paginatedItems.map((category) => (
            <tr
              className={selectedCategory?.id === category.id ? 'clickable-row selected-row' : 'clickable-row'}
              key={category.id}
              onClick={() => onSelectCategory(category)}
            >
              <td>{category.name}</td>
              <td>{category.sales_analysis_bucket ? salesAnalysisBucketLabel(category.sales_analysis_bucket) : 'None'}</td>
              <td>{category.weekly_meter_row_label || '—'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
    <PaginationControls {...pagination} />
    </>
  );
}

function RulesTable({ rules, selectedRule, statusFilter, searchQuery, onSelectRule }) {
  const pagination = usePagination(rules);

  if (rules.length === 0) {
    if (searchQuery.trim()) {
      return <p>No mapping rules match your search.</p>;
    }

    return <p>No {statusFilter === 'all' ? '' : `${statusFilter} `}mapping rules found.</p>;
  }

  return (
    <>
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Match</th>
            <th>Category</th>
            <th>Bucket</th>
          </tr>
        </thead>
        <tbody>
          {pagination.paginatedItems.map((rule) => (
            <tr
              className={selectedRule?.id === rule.id ? 'clickable-row selected-row' : 'clickable-row'}
              key={rule.id}
              onClick={() => onSelectRule(rule)}
            >
              <td>
                {rule.match_field} {rule.match_operator} {rule.pattern}
              </td>
              <td>{rule.product_category?.name ?? 'Unassigned'}</td>
              <td>{salesAnalysisBucketLabel(rule.target_bucket) ?? 'None'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
    <PaginationControls {...pagination} />
    </>
  );
}

function ReconciliationScreen({ batchId, batches, batchesLoading, setBatchId, showNotice }) {
  const [result, setResult] = useState(null);
  const [totals, setTotals] = useState([]);
  const [fees, setFees] = useState([]);
  const [feeForm, setFeeForm] = useState({ marketplace: 'Amazon', amount: '', description: '' });
  const [running, setRunning] = useState(false);
  const [addingFee, setAddingFee] = useState(false);

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
    setRunning(true);

    try {
      const response = await axios.post(`/api/import-batches/${batchId}/reconciliation`, { tolerance: 0.01 });
      setResult(response.data.data.reconciliation);
      setTotals(response.data.data.report_totals ?? []);
      showNotice('success', 'Reconciliation calculated.');
      await loadData();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to run reconciliation.'));
    } finally {
      setRunning(false);
    }
  }

  async function addFee(event) {
    event.preventDefault();
    setAddingFee(true);

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
    } finally {
      setAddingFee(false);
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span panel-legend">
        <p className="eyebrow">Reconciliation</p>
        <div className="section-heading">
          <div>
            <p>Balance Sales Analysis to Income Statement</p>
          </div>
          <button className="primary-button" disabled={!canLoad || running} type="button" onClick={runReconciliation}>
            <ButtonContent icon="run" loading={running}>{running ? 'Running...' : 'Run Reconciliation'}</ButtonContent>
          </button>
        </div>
        {!canLoad ? <p>Select a batch to continue.</p> : null}
        <BatchSelect
          batchId={batchId}
          batches={batches}
          batchesLoading={batchesLoading}
          onChange={setBatchId}
        />
        {result ? <SummaryCards result={result} /> : null}
      </article>

      <article className="panel panel-legend">
        <p className="eyebrow">Fees</p>
        <h2>Marketplace Fees</h2>
        <form className="stacked-form" onSubmit={addFee}>
          <fieldset className="form-fieldset" disabled={addingFee}>
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
          <button className="secondary-button" disabled={!canLoad || addingFee} type="submit">
            <ButtonContent icon="add" loading={addingFee}>{addingFee ? 'Adding...' : 'Add Fee'}</ButtonContent>
          </button>
          </fieldset>
        </form>
        <SimpleList items={fees.map((fee) => `${fee.marketplace}: ${currency(fee.amount)}`)} />
      </article>

      <article className="panel panel-legend">
        <p className="eyebrow">Metrics</p>
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

const howToUseSteps = [
  {
    title: 'Select a week range',
    body: 'Choose the week range for the workbook you are about to upload.',
    image: '/images/1.png',
  },
  {
    title: 'Choose a file to upload',
    body: 'Select the workbook file. For now, Sales Analysis is the only workbook type that works.',
    image: '/images/2.png',
  },
  {
    title: 'Click Upload',
    body: 'Upload the selected Sales Analysis workbook for that week.',
    image: '/images/3.png',
  },
  {
    title: 'Resolve unmatched rows',
    body: 'If unmatched rows appear, resolve them by adding a bucket and category. You can also add a mapping rule — after saving the rule, matching rows are resolved automatically.',
    image: '/images/4.png',
  },
  {
    title: 'Reports are generated automatically',
    body: 'After the Sales Analysis upload has no unmatched rows left (or after you resolve them), reports are generated automatically. Tick Include State under the Sales Analysis file input if you want State included. Open the Calendar and click the week to view them.',
    image: '/images/5.png',
  },
  {
    title: 'Download Sales Analysis',
    body: 'Open the dropdown for that batch and download the Sales Analysis report.',
    image: '/images/6.png',
  },
];

function HowToUseScreen() {
  return (
    <section className="panel-grid">
      <article className="panel panel-span">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Guide</p>
            <h2>How to Use</h2>
          </div>
        </div>

        <ol className="how-to-steps">
          {howToUseSteps.map((step, index) => {
            const stepNumber = index + 1;

            return (
              <li className="how-to-step" key={step.title}>
                <div className="how-to-step-header">
                  <span className="how-to-step-number" aria-hidden="true">
                    {stepNumber}
                  </span>
                  <div>
                    <h3>{step.title}</h3>
                    <p>{step.body}</p>
                  </div>
                </div>
                <div className="how-to-step-media">
                  <img src={step.image} alt={`Step ${stepNumber}: ${step.title}`} />
                </div>
              </li>
            );
          })}
        </ol>
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

const SALES_ANALYSIS_HIDDEN_COLUMNS_KEY = 'salesAnalysisHiddenColumns';
// Sales Rep 1 ID, Country, Bill to State
const SALES_ANALYSIS_DEFAULT_HIDDEN_COLUMNS = ['F', 'G', 'H'];

function loadSalesAnalysisHiddenColumns() {
  try {
    const stored = JSON.parse(localStorage.getItem(SALES_ANALYSIS_HIDDEN_COLUMNS_KEY));

    if (Array.isArray(stored) && stored.every((letter) => typeof letter === 'string')) {
      return stored;
    }
  } catch {
    // Fall back to the defaults when the stored value is missing or malformed.
  }

  return SALES_ANALYSIS_DEFAULT_HIDDEN_COLUMNS;
}

// Letters of the columns that contain at least one value on the sheet.
// Columns that are blank on every row (e.g. the adjusted-quantity column when
// no category uses a multiplier) are never shown.
function salesAnalysisUsedColumnLetters(sheet) {
  const used = new Set();

  sheet.rows.forEach((row) => {
    let columnIndex = 0;

    row.cells.forEach((cell) => {
      if (cell.v !== '' && cell.v != null) {
        used.add(sheet.column_letters[columnIndex]);
      }

      columnIndex += cell.colspan ?? 1;
    });
  });

  return used;
}

function SalesAnalysisColumnPicker({ columns, hiddenColumns, onChange }) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef(null);
  const visibleCount = columns.filter((column) => !hiddenColumns.includes(column.letter)).length;
  const hiddenCount = columns.length - visibleCount;

  useEffect(() => {
    if (!isOpen) {
      return undefined;
    }

    function handlePointerDown(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        setIsOpen(false);
      }
    }

    document.addEventListener('mousedown', handlePointerDown);
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('mousedown', handlePointerDown);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen]);

  function toggleColumn(letter) {
    if (hiddenColumns.includes(letter)) {
      onChange(hiddenColumns.filter((hidden) => hidden !== letter));
    } else if (visibleCount > 1) {
      onChange([...hiddenColumns, letter]);
    }
  }

  return (
    <div className="sa-column-picker" ref={containerRef}>
      <button
        aria-expanded={isOpen}
        aria-haspopup="true"
        className="secondary-button"
        type="button"
        onClick={() => setIsOpen((current) => !current)}
      >
        <ButtonContent icon="eye">{hiddenCount > 0 ? `Columns (${hiddenCount} hidden)` : 'Columns'}</ButtonContent>
      </button>
      {isOpen ? (
        <div className="sa-column-menu" role="group" aria-label="Show or hide columns">
          <div className="sa-column-menu-actions">
            <button className="link-button" type="button" onClick={() => onChange([])}>
              Show all
            </button>
            <button
              className="link-button"
              type="button"
              onClick={() => onChange(SALES_ANALYSIS_DEFAULT_HIDDEN_COLUMNS)}
            >
              Reset to default
            </button>
          </div>
          {columns.map((column) => {
            const isVisible = !hiddenColumns.includes(column.letter);

            return (
              <label className="sa-column-option" key={column.letter}>
                <input
                  checked={isVisible}
                  disabled={isVisible && visibleCount <= 1}
                  type="checkbox"
                  onChange={() => toggleColumn(column.letter)}
                />
                <span>{column.label}</span>
              </label>
            );
          })}
        </div>
      ) : null}
    </div>
  );
}

function SalesAnalysisReportScreen({ reportId, showNotice, onLoaded, onBack }) {
  const [hiddenColumns, setHiddenColumns] = useState(loadSalesAnalysisHiddenColumns);
  const [preview, setPreview] = useState(null);
  const [loading, setLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState('');
  const [activeSheetIndex, setActiveSheetIndex] = useState(0);

  useEffect(() => {
    let cancelled = false;

    setLoading(true);
    setErrorMessage('');
    setPreview(null);
    setActiveSheetIndex(0);

    axios
      .get(`/api/generated-reports/${reportId}/preview`)
      .then((response) => {
        if (!cancelled) {
          setPreview(response.data.data);
          onLoaded?.(response.data.data?.report);
        }
      })
      .catch((error) => {
        if (!cancelled) {
          const message = messageFromError(error, 'Unable to load this Sales Analysis.');

          setErrorMessage(message);
          showNotice('error', message);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [reportId, showNotice, onLoaded]);

  const report = preview?.report;
  const sheets = preview?.sheets ?? [];
  const activeSheet = sheets[activeSheetIndex] ?? sheets[0] ?? null;
  const generatedAt = report?.generated_at ? new Date(report.generated_at).toLocaleString() : null;

  const columns = useMemo(() => {
    if (!activeSheet) {
      return [];
    }

    const usedLetters = salesAnalysisUsedColumnLetters(activeSheet);

    return activeSheet.column_letters
      .map((letter, index) => ({
        letter,
        label: activeSheet.rows[0]?.cells[index]?.v || `Column ${letter}`,
      }))
      .filter((column) => usedLetters.has(column.letter));
  }, [activeSheet]);

  function updateHiddenColumns(nextHiddenColumns) {
    setHiddenColumns(nextHiddenColumns);

    try {
      localStorage.setItem(SALES_ANALYSIS_HIDDEN_COLUMNS_KEY, JSON.stringify(nextHiddenColumns));
    } catch {
      // Preferences simply won't persist if storage is unavailable.
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-span panel-legend">
        <p className="eyebrow">Sales Analysis</p>
        <div className="section-heading">
          <div>
            <h2>
              {report
                ? formatWeekRange(report.import_batch.week_start, report.import_batch.week_ending)
                : 'Sales Analysis'}
            </h2>
            {report ? (
              <p className="sa-report-meta">
                Batch #{report.import_batch.id} · {report.file_name}
                {generatedAt ? ` · Generated ${generatedAt}` : ''}
              </p>
            ) : null}
          </div>
          <div className="button-row">
            <button className="secondary-button" type="button" onClick={onBack}>
              <ButtonContent icon="back">Back to Calendar</ButtonContent>
            </button>
            {report ? (
              <a className="secondary-button" href={`/api/generated-reports/${report.id}/download`}>
                <ButtonContent icon="download">Download</ButtonContent>
              </a>
            ) : null}
          </div>
        </div>

        {loading ? <p>Loading Sales Analysis...</p> : null}
        {!loading && errorMessage ? <p className="sa-report-error">{errorMessage}</p> : null}

        {!loading && activeSheet ? (
          <>
            <div className="sa-toolbar">
              <div className="sa-tabs" role="tablist" aria-label="Sales Analysis sheets">
                {sheets.map((sheet, index) => (
                  <button
                    aria-selected={sheet === activeSheet}
                    className={sheet === activeSheet ? 'sa-tab sa-tab-active' : 'sa-tab'}
                    key={sheet.title}
                    role="tab"
                    type="button"
                    onClick={() => setActiveSheetIndex(index)}
                  >
                    {sheet.title}
                  </button>
                ))}
              </div>
              <SalesAnalysisColumnPicker
                columns={columns}
                hiddenColumns={hiddenColumns}
                onChange={updateHiddenColumns}
              />
            </div>
            <SalesAnalysisSheetTable
              hiddenColumns={hiddenColumns}
              key={activeSheet.title}
              sheet={activeSheet}
            />
          </>
        ) : null}
      </article>
    </section>
  );
}

function SalesAnalysisSheetTable({ sheet, hiddenColumns }) {
  const usedLetters = salesAnalysisUsedColumnLetters(sheet);
  const isColumnHidden = sheet.column_letters.map(
    (letter) => hiddenColumns.includes(letter) || !usedLetters.has(letter),
  );
  const visibleColumnIndexes = sheet.column_letters
    .map((_, index) => index)
    .filter((index) => !isColumnHidden[index]);
  const widthsInPixels = sheet.column_widths.map((width) => Math.round(width * 7 + 5));
  const tableWidth = visibleColumnIndexes.reduce((total, index) => total + widthsInPixels[index], 0);
  const [headerRow, ...bodyRows] = sheet.rows;

  // Cells carry no column index (merged cells are collapsed), so walk each row
  // accumulating colspans. Merged cells shrink to their visible columns and
  // disappear when every column they span is hidden.
  function visibleCells(row) {
    let columnIndex = 0;
    const result = [];

    row.cells.forEach((cell, index) => {
      const span = cell.colspan ?? 1;
      let visibleSpan = 0;

      for (let offset = 0; offset < span; offset += 1) {
        if (!isColumnHidden[columnIndex + offset]) {
          visibleSpan += 1;
        }
      }

      columnIndex += span;

      if (visibleSpan > 0) {
        result.push({ cell, index, colSpan: visibleSpan > 1 ? visibleSpan : undefined });
      }
    });

    return result;
  }

  function renderCell({ cell, index, colSpan }, rowNumber, Tag) {
    const style = sheet.styles[cell.s] ?? {};
    const inlineStyle = { textAlign: style.align ?? 'left' };

    if (style.fill) {
      inlineStyle.backgroundColor = `#${style.fill}`;
      inlineStyle.color = '#000000';
    }

    if (style.bold) {
      inlineStyle.fontWeight = 700;
    }

    if (style.italic) {
      inlineStyle.fontStyle = 'italic';
    }

    if (style.size) {
      inlineStyle.fontSize = `${style.size / 11}em`;
    }

    return (
      <Tag
        colSpan={colSpan}
        key={`${rowNumber}-${index}`}
        rowSpan={cell.rowspan}
        style={inlineStyle}
        title={cell.v || undefined}
      >
        {cell.v}
      </Tag>
    );
  }

  return (
    <div className="sa-sheet-wrap">
      <table className="sa-sheet-table" style={{ width: tableWidth }}>
        <colgroup>
          {visibleColumnIndexes.map((index) => (
            <col key={sheet.column_letters[index]} style={{ width: widthsInPixels[index] }} />
          ))}
        </colgroup>
        {headerRow ? (
          <thead>
            <tr>{visibleCells(headerRow).map((entry) => renderCell(entry, headerRow.n, 'th'))}</tr>
          </thead>
        ) : null}
        <tbody>
          {bodyRows.map((row) => (
            <tr key={row.n}>{visibleCells(row).map((entry) => renderCell(entry, row.n, 'td'))}</tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function SimpleList({ items }) {
  const normalizedItems = useMemo(() => items.filter(Boolean), [items]);
  const pagination = usePagination(normalizedItems);

  if (normalizedItems.length === 0) {
    return <p>No records yet.</p>;
  }

  return (
    <>
      <ul className="simple-list">
        {pagination.paginatedItems.map((item) => (
          <li key={item}>{item}</li>
        ))}
      </ul>
      <PaginationControls {...pagination} />
    </>
  );
}

function batchHasUploads(batch) {
  return (batch.uploaded_file_count ?? batch.uploaded_files?.length ?? 0) > 0;
}

function batchHasImportedSalesAnalysis(batch) {
  return (batch.uploaded_files ?? []).some(
    (file) => file.file_type === 'sales_analysis' && file.status === 'imported',
  );
}

function formatBatchLabel(batch) {
  return `${formatWeekRange(batch.week_start, batch.week_ending)} — Batch #${batch.id} (${batch.status})`;
}

function formatWeekRange(weekStart, weekEnding) {
  if (!weekStart && !weekEnding) {
    return 'Unknown range';
  }

  const end = weekEnding ? new Date(`${weekEnding}T00:00:00`) : null;
  const start = weekStart
    ? new Date(`${weekStart}T00:00:00`)
    : end
      ? new Date(end)
      : null;

  if (!end || !start) {
    return 'Unknown range';
  }

  if (!weekStart) {
    start.setDate(end.getDate() - 6);
  }

  const startMonth = start.toLocaleString('en-US', { month: 'short' });
  const endMonth = end.toLocaleString('en-US', { month: 'short' });
  const startDay = start.getDate();
  const endDay = end.getDate();
  const endYear = end.getFullYear();

  if (start.getFullYear() === endYear && start.getMonth() === end.getMonth()) {
    return `${startMonth} ${startDay} – ${endDay}, ${endYear}`;
  }

  if (start.getFullYear() === endYear) {
    return `${startMonth} ${startDay} – ${endMonth} ${endDay}, ${endYear}`;
  }

  return `${startMonth} ${startDay}, ${start.getFullYear()} – ${endMonth} ${endDay}, ${endYear}`;
}

function importStatusClass(status) {
  if (status === 'imported') {
    return 'import-status-imported';
  }

  if (status === 'pending_reconcile') {
    return 'import-status-pending';
  }

  if (status === 'validation_failed') {
    return 'import-status-failed';
  }

  return 'import-status-missing';
}

function formatImportStatus(status) {
  if (status === 'missing') {
    return 'No File';
  }

  if (status === 'pending_reconcile') {
    return 'pending reconcile';
  }

  return status.replace(/_/g, ' ');
}

function truncateFileName(filename, maxLength = 10) {
  if (!filename) {
    return 'File';
  }

  return filename.length > maxLength ? filename.slice(0, maxLength) : filename;
}

function currency(value) {
  const numberValue = Number(value ?? 0);

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(numberValue);
}

function messageFromError(error, fallback) {
  const data = error?.response?.data;
  const validationErrors = data?.errors;

  if (validationErrors && typeof validationErrors === 'object') {
    const firstError = Object.values(validationErrors)
      .flat()
      .find((message) => typeof message === 'string' && message.trim() !== '');

    if (firstError) {
      return firstError;
    }
  }

  return data?.message ?? fallback;
}

createRoot(document.getElementById('app')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
