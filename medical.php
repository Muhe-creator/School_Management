<?php
// Database Connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'SA_PriSchool';
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Helper function for sanitization
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}


// Function to sanitize input
// Search medical profile
$search_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search_by']) && isset($_GET['search_query'])) {
    // Sanitize input
    $by = $_GET['search_by'];
    $keyword = '%' . trim($_GET['search_query']) . '%';

    $column = '';
    // Determine the column to search based on the selected option
    switch ($by) {
        case 'name':
            $column = "CONCAT(p.p_first_name, ' ', p.p_last_name)";
            break;
        case 'blood_type':
            $column = "mp.blood_type";
            break;
        case 'allergies':
            $column = "mp.allergies";
            break;
        case 'chronic_issues':
            $column = "mp.chronic_issues";
            break;
    }
    // Prepare the SQL statement
    // Use prepared statements to prevent SQL injection
    if ($column !== '') {
        // Prepare the SQL statement
        $stmt = $conn->prepare("
            SELECT mp.*, p.pupil_id, p.p_first_name, p.p_last_name, c.class_name
            FROM medical_profile mp
            JOIN pupil p ON mp.pupil_id = p.pupil_id
            LEFT JOIN class c ON p.class_id = c.class_id
            WHERE $column LIKE ?
        ");
        // Bind the parameter
        // Use 's' for string type
        // Execute the statement
        // Fetch the results
        $stmt->bind_param('s', $keyword);
        $stmt->execute();
        $search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}



$searchTerm = '';
// Check if search term is set
if (isset($_GET['search'])) {
    // Sanitize the search term
    $searchTerm = sanitize($_GET['search']);
}

// Pupils dropdown
$pupils = $conn->query("
    SELECT pupil_id, CONCAT(p_first_name, ' ', p_last_name) AS name 
    FROM pupil 
    WHERE CONCAT(p_first_name, ' ', p_last_name) LIKE '%$searchTerm%'
");

// Medical Profiles
$profiles = $conn->query("
    SELECT p.pupil_id, CONCAT(p.p_first_name, ' ', p.p_last_name) AS name, 
           mp.blood_type, mp.allergies, mp.chronic_issues
    FROM pupil p
    LEFT JOIN medical_profile mp ON p.pupil_id = mp.pupil_id
    WHERE CONCAT(p.p_first_name, ' ', p.p_last_name) LIKE '%$searchTerm%'
");

// Filter by allergy
// Check if allergy filter is set
// Initialize an empty array for filtered profiles
$allergy_filtered_profiles = [];

// If the allergy filter is set and not empty
if (isset($_GET['allergy_filter']) && $_GET['allergy_filter'] !== '') {
    $allergy = '%' . $_GET['allergy_filter'] . '%';
    // Prepare the SQL statement

    $stmt = $conn->prepare("
        SELECT mp.*, p.pupil_id, p.p_first_name, p.p_last_name, c.class_name
        FROM medical_profile mp
        JOIN pupil p ON mp.pupil_id = p.pupil_id
        LEFT JOIN class c ON p.class_id = c.class_id
        WHERE mp.allergies LIKE ?
    ");
    // Bind the parameter
    $stmt->bind_param("s", $allergy);
    $stmt->execute();
    // Fetch the results
    // Store the results in the $allergy_filtered_profiles array
    $allergy_filtered_profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


// Add Medical Profile
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_profile'])) {
    // Sanitize input
    $pupil_id = (int)$_POST['pupil_id'];
    $blood_type = sanitize($_POST['blood_type']);
    $allergies = sanitize($_POST['allergies']);
    $chronic = sanitize($_POST['chronic_issues']);
    
    // Check if the profile already exists
    $check = $conn->prepare("SELECT profile_id FROM medical_profile WHERE pupil_id = ?");
    $check->bind_param('i', $pupil_id);
    $check->execute();
    $check_result = $check->get_result();
    
    // If no profile exists, insert a new one
    if ($check_result->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO medical_profile (pupil_id, blood_type, allergies, chronic_issues)
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isss', $pupil_id, $blood_type, $allergies, $chronic);
        $stmt->execute();
        $success = "Medical profile added successfully.";
    } else {
        // If a profile exists, show an error message
        $error = "Profile already exists for this student.";
    }
}


// Medical Record Search
// Initialize an empty array for search results
$record_search_results = [];

// Check if the search form is submitted
// Check if the request method is GET and the search parameters are set
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['record_search_by']) && isset($_GET['record_search_query'])) {
    $type = $_GET['record_search_by'];
    // Sanitize the search query
    // Use trim to remove whitespace
    $query = trim($_GET['record_search_query']);

    $sql = "
        SELECT mr.*, p.p_first_name, p.p_last_name, c.class_name
        FROM medical_record mr
        JOIN pupil p ON mr.pupil_id = p.pupil_id
        LEFT JOIN class c ON p.class_id = c.class_id
    ";
    
    // Prepare the SQL statement based on the search type
    if ($type === 'pupil_id' && is_numeric($query)) {
        // If the search type is pupil_id and the query is numeric
        $sql .= " WHERE mr.pupil_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $query);
    } elseif ($type === 'condition') {
        // If the search type is condition
        // Use LIKE to search for medical conditions
        // Use '%' to match any characters before or after the query
        $sql .= " WHERE mr.medical_condition LIKE ?";
        $like = '%' . $query . '%';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $like);
    } elseif ($type === 'date') {
        // If the search type is date
        // Use LIKE to search for dates
        $sql .= " WHERE mr.date_recorded LIKE ?";
        $like = '%' . $query . '%';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $like);
    }
    
    // Execute the statement and fetch results
    // If the statement is prepared, execute it
    if (isset($stmt)) {
        // Execute the statement
        // Fetch the results
        // Use get_result() to fetch the results
        $stmt->execute();
        $record_search_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Fetch all medical records from the database
// Use a JOIN to get pupil names and class names
$medical_records = $conn->query("
    SELECT 
        mr.record_id,
        mr.medical_condition,
        mr.treatment,
        mr.notes,
        mr.date_recorded,
        p.pupil_id,
        p.p_first_name,
        p.p_last_name,
        c.class_name
    FROM medical_record mr
    JOIN pupil p ON mr.pupil_id = p.pupil_id
    LEFT JOIN class c ON p.class_id = c.class_id
    ORDER BY mr.date_recorded DESC
");


// Add Medical Record
// Check if the form is submitted
// Check if the request method is POST and the add_record parameter is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    // Sanitize input
    // Use (int) to cast pupil_id to an integer
    $pupil_id = (int)$_POST['pupil_id'];
    $condition = sanitize($_POST['medical_condition']);
    $treatment = sanitize($_POST['treatment']);
    $notes = sanitize($_POST['notes']);
    $date = $_POST['date_recorded'];
    // Check if the date is valid
    // Use DateTime to validate the date

    $stmt = $conn->prepare("INSERT INTO medical_record (pupil_id, medical_condition, treatment, notes, date_recorded)
                            VALUES (?, ?, ?, ?, ?)");
    // Use prepared statements to prevent SQL injection
    $stmt->bind_param('issss', $pupil_id, $condition, $treatment, $notes, $date);
    if ($stmt->execute()) {
        // If the statement is executed successfully, show a success message
        $success = "Medical record added.";
    } else {
        $error = "Error adding record.";
    }
}

// Delete Medical Record
if (isset($_GET['delete_record'])) {
    $record_id = (int)$_GET['delete_record'];
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("DELETE FROM medical_record WHERE record_id = ?");
    // Use (int) to cast record_id to an integer
    $stmt->bind_param('i', $record_id);
    // Execute the statement
    // If the statement is executed successfully, show a success message
    $stmt->execute();
    header("Location: medical.php");
    exit;
}
?>

<!-- HTML Section-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records Management</title>
    <link rel="stylesheet" href="medical.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!--Back to Homepage Button-->
<a href="homepage.php" class="home-button" aria-label="Return to homepage">
    <span class="desktop-text">Return to Home</span>
    <span class="mobile-text">Home</span>
</a>

<!-- Header -->
<h1>Medical Management</h1>

<!-- Success/Error Prompt -->
<?php if (isset($success)): ?>
    <div class="success"><?= $success ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="error"><?= $error ?></div>
<?php endif; ?>

<h2>Medical Profile Management</h2>

<!-- Search Profile Section -->
<div class="form-section">
    <h2>Search Medical Profile</h2>
    <!-- Search form using GET method -->
    <form method="GET">
        <div class="form-row">
            <!-- Dropdown to choose which field to search by -->
            <label for="search_by">Search By:</label>
            <select name="search_by" required>
                <!-- User can choose name, blood type, allergies, or chronic issues -->
                <option value="name">Name</option>
                <option value="blood_type">Blood Type</option>
                <option value="allergies">Allergies</option>
                <option value="chronic_issues">Chronic Issues</option>
            </select>
        </div>
        <div class="form-row">
            <!-- Input box for entering keyword -->
            <label for="search_query">Keyword:</label>
            <input type="text" name="search_query" placeholder="Enter keyword" required>
            <!-- Submit button to search -->
            <button type="submit" class="search_button">Search</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<!-- Search Bar -->
<!-- Check if search results exist and display them -->
<?php if (!empty($search_results)): ?>
    <div class="form-section">
        <!-- Show number of results -->
        <h3>Medical Profile Results (<?= count($search_results) ?>):</h3>
        <!-- Table displaying medical profiles -->
        <table class="styled-table">
            <tr>
                <!-- Table headers -->
                <th>Profile ID</th>
                <th>Pupil ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Blood Type</th>
                <th>Allergies</th>
                <th>Chronic Issues</th>
            </tr>
            <?php foreach ($search_results as $result): ?>
                <!-- Display each matching record -->
                <tr>
                    <td><?= $result['profile_id'] ?></td>
                    <td><?= $result['pupil_id'] ?></td>
                    <td><?= htmlspecialchars($result['p_first_name'] . ' ' . $result['p_last_name']) ?></td>
                    <td><?= htmlspecialchars($result['class_name'] ?? 'N/A') ?></td>
                    <td><?= $result['blood_type'] ?></td>
                    <td><?= $result['allergies'] ?></td>
                    <td><?= $result['chronic_issues'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<!-- If search returns no results -->
<?php elseif (isset($_GET['search_query'])): ?>
    <div class="form-section">
        <!-- Error message if nothing found -->
        <p class="error">No matching results found.</p>
    </div>
<?php endif; ?>


<?php
// Fetch list of all students for dropdown options
$students = $conn->query("SELECT pupil_id, CONCAT(p_first_name, ' ', p_last_name) AS name FROM pupil");

// Get general search term if set
$search_term = isset($_GET['search']) ? '%' . $conn->real_escape_string($_GET['search']) . '%' : '%';
?>

<!-- Allergy Filter Section -->
<div class="form-section">
    <h2>Filter by Allergy Type</h2>
    <form method="GET">
        <div class="form-row">
            <!-- Dropdown to select a specific allergy -->
            <label for="allergy_filter">Select Allergy:</label>
            <select name="allergy_filter">
                <option value="">-- Choose an allergy --</option>
                <option value="Peanuts">Peanuts</option>
                <option value="Shellfish">Shellfish</option>
                <option value="Pollen">Pollen</option>
                <option value="Dust">Dust</option>
                <option value="Lactose">Lactose</option>
                <option value="Milk">Milk</option>
                <option value="Gulten">Gulten</option>
            </select>
            <!-- Filter and reset buttons -->
            <button type="submit" class="search_button">Apply Filter</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>
<!-- Show allergy filtered results if present -->
<?php if (!empty($allergy_filtered_profiles)): ?>
    <div class="form-section">
        <h3>Filtered Medical Profiles (by Allergy)(<?= count($allergy_filtered_profiles) ?>)</h3>
        <table class="styled-table">
            <tr>
                <!-- Column headers for filtered profile table -->
                <th>Profile ID</th>
                <th>Pupil ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Blood Type</th>
                <th>Allergies</th>
                <th>Chronic Issues</th>
            </tr>
            <!-- Loop to display each profile that matches the allergy filter -->
            <?php foreach ($allergy_filtered_profiles as $profile): ?>
            <tr>
                <td><?= $profile['profile_id'] ?></td>
                <td><?= $profile['pupil_id'] ?></td>
                <td><?= htmlspecialchars($profile['p_first_name'] . ' ' . $profile['p_last_name']) ?></td>
                <td><?= $profile['class_name'] ?? 'N/A' ?></td>
                <td><?= $profile['blood_type'] ?></td>
                <td><?= $profile['allergies'] ?></td>
                <td><?= $profile['chronic_issues'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php elseif (isset($_GET['allergy_filter']) && $_GET['allergy_filter'] !== ''): ?>
    <!-- Show message when no profiles match the selected allergy -->
    <div class="form-section">
        <p class="error">No profiles match the selected allergy.</p>
    </div>
<?php endif; ?>

<!-- Chart.js for Allergy Type Distribution -->
<div class="form-section">
<section class="chart-container">
    <h2>Allergy Type Distribution</h2>
    <!-- Chart.js canvas for displaying allergy statistics -->
    <canvas id="allergyChart" style="max-width: 300px; width: 100%; height: auto; display: block; margin: 0 auto;"></canvas>
</section>
</div>



<!-- Section that wraps the toggleable medical profile list -->
<!-- Show all medical profiles -->
<div class="form-section">
    <!-- Clickable header that toggles visibility of the profile list below -->
<h2 style="cursor:pointer;" onclick="toggleProfileList()" id="toggleProfileTitle">
    Show All Medical Profiles ▼
</h2>
    <!-- The hidden section containing the list of all medical profiles -->
    <div id="profileList" style="display: none;">
        <!-- Table header defining each column for medical profile info -->
        <table class="styled-table">
            <tr>
                <th>Profile ID</th>
                <th>Pupil ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Blood Type</th>
                <th>Allergies</th>
                <th>Chronic Issues</th>
            </tr>
            <?php
            // Fetch all medical profiles and join with pupil and class info
            $all_profiles = $conn->query("
                SELECT mp.*, p.pupil_id, p.p_first_name, p.p_last_name, c.class_name
                FROM medical_profile mp
                JOIN pupil p ON mp.pupil_id = p.pupil_id
                LEFT JOIN class c ON p.class_id = c.class_id
            ");
            // Loop through the result set and print each profile row
            while ($row = $all_profiles->fetch_assoc()):
            ?>
            <tr>
                <td><?= $row['profile_id'] ?></td>
                <td><?= $row['pupil_id'] ?></td>
                <!-- Display full name with HTML escaping -->
                <td><?= htmlspecialchars($row['p_first_name'] . ' ' . $row['p_last_name']) ?></td>
                <!-- Display class name or fallback to N/A -->
                <td><?= htmlspecialchars($row['class_name'] ?: 'N/A' )?></td>
                <td><?= htmlspecialchars($row['blood_type']) ?></td>
                <td><?= htmlspecialchars($row['allergies']) ?></td>
                <td><?= htmlspecialchars($row['chronic_issues']) ?></td>    
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>


<!-- Add Medical Profile -->
<section class="form-section">
    <h2>Add Medical Profile</h2>
    <form method="POST">
        <!-- Hidden input to indicate form action -->
        <input type="hidden" name="add_profile" value="1">
        <div class="form-row">
            <label>Select Student:
                <!-- Dropdown to choose student by ID and name -->
                <select name="pupil_id" required>
                    <?php while ($s = $students->fetch_assoc()): ?>
                        <option value="<?= $s['pupil_id'] ?>"><?= $s['name'] ?> (ID: <?= $s['pupil_id'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <!-- Input for blood type, required -->
            <label>Blood Type: <input type="text" name="blood_type" placeholder="e.g. A+" required></label>
        </div>
        <div class="form-row">
            <!-- Input for allergies, optional -->
            <label>Allergies: <input type="text" name="allergies"></label>
        </div>
        <div class="form-row">
            <!-- Input for chronic issues, optional -->
            <label>Chronic Issues: <input type="text" name="chronic_issues"></label>
        </div>
        <div class="form-row">
            <!-- Submit and cancel buttons -->
            <button type="submit">Add Profile</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</section>

<!-- Medical Record Management Section -->
<h2>Medical Record Management</h2>

<!-- Search form for filtering medical records -->
<section class="form-section">
    <h2>Search Medical Records</h2>
    <form method="GET">
        <div class="form-row">
            <!-- Dropdown to select search category -->
            <label for="record_search_by">Search by:</label>
            <select name="record_search_by" required>
                <option value="pupil_id">Pupil ID</option>
                <option value="condition">Condition</option>
                <option value="date">Date</option>
            </select>
        </div>
        <div class="form-row">
            <!-- Input for search keyword -->
            <label for="record_search_query">Enter Keyword:</label>
            <input type="text" name="record_search_query" placeholder="Enter your search value..." required>
        </div>
        <div class="form-row">
            <!-- Submit and cancel buttons -->
            <button type="submit">Search</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</section>

<!-- Display Search Results -->
<?php if (!empty($record_search_results)): ?>
    <!-- Show number of results -->
<section class="form-section">
    <h3>Medical Record Results (<?= count($record_search_results) ?>)</h3>
    <table class="styled-table">
        <thead>
            <tr>
                <!-- Table headers for record attributes -->
                <th>Record ID</th>
                <th>Pupil ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Condition</th>
                <th>Treatment</th>
                <th>Notes</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loop through the search results and display each record -->
            <?php foreach ($record_search_results as $r): ?>
                <tr>
                    <td><?= $r['record_id'] ?></td>
                    <td><?= $r['pupil_id'] ?></td>
                    <td><?= htmlspecialchars($r['p_first_name'] . ' ' . $r['p_last_name']) ?></td>
                    <td><?= htmlspecialchars($r['class_name'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($r['medical_condition']) ?></td>
                    <td><?= htmlspecialchars($r['treatment']) ?></td>
                    <td><?= htmlspecialchars($r['notes']) ?></td>
                    <td><?= $r['date_recorded'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php elseif (isset($_GET['record_search_by'])): ?>
    <!-- Show message when no records match the search -->
    <div class="form-section">
        <p class="error">No results found for your search.</p>
    </div>
<?php endif; ?>


<!-- Display Medical Records -->
<section class="form-section">
    <h2>Medical Records List</h2>
    <table class="styled-table">
        <thead>
            <tr>
                <th>Record ID</th>
                <th>Pupil ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>Condition</th>
                <th>Treatment</th>
                <th>Notes</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <!-- Loop through the medical records and display each record -->
        <?php if ($medical_records->num_rows > 0): ?>
            <?php while ($record = $medical_records->fetch_assoc()): ?>   
                <tr>
                    <td><?= $record['record_id'] ?></td>
                    <td><?= $record['pupil_id'] ?></td>
                    <td><?= htmlspecialchars($record['p_first_name'] . ' ' . $record['p_last_name']) ?></td>
                    <td><?= htmlspecialchars($record['class_name'] ?: 'N/A') ?></td>
                    <td><?= htmlspecialchars($record['medical_condition']) ?></td>
                    <td><?= htmlspecialchars($record['treatment']) ?></td>
                    <td><?= htmlspecialchars($record['notes']) ?></td>
                    <td><?= $record['date_recorded'] ?></td>
                    <!-- Action link with confirmation to delete the record -->
                    <td>
                        <a href="?delete_record=<?= $record['record_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Show message when no medical records are found -->
            <tr><td colspan="8">No medical records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</section>


<!-- Add Medical Record -->
<section class="form-section">
    <h2>Add Medical Record</h2>
    <form method="POST">
        <!-- Hidden input to indicate form action -->
        <input type="hidden" name="add_record" value="1">
        <div class="form-row">
            <!-- Dropdown to select student by ID and name -->
            <label>Select Student:
                <select name="pupil_id" required>
                    <?php
                    $students->data_seek(0);  // reset result pointer
                    while ($s = $students->fetch_assoc()): ?>
                        <option value="<?= $s['pupil_id'] ?>"><?= $s['name'] ?> (ID: <?= $s['pupil_id'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <!-- Input for medical condition, required -->
            <label>Condition: <input type="text" name="medical_condition" required></label>
        </div>
        <div class="form-row">
            <!-- Input for treatment, optional -->
            <label>Treatment: <input type="text" name="treatment"></label>
        </div>
        <div class="form-row">
            <!-- Input for notes, optional -->
            <label>Notes: <input type="text" name="notes"></label>
        </div>
        <div class="form-row">
            <!-- Input for date recorded, required -->
            <label>Date Recorded: <input type="date" name="date_recorded" required></label>
        </div>
        <div class="form-row">
            <!-- Submit and cancel buttons -->
            <button type="submit">Add Record</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</section>


<?php
// Get allergy statistics from the database
$allergy_stats = $conn->query("
    SELECT allergies, COUNT(*) AS total 
    FROM medical_profile 
    WHERE allergies IS NOT NULL AND allergies != ''
    GROUP BY allergies
");

// Initialize arrays to hold allergy labels and counts
$allergy_labels = [];
$allergy_data = [];

// Loop through query results and populate arrays
while ($row = $allergy_stats->fetch_assoc()) {
    $allergy_labels[] = $row['allergies'];
    $allergy_data[] = $row['total'];
}

// Get disease statistics for condition distribution
$disease_stats = $conn->query("
    SELECT medical_condition, COUNT(*) AS total 
    FROM medical_record 
    GROUP BY medical_condition
");

// Initialize arrays for disease chart
$labels = [];
$data = [];
while ($row = $disease_stats->fetch_assoc()) {
    $labels[] = $row['medical_condition'];
    $data[] = $row['total'];
}
?>
<!-- Container section for the disease condition chart -->
<div class="form-section">
<section class="chart-container">
    <h2>Disease Condition Statistics</h2>
    <!-- Canvas element for rendering the disease bar chart -->
    <canvas id="diseaseChart"></canvas>
</section>
</div>

<script>
    // Function to toggle the visibility of the medical profile list section
    function toggleProfileList() {
    // Get the profile list element and the title element
    const list = document.getElementById('profileList');
    // Toggle the display property of the list
    // Change the title based on the current state
    const title = document.getElementById('toggleProfileTitle');
    if (list.style.display === 'none') {
        list.style.display = 'block';
        title.innerHTML = 'Hide All Medical Profiles ▲';
    } else {
        list.style.display = 'none';
        title.innerHTML = 'Show All Medical Profiles ▼';
    }
}

    // Chart.js for Allergy Type Distribution
    // Get the context of the canvas element
    const allergyCtx = document.getElementById('allergyChart').getContext('2d');
    // Create a new Chart instance
    // Use the Chart.js library to create a pie chart
    // Use the data from the PHP variables
    // Use the labels and data arrays to populate the chart
    new Chart(allergyCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode($allergy_labels) ?>,
            datasets: [{
                label: 'Allergy Type Distribution',
                data: <?= json_encode($allergy_data) ?>,
                borderWidth: 1,
                // Use the backgroundColor array to set the colors of the pie chart
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#8BC34A',
                    '#FF9800',
                    '#9C27B0',
                    '#03A9F4'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'center', // Stay centered
                    labels: {
                        boxWidth: 20,  // Control the width of each legend block
                        padding: 15,   // Increase the padding on both sides to make them separate from each other a bit more.
                    }
                },
                title: {
                    display: true,
                    text: 'Allergy Type Statistics'
                }
            }
        }
    });


    // Chart.js for Disease Condition Statistics
    // Get the context of the canvas element
    const diseaseChart = document.getElementById('diseaseChart').getContext('2d');
    // Create a new Chart instance
    // Use the Chart.js library to create a bar chart
    new Chart(diseaseChart, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            // Use the labels and data arrays to populate the chart
            // Use the data array to set the values of the chart
            datasets: [{
                label: 'Number of Cases',
                // Use the data array to set the values of the chart
                data: <?= json_encode($data) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                // Use the legend object to control the display of the legend
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Medical Condition Distribution'
                }
            },
            scales: {
                // Use the scales object to control the display of the axes
                y: {
                    beginAtZero: true,
                    stepSize: 1
                }
            }
        }
    });
</script>
</body>
</html>

<?php
// All logic done, now close connection
$conn->close();
?>
