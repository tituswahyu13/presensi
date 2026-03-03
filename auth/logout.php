<?php
session_start();

// Fungsi untuk melakukan logout
function logout() {
    // Hapus semua session
    session_unset();
    // Hancurkan session
    session_destroy();
}

// Jika pengguna klik tombol logout
if(isset($_POST["logout"])) {
    logout();
    // Redirect pengguna ke halaman login setelah logout
    header("Location: /auth/login.php");
    exit();
}

// Set header untuk mencegah cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative;
            overflow: hidden;
            font-feature-settings: "cv03", "cv04", "cv11";
            animation: fadeIn 1s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle, #b2ebf2 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.6;
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
            z-index: -1; /* di bawah elemen konten */
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
                radial-gradient(circle at 10% 20%, rgba(0, 188, 212, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(0, 229, 255, 0.15) 0%, transparent 50%);
            pointer-events: none;
            z-index: -2;
        }

        .container {
            text-align: center;
            background-color: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            box-shadow: 0 0 30px var(--glow-color);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 10;
            animation: slideIn 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        @keyframes slideIn {
            from { transform: translateY(80px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .container h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: var(--text-color);
            text-shadow: 0 0 8px var(--primary-color);
            animation: text-glow-pulse 2s infinite alternate;
        }
        
        @keyframes text-glow-pulse {
            from { text-shadow: 0 0 8px var(--primary-color); }
            to { text-shadow: 0 0 20px var(--primary-color), 0 0 25px var(--secondary-color); }
        }

        .container p {
            font-size: 1rem;
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .btn-logout {
            background: linear-gradient(45deg, #ff5050, #ff8080);
            color: white;
            font-size: 1rem;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 4px 18px rgba(255, 80, 80, 0.4);
        }

        .btn-logout:hover {
            background: linear-gradient(45deg, #ff8080, #ff5050);
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255, 80, 80, 0.6);
        }
        
        .btn-logout:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(255, 80, 80, 0.4);
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            .container h1 {
                font-size: 1.8rem;
            }

            .container p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="futuristic-overlay"></div>
    <div class="particle-container"></div>
    <div class="container">
        <h1>Logout Confirmation</h1>
        <p>Are you sure you want to log out?</p>
        <form action="" method="post">
            <input type="submit" value="Logout" name="logout" class="btn-logout">
        </form>
    </div>

    <audio id="logout-sound" src="/assets/audio/logout-sound.mp3" preload="auto"></audio>
    <audio id="haptic-sound" src="/assets/audio/haptic-feedback.mp3" preload="auto"></audio>

    <script>
        window.onload = function() {
            if (window.history && window.history.pushState) {
                window.history.pushState('forward', null, '');
                window.onpopstate = function() {
                    window.location.href = "/auth/login.php";
                };
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const logoutButton = document.querySelector('.btn-logout');
            const logoutSound = document.getElementById('logout-sound');
            const hapticSound = document.getElementById('haptic-sound');
            
            logoutButton.addEventListener('click', () => {
                hapticSound.play();
                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }
            });
            
            logoutButton.addEventListener('mouseover', () => {
                logoutSound.play();
            });
            
            const card = document.querySelector('.container');
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const backgroundX = (x / rect.width) * 100;
                const backgroundY = (y / rect.height) * 100;
                
                card.style.background = `radial-gradient(circle at ${backgroundX}% ${backgroundY}%, rgba(0, 188, 212, 0.15) 0%, rgba(255, 255, 255, 0.95) 100%)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.background = 'var(--card-bg)';
            });

            // Script untuk animasi partikel
            const particleContainer = document.querySelector('.particle-container');
            const numParticles = 40;

            for (let i = 0; i < numParticles; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                const size = Math.random() * 6 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                particle.style.left = `${Math.random() * 100}vw`;
                particle.style.bottom = `-${size}px`; 
                
                const duration = Math.random() * 12 + 6;
                const delay = Math.random() * 6;
                particle.style.animationDuration = `${duration}s`;
                particle.style.animationDelay = `${delay}s`;

                particleContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>