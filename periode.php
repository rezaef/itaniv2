<?php
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
    <title>ITani – Periode Tanam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <!-- Optional: style lama, kalau ada class yang masih kepakai -->
    <link rel="stylesheet" href="css/style.css" />

    <style>
    .card-soft {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
    }

    .card-soft .card-header {
        background: transparent;
        border-bottom: none;
        padding-bottom: 0;
    }

    .section-title {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .section-subtitle {
        font-size: 0.85rem;
        color: #6b7280;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-planning {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-running {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-done {
        background-color: #e5e7eb;
        color: #374151;
    }

    .status-fail {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    .status-active-chip {
        font-size: 0.7rem;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        background-color: #0f766e;
        color: #ecfeff;
        margin-left: 0.4rem;
    }

    .table-periods tbody tr:hover {
        background-color: #f9fafb;
    }

    .btn-soft {
        border-radius: 999px;
        font-size: 0.8rem;
        padding-inline: 0.9rem;
    }

    .pill-filter .btn {
        border-radius: 999px;
        font-size: 0.8rem;
    }

    @media (max-width: 991.98px) {
        .card-soft {
            border-radius: 12px;
        }
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
</head>
<script>
function escapeHtml(str) {
    return str ?
        str.replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;") :
        "";
}

function badgeStatus(status) {
    switch (status) {
        case "berjalan":
            return '<span class="status-badge status-running">Berjalan</span>';
        case "selesai":
            return '<span class="status-badge status-done">Selesai</span>';
        case "gagal":
            return '<span class="status-badge status-fail">Gagal</span>';
        default:
            return '<span class="status-badge status-planning">Perencanaan</span>';
    }
}

function loadPeriods() {
    fetch("api/periods.php")
        .then((r) => r.json())
        .then((resp) => {
            if (!resp.success) {
                console.warn("Gagal load periode:", resp.error);
                return;
            }
            const tbody = document.getElementById("periode-tbody");
            tbody.innerHTML = "";

            if (!resp.data || resp.data.length === 0) {
                const tr = document.createElement("tr");
                tr.innerHTML = '<td colspan="5" class="text-center text-muted py-3">Belum ada periode tanam.</td>';
                tbody.appendChild(tr);
                return;
            }

            resp.data.forEach((p) => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
            <td>${escapeHtml(p.nama_periode)}</td>
            <td>${p.tanggal_mulai || "-"}</td>
            <td>${p.tanggal_selesai || "-"}</td>
            <td>${badgeStatus(p.status)}</td>
            <td class="text-end">
            <button class="btn btn-sm btn-outline-success me-1"
                onclick="setActivePeriode(${p.id})">
               Jadikan Aktif
              </button>
              <button class="btn btn-sm btn-outline-primary me-1"
                      data-id="${p.id}"
                      onclick="editPeriode(${p.id}, '${escapeHtml(p.nama_periode)}', '${p.tanggal_mulai}', '${p.tanggal_selesai || ""}', '${p.status}')">
                Edit
              </button>
              <button class="btn btn-sm btn-outline-danger"
                      onclick="deletePeriode(${p.id})">
                Hapus
              </button>
            </td>
          `;
                tbody.appendChild(tr);
            });
        })
        .catch((err) => console.error("Error fetch periods:", err));
}

function setActivePeriode(id) {
    if (!confirm("Jadikan periode ini sebagai periode tanam aktif?")) return;

    fetch("api/periods.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "set_active",
                id
            }),
        })
        .then((r) => r.json())
        .then((resp) => {
            if (!resp.success) {
                alert("Gagal mengatur periode aktif: " + (resp.error || "unknown"));
                return;
            }
            loadPeriods();
        })
        .catch((err) => alert("Error: " + err));
}

function deletePeriode(id) {
    if (!confirm("Hapus periode ini?")) return;

    fetch("api/periods.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "delete",
                id
            }),
        })
        .then((r) => r.json())
        .then((resp) => {
            if (!resp.success) {
                alert("Gagal hapus: " + (resp.error || "unknown"));
                return;
            }
            loadPeriods();
        })
        .catch((err) => alert("Error: " + err));
}

// Mode edit sederhana: isi kembali form
let editingId = null;

function editPeriode(id, nama, mulai, selesai, status) {
    editingId = id;

    const form = document.getElementById("form-periode");
    form.nama_periode.value = nama;
    form.tanggal_mulai.value = mulai;
    form.tanggal_selesai.value = selesai || "";
    // kalau mau, bisa tambahkan select status di form

    form.querySelector("button[type=submit]").textContent = "Update";
}

document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("form-periode");

    form.addEventListener("submit", function(e) {
        e.preventDefault();

        const payload = {
            action: editingId ? "update" : "create",
            id: editingId,
            nama_periode: form.nama_periode.value,
            tanggal_mulai: form.tanggal_mulai.value,
            tanggal_selesai: form.tanggal_selesai.value || null,
            // deskripsi dan status bisa ditambah nanti
        };

        fetch("api/periods.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload),
            })
            .then((r) => r.json())
            .then((resp) => {
                if (!resp.success) {
                    alert("Gagal simpan: " + (resp.error || "unknown"));
                    return;
                }
                form.reset();
                form.querySelector("button[type=submit]").textContent = "Simpan";
                editingId = null;
                loadPeriods();
            })
            .catch((err) => alert("Error: " + err));
    });

    loadPeriods();
});
</script>
<!-- Bootstrap 5 JS (WAJIB untuk hamburger berfungsi) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<body>
    <!-- TOP NAV -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand" href="index.html">ITani</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="periode.php">Periode Tanam</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stok.php">Stok Bibit &amp; Pupuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Kelola User</a>
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
        <div class="container-fluid py-4">
            <div class="row g-4">
                <!-- KIRI: Form + Tabel Periode -->
                <div class="col-lg-8">
                    <!-- Card: Tambah Periode Tanam -->
                    <div class="card card-soft mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="section-title mb-1">Periode Tanam</h5>
                                <p class="section-subtitle mb-0">
                                    Catat jadwal tanam, prediksi panen, dan kelola periode aktif.
                                </p>
                            </div>
                            <span class="badge bg-emerald-100 text-emerald-700 d-none">
                                API: <code>api/periods.php</code>
                            </span>
                        </div>
                        <div class="card-body pt-3">
                            <form id="form-periode" class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Nama Tanaman / Periode</label>
                                    <input type="text" name="nama_periode" class="form-control"
                                        placeholder="Misal: Okra Merah Bedeng 1" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" class="form-control" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Perkiraan Panen</label>
                                    <input type="date" name="tanggal_selesai" class="form-control" />
                                </div>
                                <div class="col-md-1 d-grid">
                                    <button type="submit" class="btn btn-success">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card: Daftar Periode Tanam -->
                    <div class="card card-soft">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="section-title mb-1">Daftar Periode Tanam</h6>
                                <p class="section-subtitle mb-0">
                                    Lihat seluruh periode yang pernah dibuat dan atur periode aktif.
                                </p>
                            </div>
                            <div class="pill-filter btn-group" role="group" aria-label="Filter status">
                                <button type="button" class="btn btn-outline-secondary btn-sm active">
                                    Semua
                                </button>
                                <!-- (nanti kalau mau, filter status bisa diaktifkan di JS) -->
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle table-periods mb-0">
                                    <thead>
                                        <tr class="text-muted small">
                                            <th>Tanaman / Periode</th>
                                            <th class="text-center">Mulai</th>
                                            <th class="text-center">Perkiraan Panen</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="periode-tbody">
                                        <!-- diisi lewat JS loadPeriods() -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KANAN: Ringkasan Musim Tanam & (opsional) Panen -->
                <div class="col-lg-4">
                    <!-- Ringkasan Musim Tanam -->
                    <div class="card card-soft mb-4">
                        <div class="card-body">
                            <h6 class="section-title mb-1">Ringkasan Musim Tanam</h6>
                            <p class="section-subtitle mb-3">
                                Gambaran singkat status periode tanam yang sedang berjalan.
                            </p>

                            <?php
          // ringkasan sederhana dari tabel periods
          $userId = $_SESSION['user']['id'] ?? null;
          $ringkasan = [
            'total'    => 0,
            'aktif'    => 0,
            'selesai'  => 0,
            'gagal'    => 0,
          ];

          if ($userId) {
              $stmt = $pdo->prepare("
                  SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'berjalan' THEN 1 ELSE 0 END) AS aktif,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
                    SUM(CASE WHEN status = 'gagal' THEN 1 ELSE 0 END) AS gagal
                  FROM periods
                  WHERE user_id = :uid
              ");
              $stmt->execute([':uid' => $userId]);
              $row = $stmt->fetch();
              if ($row) {
                  $ringkasan['total']   = (int)$row['total'];
                  $ringkasan['aktif']   = (int)$row['aktif'];
                  $ringkasan['selesai'] = (int)$row['selesai'];
                  $ringkasan['gagal']   = (int)$row['gagal'];
              }
          }
          ?>

                            <div class="d-flex justify-content-between mb-3">
                                <div>
                                    <div class="text-muted small">Total periode</div>
                                    <div class="fs-5 fw-semibold"><?= $ringkasan['total'] ?></div>
                                </div>
                                <div class="text-center">
                                    <div class="text-muted small">Berjalan</div>
                                    <div class="fs-5 fw-semibold text-success"><?= $ringkasan['aktif'] ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Selesai</div>
                                    <div class="fs-5 fw-semibold"><?= $ringkasan['selesai'] ?></div>
                                </div>
                            </div>

                            <?php if ($ringkasan['gagal'] > 0): ?>
                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                <strong><?= $ringkasan['gagal'] ?></strong> periode tercatat gagal.
                                Pertimbangkan evaluasi pola tanam dan penyiraman.
                            </div>
                            <?php endif; ?>

                            <p class="small text-muted mb-0">
                                Data ini otomatis terupdate setiap kali Anda membuat, mengubah,
                                atau menyelesaikan sebuah periode tanam.
                            </p>
                        </div>
                    </div>

                    <!-- (Opsional) Card Hasil Panen masih boleh statis untuk saat ini -->
                    <div class="card card-soft">
                        <div class="card-body">
                            <h6 class="section-title mb-1">Catatan Hasil Panen</h6>
                            <p class="section-subtitle mb-2">
                                Rekap singkat hasil panen bisa dikelola di tahap selanjutnya.
                            </p>
                            <p class="small text-muted mb-0">
                                Untuk saat ini tabel panen masih bersifat dummy.
                                Nanti bisa dihubungkan ke <code>api/harvests.php</code> jika
                                fitur pencatatan panen sudah diaktifkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>