// ตัวแปรหลักสำหรับระบบทัวร์นาเมนต์
let tournamentData = {
    name: '',
    participants: [],
    matches: [],
    currentRound: 0,
    isActive: false
};

// เริ่มต้นระบบทัวร์นาเมนต์
function initTournament() {
    console.log('Initializing tournament system');
    
    // โหลดข้อมูลที่บันทึกไว้ก่อน (ถ้ามี)
    loadSavedTournamentData();
    
    // ดึงข้อมูลการแข่งขันจาก DOM
    fetchTournamentData();
    
    // เพิ่ม Event Listener
    addEventListeners();
    
    // แสดงข้อมูลทัวร์นาเมนต์ในหน้าจอ
    displayTournamentInfo();
    
    console.log('Tournament system initialized successfully');
}

// โหลดข้อมูลที่บันทึกไว้ก่อนหน้า
function loadSavedTournamentData() {
    try {
        const savedData = localStorage.getItem('tournamentData');
        if (savedData) {
            tournamentData = JSON.parse(savedData);
            console.log('Loaded saved tournament data:', tournamentData);
        } else {
            console.log('No saved tournament data found');
        }
    } catch (error) {
        console.error('Error loading saved tournament data:', error);
    }
}

// ดึงข้อมูลการแข่งขันจาก DOM
function fetchTournamentData() {
    const nameInput = document.getElementById('tournament-name');
    if (nameInput && !tournamentData.name) {
        tournamentData.name = nameInput.value || 'การแข่งขันใหม่';
    }
    
    // หากมีข้อมูลผู้เข้าแข่งขันในฟอร์ม
    const participantsList = document.getElementById('participants-list');
    if (participantsList && tournamentData.participants.length === 0) {
        const participants = participantsList.querySelectorAll('li');
        participants.forEach(participant => {
            tournamentData.participants.push({
                id: generateUniqueId(),
                name: participant.textContent.trim(),
                score: 0,
                status: 'active'
            });
        });
    }
    
    console.log('Tournament data fetched:', tournamentData);
}

// เพิ่ม Event Listener ให้กับปุ่มต่างๆ
function addEventListeners() {
    // ปุ่มเพิ่มผู้เข้าแข่งขัน
    const addParticipantBtn = document.getElementById('add-participant-btn');
    if (addParticipantBtn) {
        addParticipantBtn.addEventListener('click', addParticipant);
    }
    
    // ปุ่มเริ่มการแข่งขัน
    const startTournamentBtn = document.getElementById('start-tournament-btn');
    if (startTournamentBtn) {
        startTournamentBtn.addEventListener('click', startTournament);
    }
    
    // ปุ่มบันทึกผลการแข่งขัน
    const saveResultsBtn = document.getElementById('save-results-btn');
    if (saveResultsBtn) {
        saveResultsBtn.addEventListener('click', saveMatchResults);
    }
    
    // ปุ่มไปยังรอบถัดไป
    const nextRoundBtn = document.getElementById('next-round-btn');
    if (nextRoundBtn) {
        nextRoundBtn.addEventListener('click', advanceToNextRound);
    }
    
    // ปุ่มรีเซ็ตการแข่งขัน
    const resetTournamentBtn = document.getElementById('reset-tournament-btn');
    if (resetTournamentBtn) {
        resetTournamentBtn.addEventListener('click', resetTournament);
    }
    
    console.log('Event listeners added');
}

// เพิ่มผู้เข้าแข่งขัน
function addParticipant() {
    const participantInput = document.getElementById('participant-name');
    if (!participantInput || !participantInput.value.trim()) {
        alert('กรุณาระบุชื่อผู้เข้าแข่งขัน');
        return;
    }
    
    const newParticipant = {
        id: generateUniqueId(),
        name: participantInput.value.trim(),
        score: 0,
        status: 'active'
    };
    
    tournamentData.participants.push(newParticipant);
    participantInput.value = '';
    
    // แสดงรายชื่อผู้เข้าแข่งขันที่เพิ่ม
    displayParticipants();
    
    // บันทึกข้อมูล
    saveTournamentData();
    
    console.log('Added new participant:', newParticipant);
}

