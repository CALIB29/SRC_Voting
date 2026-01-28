<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

// ------ LOGIC 1: Kunin ang ID ng kasalukuyang active na election o ang huling tapos na. ------
$stmt_active = $pdo->query("SELECT id, title, is_active FROM elections WHERE is_active = 1 LIMIT 1");
$active_election = $stmt_active->fetch();
$active_election_id = $active_election ? $active_election['id'] : null;
$election_title = "Election Results";
$is_history = false;

if (!$active_election_id) {
  // Kunin ang huling tapos na election
  $stmt_latest = $pdo->query("SELECT id, title FROM elections WHERE is_active = 0 AND end_datetime IS NOT NULL ORDER BY end_datetime DESC LIMIT 1");
  $latest_election = $stmt_latest->fetch();
  if ($latest_election) {
    $active_election_id = $latest_election['id'];
    $election_title = "Results History: " . $latest_election['title'];
    $is_history = true;
  }
}

// Get user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();


// ------ LOGIC 2: Kunin lang ang mga kandidato para sa ACTIVE na election (with tie-breaker logic). ------
$candidates = [];
if ($active_election_id) {
  $stmt_candidates = $pdo->prepare("
        SELECT c.*, 
        (SELECT MIN(vote_id) FROM votes WHERE candidate_id = c.id) as first_vote_id
        FROM candidates c
        WHERE c.election_id = :election_id
        ORDER BY 
            CASE position
                WHEN 'President' THEN 1 WHEN 'Vice President' THEN 2 WHEN 'Secretary' THEN 3 WHEN 'Treasurer' THEN 4
                WHEN 'Auditor' THEN 5 WHEN 'PRO' THEN 6 WHEN 'Business Manager' THEN 7 WHEN 'Sgt. at Arms' THEN 8
                ELSE 9
            END,
            votes DESC,
            first_vote_id ASC
    ");
  $stmt_candidates->execute(['election_id' => $active_election_id]);
  $candidates = $stmt_candidates->fetchAll(PDO::FETCH_ASSOC);
}


// Group candidates by position
$results_by_position = [];
foreach ($candidates as $candidate) {
  $results_by_position[$candidate['position']][] = $candidate;
}


// ------ LOGIC 3: Kunin lang ang boto ng user para sa ACTIVE na election. ------
$user_voted_candidates = [];
if ($active_election_id) {
  $stmt_votes = $pdo->prepare("
        SELECT v.candidate_id, c.position
        FROM votes v
        JOIN candidates c ON v.candidate_id = c.id
        WHERE v.user_id = ? AND v.election_id = ?
    ");
  $stmt_votes->execute([$user_id, $active_election_id]);
  $user_votes = $stmt_votes->fetchAll(PDO::FETCH_ASSOC);

  foreach ($user_votes as $vote) {
    $user_voted_candidates[$vote['position']] = $vote['candidate_id'];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard | Santa Rita College</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <!-- ANG IYONG BUONG CSS - HINDI GINALAW -->
  <style>
    :root {
      --primary: #3B82F6;
      --primary-light: #eff6ff;
      --primary-dark: #2563eb;
      --secondary: #6366f1;
      --success: #10b981;
      --success-dark: #059669;
      --info: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --light-gray: #e9ecef;
      --border-radius: 12px;
      --border-radius-sm: 8px;
      --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      --box-shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.12);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      --percentage-bar: #4361ee;
      --percentage-bar-bg: #e9ecef;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }

    body {
      background: #f5f7fb;
      color: var(--dark);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }

    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 280px;
      background: white;
      box-shadow: 2px 0 15px rgba(0, 0, 0, 0.03);
      padding: 20px 0;
      position: fixed;
      height: 100vh;
      z-index: 200;
      transition: var(--transition);
      left: 0;
    }

    .logo {
      padding: 20px;
      text-align: center;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
    }

    .logo img {
      max-width: 100%;
      height: auto;
      max-height: 70px;
      margin-bottom: 10px;
    }

    .logo h5 {
      color: var(--primary);
      font-size: 1.1rem;
      font-weight: 600;
      margin-top: 5px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0 15px;
    }

    .sidebar li a {
      display: flex;
      align-items: center;
      padding: 12px 20px;
      color: var(--gray);
      text-decoration: none;
      border-radius: var(--border-radius-sm);
      margin-bottom: 5px;
      font-weight: 500;
    }

    .sidebar li a:hover {
      background: var(--primary-light);
      color: var(--primary);
      transform: translateX(3px);
    }

    .sidebar li a i {
      margin-right: 12px;
      width: 20px;
      text-align: center;
      font-size: 1.1rem;
    }

    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      margin-left: 280px;
      transition: var(--transition);
    }

    .top-bar {
      background: white;
      padding: 18px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.03);
      position: sticky;
      top: 0;
      z-index: 150;
    }

    .page-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--dark);
      display: flex;
      align-items: center;
    }

    .menu-toggle {
      background: none;
      border: none;
      color: var(--dark);
      font-size: 1.5rem;
      cursor: pointer;
      margin-right: 15px;
      display: none;
    }

    .user-info {
      display: flex;
      align-items: center;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      margin-right: 15px;
      font-size: 1rem;
    }

    .logout-btn {
      background: var(--primary);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: var(--border-radius-sm);
      text-decoration: none;
      font-weight: 500;
      display: flex;
      align-items: center;
    }

    .logout-btn i {
      margin-right: 8px;
    }

    .content-area {
      padding: 30px;
      flex: 1;
    }

    .welcome-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .time-date {
      background: white;
      padding: 12px 20px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      font-weight: 500;
      color: var(--gray);
    }

    .position-results {
      background: white;
      border-radius: var(--border-radius);
      padding: 1.5rem;
      box-shadow: var(--box-shadow);
      margin-bottom: 2rem;
    }

    .position-results h3 {
      font-size: 1.25rem;
      color: var(--dark);
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--light-gray);
    }

    .table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }

    th,
    td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid var(--light-gray);
      vertical-align: middle;
    }

    th {
      background: var(--light);
      color: var(--gray);
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
    }

    tr:hover {
      background-color: rgba(79, 70, 229, 0.03);
    }

    .candidate-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .candidate-photo {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--light-gray);
    }

    .percentage-bar-container {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      width: 100%;
    }

    .percentage-bar-wrapper {
      flex-grow: 1;
      background-color: var(--percentage-bar-bg);
      border-radius: 6px;
      height: 12px;
      position: relative;
      overflow: hidden;
    }

    .percentage-bar {
      height: 100%;
      background: var(--percentage-bar);
      border-radius: 6px;
      transition: width 0.5s ease;
      min-width: 0;
    }

    .percentage-value {
      color: var(--dark);
      font-weight: 600;
      min-width: 50px;
      text-align: right;
      font-size: 0.9rem;
    }

    .voted-badge {
      display: inline-block;
      padding: 4px 10px;
      background: var(--success);
      color: white;
      font-size: 0.8rem;
      font-weight: 600;
      border-radius: 6px;
      margin-left: 10px;
    }

    .voted-row {
      border-left: 4px solid var(--success);
      background: linear-gradient(90deg, rgba(76, 201, 240, 0.04), transparent);
    }

    .overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.4);
      z-index: 100;
    }

    .overlay.active {
      display: block;
    }

    @media (max-width: 768px) {
      .menu-toggle {
        display: block;
      }

      .sidebar {
        left: -280px;
        top: 0;
        position: fixed;
        bottom: 0;
        z-index: 300;
      }

      .sidebar.active {
        left: 0;
      }

      .main-content {
        margin-left: 0;
      }

      .top-bar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 250;
      }

      .content-area {
        margin-top: 80px;
      }
    }
  </style>
