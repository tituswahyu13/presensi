<?php
session_start();
require_once('../config.php');

if (isset($_SESSION["login"]) && $_SESSION["login"] === true) {
  $role_pages = [
    'admin' => '../admin/home/home.php',
    'pegawai' => '../pegawai/home/home.php',
    'kantor' => '../pegawai/home/home.php',
    'sumber' => '../pegawai/home/sumber.php',
    'tidar' => '../pegawai/home/tidar.php',
    'kalimas' => '../pegawai/home/kalimas.php',
    'sri_ponganten' => '../pegawai/home/sri_ponganten.php',
    'satpam' => '../pegawai/home/satpam.php'
  ];
  $role = $_SESSION["role"];
  if (isset($role_pages[$role])) {
    echo "<script>window.location.replace('{$role_pages[$role]}');</script>";
  }
  exit();
}

if (isset($_POST["login"])) {
  $username = $_POST["username"];
  $password = $_POST["password"];

  $stmt = mysqli_prepare($connection, "SELECT * FROM users JOIN pegawai ON users.id_pegawai = pegawai.id WHERE username = ?");
  mysqli_stmt_bind_param($stmt, "s", $username);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if (mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);

    if (password_verify($password, $row['password'])) {
      if ($row['status'] == 'Aktif') {
        $_SESSION["login"] = true;
        $_SESSION["id"] = $row["id"];
        $_SESSION["role"] = $row["role"];
        $_SESSION["nama"] = $row["nama"];
        $_SESSION["nik"] = $row["nik"];
        $_SESSION["jabatan"] = $row["jabatan"];
        $_SESSION["lokasi_presensi"] = $row["lokasi_presensi"];
        $_SESSION["foto"] = $row["foto"];

        $role_pages = [
          'admin' => '../admin/home/home.php',
          'pegawai' => '../pegawai/home/home.php',
          'kantor' => '../pegawai/home/home.php',
          'sumber' => '../pegawai/home/sumber.php',
          'tidar' => '../pegawai/home/tidar.php',
          'kalimas' => '../pegawai/home/kalimas.php',
          'sri_ponganten' => '../pegawai/home/sri_ponganten.php',
          'satpam' => '../pegawai/home/satpam.php'
        ];
        $role = $row['role'];
        if (isset($role_pages[$role])) {
          header("Location: {$role_pages[$role]}");
          exit();
        }
      } else {
        $_SESSION["gagal"] = "Akun anda belum aktif";
      }
    } else {
      $_SESSION["gagal"] = "Password salah, silahkan coba lagi";
    }
  } else {
    $_SESSION["gagal"] = "Username salah, silahkan coba lagi";
  }
}

