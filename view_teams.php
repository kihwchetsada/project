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
    <link rel="icon" type="image/png" href="img/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/view_teams.css">
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
                                        Number <?= $index + 1 ?>
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