<?php
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$election_id = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : null;

if (!$election_id) {
    $stmt = $pdo->prepare("SELECT id FROM vot_elections WHERE is_active = 1");
    $stmt->execute();
    $active_election = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($active_election) {
        $election_id = $active_election['id'];
    }
}

if (!$election_id) {
    echo json_encode(['error' => 'No election found']);
    exit;
}

// Get Total Votes and Users
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM vot_votes WHERE election_id = :id");
$stmt->execute(['id' => $election_id]);
$total_votes = $stmt->fetchColumn();

$total_users = $pdo->query("SELECT COUNT(*) FROM students WHERE is_approved = 1")->fetchColumn();
$voter_turnout = ($total_users > 0 && $total_votes > 0) ? round(($total_votes / $total_users) * 100, 2) : 0;

// Get Candidates with Tie-breaker logic
$stmt = $pdo->prepare("
    SELECT c.id, c.first_name, c.last_name, c.position, c.votes, c.photo_path, c.platform,
    (SELECT MIN(vote_id) FROM vot_votes WHERE candidate_id = c.id) as first_vote_id
    FROM vot_candidates c 
    WHERE c.election_id = :id 
    ORDER BY c.position, c.votes DESC, first_vote_id ASC
");
$stmt->execute(['id' => $election_id]);
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by position (preserving order from query)
$results_by_position = [];
foreach ($candidates as $candidate) {
    $results_by_position[$candidate['position']][] = $candidate;
}

$fixed_order = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor', 'PRO'];
$final_data = [];

function processCandidates($cands)
{
    $pos_total = array_sum(array_column($cands, 'votes'));

    // Ordered by query already (votes DESC, first_vote_id ASC)
    $processed = [];
    $rank = 1;
    foreach ($cands as $cand) {
        $cand['percentage'] = ($pos_total > 0) ? round(($cand['votes'] / $pos_total) * 100, 2) : 0;
        $cand['rank'] = $rank++;
        $processed[] = $cand;
    }
    return $processed;
}

foreach ($fixed_order as $pos) {
    if (isset($results_by_position[$pos])) {
        $final_data[$pos] = processCandidates($results_by_position[$pos]);
        unset($results_by_position[$pos]);
    }
}

foreach ($results_by_position as $pos => $cands) {
    $final_data[$pos] = processCandidates($cands);
}

echo json_encode([
    'total_votes' => $total_votes,
    'total_voters' => $total_users,
    'voter_turnout' => $voter_turnout,
    'positions' => $final_data
]);
?>