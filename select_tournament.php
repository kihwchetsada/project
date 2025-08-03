<?php
// ดึงทัวร์นาเมนต์ทั้งหมดจากฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tournament_registration");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tournaments = $conn->query("SELECT t.id, t.tournament_name, COUNT(tm.id) as team_count 
                           FROM tournaments t 
                           LEFT JOIN teams tm ON t.id = tm.tournament_id 
                           GROUP BY t.id, t.tournament_name 
                           ORDER BY t.tournament_name");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Deployment - Challonge Integration</title>
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
                radial-gradient(circle at 25% 25%, rgba(255, 0, 150, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(0, 150, 255, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(120, 219, 226, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            z-index: -2;
        }

        /* Matrix-like animation */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(90deg, transparent 98%, rgba(0, 255, 255, 0.03) 100%),
                linear-gradient(0deg, transparent 98%, rgba(255, 0, 255, 0.03) 100%);
            background-size: 40px 40px;
            z-index: -1;
            animation: matrixFlow 15s linear infinite;
        }

        @keyframes matrixFlow {
            0% { transform: translate(0, 0); }
            100% { transform: translate(40px, 40px); }
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .main-title {
            font-family: 'Orbitron', monospace;
            font-size: 3em;
            font-weight: 900;
            background: linear-gradient(45deg, #ff006e, #8338ec, #3a86ff, #06ffa5);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientShift 4s ease infinite;
            text-shadow: 0 0 30px rgba(255, 0, 110, 0.5);
            margin-bottom: 15px;
            position: relative;
        }

        .main-title::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 0, 110, 0.1), transparent);
            animation: titleScan 3s linear infinite;
            z-index: -1;
        }

        @keyframes titleScan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .subtitle {
            font-size: 1.3em;
            color: #00ffff;
            text-transform: uppercase;
            letter-spacing: 3px;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1em;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Form Container */
        .form-container {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 25px;
            padding: 50px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(15px);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                inset 0 0 50px rgba(0, 255, 255, 0.1);
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff006e, #8338ec, #3a86ff, #06ffa5);
            background-size: 300% 100%;
            animation: borderFlow 3s ease infinite;
        }

        @keyframes borderFlow {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .form-container:hover {
            border-color: rgba(0, 255, 255, 0.6);
            box-shadow: 
                0 25px 50px rgba(0, 255, 255, 0.2),
                inset 0 0 50px rgba(0, 255, 255, 0.2);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 40px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 1.3em;
            font-weight: 600;
            color: #00ffff;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .form-label::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, #ff006e, #00ffff);
        }

        .select-wrapper {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
        }

        .tournament-select {
            width: 100%;
            padding: 20px 25px;
            font-size: 1.2em;
            font-family: 'Kanit', sans-serif;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            position: relative;
            z-index: 2;
        }

        .tournament-select:focus {
            outline: none;
            border-color: #00ffff;
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 
                0 0 20px rgba(0, 255, 255, 0.3),
                inset 0 0 20px rgba(0, 255, 255, 0.1);
        }

        .tournament-select option {
            background: #1a1a2e;
            color: #ffffff;
            padding: 10px;
        }

        .select-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #00ffff;
            font-size: 1.5em;
            pointer-events: none;
            z-index: 3;
            transition: transform 0.3s ease;
        }

        .select-wrapper:hover .select-icon {
            transform: translateY(-50%) rotate(180deg);
        }

        /* Tournament Info Cards */
        .tournament-info {
            margin-top: 20px;
            padding: 20px;
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 12px;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .tournament-info.active {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            text-align: center;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-number {
            font-family: 'Orbitron', monospace;
            font-size: 2em;
            font-weight: 700;
            color: #ff006e;
            display: block;
            margin-bottom: 5px;
        }

        .info-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, #ff006e, #8338ec);
            color: white;
            border: none;
            padding: 20px 40px;
            border-radius: 15px;
            font-size: 1.3em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            width: 100%;
            font-family: 'Orbitron', monospace;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 15px 30px rgba(255, 0, 110, 0.4),
                0 0 40px rgba(131, 56, 236, 0.3);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(-2px);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Navigation */
        .nav-container {
            text-align: center;
            margin-top: 30px;
        }

        .nav-link {
            color: #00ffff;
            text-decoration: none;
            font-weight: 500;
            padding: 12px 25px;
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link:hover {
            background: rgba(0, 255, 255, 0.1);
            border-color: #00ffff;
            text-decoration: none;
            color: #00ffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 255, 0.3);
        }

        /* Loading State */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(0, 255, 255, 0.3);
            border-top: 3px solid #00ffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        .loading-text {
            color: #00ffff;
            font-size: 1.2em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .main-title {
                font-size: 2.2em;
            }

            .form-container {
                padding: 30px 25px;
            }

            .tournament-select {
                padding: 15px 20px;
                font-size: 1.1em;
            }

            .submit-btn {
                padding: 18px 30px;
                font-size: 1.1em;
            }

            .info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Particle Animation */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #00ffff;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
        <div class="loading-text">Deploying Tournament...</div>
    </div>

    <div class="container">
        <div class="header">
            <h1 class="main-title">TOURNAMENT DEPLOYMENT</h1>
            <p class="subtitle">Challonge Integration System</p>
            <p class="description">
                เลือกทัวร์นาเมนต์ที่ต้องการส่งทีมเข้าระบบ Challonge 
                เพื่อเริ่มการแข่งขันอย่างเป็นทางการ
            </p>
        </div>

        <div class="form-container">
            <form action="submit_to_challonge.php" method="post" id="deploymentForm">
                <div class="form-group">
                    <label for="tournament_id" class="form-label">
                        <i class="fas fa-trophy"></i>
                        Select Tournament
                    </label>
                    <div class="select-wrapper">
                        <select name="tournament_id" id="tournament_id" class="tournament-select" required>
                            <option value="">-- Choose Tournament to Deploy --</option>
                            <?php if ($tournaments && $tournaments->num_rows > 0): ?>
                                <?php while ($row = $tournaments->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>" data-teams="<?= $row['team_count'] ?>">
                                        <?= htmlspecialchars($row['tournament_name']) ?> 
                                        (<?= $row['team_count'] ?> teams)
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No tournaments available</option>
                            <?php endif; ?>
                        </select>
                        <i class="fas fa-chevron-down select-icon"></i>
                    </div>

                    <div class="tournament-info" id="tournamentInfo">
                        <h4 style="color: #00ffff; margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i>
                            Tournament Details
                        </h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-number" id="teamCount">0</span>
                                <span class="info-label">Teams</span>
                            </div>
                            <div class="info-item">
                                <span class="info-number" id="matchCount">0</span>
                                <span class="info-label">Matches</span>
                            </div>
                            <div class="info-item">
                                <span class="info-number" id="roundCount">0</span>
                                <span class="info-label">Rounds</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    <i class="fas fa-rocket"></i>
                    Deploy to Challonge
                </button>
            </form>

            <div class="nav-container">
                <a href="view_teams.php" class="nav-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Teams
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tournamentSelect = document.getElementById('tournament_id');
            const tournamentInfo = document.getElementById('tournamentInfo');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('deploymentForm');
            const loading = document.getElementById('loading');

            // Handle tournament selection
            tournamentSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const teamCount = parseInt(selectedOption.dataset.teams) || 0;

                if (this.value && teamCount > 0) {
                    showTournamentInfo(teamCount);
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                } else {
                    hideTournamentInfo();
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                }
            });

            function showTournamentInfo(teamCount) {
                const matchCount = teamCount > 1 ? teamCount - 1 : 0;
                const roundCount = teamCount > 1 ? Math.ceil(Math.log2(teamCount)) : 0;

                document.getElementById('teamCount').textContent = teamCount;
                document.getElementById('matchCount').textContent = matchCount;
                document.getElementById('roundCount').textContent = roundCount;

                tournamentInfo.classList.add('active');

                // Add warning if not enough teams
                if (teamCount < 2) {
                    showWarning('ต้องมีอย่างน้อย 2 ทีมเพื่อสร้างทัวร์นาเมนต์');
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                }
            }

            function hideTournamentInfo() {
                tournamentInfo.classList.remove('active');
            }

            function showWarning(message) {
                // Remove existing warning
                const existingWarning = document.querySelector('.warning-message');
                if (existingWarning) {
                    existingWarning.remove();
                }

                const warning = document.createElement('div');
                warning.className = 'warning-message';
                warning.style.cssText = `
                    background: rgba(255, 193, 7, 0.2);
                    border: 1px solid rgba(255, 193, 7, 0.5);
                    color: #ffc107;
                    padding: 15px;
                    border-radius: 10px;
                    margin-top: 15px;
                    text-align: center;
                    font-weight: 500;
                `;
                warning.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
                
                tournamentInfo.appendChild(warning);
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                const selectedTeams = parseInt(document.getElementById('teamCount').textContent);
                
                if (selectedTeams < 2) {
                    e.preventDefault();
                    alert('ไม่สามารถสร้างทัวร์นาเมนต์ได้ เนื่องจากมีทีมน้อยกว่า 2 ทีม');
                    return;
                }

                // Show loading
                loading.classList.add('active');
                
                // Add deployment sound effect (visual feedback)
                createDeploymentEffect();
            });

            function createDeploymentEffect() {
                const container = document.querySelector('.form-container');
                
                // Create particles
                for (let i = 0; i < 20; i++) {
                    setTimeout(() => {
                        createParticle(container);
                    }, i * 100);
                }
            }

            function createParticle(container) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const rect = container.getBoundingClientRect();
                particle.style.left = Math.random() * rect.width + 'px';
                particle.style.top = Math.random() * rect.height + 'px';
                
                container.appendChild(particle);
                
                // Animate particle
                particle.animate([
                    { 
                        transform: 'translate(0, 0) scale(1)', 
                        opacity: 1 
                    },
                    { 
                        transform: `translate(${Math.random() * 200 - 100}px, ${Math.random() * 200 - 100}px) scale(0)`, 
                        opacity: 0 
                    }
                ], {
                    duration: 1500,
                    easing: 'ease-out'
                }).onfinish = () => {
                    particle.remove();
                };
            }

            // Entrance animation
            setTimeout(() => {
                document.querySelector('.form-container').style.animation = 'slideUp 0.8s ease-out';
            }, 300);

            // Add scan line effect on hover
            const formContainer = document.querySelector('.form-container');
            formContainer.addEventListener('mouseenter', function() {
                if (!this.querySelector('.scan-line')) {
                    const scanLine = document.createElement('div');
                    scanLine.className = 'scan-line';
                    scanLine.style.cssText = `
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 2px;
                        background: linear-gradient(90deg, transparent, #00ffff, transparent);
                        animation: scanMove 2s ease-in-out infinite;
                        z-index: 10;
                    `;
                    this.appendChild(scanLine);
                }
            });
        });

        // Add scan animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes scanMove {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(100vh); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>