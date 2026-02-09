<?php
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['path' => '/']);
  session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit;
}

// ------ LOGIC 1: Get the ID of the currently active election or the last ended one. ------
$stmt_active = $pdo->query("SELECT id, title, is_active FROM vot_elections WHERE is_active = 1 LIMIT 1");
$active_election = $stmt_active->fetch();
$active_election_id = $active_election ? $active_election['id'] : null;
$election_title = "Election Results";
$is_history = false;

if (!$active_election_id) {
  // Get the last ended election
  $stmt_latest = $pdo->query("SELECT id, title FROM vot_elections WHERE is_active = 0 AND end_datetime IS NOT NULL ORDER BY end_datetime DESC LIMIT 1");
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


// ------ LOGIC 2: Get candidates for the ACTIVE election (with tie-breaker logic). ------
$candidates = [];
if ($active_election_id) {
  $stmt_candidates = $pdo->prepare("
        SELECT c.*, 
        (SELECT MIN(vote_id) FROM vot_votes WHERE candidate_id = c.id) as first_vote_id
        FROM vot_candidates c
        WHERE c.election_id = :election_id
        ORDER BY 
            CASE position
                WHEN 'President' THEN 1 WHEN 'Vice President' THEN 2 WHEN 'Secretary' THEN 3 WHEN 'Treasurer' THEN 4
                WHEN 'Auditor' THEN 5 WHEN 'PRO' THEN 6 WHEN 'Business Manager' THEN 7 WHEN 'Sgt. at Arms' THEN 8
                ELSE 9
            END, votes DESC,
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


// ------ LOGIC 3: Get user's vote for the ACTIVE election. ------
$user_voted_candidates = [];
if ($active_election_id) {
  $stmt_votes = $pdo->prepare("
        SELECT v.candidate_id, c.position
        FROM vot_votes v
        JOIN vot_candidates c ON v.candidate_id = c.id
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
  <title>Election Results | Santa Rita College</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/student_premium.css">
  <link rel="stylesheet" href="../assets/css/mobile_base.css">
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <style>
    .page-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: var(--radius-xl);
      padding: 2rem;
      color: white;
      margin-bottom: 2.5rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    }

    .page-header::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
      animation: rotate 20s linear infinite;
    }

    @keyframes rotate {
      from {
        transform: rotate(0deg);
      }

      to {
        transform: rotate(360deg);
      }
    }

    .header-content {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .spotlight-section {
      margin-bottom: 3rem;
    }

    .section-heading {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 1.5rem;
      color: var(--text-main);
      font-size: 1.25rem;
      font-weight: 700;
    }

    .scroll-container {
      display: flex;
      gap: 1.5rem;
      overflow-x: auto;
      padding-bottom: 1.5rem;
      scrollbar-width: thin;
    }

    .spotlight-card {
      flex: 0 0 240px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      text-align: center;
      position: relative;
      transition: var(--transition);
    }

    .spotlight-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary);
      box-shadow: var(--shadow-lg);
    }

    .winner-badge {
      background: #F59E0B;
      color: white;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 0.9rem;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      z-index: 5;
    }

    .spotlight-img {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--primary);
      margin-bottom: 1rem;
      padding: 2px;
      background: var(--bg-card);
    }

    .spotlight-name {
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 0.25rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .spotlight-position {
      font-size: 0.8rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.75rem;
    }

    .spotlight-votes {
      background: rgba(59, 130, 246, 0.1);
      color: var(--primary);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-block;
    }

    .results-table-container {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .table-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
      font-weight: 700;
      color: var(--primary);
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(59, 130, 246, 0.05);
    }

    .results-table {
      width: 100%;
      border-collapse: collapse;
    }

    .results-table th {
      text-align: left;
      padding: 1rem 1.5rem;
      color: var(--text-muted);
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      border-bottom: 1px solid var(--border);
    }

    .results-table td {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
      color: var(--text-main);
      vertical-align: middle;
    }

    .results-table tr:last-child td {
      border-bottom: none;
    }

    .candidate-cell {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .table-img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .progress-container {
      width: 100%;
      height: 8px;
      background: var(--border);
      border-radius: 10px;
      overflow: hidden;
      margin-top: 5px;
    }

    .progress-bar {
      height: 100%;
      background: var(--primary);
      border-radius: 10px;
    }

    .voted-highlight {
      background: rgba(16, 185, 129, 0.05);
    }

    .voted-badge {
      background: rgba(16, 185, 129, 0.1);
      color: var(--success);
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      margin-left: 8px;
    }

    @media (max-width: 768px) {
      .page-header {
        padding: 1.5rem;
        text-align: center;
      }

      .header-content {
        flex-direction: column;
        justify-content: center;
      }

      .results-table thead {
        display: none;
      }

      .results-table tr {
        display: block;
        padding: 1rem;
        border-bottom: 1px solid var(--border);
      }

      .results-table td {
        display: block;
        padding: 0.5rem 0;
        border: none;
        text-align: right;
      }

      .results-table td:first-child {
        text-align: center;
        font-weight: bold;
        margin-bottom: 0.5rem;
      }

      .candidate-cell {
        justify-content: flex-end;
      }

      .results-table td::before {
        content: attr(data-label);
        float: left;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.75rem;
        margin-top: 5px;
      }
    }
  </style>
</head>

<body>
  <?php if (function_exists('renderMobileTopBar'))
    renderMobileTopBar('Results'); ?>

  <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-header">
        <img src="pic/srclogo.png" alt="Logo">
        <div class="sidebar-brand">
          <h5>Santa Rita College</h5>
          <span
            class="dept-badge"><?php echo isset($_SESSION['departments']) ? str_replace([' VOTING', '_', '-'], '', $_SESSION['departments']) : 'Voting System'; ?></span>
        </div>
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span></a></li>
        <li class="nav-item"><a href="vote.php" class="nav-link"><i class="fas fa-vote-yea"></i> <span>Vote
              Now</span></a></li>
        <li class="nav-item"><a href="view.php" class="nav-link"><i class="fas fa-users"></i> <span>View
              Candidates</span></a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <div class="page-title">
          <h1>Election Results</h1>
        </div>
        <div class="user-profile">
          <div class="avatar"><?php echo substr($_SESSION['first_name'], 0, 1); ?></div>
          <div class="user-meta">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Student'); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="content-area">

        <div class="page-header">
          <div class="header-content">
            <div>
              <h2 style="margin: 0; font-size: 1.8rem; font-weight: 800;">
                <?php echo htmlspecialchars($election_title); ?>
              </h2>
              <p style="margin: 5px 0 0 0; opacity: 0.9;">
                <?php echo $is_history ? 'Final results for the concluded election' : 'Live updates of the ongoing election'; ?>
              </p>
            </div>
            <div style="text-align: right;">
              <div style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 5px;"><i class="far fa-clock"></i> Last
                Updated</div>
              <div style="font-weight: 700; font-size: 1.1rem;"><?php echo date('F j, Y g:i A'); ?></div>
            </div>
          </div>
        </div>

        <?php if (empty($results_by_position)): ?>
          <div class="status-card pending">
            <i class="fas fa-poll"></i>
            <div class="status-content">
              <h3>No Results Available</h3>
              <p>There are no active or past elections available to display yet.</p>
            </div>
          </div>
        <?php else: ?>

          <!-- Leaders Spotlight -->
          <div class="spotlight-section">
            <div class="section-heading">
              <i class="fas fa-trophy" style="color: #F59E0B;"></i>
              <?php echo $is_history ? 'Official Winners' : 'Current Leaders'; ?>
            </div>
            <div class="scroll-container">
              <?php foreach ($results_by_position as $pos => $cands):
                $leader = $cands[0]; // First one is leader due to sort order
                if ($leader['votes'] > 0 || !$is_history):
                  ?>
                  <div class="spotlight-card">
                    <?php if ($leader['votes'] > 0): ?>
                      <div class="winner-badge"><i class="fas fa-crown"></i></div>
                    <?php endif; ?>

                    <?php
                    $photo = fixCandidatePhotoPath($leader['photo_path'], '../');
                    $alt = htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']);
                    echo "<img src=\"{$photo}\" class=\"spotlight-img\" alt=\"{$alt}\" onerror=\"this.src='pic/srclogo.png'\">";
                    ?>

                    <div class="spotlight-position"><?= htmlspecialchars($pos) ?></div>
                    <h4 class="spotlight-name"><?= htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']) ?>
                    </h4>

                    <div class="spotlight-votes">
                      <?= number_format($leader['votes']) ?> Votes
                    </div>
                  </div>
                <?php endif; endforeach; ?>
            </div>
          </div>

          <!-- Detailed Results -->
          <?php foreach ($results_by_position as $position => $position_candidates):
            $position_total_votes = array_sum(array_column($position_candidates, 'votes'));
            ?>
            <div class="results-table-container">
              <div class="table-header">
                <i class="fas fa-user-tie"></i> <?= htmlspecialchars($position) ?>
              </div>
              <table class="results-table">
                <thead>
                  <tr>
                    <th style="width: 50px;">#</th>
                    <th>Candidate</th>
                    <th style="text-align: right;">Votes</th>
                    <th style="width: 30%;">Percentage</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $rank = 1;
                  foreach ($position_candidates as $candidate):
                    $percentage = ($position_total_votes > 0) ? round(($candidate['votes'] / $position_total_votes) * 100, 2) : 0;
                    $is_voted = (isset($user_voted_candidates[$position]) && $user_voted_candidates[$position] == $candidate['id']);
                    ?>
                    <tr class="<?= $is_voted ? 'voted-highlight' : '' ?>">
                      <td data-label="Rank" style="font-weight: 700; color: var(--text-muted);"><?= $rank++ ?></td>
                      <td data-label="Candidate">
                        <div class="candidate-cell">
                          <?php
                          $candPhoto = fixCandidatePhotoPath($candidate['photo_path'], '../');
                          echo "<img src=\"{$candPhoto}\" class=\"table-img\" alt=\"\" onerror=\"this.src='pic/srclogo.png'\">";
                          ?>
                          <div>
                            <div style="font-weight: 600; color: var(--text-main);">
                              <?= htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']) ?>
                              <?php if ($is_voted): ?>
                                <span class="voted-badge">Voted</span>
                              <?php endif; ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                              <?= htmlspecialchars($candidate['partylist'] ?? 'Independent') ?>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td data-label="Votes" style="text-align: right; font-weight: 700; color: var(--primary);">
                        <?= number_format($candidate['votes']) ?>
                      </td>
                      <td data-label="Percentage">
                        <div
                          style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; font-size: 0.85rem; font-weight: 600;">
                          <span><?= $percentage ?>%</span>
                        </div>
                        <div class="progress-container">
                          <div class="progress-bar" style="width: <?= $percentage ?>%;"></div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (function_exists('renderMobileBottomNav'))
    renderMobileBottomNav('student'); ?>

  <?php if ($is_history): ?>
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
</body>

</html>