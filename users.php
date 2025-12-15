<?php
// users.php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: login.html');
  exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>ITani – Kelola User</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Bootstrap 5 -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />

  <link rel="stylesheet" href="css/style.css" />

  <style>
    body {
      background-color: #f3f4f6;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
        sans-serif;
    }

    .navbar-brand {
      font-weight: 700;
      letter-spacing: 0.03em;
    }

    .page-wrapper {
      padding: 1.5rem 0 2rem;
    }

    .card-soft {
      border-radius: 1rem;
      border: none;
      box-shadow: 0 12px 25px rgba(15, 23, 42, 0.06);
      background-color: #ffffff;
      padding: 1.25rem 1.5rem;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 0.75rem;
    }

    .section-title {
      font-size: 1.05rem;
      font-weight: 600;
    }

    .section-subtitle {
      font-size: 0.85rem;
      color: #6b7280;
    }

    .table-soft thead {
      background-color: #f9fafb;
      font-size: 0.8rem;
      color: #6b7280;
    }

    .table-soft th,
    .table-soft td {
      vertical-align: middle;
      font-size: 0.9rem;
    }

    .role-badge {
      border-radius: 999px;
      padding: 0.15rem 0.6rem;
      font-size: 0.7rem;
      font-weight: 500;
    }

    .role-admin {
      background-color: #fee2e2;
      color: #b91c1c;
    }

    .role-operator {
      background-color: #dbeafe;
      color: #1d4ed8;
    }

    .role-viewer {
      background-color: #dcfce7;
      color: #166534;
    }

    .avatar-circle {
      width: 34px;
      height: 34px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }
  </style>

  <script>
    let editUserModal = null;
    let editUserId = null;

    async function loadUsers() {
      try {
        const res = await fetch('api/users.php');
        const data = await res.json();

        const tbody = document.querySelector('#usersTable tbody');
        tbody.innerHTML = '';

        data.forEach(user => {
          const roleClass =
            user.role === 'Admin' ? 'role-admin' :
            user.role === 'Operator' ? 'role-operator' :
            'role-viewer';

          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>
              <div class="fw-semibold">${user.name}</div>
              <div class="small text-muted">@${user.username}</div>
            </td>
            <td>
              <span class="role-badge ${roleClass}">${user.role}</span>
            </td>
            <td class="small text-muted">${user.created_at ?? '-'}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary me-1 btn-edit-user">Edit</button>
              <button class="btn btn-sm btn-outline-danger">Hapus</button>
            </td>
          `;
          tbody.appendChild(tr);

          // tombol Edit
          const editBtn = tr.querySelector('.btn-edit-user');
          editBtn.addEventListener('click', () => openEditUser(user));

          // tombol Hapus
          const deleteBtn = tr.querySelector('.btn-outline-danger');
          deleteBtn.addEventListener('click', () => deleteUser(user.id));
        });
      } catch (err) {
        console.error('Gagal load users:', err);
        alert('Gagal memuat daftar user (cek console).');
      }
    }

    async function deleteUser(id) {
      if (!confirm('Yakin ingin menghapus user ini?')) return;

      try {
        const res = await fetch('api/users.php?id=' + id, {
          method: 'DELETE'
        });
        const data = await res.json();
        if (!res.ok || data.error) {
          alert(data.error || 'Gagal menghapus user');
          return;
        }
        loadUsers();
      } catch (err) {
        console.error('Gagal hapus user:', err);
        alert('Gagal hapus user (cek console).');
      }
    }

    function setupAddUserForm() {
      const form = document.querySelector('#addUserForm');
      if (!form) return;

      form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name     = form.querySelector('input[name="name"]').value.trim();
        const username = form.querySelector('input[name="username"]').value.trim();
        const role     = form.querySelector('select[name="role"]').value;

        if (!name || !username || !role) {
          alert('Lengkapi semua field terlebih dahulu.');
          return;
        }

        try {
          const res = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, username, role })
          });

          const data = await res.json();
          if (!res.ok || data.error) {
            alert(data.error || 'Gagal menyimpan user');
            return;
          }

          form.reset();
          loadUsers();
        } catch (err) {
          console.error('Gagal simpan user:', err);
          alert('Gagal menyimpan user (cek console).');
        }
      });
    }

    function openEditUser(user) {
      editUserId = user.id;

      document.getElementById('editName').value     = user.name;
      document.getElementById('editUsername').value = user.username;
      document.getElementById('editRole').value     = user.role;
      document.getElementById('editPassword').value = '';
      document.getElementById('editPasswordConfirm').value = '';

      editUserModal.show();
    }

    function setupEditUserForm() {
      const form = document.getElementById('editUserForm');
      if (!form) return;

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!editUserId) return;

        const name     = document.getElementById('editName').value.trim();
        const username = document.getElementById('editUsername').value.trim();
        const role     = document.getElementById('editRole').value;
        const password = document.getElementById('editPassword').value.trim();
        const password2 = document.getElementById('editPasswordConfirm').value.trim();

        if (!name || !username || !role) {
          alert('Nama, username, dan peran wajib diisi.');
          return;
        }

        if (password !== '' && password !== password2) {
          alert('Konfirmasi password tidak sama.');
          return;
        }

        const payload = { name, username, role };
        if (password !== '') {
          payload.password = password;
        }

        try {
          const res = await fetch('api/users.php?id=' + editUserId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });

          const data = await res.json();
          if (!res.ok || data.error) {
            alert(data.error || 'Gagal mengupdate user');
            return;
          }

          editUserModal.hide();
          loadUsers();
        } catch (err) {
          console.error('Gagal update user:', err);
          alert('Gagal mengupdate user (cek console).');
        }
      });
    }

    document.addEventListener('DOMContentLoaded', () => {
      setupAddUserForm();
      setupEditUserForm();
      loadUsers();

      // inisialisasi modal setelah Bootstrap JS siap
      const modalEl = document.getElementById('editUserModal');
      if (modalEl) {
        editUserModal = new bootstrap.Modal(modalEl);
      }
    });
  </script>
</head>

<body>
  <!-- TOP NAV -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid px-3 px-md-4">
      <a class="navbar-brand" href="index.php">ITani</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="periode.php">Periode Tanam</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="stok.php">Stok Bibit &amp; Pupuk</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="users.php">Kelola User</a>
          </li>
        </ul>

        <div class="d-flex align-items-center text-white">
          <div class="text-end me-3 d-none d-md-block">
            <div style="font-size: 0.8rem; opacity: 0.8;">
              <?= htmlspecialchars($user['name'] ?? 'Admin') ?>
            </div>
            <small class="text-white-50">@<?= htmlspecialchars($user['username'] ?? 'admin') ?></small>
          </div>
          <div class="avatar-circle">
            <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- CONTENT -->
  <main class="page-wrapper">
    <div class="container-fluid px-3 px-md-4">
      <div class="mb-3">
        <h3 class="mb-1">Kelola User</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
          Tambahkan akun admin/operator/viewer untuk mengakses dashboard ITani.
        </p>
      </div>

      <div class="row g-3">
        <!-- Form tambah user -->
        <div class="col-lg-4">
          <div class="card-soft mb-3">
            <div class="section-header">
              <div>
                <div class="section-title">Tambah User Baru</div>
                <div class="section-subtitle">
                  Password default: <code>password123</code>.
                </div>
              </div>
            </div>

            <form id="addUserForm" class="row g-3">
              <div class="col-12">
                <label class="form-label">Nama Lengkap</label>
                <input
                  type="text"
                  name="name"
                  class="form-control"
                  placeholder="Nama user..."
                  required
                />
              </div>
              <div class="col-12">
                <label class="form-label">Username</label>
                <input
                  type="text"
                  name="username"
                  class="form-control"
                  placeholder="user123"
                  required
                />
              </div>
              <div class="col-12">
                <label class="form-label">Peran</label>
                <select class="form-select" name="role" required>
                  <option value="">Pilih peran...</option>
                  <option>Admin</option>
                  <option>Operator</option>
                  <option>Viewer</option>
                </select>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-success w-100">
                  Simpan User
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel user -->
        <div class="col-lg-8">
          <div class="card-soft">
            <div class="section-header">
              <div>
                <div class="section-title">Daftar User</div>
                <div class="section-subtitle">
                  Data berasal dari tabel <code>users</code> di database.
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table id="usersTable" class="table table-soft align-middle">
                <thead>
                  <tr>
                    <th>Identitas</th>
                    <th>Peran</th>
                    <th>Dibuat</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- diisi loadUsers() -->
                </tbody>
              </table>
            </div>

            <p class="small text-muted mb-0">
              Klik <strong>Edit</strong> untuk mengubah data user atau mengganti password.
              Biarkan field password kosong jika tidak ingin diubah.
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- MODAL EDIT USER -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="editUserForm">
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" id="editName" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" id="editUsername" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Peran</label>
              <select id="editRole" class="form-select" required>
                <option>Admin</option>
                <option>Operator</option>
                <option>Viewer</option>
              </select>
            </div>
            <hr>
            <div class="mb-2">
              <label class="form-label">Password Baru (opsional)</label>
              <input type="password" id="editPassword" class="form-control"
                     placeholder="Isi jika ingin mengganti password">
              <small class="text-muted">Biarkan kosong jika password tidak ingin diubah.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Konfirmasi Password Baru</label>
              <input type="password" id="editPasswordConfirm" class="form-control"
                     placeholder="Ulangi password baru">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