</head>

<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="logo">
        <img src="pic/srclogo.png" alt="Santa Rita College Logo">
        <h5>Santa Rita College</h5>
      </div>
      <ul>
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="vote.php"><i class="fas fa-vote-yea"></i> Vote Now</a></li>
        <li><a href="view.php"><i class="fas fa-users"></i> View Candidates</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <div class="page-title">
          <button class="menu-toggle" onclick="toggleSidebar()">
            <i id="menu-icon" class="fas fa-bars"></i>
          </button>
          <?php echo $election_title; ?>
        </div>
        <div class="user-info">
          <div class="user-avatar">
            <?php echo isset($_SESSION['first_name']) ? substr($_SESSION['first_name'], 0, 1) : 'U'; ?>
          </div>
          <span><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User'; ?></span>
          <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </div>

      <!-- Content Area -->
      <div class="content-area">
        <div class="welcome-header">
          <h2><i class="fas fa-chart-bar" style="color:var(--primary); margin-right:12px;"></i>
            <?php echo htmlspecialchars($election_title); ?></h2>
          <div class="time-date">
            <i class="far fa-calendar-alt"></i> <span id="current-date"></span>
            <i class="far fa-clock" style="margin-left:15px;"></i> <span id="current-time"></span>
          </div>
        </div>

        <?php if ($is_history): ?>
          <!-- Celebration Banner for Results History -->
          <div class="celebration-banner"
            style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem; display: flex; align-items: center; gap: 1.5rem; box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2); animation: slideIn 0.5s ease-out;">
            <div style="font-size: 2.5rem;">🎉</div>
            <div style="flex: 1;">
              <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Congratulations to the Winners!</h3>
              <p style="margin: 5px 0 0 0; opacity: 0.9;">The election has concluded successfully. Below are the official
                final results.</p>
            </div>
            <div style="font-size: 2.5rem;">🎊</div>
          </div>
          <style>
            @keyframes slideIn {
              from {
                transform: translateY(-20px);
                opacity: 0;
              }

              to {
                transform: translateY(0);
                opacity: 1;
              }
            }
          </style>
          <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
          <script>
            window.addEventListener('load', () => {
              const duration = 5 * 1000;
              const end = Date.now() + duration;

              (function frame() {
                confetti({
                  particleCount: 3,
                  angle: 60,
                  spread: 55,
                  origin: { x: 0 },
                  colors: ['#3B82F6', '#6366f1', '#10b981']
                });
                confetti({
                  particleCount: 3,
                  angle: 120,
                  spread: 55,
                  origin: { x: 1 },
                  colors: ['#3B82F6', '#6366f1', '#10b981']
                });

                if (Date.now() < end) {
                  requestAnimationFrame(frame);
                }
              }());
            });
          </script>
        <?php endif; ?>

        <?php if (!empty($results_by_position)): ?>
          <!-- Winner Spotlight / Current Leaders -->
          <div class="winner-spotlight" style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem;">
              <div
                style="width: 40px; height: 40px; background: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white;">
                <i class="fas <?php echo $is_history ? 'fa-trophy' : 'fa-chart-line'; ?>"></i>
              </div>
              <div>
                <h3 style="font-weight: 700; color: var(--dark); line-height: 1.2;">
                  <?php echo $is_history ? 'Official Winners' : 'Current Election Leaders'; ?>
                </h3>
                <p style="font-size: 0.85rem; color: var(--gray);">
                  <?php echo $is_history ? 'Final results for the concluded election' : 'Real-time standings of the ongoing vote'; ?>
                </p>
              </div>
            </div>

            <div class="spotlight-scroll"
              style="display: flex; gap: 1.5rem; overflow-x: auto; padding: 10px 5px 20px; scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
              <?php foreach ($results_by_position as $pos => $cands):
                $winner = $cands[0]; // First one is the leader due to DESC sort
                if ($winner['votes'] > 0 || !$is_history):
                  ?>
                  <div class="spotlight-card"
                    style="flex: 0 0 220px; background: white; border-radius: 20px; padding: 1.5rem; border: 1px solid var(--light-gray); text-align: center; box-shadow: var(--box-shadow); position: relative; transition: var(--transition);">
                    <div
                      style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; z-index: 5; box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);">
                      <?php echo htmlspecialchars($pos); ?>
                    </div>

                    <div style="position: relative; width: 85px; height: 85px; margin: 10px auto 15px;">
                      <img src="<?php echo htmlspecialchars($winner['photo_path']); ?>"
                        onerror="this.src='pic/default-avatar.png'"
                        style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-light);">
                      <?php if ($winner['votes'] > 0): ?>
                        <div
                          style="position: absolute; bottom: 0; right: 0; background: #FFD700; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #8B4513; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                          <i class="fas fa-crown" style="font-size: 0.7rem;"></i>
                        </div>
                      <?php endif; ?>
                    </div>

                    <h4
                      style="font-size: 0.95rem; font-weight: 700; color: var(--dark); margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                      <?php echo htmlspecialchars($winner['first_name'] . ' ' . $winner['last_name']); ?>
                    </h4>

                    <div
                      style="display: inline-flex; align-items: center; gap: 6px; background: var(--primary-light); color: var(--primary); padding: 5px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 700;">
                      <i class="fas fa-vote-yea" style="font-size: 0.75rem;"></i>
                      <span>
                        <?php echo number_format($winner['votes']); ?> Votes
                      </span>
                    </div>
                  </div>
                <?php endif; endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- ------ LOGIC CHANGE 4: DYNAMIC MESSAGE KUNG WALANG RESULTA ------ -->
        <?php if (empty($results_by_position)): ?>
          <div class="position-results text-center p-5">
            <i class="fas fa-poll fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No Election Results Available</h4>
            <p class="text-muted">There are no active or past elections available to display results for at this time.</p>
          </div>
        <?php else: ?>
          <?php foreach ($results_by_position as $position => $position_candidates): ?>
            <div class="position-results">
              <h3><?php echo htmlspecialchars($position); ?></h3>
              <div class="table-container">
                <table>
                  <thead>
                    <tr>
                      <th>Rank</th>
                      <th>Candidate</th>
                      <th>Status</th>
                      <th>Vote Percentage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $position_total_votes = array_sum(array_column($position_candidates, 'votes'));
                    $rank = 1;
                    foreach ($position_candidates as $candidate):
                      $percentage = ($position_total_votes > 0) ? round(($candidate['votes'] / $position_total_votes) * 100, 2) : 0;
                      $is_voted = (isset($user_voted_candidates[$position]) && $user_voted_candidates[$position] == $candidate['id']);
                      ?>
                      <tr class="<?php echo $is_voted ? 'voted-row' : ''; ?>">
                        <td><?php echo $rank++; ?></td>
                        <td>
                          <div class="candidate-info">
                            <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>"
                              alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['middle_name'] . ' ' . $candidate['last_name']); ?>"
                              class="candidate-photo">
                            <div>
                              <h4 style="margin-bottom:6px;">
                                <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['middle_name'] . ' ' . $candidate['last_name']); ?>
                              </h4>
                              <p class="platform" style="color:var(--gray); max-width:350px;">
                                <?php echo nl2br(htmlspecialchars($candidate['platform'])); ?>
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
                          <?php if ($is_voted): ?>
                            <span class="voted-badge">Voted</span>
                          <?php else: ?>
                            &ndash;
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="percentage-bar-container">
                            <div class="percentage-bar-wrapper">
                              <div class="percentage-bar" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <span class="percentage-value"><?php echo $percentage; ?>%</span>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Overlay - Hindi Ginalaw -->
  <div class="overlay" onclick="toggleSidebar()"></div>
  <!-- JavaScript - Hindi Ginalaw -->
  <script>
    function updateDateTime() {
      const now = new Date();
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
      let hours = now.getHours();
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12 || 12;
      const minutes = now.getMinutes().toString().padStart(2, '0');
      document.getElementById('current-time').textContent = `${hours}:${minutes} ${ampm}`;
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);
    function toggleSidebar() {
      document.querySelector('.sidebar').classList.toggle('active');
      document.querySelector('.overlay').classList.toggle('active');
      const icon = document.getElementById('menu-icon');
      if (document.querySelector('.sidebar').classList.contains('active')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    }
  </script>
</body>

</html>