// เริ่มต้นการแข่งขัน
function startTournament() {
    if (tournamentData.participants.length < 2) {
        alert('ต้องมีผู้เข้าแข่งขันอย่างน้อย 2 คนเพื่อเริ่มการแข่งขัน');
        return;
    }
    
    // สลับลำดับผู้เล่นแบบสุ่ม
    shuffleParticipants();
    
    // กำหนดค่าเริ่มต้น
    tournamentData.currentRound = 1;
    tournamentData.isActive = true;
    tournamentData.matches = [];
    
    // จัดคู่แข่งขันรอบแรก
    createMatchesForCurrentRound();
    
    // แสดงคู่แข่งขัน
    displayMatches();
    
    // อัพเดตสถานะการแข่งขัน
    updateTournamentStatus();
    
    // บันทึกข้อมูล
    saveTournamentData();
    
    console.log('Tournament started, first round matches created');
}

// สลับลำดับผู้เล่นแบบสุ่ม
function shuffleParticipants() {
    for (let i = tournamentData.participants.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [tournamentData.participants[i], tournamentData.participants[j]] = 
        [tournamentData.participants[j], tournamentData.participants[i]];
    }
}

// สร้างคู่แข่งขันสำหรับรอบปัจจุบัน
function createMatchesForCurrentRound() {
    const activeParticipants = tournamentData.participants.filter(p => p.status === 'active');
    
    // ถ้าเหลือผู้เล่นคนเดียว จบการแข่งขัน
    if (activeParticipants.length === 1) {
        announceWinner(activeParticipants[0]);
        return;
    }
    
    // จัดคู่แข่งขัน
    const roundMatches = [];
    for (let i = 0; i < activeParticipants.length; i += 2) {
        // ถ้าจำนวนผู้เล่นคี่ คนสุดท้ายจะได้ผ่านเข้ารอบโดยอัตโนมัติ
        if (i + 1 >= activeParticipants.length) {
            const byeParticipant = activeParticipants[i];
            byeParticipant.score += 1; // ให้คะแนนสำหรับการผ่านรอบ
            
            roundMatches.push({
                id: generateUniqueId(),
                round: tournamentData.currentRound,
                participant1: byeParticipant.id,
                participant2: null,
                score1: 1,
                score2: 0,
                winner: byeParticipant.id,
                status: 'completed',
                isBye: true
            });
            
            continue;
        }
        
        roundMatches.push({
            id: generateUniqueId(),
            round: tournamentData.currentRound,
            participant1: activeParticipants[i].id,
            participant2: activeParticipants[i + 1].id,
            score1: 0,
            score2: 0,
            winner: null,
            status: 'pending'
        });
    }
    
    // เพิ่มการแข่งขันของรอบนี้
    tournamentData.matches = [...tournamentData.matches, ...roundMatches];
}

