<?php
session_start();

// Dynamic PHP content
$currentTime = date('Y-m-d H:i:s');
$currentHour = date('H');
$serverName = $_SERVER['SERVER_NAME'];
$userIP = $_SERVER['REMOTE_ADDR'];
$phpVersion = phpversion();

// Dynamic greeting based on time
if ($currentHour < 12) {
    $greeting = "صباح الخير! 🌅";
    $timeMessage = "بداية يوم جديد مليء بالإمكانيات";
} elseif ($currentHour < 18) {
    $greeting = "مساء الخير! ☀️";
    $timeMessage = "وقت رائع لاستكشاف الجديد";
} else {
    $greeting = "مساء الخير! 🌙";
    $timeMessage = "أمسية مثالية للإبداع والتطوير";
}

// Visit counter
if (!isset($_SESSION['visits'])) {
    $_SESSION['visits'] = 1;
} else {
    $_SESSION['visits']++;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 صفحة الهبوط الاحترافية - مرحباً بك في عالمنا الرقمي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-light: rgba(255, 255, 255, 0.9);
            --text-dark: rgba(0, 0, 0, 0.8);
            --shadow-light: 0 8px 32px rgba(31, 38, 135, 0.37);
            --shadow-heavy: 0 15px 35px rgba(31, 38, 135, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--primary-gradient);
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            animation: backgroundShift 15s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            25% { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
            50% { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
            75% { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        }

        /* Animated Background Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 1; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 0.5; }
        }

        /* Glass Container */
        .glass-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            position: relative;
            z-index: 10;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .logo {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-10px) scale(1.05); }
        }

        .main-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--text-light);
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: titleGlow 2s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3); }
            to { text-shadow: 0 0 20px rgba(255, 255, 255, 0.5), 2px 2px 4px rgba(0, 0, 0, 0.3); }
        }

        .subtitle {
            font-size: 1.3rem;
            color: var(--text-light);
            opacity: 0.9;
            animation: slideInUp 1s ease-out 0.5s both;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 0.9; transform: translateY(0); }
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .info-card {
            padding: 2rem;
            animation: cardSlideIn 0.8s ease-out;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-heavy);
        }

        @keyframes cardSlideIn {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .card-content {
            color: var(--text-light);
            line-height: 1.6;
            opacity: 0.9;
        }

        /* Dynamic Info Section */
        .dynamic-info {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.05));
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--text-light);
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-light);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin: 2rem 0;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--secondary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(240, 147, 251, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.6);
        }

        .btn-secondary {
            background: var(--success-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .footer-content {
            color: var(--text-light);
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2.5rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-gradient);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-gradient);
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>

    <!-- Header Section -->
    <header class="header">
        <div class="logo">🚀</div>
        <h1 class="main-title"><?php echo $greeting; ?></h1>
        <p class="subtitle"><?php echo $timeMessage; ?></p>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dynamic Information -->
        <section class="glass-container dynamic-info">
            <h2 class="card-title">
                <i class="fas fa-chart-line"></i>
                معلومات الجلسة الحالية
            </h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">الوقت الحالي</div>
                    <div class="info-value"><?php echo $currentTime; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">اسم الخادم</div>
                    <div class="info-value"><?php echo $serverName; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">عنوان IP الخاص بك</div>
                    <div class="info-value"><?php echo $userIP; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">إصدار PHP</div>
                    <div class="info-value"><?php echo $phpVersion; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">عدد زياراتك</div>
                    <div class="info-value"><?php echo $_SESSION['visits']; ?></div>
                </div>
            </div>
        </section>

        <!-- Content Grid -->
        <div class="content-grid">
            <div class="glass-container info-card">
                <div class="card-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3 class="card-title">تقنيات متطورة</h3>
                <p class="card-content">
                    نستخدم أحدث التقنيات في تطوير الويب مع PHP الديناميكي وتصميم Glassmorphism الحديث لتقديم تجربة استخدام فريدة ومبتكرة.
                </p>
            </div>

            <div class="glass-container info-card">
                <div class="card-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <h3 class="card-title">تصميم جذاب</h3>
                <p class="card-content">
                    تصميم متجاوب وجميل مع تأثيرات بصرية مذهلة وألوان متدرجة تتغير تلقائياً لتوفير تجربة بصرية ممتعة ومريحة للعين.
                </p>
            </div>

            <div class="glass-container info-card">
                <div class="card-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3 class="card-title">أداء محسن</h3>
                <p class="card-content">
                    محسن للسرعة والأداء مع تحميل سريع وتأثيرات سلسة. يعمل بكفاءة على جميع الأجهزة والمتصفحات الحديثة.
                </p>
            </div>

            <div class="glass-container info-card">
                <div class="card-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="card-title">أمان عالي</h3>
                <p class="card-content">
                    مبني بأعلى معايير الأمان مع حماية البيانات وتشفير الاتصالات لضمان خصوصية وأمان المستخدمين.
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="#" class="btn btn-primary" onclick="showAlert('مرحباً بك!', 'شكراً لزيارة موقعنا الرائع!')">
                <i class="fas fa-play"></i>
                ابدأ الآن
            </a>
            <a href="#" class="btn btn-secondary" onclick="showAlert('معلومات إضافية', 'ستجد هنا كل ما تحتاجه!')">
                <i class="fas fa-info-circle"></i>
                اعرف المزيد
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p>
                <i class="fas fa-heart" style="color: #ff6b6b;"></i>
                تم التطوير بحب وإبداع © <?php echo date("Y"); ?>
                <i class="fas fa-code" style="color: #4ecdc4;"></i>
            </p>
            <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                صفحة هبوط احترافية مع PHP ديناميكي وتصميم Glassmorphism
            </p>
        </div>
    </footer>

    <script>
        // Create animated particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Custom alert function
        function showAlert(title, message) {
            // Create modal instead of alert for better UX
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                backdrop-filter: blur(5px);
            `;

            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 20px;
                padding: 2rem;
                max-width: 400px;
                text-align: center;
                color: white;
                box-shadow: 0 15px 35px rgba(31, 38, 135, 0.5);
                animation: modalSlideIn 0.3s ease-out;
            `;

            modalContent.innerHTML = `
                <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">${title}</h3>
                <p style="margin-bottom: 1.5rem; line-height: 1.6;">${message}</p>
                <button onclick="this.closest('.modal').remove()" style="
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    border: none;
                    padding: 0.8rem 2rem;
                    border-radius: 25px;
                    color: white;
                    font-weight: 600;
                    cursor: pointer;
                    transition: transform 0.3s ease;
                " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    حسناً
                </button>
            `;

            modal.className = 'modal';
            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes modalSlideIn {
                    from { opacity: 0; transform: scale(0.8) translateY(-50px); }
                    to { opacity: 1; transform: scale(1) translateY(0); }
                }
            `;
            document.head.appendChild(style);

            // Close on outside click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Initialize particles when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();

            // Add floating animation to cards
            const cards = document.querySelectorAll('.info-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.2) + 's';
            });

            // Add click effect to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.5);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });

            // Add ripple animation
            const style = document.createElement('style');
            style.textContent += `
                @keyframes ripple {
                    to { transform: scale(2); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        });

        // Update time every second
        setInterval(function() {
            const now = new Date();
            const timeString = now.toLocaleString('ar-EG');
            const timeElements = document.querySelectorAll('.info-value');
            if (timeElements[0]) {
                timeElements[0].textContent = timeString;
            }
        }, 1000);
    </script>
</body>
</html>