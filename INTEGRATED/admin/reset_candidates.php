<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "src_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Delete all votes first (to avoid foreign key constraint errors)
$deleteVotes = "DELETE FROM vot_votes";

// 2. Delete all candidates
$deleteCandidates = "DELETE FROM vot_candidates";

// 3. Reset users' vote status
$resetVotes = "UPDATE students SET has_voted = 0"; // Update table/column names if different

if (
    $conn->query($deleteVotes) === TRUE &&
    $conn->query($deleteCandidates) === TRUE &&
    $conn->query($resetVotes) === TRUE
) {

    echo "<script>alert('Election data has been reset: votes deleted, vot_candidates cleared, and user votes reset.'); window.location.href='dashboard.php';</script>";
} else {
    echo "Error during reset: " . $conn->error;
}

$conn->close();
?>