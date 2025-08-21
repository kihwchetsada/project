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
    <link rel="stylesheet" href="../css/api.css">
    <title>Tournament Control Center - Add Teams</title>
   
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> TOURNAMENT CONTROL CENTER</h1>
            <div class="subtitle">ADD TEAMS TO TOURNAMENT</div>
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
                    <i class="fas fa-home"></i> RETURN TO DASHBOARD
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