// บันทึกผลการแข่งขัน
function saveMatchResults() {
    const pendingMatches = tournamentData.matches.filter(
        m => m.round === tournamentData.currentRound && m.status === 'pending'
    );
    
    if (pendingMatches.length === 0) {
        alert('ไม่มีการแข่งขันที่รอผลในรอบนี้');
        return;
    }
    
    let allMatchesComplete = true;
    
    // ดึงผลการแข่งขันจากฟอร์ม
    pendingMatches.forEach(match => {
        const scoreInput1 = document.getElementById(`score-${match.id}-1`);
        const scoreInput2 = document.getElementById(`score-${match.id}-2`);
        
        if (scoreInput1 && scoreInput2) {
            const score1 = parseInt(scoreInput1.value) || 0;
            const score2 = parseInt(scoreInput2.value) || 0;
            
            // ต้องมีผู้ชนะเสมอ
            if (score1 === score2) {
                alert(`การแข่งขันระหว่าง ${getParticipantName(match.participant1)} และ ${getParticipantName(match.participant2)} ต้องมีผู้ชนะ`);
                allMatchesComplete = false;
                return;
            }
            
            match.score1 = score1;
            match.score2 = score2;
            match.winner = score1 > score2 ? match.participant1 : match.participant2;
            match.status = 'completed';
            
            // เพิ่มคะแนนให้ผู้ชนะ
            const winner = tournamentData.participants.find(p => p.id === match.winner);
            if (winner) {
                winner.score += 1;
            }
        } else {
            allMatchesComplete = false;
        }
    });
    
    if (!allMatchesComplete) {
        return;
    }
    
    // อัพเดตสถานะของผู้เล่นที่แพ้
    updateParticipantStatus();
    
    // บันทึกข้อมูล
    saveTournamentData();
    
    // อัพเดตการแสดงผล
    displayMatches();
    displayParticipants();
    
    // เปิดปุ่มไปรอบถัดไป
    const nextRoundBtn = document.getElementById('next-round-btn');
    if (nextRoundBtn) {
        nextRoundBtn.disabled = false;
    }
    
    console.log('Match results saved for round', tournamentData.currentRound);
}

// อัพเดตสถานะของผู้เล่น (ชนะ/แพ้)
function updateParticipantStatus() {
    const currentRoundMatches = tournamentData.matches.filter(
        m => m.round === tournamentData.currentRound && m.status === 'completed'
    );
    
    currentRoundMatches.forEach(match => {
        if (match.isBye) return; // ข้ามถ้าเป็นการผ่านรอบอัตโนมัติ
        
        // ผู้แพ้จะถูกเปลี่ยนสถานะเป็น 'eliminated'
        const loserId = match.winner === match.participant1 ? match.participant2 : match.participant1;
        const loser = tournamentData.participants.find(p => p.id === loserId);
        
        if (loser) {
            loser.status = 'eliminated';
        }
    });
}

// ดำเนินการไปรอบถัดไป
function advanceToNextRound() {
    // ตรวจสอบว่าการแข่งขันในรอบปัจจุบันเสร็จสิ้นหรือไม่
    const pendingMatches = tournamentData.matches.filter(
        m => m.round === tournamentData.currentRound && m.status === 'pending'
    );
    
    if (pendingMatches.length > 0) {
        alert('กรุณาบันทึกผลการแข่งขันในรอบปัจจุบันให้ครบก่อน');
        return;
    }
    
    // นับจำนวนผู้เล่นที่ยังอยู่ในการแข่งขัน
    const activeParticipants = tournamentData.participants.filter(p => p.status === 'active');
    
    if (activeParticipants.length <= 1) {
        if (activeParticipants.length === 1) {
            announceWinner(activeParticipants[0]);
        } else {
            alert('ไม่มีผู้เล่นที่เหลืออยู่ในการแข่งขัน');
        }
        return;
    }
    
    // เพิ่มรอบการแข่งขัน
    tournamentData.currentRound += 1;
    
    // สร้างคู่แข่งขันสำหรับรอบใหม่
    createMatchesForCurrentRound();
    
    // อัพเดตการแสดงผล
    displayMatches();
    updateTournamentStatus();
    
    // บันทึกข้อมูล
    saveTournamentData();
    
    console.log('Advanced to round', tournamentData.currentRound);
}

// ประกาศผู้ชนะ
function announceWinner(winner) {
    tournamentData.isActive = false;
    
    alert(`ยินดีด้วย! ${winner.name} ชนะการแข่งขัน!`);
    
    // แสดงผู้ชนะบนหน้าเว็บ
    const winnerElement = document.getElementById('tournament-winner');
    if (winnerElement) {
        winnerElement.textContent = winner.name;
        winnerElement.parentElement.classList.remove('hidden');
    }
    
    console.log('Tournament completed, winner:', winner);
}

