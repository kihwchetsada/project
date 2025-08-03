<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลการตั้งค่า Challonge API
$config_sql = "SELECT * FROM challonge_config LIMIT 1";
$config_result = $conn->query($config_sql);

if ($config_result && $config_result->num_rows > 0) {
    $config = $config_result->fetch_assoc();
    $api_key = $config['api_key'];
    $tournament_url = $config['tournament_url'];
} else {
    die("ไม่พบการตั้งค่า Challonge API กรุณาตั้งค่าก่อนใช้งาน");
}

// ดึงชื่อทีมจากฐานข้อมูล
$sql = "SELECT team_name FROM teams";
$result = $conn->query($sql);

$team_names = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $team_names[] = $row['team_name'];
    }
}

// ส่วนแสดงผล
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../img/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>🎮 Tournament Control Center - Add Teams</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #ffffff;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Effects */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 226, 0.2) 0%, transparent 50%);
            z-index: -1;
            animation: pulse 4s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            0% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Floating particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00ffff;
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1);
            border-radius: 2px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            0% { box-shadow: 0 0 5px #ff6b6b, 0 0 10px #ff6b6b, 0 0 15px #ff6b6b; }
            100% { box-shadow: 0 0 10px #4ecdc4, 0 0 20px #4ecdc4, 0 0 30px #4ecdc4; }
        }

        h1 {
            font-family: 'Orbitron', monospace;
            font-weight: 900;
            font-size: 2.5rem;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient 3s ease infinite;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
            margin-bottom: 10px;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .subtitle {
            font-family: 'Orbitron', monospace;
            color: #4ecdc4;
            font-size: 1.1rem;
            letter-spacing: 2px;
            opacity: 0.8;
        }

        .game-panel {
            background: rgba(26, 26, 46, 0.9);
            border: 2px solid transparent;
            background-clip: padding-box;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            backdrop-filter: blur(10px);
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .game-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 15px;
            padding: 2px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4);
            background-size: 400% 400%;
            animation: borderGlow 3s ease infinite;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: subtract;
            z-index: -1;
        }

        @keyframes borderGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .info-box {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.05));
            border: 1px solid rgba(76, 175, 80, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .info-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-item i {
            margin-right: 10px;
            color: #4ecdc4;
            width: 20px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-link {
            color: #ff6b6b;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .info-link:hover {
            color: #ffffff;
            text-shadow: 0 0 10px #ff6b6b;
        }

        h2 {
            font-family: 'Orbitron', monospace;
            color: #4ecdc4;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, #4ecdc4, #45b7d1);
            border-radius: 1px;
        }

        .team-list {
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
            padding-right: 10px;
        }

        .team-list::-webkit-scrollbar {
            width: 8px;
        }

        .team-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .team-list::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #4ecdc4, #45b7d1);
            border-radius: 4px;
        }

        .team-item {
            padding: 15px 20px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .team-item:hover {
            background: rgba(78, 205, 196, 0.1);
            border-color: #4ecdc4;
            transform: translateX(5px);
        }

        .team-item::before {
            content: '🎮';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }

        .team-item {
            padding-left: 45px;
        }

        .controls {
            text-align: center;
            margin: 30px 0;
        }

        .btn {
            font-family: 'Orbitron', monospace;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 1px;
            margin: 0 10px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
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

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #495057);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .result-area {
            margin-top: 30px;
            padding: 25px;
            background: rgba(26, 26, 46, 0.8);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .result-area h3 {
            font-family: 'Orbitron', monospace;
            color: #4ecdc4;
            margin-bottom: 15px;
            text-align: center;
        }

        .success {
            color: #4ecdc4;
            display: flex;
            align-items: center;
        }

        .success::before {
            content: '✅';
            margin-right: 8px;
        }

        .error {
            color: #ff6b6b;
            display: flex;
            align-items: center;
        }

        .error::before {
            content: '❌';
            margin-right: 8px;
        }

        .result-area ul {
            list-style: none;
            padding: 0;
        }

        .result-area li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .result-area li:last-child {
            border-bottom: none;
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #4ecdc4;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .btn {
                padding: 12px 25px;
                font-size: 1rem;
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <!-- Particles will be generated by JavaScript -->
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> TOURNAMENT CONTROL CENTER</h1>
            <div class="subtitle">ADD TEAMS TO BATTLE</div>
        </div>
        
        <div class="game-panel">
            <div class="info-box">
                <div class="info-item">
                    <i class="fas fa-gamepad"></i>
                    <strong>Tournament:</strong> <?php echo htmlspecialchars($tournament_url); ?>
                </div>
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <strong>Total Teams:</strong> <?php echo count($team_names); ?> Teams Ready for Battle
                </div>
                <div class="info-item">
                    <i class="fas fa-cog"></i>
                    <a href="challonge_config.php" class="info-link">⚙️ Configure Challonge API Settings</a>
                </div>
            </div>
            
            <h2><i class="fas fa-list"></i> REGISTERED TEAMS</h2>
            <div class="team-list">
                <?php foreach ($team_names as $index => $team): ?>
                    <div class="team-item" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <?php echo htmlspecialchars($team); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="controls">
                <button id="addTeamsBtn" class="btn">
                    <i class="fas fa-rocket"></i> DEPLOY TEAMS TO TOURNAMENT
                </button>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> RETURN TO BASE
                </a>
            </div>
            
            <div id="resultArea" class="result-area" style="display: none;">
                <h3><i class="fas fa-chart-bar"></i> DEPLOYMENT RESULTS</h3>
                <div id="resultContent"></div>
            </div>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const particles = document.querySelector('.particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
                particles.appendChild(particle);
            }
        }

        // Initialize particles
        createParticles();

        // Add teams functionality
        document.getElementById('addTeamsBtn').addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<div class="loading"></div>INITIALIZING DEPLOYMENT...';
            
            fetch('process_challonge_teams.php')
                .then(response => response.json())
                .then(data => {
                    const resultArea = document.getElementById('resultArea');
                    const resultContent = document.getElementById('resultContent');
                    
                    resultArea.style.display = 'block';
                    
                    if (data.success) {
                        let html = '<p class="success">Teams successfully deployed to tournament arena!</p>';
                        html += '<ul>';
                        
                        data.results.forEach(result => {
                            let status = result.success ? 'success' : 'error';
                            html += `<li class="${status}">${result.team}: ${result.message}</li>`;
                        });
                        
                        html += '</ul>';
                        resultContent.innerHTML = html;
                    } else {
                        resultContent.innerHTML = `<p class="error">Deployment failed: ${data.message}</p>`;
                    }
                    
                    this.innerHTML = '<i class="fas fa-rocket"></i> DEPLOY TEAMS TO TOURNAMENT';
                    this.disabled = false;
                })
                .catch(error => {
                    document.getElementById('resultArea').style.display = 'block';
                    document.getElementById('resultContent').innerHTML = '<p class="error">Connection error - Mission aborted</p>';
                    console.error(error);
                    
                    this.innerHTML = '<i class="fas fa-rocket"></i> DEPLOY TEAMS TO TOURNAMENT';
                    this.disabled = false;
                });
        });

        // Add hover sound effect simulation
        document.querySelectorAll('.btn, .team-item').forEach(element => {
            element.addEventListener('mouseenter', function() {
                // You can add actual sound effects here
                this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            });
        });
    </script>
</body>
</html>