// Get server time
date_default_timezone_set('Asia/Jakarta'); // Set your timezone
$server_time = date('Y-m-d H:i:s');
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>PRES.PAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="manifest" href="../manifest.json">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #00bcd4; /* Biru langit/air */
      --secondary-color: #00e5ff; /* Cyan terang */
      --bg-color: #e0f7fa; /* Biru muda sangat terang */
      --card-bg: rgba(255, 255, 255, 0.95); /* Putih hampir solid */
      --text-color: #212529; /* Teks hitam standar */
      --placeholder-color: #6c757d; /* Abu-abu sedang */
      --border-color: rgba(0, 188, 212, 0.4); /* Border biru terang */
      --glow-color: rgba(0, 229, 255, 0.6); /* Glow cyan terang */
    }
    
    body {
      background: var(--bg-color);
      color: var(--text-color);
      font-family: 'Inter', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      overflow: hidden;
      position: relative;
      font-feature-settings: "cv03", "cv04", "cv11";
      animation: fadeIn 1s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Layer 1: Animasi Grid Background */
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: radial-gradient(circle, #b2ebf2 1px, transparent 1px); /* Grid lebih cerah */
      background-size: 20px 20px;
      opacity: 0.6; /* Lebih terlihat */
      z-index: -1;
      animation: pan-grid 60s linear infinite;
    }

    @keyframes pan-grid {
      from {
        background-position: 0 0;
      }
      to {
        background-position: -2000px 2000px;
      }
    }

    /* Kontainer untuk partikel */
    .particle-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        overflow: hidden;
        z-index: -1;
    }

    /* Gaya partikel */
    .particle {
        position: absolute;
        background-color: var(--primary-color);
        border-radius: 50%;
        opacity: 0;
        animation: floatAndFade 15s infinite ease-out;
        box-shadow: 0 0 8px var(--glow-color); /* Glow partikel lebih kuat */
    }

    @keyframes floatAndFade {
        0% { transform: translateY(0) scale(0); opacity: 0; }
        10% { opacity: 0.7; transform: translateY(-5vh) scale(0.6); } /* Lebih jelas di awal */
        90% { opacity: 0; transform: translateY(-100vh) scale(1.5); } /* Ukuran lebih besar saat naik */
        100% { opacity: 0; transform: translateY(-120vh) scale(0); }
    }

    .futuristic-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
          radial-gradient(circle at 10% 20%, rgba(0, 188, 212, 0.15) 0%, transparent 50%), /* Overlay lebih cerah */
          radial-gradient(circle at 90% 80%, rgba(0, 229, 255, 0.15) 0%, transparent 50%);
      pointer-events: none;
      z-index: -2;
    }
    
    .page {
      position: relative;
      z-index: 10;
    }

    .container-tight {
      max-width: 400px;
      width: 100%;
      padding: 20px;
    }

    .card {
      background: var(--card-bg);
      backdrop-filter: blur(12px); /* Blur sedikit lebih kuat */
      border: 1px solid var(--border-color);
      border-radius: 20px;
      box-shadow: 0 0 30px var(--glow-color); /* Shadow lebih menonjol */
      transition: transform 0.3s ease-in-out, box-shadow 0.3s ease;
      animation: slideIn 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Kurva animasi lebih dinamis */
    }

    @keyframes slideIn {
        from { transform: translateY(80px); opacity: 0; } /* Mulai lebih rendah */
        to { transform: translateY(0); opacity: 1; }
    }

    .card:hover {
      transform: translateY(-8px); /* Efek hover lebih jelas */
      box-shadow: 0 0 45px rgba(0, 229, 255, 0.8); /* Glow hover lebih terang */
    }

    .card-body {
      padding: 40px;
    }

    .h2.text-center {
      font-weight: 700;
      color: var(--text-color);
      text-shadow: 0 0 8px var(--primary-color); /* Text shadow lebih kuat */
      animation: pulse 2s ease-in-out infinite alternate; /* Animasi pulse lebih halus dan berulang */
    }

    @keyframes pulse {
        0% { text-shadow: 0 0 8px var(--primary-color); }
        100% { text-shadow: 0 0 20px var(--primary-color), 0 0 25px var(--secondary-color); }
    }


    .form-label {
      color: var(--text-color);
      font-weight: 500;
    }

    .form-control {
      background: rgba(0, 0, 0, 0.08); /* Latar belakang input sedikit gelap agar kontras */
      border: 1px solid var(--border-color);
      color: var(--text-color); /* Warna teks yang jelas saat diketik */
      caret-color: var(--primary-color); /* Warna kursor sesuai tema */
      padding: 15px;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    /* Memastikan warna autofill cocok dengan tema terang */
    input.form-control:-webkit-autofill,
    input.form-control:-webkit-autofill:hover,
    input.form-control:-webkit-autofill:focus,
    textarea.form-control:-webkit-autofill,
    textarea.form-control:-webkit-autofill:hover,
    textarea.form-control:-webkit-autofill:focus,
    select.form-control:-webkit-autofill,
    select.form-control:-webkit-autofill:hover,
    select.form-control:-webkit-autofill:focus {
        -webkit-text-fill-color: var(--text-color) !important;
        transition: background-color 9999s ease-out 0s;
        -webkit-box-shadow: 0 0 0px 1000px rgba(0, 0, 0, 0.1) inset !important; /* Latar belakang autofill */
        box-shadow: 0 0 0px 1000px rgba(0, 0, 0, 0.1) inset !important;
        background-clip: content-box !important;
    }

    /* Mengatur skema warna halaman untuk browser */
    @media (prefers-color-scheme: light) {
        body, input, select, textarea, button {
            color-scheme: light;
        }
    }

    .form-control::placeholder {
      color: var(--placeholder-color); /* Memastikan warna placeholder jelas */
      opacity: 0.9; /* Sedikit lebih jelas */
    }

    .form-control:focus {
      background: rgba(0, 0, 0, 0.12); /* Fokus sedikit lebih gelap */
      border-color: var(--secondary-color); /* Border fokus ke secondary color */
      box-shadow: 0 0 15px var(--glow-color); /* Glow fokus lebih kuat */
    }
    
    .input-group-text {
      background: rgba(0, 0, 0, 0.08);
      border: 1px solid var(--border-color);
      border-left: none;
      color: var(--primary-color);
      padding: 0 15px;
      border-radius: 0 10px 10px 0;
    }

    .fa-eye-slash, .fa-eye {
      color: var(--primary-color);
      cursor: pointer;
      transition: transform 0.2s ease;
    }

    .fa-eye-slash:hover, .fa-eye:hover {
      transform: scale(1.2); /* Efek hover lebih besar */
    }

    .btn-primary {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      border: none;
      border-radius: 10px;
      padding: 15px;
      font-weight: bold;
      letter-spacing: 1px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 18px var(--glow-color); /* Shadow tombol lebih besar */
      color: #fff; /* Teks tombol tetap putih agar kontras */
    }

    .btn-primary:hover {
      background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
      transform: translateY(-5px); /* Efek hover lebih jelas */
      box-shadow: 0 8px 25px var(--glow-color); /* Shadow hover lebih kuat */
    }

    .datetime-display {
      color: var(--text-color);
      font-size: 0.8rem; /* Ukuran sedikit lebih besar */
      font-family: 'Courier New', Courier, monospace;
      white-space: nowrap;
      overflow: hidden;
      /* border-right: .18em solid var(--primary-color); Border kursor sedikit lebih tebal */
      /* animation: typing 3s steps(40, end) forwards, blink-caret .8s step-end infinite; Animasi typing lebih cepat */
    }

    @keyframes typing {
      from { width: 0 }
      to { width: 100% }
    }

    @keyframes blink-caret {
      from, to { border-color: transparent }
      50% { border-color: var(--primary-color) }
    }

    .navbar-brand img {
      filter: drop-shadow(0 0 8px rgba(0, 188, 212, 0.6)); /* Drop shadow logo lebih jelas */
      transition: transform 0.3s ease;
    }

    .navbar-brand img:hover {
      transform: scale(1.1); /* Efek hover logo lebih besar */
    }

    #installPrompt {
      background-color: var(--card-bg); /* Sesuai card background */
      border: 2px solid var(--secondary-color);
      box-shadow: 0 0 25px var(--glow-color);
      border-radius: 15px;
      animation: fadeIn 0.5s ease-in-out;
      color: var(--text-color); /* Teks di prompt sesuai teks utama */
    }

    #installButton {
      background-color: var(--secondary-color);
      color: #fff;
      font-weight: 700;
      box-shadow: 0 0 12px var(--glow-color);
      transition: background-color 0.3s, box-shadow 0.3s;
    }

    #installButton:hover {
      background-color: var(--primary-color); /* Hover ke primary color */
      box-shadow: 0 0 18px var(--glow-color);
    }

    #dismissButton {
      border-color: var(--primary-color);
      color: var(--primary-color);
      transition: all 0.3s;
    }

    #dismissButton:hover {
      background-color: rgba(0, 188, 212, 0.2); /* Hover dengan primary color transparan */
      color: var(--text-color); /* Teks berubah saat hover */
    }
    
    .alert-danger {
      color: #d32f2f !important; /* Warna merah yang lebih tegas */
      background-color: rgba(229, 57, 53, 0.1); /* Latar alert merah muda transparan */
      border-color: rgba(229, 57, 53, 0.5); /* Border alert merah */
      font-weight: bold;
      text-shadow: 0 0 6px rgba(229, 57, 53, 0.4);
      animation: shake 0.5s;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-7px); } /* Guncangan lebih kuat */
      20%, 40%, 60%, 80% { transform: translateX(7px); }
    }
  </style>
