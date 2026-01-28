<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Election History Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* Reset & basic */
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f2f4f8; color: #333; }

/* Container */
.dashboard-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

/* Heading */
h1 {
    text-align: center;
    margin-bottom: 3rem;
    color: #005BAA;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 700;
    letter-spacing: 1px;
    position: relative;
}
h1::after {
    content: '';
    width: 120px;
    height: 4px;
    background: #D4AF37;
    display: block;
    margin: 10px auto 0;
    border-radius: 2px;
}

/* Position Heading */
.position-heading {
    font-size: 1.8rem;
    font-weight: 700;
    color: #005BAA;
    border-bottom: 3px solid #D4AF37;
    padding-bottom: 0.4rem;
    margin: 2rem 0 1rem;
}

/* Grid */
.card-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.8rem;
}

@media (max-width: 992px) { .card-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .card-grid { grid-template-columns: 1fr; } }

/* Candidate Card */
.candidate-card {
    background: linear-gradient(180deg, #E6F2FF 0%, #ffffff 100%);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: center;
    border: 2px solid #005BAA;
}
.candidate-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

/* Candidate Photo */
.candidate-photo {
    width: 100%;
    height: 220px;
    object-fit: cover;
    border-bottom: 3px solid #D4AF37;
    transition: transform 0.3s;
}
.candidate-photo:hover { transform: scale(1.05); }

/* Candidate Info */
.candidate-info {
    padding: 1.2rem;
}
.candidate-info h3 {
    font-size: 1.4rem;
    color: #0b1f4c;
    margin-bottom: 0.3rem;
    font-weight: 700;
}
.candidate-info p {
    font-size: 0.95rem;
    color: #555;
    margin: 0.2rem 0;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
    animation: fadeIn 0.3s ease;
}
.modal-content {
    background-color: #fff;
    margin: 2% auto;
    padding: 2rem;
    border-radius: 16px;
    max-width: 650px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    text-align: center;
    position: relative;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}
.modal-content img {
    width: 160px;
    height: 160px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 1rem;
    border: 3px solid #005BAA;
    cursor: pointer;
    transition: transform 0.3s;
}
.modal-content img:hover { transform: scale(1.05); }

.close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 1.6rem;
    cursor: pointer;
    color: #0b1f4c;
    transition: color 0.2s;
}
.close-btn:hover { color: #D4AF37; }

/* Platforms */
.platform-container {
    background-color: #f0f0f0;
    padding: 1rem;
    margin-top: 1rem;
    border-radius: 12px;
}
.platform-container h2 {
    text-align: center;
    margin-bottom: 0.5rem;
    color: #005BAA;
}
.platform-text { text-align: center; white-space: pre-line; font-size: 0.95rem; color: #333; }

/* Photo Gallery */
.photo-gallery {
    margin-top: 1.5rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 0.8rem;
}
.photo-gallery img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}
.photo-gallery img:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 15px rgba(0,0,0,0.25);
}

