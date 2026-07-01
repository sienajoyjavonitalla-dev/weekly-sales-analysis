import axios from 'axios';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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

function ActionIcon({ name }) {
  const paths = {
    add: ['M12 5v14', 'M5 12h14'],
    cancel: ['M6 6l12 12', 'M18 6L6 18'],
    check: ['M5 13l4 4L19 7'],
    delete: ['M5 7h14', 'M10 11v6', 'M14 11v6', 'M8 7l1-3h6l1 3', 'M7 7l1 13h8l1-13'],
    download: ['M12 4v10', 'M8 10l4 4 4-4', 'M5 20h14'],
    edit: ['M5 19l4-1 9-9-3-3-9 9-1 4z', 'M14 6l3 3'],
    export: ['M6 4h9l3 3v13H6z', 'M14 4v4h4', 'M12 11v6', 'M9 14l3 3 3-3'],
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

function ButtonContent({ icon, children }) {
  return (
    <>
      <ActionIcon name={icon} />
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
  { id: 'upload', label: 'Upload', icon: UploadIcon },
  { id: 'rules', label: 'Mapping Rules', icon: RulesIcon },
  { id: 'categories', label: 'Categories', icon: CategoriesIcon },
  { id: 'review', label: 'Review Rows', icon: ReviewIcon },
  { id: 'reconcile', label: 'Reconcile', icon: ReconcileIcon },
  { id: 'exports', label: 'Exports', icon: ExportIcon },
];

const workbookTypes = [
  { key: 'sales_analysis', label: 'Sales Analysis' },
  { key: 'income_statement', label: 'Income Statement' },
  { key: 'total_sales_report', label: 'Total Sales Report' },
  { key: 'weekly_meter_report', label: 'Weekly Meter Report' },
  { key: 'open_orders', label: 'Open Orders' },
  { key: 'ptd_orders', label: 'PTD Orders' },
];

function App() {
  const [activeTab, setActiveTab] = useState('upload');
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

  const loadBatches = useCallback(async () => {
    setBatchesLoading(true);

    try {
      const response = await axios.get('/api/import-batches');
      const nextBatches = response.data.data ?? [];

      setBatches(nextBatches);
      setBatchId((current) => {
        if (current && nextBatches.some((batch) => String(batch.id) === String(current))) {
          return current;
        }

        return nextBatches[0] ? String(nextBatches[0].id) : '';
      });
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to load import batches.'));
    } finally {
      setBatchesLoading(false);
    }
  }, [showNotice]);

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
      return;
    }

    loadBatches();
  }, [user, loadBatches]);

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
            <UploadScreen
              batchId={batchId}
              batches={batches}
              batchesLoading={batchesLoading}
              loadBatches={loadBatches}
              setBatchId={setBatchId}
              showNotice={showNotice}
            />
          ) : null}
          {activeTab === 'rules' ? <MappingRulesScreen showNotice={showNotice} /> : null}
          {activeTab === 'categories' ? <CategoriesScreen showNotice={showNotice} /> : null}
          {activeTab === 'review' ? (
            <ReviewRowsScreen
              batchId={batchId}
              batches={batches}
              batchesLoading={batchesLoading}
              setBatchId={setBatchId}
              showNotice={showNotice}
            />
          ) : null}
          {activeTab === 'reconcile' ? (
            <ReconciliationScreen
              batchId={batchId}
              batches={batches}
              batchesLoading={batchesLoading}
              setBatchId={setBatchId}
              showNotice={showNotice}
            />
          ) : null}
          {activeTab === 'exports' ? (
            <ExportsScreen
              batchId={batchId}
              batches={batches}
              batchesLoading={batchesLoading}
              setBatchId={setBatchId}
              showNotice={showNotice}
            />
          ) : null}
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
            <ButtonContent icon="login">{submitting ? 'Signing in...' : 'Sign in'}</ButtonContent>
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
            <ButtonContent icon="upload">{isSavingPhoto ? 'Uploading...' : 'Update Picture'}</ButtonContent>
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
            <ButtonContent icon="save">{isSavingPassword ? 'Saving...' : 'Change Password'}</ButtonContent>
          </button>
        </form>
      </section>
    </div>
  );
}

