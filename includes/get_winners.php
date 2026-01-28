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
            FROM elections 
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
        // Using a subquery approach for compatibility or window functions if available.
        // We'll fetch all candidates and filter in PHP for maximum reliability across MySQL versions.
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                (SELECT MIN(vote_id) FROM votes WHERE candidate_id = c.id) as first_vote_id
            FROM candidates c
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

        // Re-sort winners by standard position order
        usort($winners, function ($a, $b) {
            $order = [
                'President' => 1,
                'Vice President' => 2,
                'Secretary' => 3,
                'Treasurer' => 4,
                'Auditor' => 5,
                'PIO' => 6,
                'PRO' => 6 // Some might use PRO instead of PIO
            ];
            $rankA = $order[$a['position']] ?? 99;
            $rankB = $order[$b['position']] ?? 99;
            return $rankA <=> $rankB;
        });

        // Get total voter turnout
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) as total_voters
            FROM votes
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

// Check if this is being called directly (for AJAX)
if (basename($_SERVER['PHP_SELF']) === 'get_winners.php') {
    header('Content-Type: application/json');
    $winnersData = getLatestElectionWinners($pdo);
    echo json_encode($winnersData);
    exit;
}
?>