// รีเซ็ตการแข่งขัน
function resetTournament() {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะรีเซ็ตการแข่งขันทั้งหมด?')) {
        return;
    }
    
    tournamentData = {
        name: tournamentData.name,
        participants: [],
        matches: [],
        currentRound: 0,
        isActive: false
    };
    
    // บันทึกข้อมูล
    saveTournamentData();
    
    // รีเฟรชหน้า
    location.reload();
    
    console.log('Tournament reset');
}

// แสดงข้อมูลทัวร์นาเมนต์ในหน้าจอ
function displayTournamentInfo() {
    const nameElement = document.getElementById('display-tournament-name');
    if (nameElement) {
        nameElement.textContent = tournamentData.name;
    }
    
    displayParticipants();
    displayMatches();
    updateTournamentStatus();
}

// แสดงรายชื่อผู้เข้าแข่งขัน
function displayParticipants() {
    const participantsContainer = document.getElementById('participants-container');
    if (!participantsContainer) return;
    
    participantsContainer.innerHTML = '';
    
    if (tournamentData.participants.length === 0) {
        participantsContainer.innerHTML = '<p>ยังไม่มีผู้เข้าแข่งขัน</p>';
        return;
    }
    
    const participantsList = document.createElement('ul');
    participantsList.className = 'participants-list';
    
    tournamentData.participants.forEach(participant => {
        const listItem = document.createElement('li');
        listItem.className = `participant ${participant.status}`;
        listItem.innerHTML = `
            <span class="name">${participant.name}</span>
            <span class="score">คะแนน: ${participant.score}</span>
            <span class="status">${getStatusText(participant.status)}</span>
        `;
        participantsList.appendChild(listItem);
    });
    
    participantsContainer.appendChild(participantsList);
}

// แสดงคู่แข่งขัน
function displayMatches() {
    const matchesContainer = document.getElementById('matches-container');
    if (!matchesContainer) return;
    
    matchesContainer.innerHTML = '';
    
    if (tournamentData.matches.length === 0) {
        matchesContainer.innerHTML = '<p>ยังไม่มีการแข่งขัน</p>';
        return;
    }
    
    // แสดงเฉพาะการแข่งขันในรอบปัจจุบัน
    const currentRoundMatches = tournamentData.matches.filter(
        m => m.round === tournamentData.currentRound
    );
    
    if (currentRoundMatches.length === 0) {
        matchesContainer.innerHTML = '<p>ไม่มีการแข่งขันในรอบนี้</p>';
        return;
    }
    
    const matchesList = document.createElement('div');
    matchesList.className = 'matches-list';
    
    currentRoundMatches.forEach(match => {
        const matchElement = document.createElement('div');
        matchElement.className = `match ${match.status}`;
        
        if (match.isBye) {
            // กรณีผ่านรอบอัตโนมัติ
            const participant = tournamentData.participants.find(p => p.id === match.participant1);
            matchElement.innerHTML = `
                <div class="match-header">คู่ที่ ${currentRoundMatches.indexOf(match) + 1}</div>
                <div class="match-participants">
                    <div class="participant winner">${participant ? participant.name : 'ไม่ระบุ'}</div>
                    <div class="vs">ได้รับการผ่านรอบอัตโนมัติ</div>
                </div>
            `;
        } else {
            // กรณีแข่งขันปกติ
            const participant1 = tournamentData.participants.find(p => p.id === match.participant1);
            const participant2 = tournamentData.participants.find(p => p.id === match.participant2);
            
            const p1Class = match.winner === match.participant1 ? 'winner' : (match.status === 'completed' ? 'loser' : '');
            const p2Class = match.winner === match.participant2 ? 'winner' : (match.status === 'completed' ? 'loser' : '');
            
            matchElement.innerHTML = `
                <div class="match-header">คู่ที่ ${currentRoundMatches.indexOf(match) + 1}</div>
                <div class="match-participants">
                    <div class="participant ${p1Class}">${participant1 ? participant1.name : 'ไม่ระบุ'}</div>
                    <div class="vs">VS</div>
                    <div class="participant ${p2Class}">${participant2 ? participant2.name : 'ไม่ระบุ'}</div>
                </div>
            `;
            
            if (match.status === 'pending') {
                // สร้างฟอร์มบันทึกผลการแข่งขัน
                const scoreForm = document.createElement('div');
                scoreForm.className = 'score-form';
                scoreForm.innerHTML = `
                    <div class="score-inputs">
                        <input type="number" id="score-${match.id}-1" min="0" value="0">
                        <span>:</span>
                        <input type="number" id="score-${match.id}-2" min="0" value="0">
                    </div>
                `;
                matchElement.appendChild(scoreForm);
            } else {
                // แสดงผลการแข่งขัน
                const scoreDisplay = document.createElement('div');
                scoreDisplay.className = 'score-display';
                scoreDisplay.innerHTML = `
                    <div class="scores">
                        <span>${match.score1}</span>
                        <span>:</span>
                        <span>${match.score2}</span>
                    </div>
                `;
                matchElement.appendChild(scoreDisplay);
            }
        }
        
        matchesList.appendChild(matchElement);
    });
    
    matchesContainer.appendChild(matchesList);
}