function UploadScreen({ batchId, batches, batchesLoading, loadBatches, setBatchId, showNotice }) {
  const [selectedFiles, setSelectedFiles] = useState({});
  const [uploading, setUploading] = useState(false);

  const hasFiles = Object.values(selectedFiles).some(Boolean);

  async function handleUploadClick() {
    if (!hasFiles) {
      showNotice('error', 'Choose at least one workbook before importing.');
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
      showNotice('success', `Imported ${Object.keys(result.summary).length} workbook type(s) into batch #${nextBatchId}.`);
      await loadBatches();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to upload and import the selected files.'));
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
          Choose one file, several files, or the full weekly set. The app creates a new batch automatically unless a
          batch is already selected below.
        </p>
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
            <ButtonContent icon="upload">{uploading ? 'Importing...' : 'Upload / Import Selected Files'}</ButtonContent>
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
          />
        ) : null}
      </article>
    </section>
  );
}

function UploadedBatchesTable({ batches, loadBatches, setBatchId, showNotice }) {
  const [uploadingCell, setUploadingCell] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const fileInputRefs = useRef({});

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
      const nextBatchId = String(response.data.data.import_batch.id);

      setBatchId(nextBatchId);
      showNotice('success', `${fileTypeLabel} imported into batch #${nextBatchId}.`);
      await loadBatches();
    } catch (error) {
      showNotice('error', messageFromError(error, `Unable to upload ${fileTypeLabel}.`));
    } finally {
      setUploadingCell('');
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
            <th>Week Ending</th>
            <th>Status</th>
            {workbookTypes.map((type) => (
              <th key={type.key}>{type.label}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {batches.map((batch) => (
            <tr key={batch.id}>
              <td>#{batch.id}</td>
              <td>{formatBatchDate(batch.week_ending)}</td>
              <td className="batch-status-text">{batch.status}</td>
              {workbookTypes.map((type) => {
                const uploaded = (batch.uploaded_files ?? []).find((file) => file.file_type === type.key);
                const status = uploaded?.status ?? 'missing';
                const cellKey = `${batch.id}-${type.key}`;
                const isUploading = uploadingCell === cellKey;
                const canUpload = status === 'missing' || status === 'validation_failed';
                const canDownload = status === 'imported' && uploaded?.id;

                return (
                  <td key={type.key}>
                    <div className="batch-cell-status">
                      {canDownload ? (
                        <>
                          <a
                            className={`import-status import-status-download ${importStatusClass(status)}`}
                            href={`/api/uploaded-files/${uploaded.id}/download`}
                            title={`Download ${type.label}`}
                          >
                            {formatImportStatus(status)}
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
                            <ActionIcon name="upload" />
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
            </tr>
          ))}
        </tbody>
      </table>
    </div>
    </>
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
  const [statusFilter, setStatusFilter] = useState('active');

  const filteredRules = useMemo(() => {
    if (statusFilter === 'all') {
      return rules;
    }

    return rules.filter((rule) => Boolean(rule.is_active) === (statusFilter === 'active'));
  }, [rules, statusFilter]);

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
      <article className="panel panel-legend panel-transparent">
        <div className="section-heading">
          <div>
            <p className="eyebrow">Rules</p>
            <h2>Mapping Rules</h2>
          </div>
          <div className="button-row">
            <label className="status-filter">
              Status
              <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="all">All</option>
              </select>
            </label>
            <button className="primary-button" type="button" onClick={() => setIsCreateOpen(true)}>
              <ButtonContent icon="add">Create Rule</ButtonContent>
            </button>
          </div>
        </div>
        {loading ? (
          <p>Loading rules...</p>
        ) : (
          <RulesTable
            rules={filteredRules}
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
            primaryLabel="Update Rule"
            setForm={setEditForm}
            onSubmit={submitEditRule}
          >
            <button className="danger-button" type="button" onClick={deleteSelectedRule}>
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
          <ButtonContent icon={primaryLabel.startsWith('Update') ? 'save' : 'check'}>{primaryLabel}</ButtonContent>
        </button>
        {children}
      </div>
    </form>
  );
}

function ConfirmDialog({ title, message, confirmLabel, isProcessing, onConfirm, onCancel }) {
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
            Cancel
          </button>
          <button className="danger-button" disabled={isProcessing} type="button" onClick={onConfirm}>
            <ButtonContent icon="delete">{isProcessing ? 'Deleting...' : confirmLabel}</ButtonContent>
          </button>
        </div>
      </section>
    </div>
  );
}

function Modal({ children, title, onClose }) {
  return (
    <div className="modal-backdrop" role="presentation">
      <section className="modal-card" role="dialog" aria-modal="true" aria-label={title}>
        <button className="modal-close-button" type="button" aria-label="Close" onClick={onClose}>
          <ActionIcon name="cancel" />
        </button>
        <div className="section-heading">
          <div>
            <p className="eyebrow">Create</p>
            <h2>{title}</h2>
          </div>
        </div>
        {children}
      </section>
    </div>
  );
}

function CategoriesScreen({ showNotice }) {
  const emptyForm = {
    name: '',
    sales_analysis_bucket: 'rhp',
    total_sales_row_label: '',
    weekly_meter_row_label: '',
    sort_order: 100,
    is_active: true,
  };
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [editForm, setEditForm] = useState(emptyForm);
  const [createForm, setCreateForm] = useState(emptyForm);
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [statusFilter, setStatusFilter] = useState('active');

  const filteredCategories = useMemo(() => {
    if (statusFilter === 'all') {
      return categories;
    }

    return categories.filter((category) => Boolean(category.is_active) === (statusFilter === 'active'));
  }, [categories, statusFilter]);

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
      ...form,
      sort_order: Number(form.sort_order),
      sales_analysis_bucket: form.sales_analysis_bucket || null,
      total_sales_row_label: form.total_sales_row_label || null,
      weekly_meter_row_label: form.weekly_meter_row_label || null,
      is_active: Boolean(form.is_active),
    };
  }

  async function submitCreateCategory(event) {
    event.preventDefault();

    try {
      const response = await axios.post('/api/product-categories', categoryPayload(createForm));

      showNotice('success', 'Category created.');
      setCreateForm(emptyForm);
      setIsCreateOpen(false);
      await loadCategories();
      setSelectedCategory(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to create category.'));
    }
  }

  async function submitEditCategory(event) {
    event.preventDefault();

    if (!selectedCategory) {
      return;
    }

    try {
      const response = await axios.patch(`/api/product-categories/${selectedCategory.id}`, categoryPayload(editForm));

      showNotice('success', 'Category updated.');
      await loadCategories();
      setSelectedCategory(response.data.data);
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to update category.'));
    }
  }

  async function deleteSelectedCategory() {
    if (!selectedCategory || !window.confirm(`Delete category "${selectedCategory.name}"?`)) {
      return;
    }

    try {
      await axios.delete(`/api/product-categories/${selectedCategory.id}`);
      showNotice('success', 'Category deleted.');
      setSelectedCategory(null);
      await loadCategories();
    } catch (error) {
      showNotice('error', messageFromError(error, 'Unable to delete category.'));
    }
  }

  return (
    <section className="panel-grid">
      <article className="panel panel-legend panel-transparent">
        <div className="section-heading">
          <div>
            <p className="eyebrow">List</p>
            <h2>Categories</h2>
          </div>
          <div className="button-row">
            <label className="status-filter">
              Status
              <select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="all">All</option>
              </select>
            </label>
            <button className="primary-button" type="button" onClick={() => setIsCreateOpen(true)}>
              <ButtonContent icon="add">Create Category</ButtonContent>
            </button>
          </div>
        </div>
        {loading ? (
          <p>Loading categories...</p>
        ) : (
          <CategoryTable
            categories={filteredCategories}
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
          <CategoryForm form={editForm} primaryLabel="Update Category" setForm={setEditForm} onSubmit={submitEditCategory}>
            <button className="danger-button" type="button" onClick={deleteSelectedCategory}>
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
        <Modal title="Create Category" onClose={() => setIsCreateOpen(false)}>
          <CategoryForm
            form={createForm}
            primaryLabel="Create Category"
            setForm={setCreateForm}
            onSubmit={submitCreateCategory}
          />
        </Modal>
      ) : null}
    </section>
  );
}

function categoryToForm(category) {
  return {
    name: category.name ?? '',
    sales_analysis_bucket: category.sales_analysis_bucket ?? '',
    total_sales_row_label: category.total_sales_row_label ?? '',
    weekly_meter_row_label: category.weekly_meter_row_label ?? '',
    sort_order: category.sort_order ?? 100,
    is_active: Boolean(category.is_active),
  };
}

function CategoryForm({ children, form, primaryLabel, setForm, onSubmit }) {
  return (
    <form className="stacked-form" onSubmit={onSubmit}>
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
      <div className="form-row">
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
      </div>
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
      <div className="form-actions">
        <button className="primary-button" type="submit">
          <ButtonContent icon={primaryLabel.startsWith('Update') ? 'save' : 'add'}>{primaryLabel}</ButtonContent>
        </button>
        {children}
      </div>
    </form>
  );
}

function CategoryTable({ categories, selectedCategory, statusFilter, onSelectCategory }) {
  if (categories.length === 0) {
    return <p>No {statusFilter === 'all' ? '' : `${statusFilter} `}categories found.</p>;
  }

  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Bucket</th>
          </tr>
        </thead>
        <tbody>
          {categories.map((category) => (
            <tr
              className={selectedCategory?.id === category.id ? 'clickable-row selected-row' : 'clickable-row'}
              key={category.id}
              onClick={() => onSelectCategory(category)}
            >
              <td>{category.name}</td>
              <td>{category.sales_analysis_bucket ?? 'None'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RulesTable({ rules, selectedRule, statusFilter, onSelectRule }) {
  if (rules.length === 0) {
    return <p>No {statusFilter === 'all' ? '' : `${statusFilter} `}mapping rules found.</p>;
  }

  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Match</th>
            <th>Category</th>
          </tr>
        </thead>
        <tbody>
          {rules.map((rule) => (
            <tr
              className={selectedRule?.id === rule.id ? 'clickable-row selected-row' : 'clickable-row'}
              key={rule.id}
              onClick={() => onSelectRule(rule)}
            >
              <td>
                {rule.match_field} {rule.match_operator} {rule.pattern}
              </td>
              <td>{rule.product_category?.name ?? 'Unassigned'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function ReviewRowsScreen({ batchId, batches, batchesLoading, setBatchId, showNotice }) {
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
        <BatchSelect
          batchId={batchId}
          batches={batches}
          batchesLoading={batchesLoading}
          onChange={setBatchId}
        />
        <div className="section-heading">
          <div>
            <p className="eyebrow">Review</p>
            <h2>Unmatched Sales Rows</h2>
          </div>
          <div className="button-row">
            <button className="secondary-button" disabled={!canLoad} type="button" onClick={loadRows}>
              <ButtonContent icon="refresh">Refresh</ButtonContent>
            </button>
            <button className="primary-button" disabled={!canLoad} type="button" onClick={classifyBatch}>
              <ButtonContent icon="run">Run Rules</ButtonContent>
            </button>
          </div>
        </div>
        {!canLoad ? <p>Select a batch to continue.</p> : null}
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
                  <ButtonContent icon="resolve">Resolve</ButtonContent>
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function ReconciliationScreen({ batchId, batches, batchesLoading, setBatchId, showNotice }) {
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
        <BatchSelect
          batchId={batchId}
          batches={batches}
          batchesLoading={batchesLoading}
          onChange={setBatchId}
        />
        <div className="section-heading">
          <div>
            <p className="eyebrow">Reconciliation</p>
            <h2>Balance Sales Analysis to Income Statement</h2>
          </div>
          <button className="primary-button" disabled={!canLoad} type="button" onClick={runReconciliation}>
            <ButtonContent icon="run">Run Reconciliation</ButtonContent>
          </button>
        </div>
        {!canLoad ? <p>Select a batch to continue.</p> : null}
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
            <ButtonContent icon="add">Add Fee</ButtonContent>
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

function ExportsScreen({ batchId, batches, batchesLoading, setBatchId, showNotice }) {
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
        <BatchSelect
          batchId={batchId}
          batches={batches}
          batchesLoading={batchesLoading}
          onChange={setBatchId}
        />
        <div className="section-heading">
          <div>
            <p className="eyebrow">Exports</p>
            <h2>Generated Excel Reports</h2>
          </div>
          <div className="button-row">
            <button className="secondary-button" disabled={!canLoad} type="button" onClick={loadReports}>
              <ButtonContent icon="refresh">Refresh</ButtonContent>
            </button>
            <button className="primary-button" disabled={!canLoad} type="button" onClick={generateReports}>
              <ButtonContent icon="export">Generate Reports</ButtonContent>
            </button>
          </div>
        </div>
        {!canLoad ? <p>Select a batch to continue.</p> : null}
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
                    <ButtonContent icon="download">Download</ButtonContent>
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

function formatBatchDate(value) {
  if (!value) {
    return 'Unknown date';
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(`${value}T00:00:00`));
}

function formatBatchLabel(batch) {
  return `${formatBatchDate(batch.week_ending)} — Batch #${batch.id} (${batch.status})`;
}

function importStatusClass(status) {
  if (status === 'imported') {
    return 'import-status-imported';
  }

  if (status === 'validation_failed') {
    return 'import-status-failed';
  }

  return 'import-status-missing';
}

function formatImportStatus(status) {
  if (status === 'missing') {
    return 'Not uploaded';
  }

  return status.replace(/_/g, ' ');
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