</head>

<body>
  <div class="futuristic-overlay"></div>
  <div class="particle-container"></div>

  <div class="page page-center">
    <div class="container container-normal py-4">
      <div class="row align-items-center g-4">
        <div class="col-lg">
          <div class="container-tight">
            <div class="text-center mb-4">
              <a href="" class="navbar-brand navbar-brand-autodark"><img src="/assets/img/logo.png" height="100" alt=""></a>
            </div>
            <div class="card card-md">
              <div class="card-body">
                <h2 class="h2 text-center mb-4">Login to your account</h2>
                <?php if (isset($_SESSION["gagal"])) : ?>
                  <div class="alert alert-danger text-center" role="alert">
                    <?= $_SESSION["gagal"]; ?>
                  </div>
                  <?php unset($_SESSION["gagal"]); ?>
                <?php endif; ?>
                <form action="" method="POST" autocomplete="off" novalidate>
                  <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" autofocus name="username" placeholder="Username" autocomplete="off">
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                      <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password">
                      <span class="input-group-text">
                        <i class="fa fa-eye-slash" id="togglePassword"></i>
                      </span>
                    </div>
                  </div>
                  <div class="form-footer">
                    <button type="submit" name="login" class="btn btn-primary w-100">Masuk</button>
                  </div>
                </form>
              </div>
              <div class="text-center mt-4">
                <p id="datetime-display" class="datetime-display mb-0"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js', {
          scope: '/'
        })
        .then(function(registration) {
          console.log('Service Worker terdaftar dengan scope:', registration.scope);
        })
        .catch(function(error) {
          console.log('Pendaftaran Service Worker gagal:', error);
        });
    }

    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      showInstallPopup();
    });

    document.addEventListener('DOMContentLoaded', function() {
      const togglePassword = document.querySelector('#togglePassword');
      const password = document.querySelector('#password');

      togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
      });

      function updateDateTime() {
        const datetimeDisplay = document.getElementById('datetime-display');
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        datetimeDisplay.textContent = now.toLocaleDateString('id-ID', options);
      }
      setInterval(updateDateTime, 1000);
      updateDateTime();

      const particleContainer = document.querySelector('.particle-container');
      const numParticles = 30;

      for (let i = 0; i < numParticles; i++) {
          const particle = document.createElement('div');
          particle.classList.add('particle');
          
          const size = Math.random() * 5 + 2;
          particle.style.width = `${size}px`;
          particle.style.height = `${size}px`;
          
          particle.style.left = `${Math.random() * 100}vw`;
          particle.style.bottom = `-${size}px`; 
          
          const duration = Math.random() * 10 + 5;
          const delay = Math.random() * 5;
          particle.style.animationDuration = `${duration}s`;
          particle.style.animationDelay = `${delay}s`;

          particleContainer.appendChild(particle);
      }
    });

    function showInstallPopup() {
      const installPrompt = document.createElement('div');
      installPrompt.id = 'installPrompt';
      installPrompt.style.position = 'fixed';
      installPrompt.style.bottom = '20px';
      installPrompt.style.left = '50%';
      installPrompt.style.transform = 'translateX(-50%)';
      installPrompt.style.padding = '20px';
      installPrompt.style.backgroundColor = 'var(--card-bg)';
      installPrompt.style.color = 'var(--text-color)';
      installPrompt.style.border = '2px solid var(--secondary-color)';
      installPrompt.style.borderRadius = '15px';
      installPrompt.style.boxShadow = '0 0 25px var(--glow-color)';
      installPrompt.style.textAlign = 'center';
      installPrompt.style.fontFamily = "'Inter', sans-serif";
      installPrompt.style.zIndex = '1000';
      installPrompt.innerHTML = `
    <p style="margin: 0; font-size: 18px; font-weight: 500;">📲 Install Aplikasi untuk Akses Lebih Cepat!</p>
    <div style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
      <button id="installButton" style="
        padding: 8px 20px;
        background-color: var(--secondary-color);
        color: #ffffff;
        border: none;
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s;
      ">Install</button>
      <button id="dismissButton" style="
        padding: 8px 20px;
        background-color: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
      ">Nanti</button>
    </div>
  `;

      document.body.appendChild(installPrompt);

      const installButton = document.getElementById('installButton');
      const dismissButton = document.getElementById('dismissButton');

      installButton.addEventListener('click', async () => {
        installPrompt.remove();
        if (deferredPrompt) {
          deferredPrompt.prompt();
          const choiceResult = await deferredPrompt.userChoice;
          console.log(choiceResult.outcome === 'accepted' ? 'User accepted the install prompt' : 'User dismissed the install prompt');
          deferredPrompt = null;
        }
      });

        dismissButton.addEventListener('click', () => {
            installPrompt.remove();
        });
    }
  </script>
</body>
</html>