<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SideQuest - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #ff8a00, #e52e71);
            min-height: 100vh;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            z-index: 1000;
        }

        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }

        .nav-buttons {
            display: flex;
            gap: 20px;
        }

        .nav-button {
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .nav-button:hover {
            background: white;
            color: #e52e71;
        }

        .hero {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
            color: white;
        }

        .hero h1 {
            font-size: 80px;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }

        .hero p {
            font-size: 20px;
            max-width: 600px;
            margin-bottom: 30px;
            animation: fadeInUp 1s ease 0.3s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            animation: fadeInUp 1s ease 0.6s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-card h3 {
            color: white;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: rgba(255, 255, 255, 0.8);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 50px;
            }

            .hero p {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">SIDEQUEST</a>
        <div class="nav-buttons">
            <a href="login.php" class="nav-button">Login</a>
            <a href="register.php" class="nav-button">Register</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <h1>SIDEQUEST</h1>
            <p>Empowering students through meaningful tasks and rewards. Connect with faculty members and earn while you learn.</p>
            
            <div class="features">
                <div class="feature-card">
                    <h3>Task Management</h3>
                    <p>Easily manage and track your tasks from faculty members</p>
                </div>
                <div class="feature-card">
                    <h3>Reward System</h3>
                    <p>Earn rewards for completing tasks and building your portfolio</p>
                </div>
                <div class="feature-card">
                    <h3>Direct Communication</h3>
                    <p>Connect directly with faculty members for guidance and support</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html> 