/* Image Modal */
.image-modal { display: none; position: fixed; z-index: 2000; left:0; top:0; width:100%; height:100%; background-color: rgba(0,0,0,0.95);}
.image-modal-content { position:absolute; top:50%; left:50%; transform: translate(-50%, -50%); max-width:80%; max-height:80%; border-radius:12px; object-fit: contain;}
.image-close { position:absolute; top:20px; right:40px; font-size:45px; font-weight:bold; color:#fff; cursor:pointer; transition:0.3s;}
.image-close:hover { color:#D4AF37; }

/* Animations */
@keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
@keyframes slideUp { from {transform: translateY(50px);} to {transform: translateY(0);} }

/* Responsive tweaks */
@media (max-width: 768px) { .modal-content { width: 95%; padding: 1.5rem; } .candidate-photo { height: 180px; } }
@media (max-width: 480px) { .card-grid { grid-template-columns: 1fr; gap: 1rem; } .modal-content { width: 98%; padding: 1rem; } }
</style>
</head>
<body>

<div class="dashboard-container">
    <h1>Election History</h1>
    <div id="candidateGrid">
        <!-- Candidate cards will be inserted here grouped by position -->
    </div>
</div>

<!-- Candidate Modal -->
<div class="modal" id="candidateModal">
    <div class="modal-content">
        <span class="close-btn" id="closeModal">&times;</span>
        <img id="modalPhoto" src="" alt="Candidate Photo">
        <h2 id="modalName"></h2>
        <p id="modalPosition"></p>
        <p id="modalYearSection"></p>
        <p id="modalYear"></p>

        <div class="platform-container">
            <h2>Platforms</h2>
            <div id="platformText" class="platform-text"></div>
        </div>

        <div class="photo-gallery" id="photoGallery"></div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="image-modal" id="imageModal">
    <span class="image-close" id="imageClose">&times;</span>
    <img class="image-modal-content" id="previewImage">
</div>

<script>
// Modal functionality
const modal = document.getElementById('candidateModal');
const closeModal = document.getElementById('closeModal');
const imageModal = document.getElementById('imageModal');
const imageClose = document.getElementById('imageClose');
const previewImage = document.getElementById('previewImage');

closeModal.onclick = () => modal.style.display = 'none';
imageClose.onclick = () => imageModal.style.display = 'none';
window.onclick = (e) => { 
    if(e.target == modal) modal.style.display = 'none';
    if(e.target == imageModal) imageModal.style.display = 'none';
}

function openImagePreview(imageSrc) {
    previewImage.src = imageSrc;
    imageModal.style.display = 'block';
}

function openModal(candidate) {
    document.getElementById('modalPhoto').src = candidate.photo_path;
    document.getElementById('modalName').innerText = candidate.fullname;
    document.getElementById('modalPosition').innerText = "Position: " + candidate.position;
    document.getElementById('modalYearSection').innerText = "Year & Section: " + candidate.year_section;
    document.getElementById('modalYear').innerText = "Year: " + candidate.year;
    document.getElementById('platformText').innerText = candidate.platforms;

    const gallery = document.getElementById('photoGallery');
    gallery.innerHTML = "";
    if (candidate.photos && candidate.photos.length > 0) {
        candidate.photos.forEach(path => {
            const img = document.createElement('img');
            img.src = path;
            img.alt = "Gallery Photo";
            img.onclick = (e) => { e.stopPropagation(); openImagePreview(path); };
            gallery.appendChild(img);
        });
    } else {
        gallery.innerHTML = "<p style='color:#555;font-size:0.9rem;'>No additional photos</p>";
    }

    modal.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    const modalPhoto = document.getElementById('modalPhoto');
    modalPhoto.onclick = (e) => { e.stopPropagation(); openImagePreview(modalPhoto.src); };
    modalPhoto.style.cursor = 'pointer';
});
</script>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "src_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "SELECT * FROM election_history";
$result = $conn->query($sql);

$candidates = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['photos'] = []; 
        $candidates[$row['id']] = $row;
    }
}

$sql2 = "SELECT * FROM election_history_photos";
$result2 = $conn->query($sql2);
if ($result2->num_rows > 0) {
    while($row2 = $result2->fetch_assoc()) {
        $hid = $row2['history_id'];
        if (isset($candidates[$hid])) {
            $candidates[$hid]['photos'][] = $row2['photo_path'];
        }
    }
}

echo "<script>const candidates = " . json_encode(array_values($candidates)) . ";</script>";

$conn->close();
?>

<script>
// Group candidates by position
const grid = document.getElementById('candidateGrid');
const groupedCandidates = {};
candidates.forEach(c => {
    if (!groupedCandidates[c.position]) groupedCandidates[c.position] = [];
    groupedCandidates[c.position].push(c);
});

// Define order of positions
const positionOrder = ["President", "Vice President", "Secretary", "Treasurer", "Auditor", "PIO", "Business Manager"];
positionOrder.forEach(pos => {
    if (groupedCandidates[pos]) {
        const heading = document.createElement('div');
        heading.className = 'position-heading';
        heading.innerText = pos;
        grid.appendChild(heading);

        const cardContainer = document.createElement('div');
        cardContainer.className = 'card-grid';
        grid.appendChild(cardContainer);

        groupedCandidates[pos].forEach(c => {
            const card = document.createElement('div');
            card.className = 'candidate-card';
            card.innerHTML = `
                <img src="${c.photo_path}" class="candidate-photo" alt="${c.fullname}">
                <div class="candidate-info">
                    <h3>${c.fullname}</h3>
                    <p>${c.position}</p>
                    <p>${c.year_section}</p>
                    <p>${c.year}</p>
                </div>
            `;
            card.onclick = () => openModal(c);
            cardContainer.appendChild(card);
        });
    }
});
</script>

</body>
</html>
