<?php
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "
SELECT t.team_name, tm.tournament_name
FROM teams t
JOIN tournaments tm ON t.tournament_id = tm.id
ORDER BY tm.tournament_name, t.team_name
";

$result = $conn->query($sql);

$teams_by_tournament = [];
$total_teams = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teams_by_tournament[$row['tournament_name']][] = $row['team_name'];
        $total_teams++;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Dashboard - Gaming Tournament</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(120, 219, 226, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            z-index: -2;
        }

        /* Circuit pattern overlay */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(90deg, transparent 98%, rgba(0, 255, 255, 0.03) 100%),
                linear-gradient(0deg, transparent 98%, rgba(0, 255, 255, 0.03) 100%);
            background-size: 50px 50px;
            z-index: -1;
            animation: circuitMove 20s linear infinite;
        }

        @keyframes circuitMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .main-title {
            font-family: 'Orbitron', monospace;
            font-size: 3.5em;
            font-weight: 900;
            background: linear-gradient(45deg, #00ffff, #ff00ff, #ffff00, #00ffff);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 4s ease infinite;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            margin-bottom: 10px;
            position: relative;
        }

        .main-title::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(0, 255, 255, 0.1), transparent);
            animation: scan 2s linear infinite;
            z-index: -1;
        }

        @keyframes scan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .subtitle {
            font-size: 1.2em;
            color: #00ffff;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.8;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .stat-item {
            background: rgba(0, 255, 255, 0.1);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 15px;
            padding: 20px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-width: 150px;
            transition: all 0.3s ease;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .stat-item:hover::before {
            left: 100%;
        }

        .stat-item:hover {
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.4);
            transform: translateY(-5px);
        }

        .stat-number {
            font-family: 'Orbitron', monospace;
            font-size: 2.5em;
            font-weight: 700;
            color: #00ffff;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #ffffff;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Navigation */
        .nav-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: linear-gradient(135deg, rgba(255, 0, 150, 0.8), rgba(0, 150, 255, 0.8));
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 150, 255, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
            text-decoration: none;
            color: white;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn.primary {
            background: linear-gradient(135deg, #00ff87, #60efff);
            color: #000;
        }

        .nav-btn.primary:hover {
            color: #000;
            box-shadow: 0 10px 25px rgba(0, 255, 135, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            border: 2px dashed rgba(255, 255, 255, 0.2);
            margin-top: 40px;
        }

        .empty-icon {
            font-size: 4em;
            color: rgba(255, 255, 255, 0.3);
            margin-bottom: 20px;
        }

        .empty-text {
            font-size: 1.5em;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 30px;
        }

        /* Tournament Cards */
        .tournament-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .tournament-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(0, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .tournament-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff006e, #8338ec, #3a86ff, #06ffa5);
            background-size: 300% 100%;
            animation: gradientMove 3s ease infinite;
        }

        @keyframes gradientMove {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .tournament-card:hover {
            transform: translateY(-10px);
            border-color: rgba(0, 255, 255, 0.6);
            box-shadow: 
                0 20px 40px rgba(0, 255, 255, 0.2),
                inset 0 0 50px rgba(0, 255, 255, 0.1);
        }

        .tournament-title {
            font-family: 'Orbitron', monospace;
            font-size: 1.8em;
            font-weight: 700;
            color: #00ffff;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .tournament-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ff006e, #8338ec);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            color: white;
        }

        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .team-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .team-card:hover {
            border-color: #ff00ff;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 0, 255, 0.3);
        }

        .team-card:hover::before {
            left: 100%;
        }

        .team-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 1.1em;
            margin-bottom: 8px;
        }

        .team-rank {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #000;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .team-count {
            background: rgba(0, 255, 255, 0.2);
            color: #00ffff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
            border: 1px solid rgba(0, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2.5em;
            }

            .tournament-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .teams-grid {
                grid-template-columns: 1fr;
            }

            .nav-container {
                flex-direction: column;
                align-items: center;
            }

            .stats-bar {
                flex-direction: column;
                align-items: center;
            }

            .container {
                padding: 15px;
            }
        }

        /* Loading Animation */
        .loading-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .loading-animation.active {
            opacity: 1;
            pointer-events: all;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(0, 255, 255, 0.3);
            border-top: 3px solid #00ffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Glitch Effect */
        .glitch {
            position: relative;
            animation: glitch 2s infinite;
        }

        @keyframes glitch {
            0%, 90%, 100% { transform: translate(0); }
            10% { transform: translate(-1px, -1px); }
            20% { transform: translate(1px, 1px); }
            30% { transform: translate(-1px, 1px); }
            40% { transform: translate(1px, -1px); }
            50% { transform: translate(-1px, -1px); }
            60% { transform: translate(1px, 1px); }
            70% { transform: translate(-1px, 1px); }
            80% { transform: translate(1px, -1px); }
        }
    </style>
</head>
<body>
    <div class="loading-animation" id="loadingAnimation">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <div class="header">
            <h1 class="main-title glitch">Tournament DASHBOARD</h1>
            <p class="subtitle">Gaming Tournament Management System</p>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($teams_by_tournament); ?></span>
                <span class="stat-label">Tournaments</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_teams; ?></span>
                <span class="stat-label">Total Teams</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">
                    <?php echo $total_teams > 0 ? number_format($total_teams / max(count($teams_by_tournament), 1), 1) : 0; ?>
                </span>
                <span class="stat-label">Avg per Tournament</span>
            </div>
        </div>

        <div class="nav-container">
            <a href="add_team.php" class="nav-btn primary">
                <i class="fas fa-plus-circle"></i>
                Add New Team
            </a>
            <a href="select_tournament.php" class="nav-btn">
                <i class="fas fa-rocket"></i>
                Deploy to Challonge
            </a>
        </div>

        <?php if (empty($teams_by_tournament)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="empty-text">No Teams Registered Yet</div>
                <p style="color: rgba(255,255,255,0.5); margin-bottom: 30px;">
                    Start building your tournament by adding the first team!
                </p>
                <a href="add_team.php" class="nav-btn primary">
                    <i class="fas fa-plus-circle"></i>
                    Create First Team
                </a>
            </div>
        <?php else: ?>
            <div class="tournament-grid">
                <?php foreach ($teams_by_tournament as $tournament => $teams): ?>
                    <div class="tournament-card">
                        <div class="tournament-title">
                            <div class="tournament-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <?= htmlspecialchars($tournament) ?>
                        </div>
                        
                        <div class="team-count">
                            <i class="fas fa-users"></i>
                            <?= count($teams) ?> Teams Registered
                        </div>

                        <div class="teams-grid">
                            <?php foreach ($teams as $index => $team): ?>
                                <div class="team-card">
                                    <div class="team-name">
                                        <i class="fas fa-flag"></i>
                                        <?= htmlspecialchars($team) ?>
                                    </div>
                                    <div class="team-rank">
                                        Rank #<?= $index + 1 ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Page loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const loading = document.getElementById('loadingAnimation');
            
            // Show loading briefly for effect
            loading.classList.add('active');
            setTimeout(() => {
                loading.classList.remove('active');
            }, 1000);

            // Add entrance animations to cards
            const cards = document.querySelectorAll('.tournament-card, .team-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 1200 + (index * 100));
            });

            // Add hover sound effect simulation
            const teamCards = document.querySelectorAll('.team-card');
            teamCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    // Visual feedback for hover
                    this.style.transition = 'all 0.2s ease';
                });
            });

            // Navigation with loading
            const navBtns = document.querySelectorAll('.nav-btn');
            navBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    loading.classList.add('active');
                    // Let the navigation proceed naturally
                });
            });

            // Add particle effect on tournament cards
            const tournamentCards = document.querySelectorAll('.tournament-card');
            tournamentCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    createParticles(this);
                });
            });
        });

        function createParticles(element) {
            for (let i = 0; i < 5; i++) {
                const particle = document.createElement('div');
                particle.style.position = 'absolute';
                particle.style.width = '4px';
                particle.style.height = '4px';
                particle.style.background = '#00ffff';
                particle.style.borderRadius = '50%';
                particle.style.pointerEvents = 'none';
                particle.style.zIndex = '1000';
                
                const rect = element.getBoundingClientRect();
                particle.style.left = Math.random() * rect.width + 'px';
                particle.style.top = Math.random() * rect.height + 'px';
                
                element.appendChild(particle);
                
                // Animate particle
                particle.animate([
                    { 
                        transform: 'translate(0, 0) scale(1)', 
                        opacity: 1 
                    },
                    { 
                        transform: `translate(${Math.random() * 100 - 50}px, ${Math.random() * 100 - 50}px) scale(0)`, 
                        opacity: 0 
                    }
                ], {
                    duration: 1000 + Math.random() * 500,
                    easing: 'ease-out'
                }).onfinish = () => {
                    particle.remove();
                };
            }
        }

        // Random glitch effect on title
        setInterval(() => {
            const title = document.querySelector('.main-title');
            if (Math.random() < 0.1) { // 10% chance every interval
                title.style.textShadow = `
                    ${Math.random() * 10 - 5}px ${Math.random() * 10 - 5}px 0 #ff00ff,
                    ${Math.random() * 10 - 5}px ${Math.random() * 10 - 5}px 0 #00ffff
                `;
                setTimeout(() => {
                    title.style.textShadow = '0 0 30px rgba(0, 255, 255, 0.5)';
                }, 150);
            }
        }, 2000);
    </script>
</body>
</html>