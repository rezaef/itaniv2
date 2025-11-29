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
    <title>ITani Dashboard – Monitoring Okra Merah</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="css/style.css" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Paho MQTT (versi yang expose Paho.MQTT.Client) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js" type="text/javascript">
    </script>

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

    .metric-card {
        text-align: left;
    }

    .metric-label {
        font-size: 0.8rem;
        color: #6b7280;
    }

    .metric-value {
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 0.25rem;
    }

    .metric-sub {
        font-size: 0.85rem;
        color: #10b981;
        font-weight: 500;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .section-title {
        font-size: 1.05rem;
        font-weight: 600;
    }

    .badge-pill {
        border-radius: 999px;
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .log-container {
        max-height: 260px;
        overflow-y: auto;
    }

    .log-item-time {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .log-badge {
        font-size: 0.7rem;
        border-radius: 999px;
        padding: 0.1rem 0.5rem;
    }

    .log-badge.manual {
        background-color: #dbeafe;
        color: #1d4ed8;
    }

    .log-badge.otomatis {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
        margin-right: 6px;
    }

    .status-dot.connected {
        background-color: #22c55e;
    }

    .status-dot.disconnected {
        background-color: #ef4444;
    }

    @media (max-width: 767.98px) {
        .metric-value {
            font-size: 1.8rem;
        }
    }
    </style>
</head>

<body>
    <!-- TOP NAV -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand" href="#">ITani</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="periode.php">Periode Tanam</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stok.php">Stok Bibit &amp; Pupuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Kelola User</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div style="font-size: 0.8rem; color: #d1fae5;">MQTT</div>
                        <span class="badge-pill bg-light text-dark" id="mqttStatus">
                            <span class="status-dot disconnected"></span>
                            Disconnected
                        </span>
                    </div>
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
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div class="container-fluid page-wrapper px-3 px-md-4">
        <div class="row g-3 mb-3">
            <div class="col-12">
                <h4 class="mb-1">Dashboard Monitoring Okra Merah</h4>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">
                    Pantau kondisi tanah, kontrol pompa, dan lihat riwayat penyiraman dalam satu halaman.
                </p>
            </div>
        </div>

        <div class="row g-3">
            <!-- KOLOM KIRI: METRIC + GRAFIK -->
            <div class="col-lg-8">
                <!-- Metrics -->
                <div class="card-soft mb-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-label mb-1">pH Tanah</div>
                                <div class="metric-value" id="phNumber">–</div>
                                <div class="metric-sub" id="phLabel">Menunggu data...</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-label mb-1">Kelembapan Tanah (%)</div>
                                <div class="metric-value" id="humiNumber">–</div>
                                <div class="metric-sub" id="humiLabel">Menunggu data...</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-label mb-1">Suhu Tanah (°C)</div>
                                <div class="metric-value" id="tempNumber">–</div>
                                <div class="metric-sub" id="tempLabel">Menunggu data...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik -->
                <div class="card-soft">
                    <div class="section-header">
                        <div class="section-title">Grafik Perubahan Data Sensor</div>
                        <small class="text-muted" id="lastUpdate">Last update: –</small>
                    </div>
                    <canvas id="sensorChart" height="130"></canvas>
                </div>
            </div>

            <!-- KOLOM KANAN: KONTROL + RIWAYAT -->
            <div class="col-lg-4">
                <div class="card-soft mb-3">
                    <div class="section-header mb-2">
                        <div class="section-title mb-0">Kontrol Penyiraman</div>
                        <span id="pumpStatus" class="badge-pill bg-danger-subtle text-danger fw-semibold">
                            Pompa: UNKNOWN
                        </span>
                    </div>

                    <button class="btn btn-success w-100 mb-3" id="btnTogglePump">
                        Nyalakan Pompa
                    </button>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold" style="font-size: 0.9rem;">
                                Mode Otomatis
                            </div>
                            <small class="text-muted" style="font-size: 0.8rem;">
                                ESP akan mengatur pompa berdasarkan kelembapan tanah.
                            </small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoMode" checked />
                        </div>
                    </div>

                    <!-- <div style="font-size: 0.8rem;" class="text-muted">
            Catatan: perintah dari dashboard dicatat sebagai penyiraman <b>manual</b>.
            Penyiraman otomatis dari ESP bisa dicatat lewat subscriber MQTT terpisah.
          </div> -->
                </div>

                <div class="card-soft">
                    <div class="section-header mb-2">
                        <div class="section-title mb-0">Riwayat Penyiraman</div>
                        <button class="btn btn-sm btn-outline-secondary" style="font-size: 0.75rem;"
                            onclick="loadWateringLogs()">
                            Refresh
                        </button>
                    </div>
                    <div class="log-container list-group small" id="logList">
                        <!-- diisi lewat JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // =============================
    //  KONFIGURASI MQTT (RabbitMQ)
    // =============================
    // GANTI sesuai broker kamu
    const MQTT_HOST = "127.0.0.1";
    const MQTT_PORT = 15675; // Web MQTT RabbitMQ
    const MQTT_PATH = "/ws";
    const MQTT_USERNAME = "guest";
    const MQTT_PASSWORD = "guest";

    const TOPIC_SENSOR = "okra/sensor";
    const TOPIC_PUMP_CMD = "okra/pump/cmd";
    const TOPIC_PUMP_STATUS = "okra/pump/status";

    let mqttClient = null;

    // =============================
    //  GRAFIK (Chart.js)
    // =============================
    const chartLabels = [];
    const dataPh = [];
    const dataHumi = [];
    const dataTemp = [];

    const ctx = document.getElementById("sensorChart").getContext("2d");
    const sensorChart = new Chart(ctx, {
        type: "line",
        data: {
            labels: chartLabels,
            datasets: [{
                    label: "Kelembapan Tanah (%)",
                    data: dataHumi,
                    tension: 0.35
                },
                {
                    label: "Suhu Tanah (°C)",
                    data: dataTemp,
                    tension: 0.35
                },
                {
                    label: "pH Tanah",
                    data: dataPh,
                    tension: 0.35
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: "#4b5563",
                        font: {
                            size: 11
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: "#6b7280",
                        maxTicksLimit: 6
                    },
                    grid: {
                        color: "rgba(209, 213, 219, 0.6)"
                    }
                },
                y: {
                    ticks: {
                        color: "#6b7280"
                    },
                    grid: {
                        color: "rgba(209, 213, 219, 0.4)"
                    }
                }
            }
        }
    });

    function addSensorPoint(ph, humi, temp) {
        const now = new Date();
        const label = now.toLocaleTimeString("id-ID", {
            hour12: false
        });

        chartLabels.push(label);
        dataPh.push(ph);
        dataHumi.push(humi);
        dataTemp.push(temp);

        if (chartLabels.length > 40) {
            chartLabels.shift();
            dataPh.shift();
            dataHumi.shift();
            dataTemp.shift();
        }
        sensorChart.update();
    }

    // =============================
    //  UI ELEMENTS
    // =============================
    const phNumber = document.getElementById("phNumber");
    const humiNumber = document.getElementById("humiNumber");
    const tempNumber = document.getElementById("tempNumber");
    const phLabel = document.getElementById("phLabel");
    const humiLabel = document.getElementById("humiLabel");
    const tempLabel = document.getElementById("tempLabel");
    const lastUpdate = document.getElementById("lastUpdate");

    const mqttStatus = document.getElementById("mqttStatus");
    const pumpStatus = document.getElementById("pumpStatus");
    const btnTogglePump = document.getElementById("btnTogglePump");
    const autoMode = document.getElementById("autoMode");
    const logList = document.getElementById("logList");

    // mode otomatis di UI
    let isAutoMode = autoMode.checked;

    // untuk melacak apakah status berikutnya berasal dari klik dashboard
    let lastCommandSource = null; // "manual" atau null
    let lastCommandTime = 0; // timestamp ms

    function setMQTTStatus(connected) {
        mqttStatus.innerHTML = `
        <span class="status-dot ${connected ? "connected" : "disconnected"}"></span>
        ${connected ? "Connected" : "Disconnected"}
      `;
    }

    function setPumpStatusText(text) {
        pumpStatus.textContent = `Pompa: ${text}`;
        pumpStatus.classList.remove("bg-danger-subtle", "text-danger", "bg-success-subtle", "text-success");

        if (text === "ON") {
            pumpStatus.classList.add("bg-success-subtle", "text-success");
            btnTogglePump.textContent = "Matikan Pompa";
        } else if (text === "OFF") {
            pumpStatus.classList.add("bg-danger-subtle", "text-danger");
            btnTogglePump.textContent = "Nyalakan Pompa";
        } else {
            pumpStatus.classList.add("bg-danger-subtle", "text-danger");
            btnTogglePump.textContent = "Nyalakan Pompa";
        }
    }

    // =============================
    //  SENSOR UI
    // =============================
    function updateSensorUI(data) {
        const ph = typeof data.ph === "number" ? data.ph : null;
        const humi = typeof data.humi === "number" ? data.humi : (typeof data.moisture === "number" ? data.moisture :
            null);
        const temp = typeof data.temp === "number" ? data.temp : (typeof data.soilTemp === "number" ? data.soilTemp :
            null);

        if (ph !== null) {
            phNumber.textContent = ph.toFixed(1);
            phLabel.textContent =
                ph >= 5.5 && ph <= 7 ? "Kondisi optimal untuk okra merah" : "Perlu penyesuaian pH";
        }

        if (humi !== null) {
            humiNumber.textContent = humi.toFixed(1);
            humiLabel.textContent =
                humi >= 40 && humi <= 70 ? "Kelembapan ideal" : "Di luar rentang ideal";
        }

        if (temp !== null) {
            tempNumber.textContent = temp.toFixed(1);
            tempLabel.textContent =
                temp >= 24 && temp <= 30 ? "Suhu optimal" : "Suhu perlu dipantau";
        }

        if (ph !== null && humi !== null && temp !== null) {
            addSensorPoint(ph, humi, temp);
        }

        const nowStr = new Date().toLocaleString("id-ID");
        lastUpdate.textContent = "Last update: " + nowStr;
    }

    // =============================
    //  RIWAYAT PENYIRAMAN (DB)
    // =============================
    function renderLogList(logs) {
        logList.innerHTML = "";

        if (!logs || logs.length === 0) {
            const empty = document.createElement("div");
            empty.className = "text-muted text-center py-2";
            empty.style.fontSize = "0.8rem";
            empty.textContent = "Belum ada riwayat penyiraman.";
            logList.appendChild(empty);
            return;
        }

        logs.forEach(log => {
            const item = document.createElement("div");
            item.className = "list-group-item d-flex justify-content-between align-items-start";

            const badgeClass = log.source === "manual" ? "manual" : "otomatis";
            const notesText = log.notes ? ` – ${log.notes}` : "";

            item.innerHTML = `
      <div>
        <span class="log-badge ${badgeClass} me-2">${log.source}</span>
        <strong>${log.action}</strong>${notesText}
      </div>
      <div class="text-end">
        <div class="log-item-time">${log.log_time}</div>
        ${log.duration_seconds ? `<small>${log.duration_seconds}s</small>` : ""}
      </div>
    `;
            logList.appendChild(item);
        });
    }

    async function loadWateringLogs() {
        try {
            const res = await fetch("api/watering_logs.php?limit=20");
            const data = await res.json();

            if (!Array.isArray(data)) {
                console.warn("Response watering_logs bukan array:", data);
                renderLogList([]);
                return;
            }

            renderLogList(data);
        } catch (err) {
            console.error("Gagal load watering logs:", err);
            renderLogList([]);
        }
    }


    async function saveWateringLog(source, action, durationSeconds = null, notes = null) {
        try {
            const res = await fetch("api/watering_logs.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    source,
                    action,
                    duration_seconds: durationSeconds,
                    notes
                })
            });

            const data = await res.json();

            if (!res.ok || data.error) {
                console.error("Gagal simpan log:", data);
                return;
            }

            // kalau berhasil, reload list
            loadWateringLogs();
        } catch (err) {
            console.error("Exception saat simpan watering log:", err);
        }
    }


    // =============================
    //  MQTT HANDLER
    // =============================
    function connectMQTT() {
        const clientId = "ITANI_WEB_" + Math.floor(Math.random() * 100000);
        console.log("Connect MQTT as:", clientId);

        mqttClient = new Paho.MQTT.Client(
            MQTT_HOST,
            Number(MQTT_PORT),
            MQTT_PATH,
            clientId
        );

        mqttClient.onConnectionLost = (resp) => {
            console.warn("MQTT lost:", resp.errorMessage);
            setMQTTStatus(false);
            setTimeout(connectMQTT, 2000);
        };

        mqttClient.onMessageArrived = (message) => {
            console.log("MQTT message:", message.destinationName, message.payloadString);

            if (message.destinationName === TOPIC_SENSOR) {
                try {
                    const data = JSON.parse(message.payloadString);

                    // 1) update tampilan & grafik seperti biasa
                    updateSensorUI(data);

                    // 2) simpan ke database lewat PHP
                    saveSensorToDb(data);
                } catch (err) {
                    console.error("Gagal parse JSON sensor:", err);
                }
            } else if (message.destinationName === TOPIC_PUMP_STATUS) {
                const raw = message.payloadString.trim().toUpperCase();
                const status = (raw === "ON" || raw === "OFF") ? raw : "UNKNOWN";

                // Kalau payload-nya aneh
                if (status !== "ON" && status !== "OFF") {
                    console.warn("Status pompa tidak dikenali dari MQTT:", raw);
                    return;
                }

                const now = Date.now();

                // CASE 1: echo dari perintah MANUAL (klik tombol dashboard)
                if (lastCommandSource === "manual" && (now - lastCommandTime) <= 3000) {
                    console.log("MQTT status dianggap echo manual.");
                    // Kita SUDAH simpan log manual saat klik tombol,
                    // jadi di sini cukup pastikan tampilan sinkron:
                    setPumpStatusText(status);
                    // habis dipakai, reset flag
                    lastCommandSource = null;
                    return;
                }

                // CASE 2: tidak ada perintah manual sebelumnya
                if (isAutoMode) {
                    // Mode otomatis AKTIF → anggap ini perintah otomatis dari ESP
                    setPumpStatusText(status);
                    saveWateringLog("otomatis", status, null, "Perintah dari ESP");
                    return;
                }

                // CASE 3: mode otomatis MATI dan bukan echo manual
                // → abaikan pesan dari ESP (tidak log, tidak mengubah UI)
                console.log(
                    "MQTT status dari ESP diabaikan karena mode otomatis OFF dan bukan echo manual."
                );
            }
        };


        const options = {
            timeout: 5,
            useSSL: false,
            onSuccess: () => {
                console.log("MQTT connected");
                setMQTTStatus(true);
                mqttClient.subscribe(TOPIC_SENSOR);
                mqttClient.subscribe(TOPIC_PUMP_STATUS);
            },
            onFailure: (err) => {
                console.error("MQTT connect failed:", err.errorMessage);
                setMQTTStatus(false);
                setTimeout(connectMQTT, 2000);
            }
        };

        if (MQTT_USERNAME) {
            options.userName = MQTT_USERNAME;
            options.password = MQTT_PASSWORD;
        }

        mqttClient.connect(options);
    }

    function sendPumpCommand(cmd) {
        if (!mqttClient || !mqttClient.isConnected()) {
            console.warn("MQTT belum terhubung, CMD tidak terkirim");
            return;
        }
        const msg = new Paho.MQTT.Message(cmd);
        msg.destinationName = TOPIC_PUMP_CMD;
        mqttClient.send(msg);
    }

    function saveSensorToDb(data) {
        // Kirim ke PHP dalam bentuk JSON yang sama
        fetch("api/sensors_insert.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data),
            })
            .then((res) => res.json())
            .then((resp) => {
                if (!resp.success) {
                    console.error("Gagal simpan sensor ke DB:", resp.error);
                } else {
                    console.log("Sensor tersimpan ke DB");
                }
            })
            .catch((err) => {
                console.error("Error fetch sensors_insert.php:", err);
            });
    }


    // =============================
    //  EVENT UI
    // =============================
    btnTogglePump.addEventListener("click", () => {
        const currentText = pumpStatus.textContent || "";
        const isOn = currentText.includes("ON");
        const nextCmd = isOn ? "OFF" : "ON";

        // 1) Simpan log MANUAL langsung ke database
        saveWateringLog("manual", nextCmd, null, "Perintah dari dashboard");

        // 2) Tandai bahwa setelah ini kita EXPECT balasan dari ESP (echo manual)
        lastCommandSource = "manual";
        lastCommandTime = Date.now();

        // 3) Kirim perintah ke ESP via MQTT
        sendPumpCommand(nextCmd);

        // 4) Update UI langsung (biar terasa responsif)
        setPumpStatusText(nextCmd);
    });


    autoMode.addEventListener("change", () => {
        const aktif = autoMode.checked;
        isAutoMode = aktif; // <-- penting, state JS ikut berubah

        const modeText = aktif ? "diaktifkan" : "dinonaktifkan";
        console.log("Mode otomatis:", modeText);

        saveWateringLog(
            "manual",
            aktif ? "ON" : "OFF",
            null,
            "Mode otomatis " + modeText
        );
    });




    // =============================
    //  START
    // =============================
    window.addEventListener("load", () => {
        // 1) load log dari DB
        loadWateringLogs();

        fetch("api/pump_status_latest.php")
            .then(r => r.json())
            .then(data => {
                if (data.exists && (data.action === "ON" || data.action === "OFF")) {
                    setPumpStatusText(data.action);
                } else {
                    setPumpStatusText("UNKNOWN");
                }
            })
            .catch(err => {
                console.warn("Tidak bisa load status pompa terakhir:", err);
            });

        fetch("api/sensors_latest.php")
            .then((r) => r.json())
            .then((data) => {
                if (data.exists) updateSensorUI(data);
            })
            .catch((err) => console.warn("Tidak bisa load sensor terakhir:", err));

        // 3) connect MQTT
        connectMQTT();
    });
    </script>
</body>

</html>