// อัพเดตสถานะการแข่งขัน
function updateTournamentStatus() {
    const statusElement = document.getElementById('tournament-status');
    if (!statusElement) return;
    
    if (!tournamentData.isActive) {
        if (tournamentData.currentRound === 0) {
            statusElement.textContent = 'ยังไม่เริ่มการแข่งขัน';
        } else {
            statusElement.textContent = 'การแข่งขันเสร็จสิ้นแล้ว';
        }
    } else {
        statusElement.textContent = `กำลังแข่งขันรอบที่ ${tournamentData.currentRound}`;
    }
    
    // ซ่อน/แสดงปุ่มตามสถานะ
    const startBtn = document.getElementById('start-tournament-btn');
    const saveResultsBtn = document.getElementById('save-results-btn');
    const nextRoundBtn = document.getElementById('next-round-btn');
    
    if (startBtn) {
        startBtn.disabled = tournamentData.isActive;
    }
    
    if (saveResultsBtn) {
        saveResultsBtn.disabled = !tournamentData.isActive;
    }
    
    if (nextRoundBtn) {
        const pendingMatches = tournamentData.matches.filter(
            m => m.round === tournamentData.currentRound && m.status === 'pending'
        );
        nextRoundBtn.disabled = !tournamentData.isActive || pendingMatches.length > 0;
    }
}

// บันทึกข้อมูลทัวร์นาเมนต์
function saveTournamentData() {
    try {
        localStorage.setItem('tournamentData', JSON.stringify(tournamentData));
        console.log('Tournament data saved to localStorage');
    } catch (error) {
        console.error('Error saving tournament data:', error);
    }
}

// ฟังก์ชันดึงชื่อผู้เข้าแข่งขันจาก ID
function getParticipantName(id) {
    const participant = tournamentData.participants.find(p => p.id === id);
    return participant ? participant.name : 'ไม่ระบุ';
}

// ฟังก์ชันแปลงสถานะเป็นข้อความภาษาไทย
function getStatusText(status) {
    switch (status) {
        case 'active': return 'กำลังแข่งขัน';
        case 'eliminated': return 'ตกรอบแล้ว';
        default: return status;
    }
}

// สร้าง Unique ID
function generateUniqueId() {
    return Date.now().toString(36) + Math.random().toString(36).substring(2);
}

// เริ่มต้นระบบเมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', initTournament);