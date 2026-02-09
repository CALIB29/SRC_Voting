<?php
// Election Winners Display Component
// This file fetches and returns the latest election winners for display on the homepage

require_once __DIR__ . '/db_connect.php';

function getLatestElectionWinners($pdo)
{
    try {
        // Get the most recent completed election (not active, has ended)
        $stmt = $pdo->prepare("
            SELECT id, title, end_datetime, created_at 
            FROM vot_elections 
            WHERE is_active = 0 
            ORDER BY end_datetime DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            return null;
        }

        // Get winners for each position (candidate with most votes per position, tie-breaker: earliest vote)
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                (SELECT MIN(vote_id) FROM vot_votes WHERE candidate_id = c.id) as first_vote_id
            FROM vot_candidates c
            WHERE c.election_id = :election_id
            AND c.votes > 0
            ORDER BY c.position, c.votes DESC, first_vote_id ASC
        ");
        $stmt->execute(['election_id' => $election['id']]);
        $all_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $winners = [];
        $positions_seen = [];
        foreach ($all_candidates as $cand) {
            if (!in_array($cand['position'], $positions_seen)) {
                $winners[] = $cand;
                $positions_seen[] = $cand['position'];
            }
        }

        // Standard position order
        $order_map = [
            'President' => 1,
            'Vice President' => 2,
            'Secretary' => 3,
            'Treasurer' => 4,
            'Auditor' => 5,
            'PIO' => 6,
            'PRO' => 6
        ];
        usort($winners, function ($a, $b) use ($order_map) {
            $rankA = $order_map[$a['position']] ?? 99;
            $rankB = $order_map[$b['position']] ?? 99;
            return $rankA <=> $rankB;
        });

        // Get total voter turnout
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) as total_voters
            FROM vot_votes
            WHERE election_id = :election_id
        ");
        $stmt->execute(['election_id' => $election['id']]);
        $turnout = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'election' => $election,
            'winners' => $winners,
            'total_voters' => $turnout['total_voters'] ?? 0
        ];
    } catch (PDOException $e) {
        error_log("Error fetching election winners: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetches current leaders/winners across ALL departments for the Master Dashboard
 */
function getWinnersForAllDepartments($pdo)
{
    try {
        // We look for active or most recent election winners per department
        // For simplicity in this system, we'll fetch top candidates currently leading if election is active,
        // or winners if it's finished.
        $stmt = $pdo->prepare("
             SELECT c.*, e.title as election_title, d.department_name
             FROM vot_candidates c
             JOIN vot_elections e ON c.election_id = e.id
             JOIN departments d ON e.department_id = d.department_id
             WHERE e.is_active = 1 OR e.end_datetime > DATE_SUB(NOW(), INTERVAL 30 DAY)
             AND c.votes > 0
             ORDER BY d.department_name, c.position, c.votes DESC
        ");
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $seen = []; // To keep only top winner per position per department

        foreach ($candidates as $cand) {
            $key = $cand['department_name'] . '|' . $cand['position'];
            if (!isset($seen[$key])) {
                $results[$cand['department_name']][$cand['position']] = [
                    'name' => $cand['first_name'] . ' ' . $cand['last_name'],
                    'first_name' => $cand['first_name'],
                    'last_name' => $cand['last_name'],
                    'votes' => $cand['votes'],
                    'photo_path' => $cand['photo_path'],
                    'election' => $cand['election_title']
                ];
                $seen[$key] = true;
            }
        }
        return $results;
    } catch (PDOException $e) {
        error_log("Error in getWinnersForAllDepartments: " . $e->getMessage());
        return [];
    }
}

// Check if this is being called directly (for AJAX)
if (basename($_SERVER['PHP_SELF']) === 'get_winners.php') {
    header('Content-Type: application/json');
    $winnersData = getLatestElectionWinners($pdo);
    echo json_encode($winnersData);
    exit;
}
?>