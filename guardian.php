<?php
// Manage Guardians and Relationships

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

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// If form is submitted

    // Add New Guardian Form
    if (isset($_POST['add_guardian'])) {
        // Get and sanitize form inputs
        $first = sanitize_input($_POST['first_name']);
        $last = sanitize_input($_POST['last_name']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $address = sanitize_input($_POST['address']);
        $occupation = sanitize_input($_POST['occupation']);

        // Insert new guardian into the database
        $stmt = $conn->prepare("INSERT INTO guardian (g_first_name, g_last_name, g_phone, g_email, g_address, g_occupation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $first, $last, $phone, $email, $address, $occupation);
        // Check result of insertion
        if ($stmt->execute()) {
            $success = "Guardian added successfully!";
        } else {
            $error = "Error adding guardian: " . $conn->error;
        }
    }

    // Link Guardian to Pupil Form
    if (isset($_POST['link_guardian'])) {
        // Get form values
        $guardian_id = (int)$_POST['guardian_id'];
        $pupil_id = (int)$_POST['pupil_id'];
        $relationship = sanitize_input($_POST['relationship_type']);

        // Prevent duplicate linking
        $check = $conn->prepare("SELECT * FROM pupil_guardian WHERE pupil_id = ? AND guardian_id = ?");
        $check->bind_param("ii", $pupil_id, $guardian_id);
        $check->execute();
        $result = $check->get_result();

        // Insert if not already linked
        if ($result->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO pupil_guardian (pupil_id, guardian_id, relationship_type) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $pupil_id, $guardian_id, $relationship);
            if ($stmt->execute()) {
                $success = "Guardian linked to pupil.";
            } else {
                $error = "Error linking guardian: " . $conn->error;
            }
        } else {
            $error = "This guardian is already linked to the pupil.";
        }
    }
}

// Handle Delete Guardian
// Handle guardian deletion request
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $guardian_id = (int)$_GET['id'];

    // Check if this guardian has pupils
    $stmt = $conn->prepare("SELECT * FROM pupil_guardian WHERE guardian_id = ?");
    $stmt->bind_param("i", $guardian_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Only allow deletion if no pupils linked
    if ($result->num_rows > 0) {
        $error = "Cannot delete guardian with associated pupils.";
    } else {
        $stmt = $conn->prepare("DELETE FROM guardian WHERE guardian_id = ?");
        $stmt->bind_param("i", $guardian_id);
        if ($stmt->execute()) {
            $success = "Guardian deleted successfully.";
        } else {
            $error = "Error deleting guardian: " . $conn->error;
        }
    }
}

// Unlink guardian from pupil
// Handle unlinking guardian from a pupil
if (isset($_GET['action']) && $_GET['action'] === 'unlink') {
    $guardian_id = (int)$_GET['guardian_id'];
    $pupil_id = (int)$_GET['pupil_id'];

    // Perform the unlinking
    $stmt = $conn->prepare("DELETE FROM pupil_guardian WHERE guardian_id = ? AND pupil_id = ?");
    $stmt->bind_param("ii", $guardian_id, $pupil_id);
    if ($stmt->execute()) {
        $success = "Guardian unlinked from pupil.";
    } else {
        $error = "Unlinking failed: " . $conn->error;
    }
}

// Retrieve Pupil List
// Get all pupils for linking purposes
$pupil_query = $conn->query("SELECT pupil_id, CONCAT(p_first_name, ' ', p_last_name) AS name FROM pupil ORDER BY pupil_id ASC");

// Retrieve Guardian Data (optionally filtered by search)

// 1) Default complete list of guardians
$all_guardians_result = $conn->query("SELECT * FROM guardian ORDER BY guardian_id ASC");
$all_guardians = [];
while ($row = $all_guardians_result->fetch_assoc()) {
    $all_guardians[] = $row;
}

// 2) If there is a search, obtain the search results separately.
$search_guardians = [];
$search = '';
$search_type = $_GET['search_type'] ?? 'name';

// Perform search if search keyword is provided
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = sanitize_input($_GET['search']);
    $like = "%$search%";

    // Different query based on search type
    switch ($search_type) {
        case 'pupil_id':
            // Join with pupil_guardian and pupil tables
            $stmt = $conn->prepare("
                SELECT g.*, p.pupil_id, CONCAT(p.p_first_name, ' ', p.p_last_name) AS pupil_name, pg.relationship_type
                FROM guardian g
                JOIN pupil_guardian pg ON g.guardian_id = pg.guardian_id
                JOIN pupil p ON pg.pupil_id = p.pupil_id
                WHERE p.pupil_id = ?");
            $stmt->bind_param("i", $search);
            break;

        case 'pupil_name':
            // Search by pupil name (first or last)
            $stmt = $conn->prepare("
                SELECT g.*, p.pupil_id, CONCAT(p.p_first_name, ' ', p.p_last_name) AS pupil_name, pg.relationship_type
                FROM guardian g
                JOIN pupil_guardian pg ON g.guardian_id = pg.guardian_id
                JOIN pupil p ON pg.pupil_id = p.pupil_id
                WHERE p.p_first_name LIKE ? OR p.p_last_name LIKE ?");
            $stmt->bind_param("ss", $like, $like);
            break;

        case 'phone':
            // Search by guardian phone
            $stmt = $conn->prepare("SELECT * FROM guardian WHERE g_phone LIKE ?");
            $stmt->bind_param("s", $like);
            break;

        case 'email':
            // Search by guardian email
            $stmt = $conn->prepare("SELECT * FROM guardian WHERE g_email LIKE ?");
            $stmt->bind_param("s", $like);
            break;

        case 'occupation':
            $stmt = $conn->prepare("SELECT * FROM guardian WHERE g_occupation LIKE ?");
            $stmt->bind_param("s", $like);
            break;

        default: // guardian name
            $stmt = $conn->prepare("SELECT * FROM guardian WHERE g_first_name LIKE ? OR g_last_name LIKE ?");
            $stmt->bind_param("ss", $like, $like);
            break;
    }
    
    // Execute the search and store results
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $search_guardians[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian Management</title>
    <link rel="stylesheet" href="guardian.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!--Back to Homepage Button-->
<a href="homepage.php" class="home-button" aria-label="Return to homepage">
    <span class="desktop-text">Return to Home</span>
    <span class="mobile-text">Home</span>
</a>

<!-- Main Content -->
<h1>Guardian Management</h1>
<!-- Navigation links for different sections -->
<div class="nav">
    <a href="#full-list" class="nav-link">Guardian List</a> |
    <a href="#add_guardian_form" class="nav-link">Add Guardian</a> |
    <a href="#link_guardian_form" class="nav-link">Link to Pupil</a>
</div>

<!-- Success/Error Messages -->
<!-- Display success or error messages based on form submission results -->
<?php if (isset($success)): ?>
    <div class="success"><?= $success ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="error"><?= $error ?></div>
<?php endif; ?>

<!-- Search Form for filtering guardians based on selected criteria -->
<div class="form-section">
    <h2>Search Guardians</h2>
    <form method="GET">
        <!-- Dropdown to choose the search field (e.g., name, phone, pupil name etc.) -->
    <select name="search_type">
        <option value="name">Guardian Name</option>
        <option value="phone">Phone</option>
        <option value="email">Email</option>
        <option value="occupation">Occupation</option>
        <option value="pupil_name">Pupil Name</option>
        <option value="pupil_id">Pupil ID</option>
    </select>
    <!-- Input field for the search keyword -->
    <input type="text" name="search" placeholder="Enter keyword" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <!-- Reset button to clear the search -->
    <a href="guardian.php" class="btn-cancel">Reset</a>
    </form>
</div>

<!-- Search Results -->
<!-- If there are search results, display them in a table -->
<!-- If no results, show a message -->
<?php if ($search && count($search_guardians) > 0): ?>
<section class="form-section">
    <h2>Search Results (<?= count($search_guardians) ?>)</h2>
    <table>
        <!-- Table headers for guardian details -->
        <tr>
            <th>Guardian ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Address</th>
            <th>Occupation</th>
            <th>Pupils</th>
            <th>Relationship</th>
        </tr>
        <!-- Loop through each guardian in the search results and display their details -->
        <!-- Use prepared statements to prevent SQL injection -->
        <!-- Use htmlspecialchars to prevent XSS attacks -->
        <?php foreach ($search_guardians as $g): ?>
            <tr>
                <td><?= $g['guardian_id'] ?></td>
                <td><?= htmlspecialchars($g['g_first_name'] . ' ' . $g['g_last_name']) ?></td>
                <td><?= htmlspecialchars($g['g_phone']) ?></td>
                <td><?= htmlspecialchars($g['g_email']) ?></td>
                <td><?= htmlspecialchars($g['g_address']) ?></td>
                <td><?= htmlspecialchars($g['g_occupation']) ?></td>
                <!-- Fetch linked pupils for this guardian -->
                <td>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT p.pupil_id, CONCAT(p_first_name, ' ', p_last_name) AS pupil_name 
                        FROM pupil_guardian pg 
                        JOIN pupil p ON pg.pupil_id = p.pupil_id 
                        WHERE pg.guardian_id = ?
                    ");
                    $stmt->bind_param("i", $g['guardian_id']);
                    $stmt->execute();
                    $pupil_result = $stmt->get_result();
                    $linked = [];
                    // Loop through each linked pupil and display their name and ID
                    while ($p = $pupil_result->fetch_assoc()) {
                        $linked[] = "{$p['pupil_name']} (ID: {$p['pupil_id']})";
                    }
                    echo implode('<br>', $linked) ?: "<em>None</em>";
                    ?>
                </td>
                <!-- Fetch relationship types for this guardian -->
                <!-- Loop through each relationship type and display it -->
                <td>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT relationship_type 
                        FROM pupil_guardian 
                        WHERE guardian_id = ?
                    ");
                    $stmt->bind_param("i", $g['guardian_id']);
                    $stmt->execute();
                    $rel_result = $stmt->get_result();
                    $rels = [];
                    while ($r = $rel_result->fetch_assoc()) {
                        $rels[] = $r['relationship_type'];
                    }
                    echo implode('<br>', $rels) ?: "<em>None</em>";
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<!-- Display message if no matching search results -->
<?php elseif ($search): ?>
    <div class="notice">No matching guardians found.</div>
<?php endif; ?>


<!-- Add Guardian -->
<section id="add_guardian_form" class="form-section">
    <!-- Form to add a new guardian -->
    <!-- Use POST method to submit the form -->
    <!-- Include hidden input to identify the form submission -->
    <h2>Add New Guardian</h2>
    <form method="POST">
        <input type="hidden" name="add_guardian" value="1">
        <div class="form-row">
            <label>First Name:
                <input type="text" name="first_name" required>
            </label>
        </div>
        <div class="form-row">
            <label>Last Name:
                <input type="text" name="last_name" required>
            </label>
        </div>
        <div class="form-row">
            <label>Phone:
                <input type="text" name="phone" required>
            </label>
        </div>
        <div class="form-row">
            <label>Email:
                <input type="email" name="email">
            </label>
        </div>
        <div class="form-row">
            <label>Address:
                <input type="text" name="address">
            </label>
        </div>
        <div class="form-row">
            <label>Occupation:
                <input type="text" name="occupation">
            </label>
        </div>
        <div class="form-row">
            <button type="submit">Add Guardian</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</section>

<!-- Link Guardian to Pupil -->
<section id="link_guardian_form" class="form-section">
    <!-- Form to link a guardian to a pupil -->
    <h2>Link Guardian to Pupil</h2>
    <!-- Use POST method to submit the form -->
    <form method="POST">
        <!-- Hidden input to identify the form submission -->
        <input type="hidden" name="link_guardian" value="1">
        <div class="form-row">
            <label>Select Guardian:
                <select name="guardian_id" required>
                    <!-- Loop through all guardians and display them in a dropdown -->
                    <!-- Use htmlspecialchars to prevent XSS attacks -->
                <?php foreach ($all_guardians as $g): ?>
                        <option value="<?= $g['guardian_id'] ?>">
                            <?= htmlspecialchars($g['g_first_name'] . ' ' . $g['g_last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <!-- Dropdown to select a pupil from the list -->
            <label>Select Pupil:
                <select name="pupil_id" required>
                    <!-- Loop through all pupils and display them in a dropdown -->
                    <?php while ($row = $pupil_query->fetch_assoc()): ?>
                        <option value="<?= $row['pupil_id'] ?>"><?= $row['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>Relationship Type:
                <input type="text" name="relationship_type" placeholder="e.g. Mother, Father, Uncle" required>
            </label>
        </div>
        <div class="form-row">
            <button type="submit">Link</button>
            <a href="?" class="btn-cancel">Cancel</a>
        </div>
    </form>
</section>

<!-- Guardian Full List -->
<section id="full-list" class="form-section">
    <!-- Display the full list of guardians -->
    <!-- Use a table to display guardian details -->
    <!-- Include headers for each column -->
    <h2>Guardian Full List</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Address</th>
            <th>Occupation</th>
            <th>Pupils</th>
            <th>Relationship</th>
            <th>Action</th>
        </tr>
        <?php foreach ($all_guardians as $g): ?>
            <!-- Loop through each guardian and display their details -->
            <tr>
                <td><?= $g['guardian_id'] ?></td>
                <td><?= htmlspecialchars($g['g_first_name'] . ' ' . $g['g_last_name']) ?></td>
                <td><?= htmlspecialchars($g['g_phone']) ?></td>
                <td><?= htmlspecialchars($g['g_email']) ?></td>
                <td><?= htmlspecialchars($g['g_address']) ?></td>
                <td><?= htmlspecialchars($g['g_occupation']) ?></td>
                <td>
                    <?php
                    // Fetch linked pupils for this guardian
                    // Use prepared statements to prevent SQL injection
                        $stmt = $conn->prepare("
                            SELECT p.pupil_id, CONCAT(p_first_name, ' ', p_last_name) AS pupil_name, relationship_type 
                            FROM pupil_guardian pg 
                            JOIN pupil p ON pg.pupil_id = p.pupil_id 
                            WHERE pg.guardian_id = ?
                        ");
                    // Bind the guardian ID to the prepared statement
                    // Execute the statement and fetch results
                    // Store the linked pupils in an array
                    // Loop through each linked pupil and display their name and ID
                    $stmt->bind_param("i", $g['guardian_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $linked_rows = [];
                    // Fetch each linked pupil and store in the array
                    while ($row = $result->fetch_assoc()) {
                        // Store the pupil ID, name, and relationship type
                        $linked_rows[] = $row;
                    }
                
                    foreach ($linked_rows as $row): ?>
                        <!-- Display each linked pupil's name and ID -->
                        <div><?= htmlspecialchars($row['pupil_name']) ?> (ID: <?= $row['pupil_id'] ?>)</div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php foreach ($linked_rows as $row): ?>
                        <div>
                            <!-- Display the relationship type for each linked pupil -->
                            <?= htmlspecialchars($row['relationship_type']) ?>
                            <a href="?action=unlink&guardian_id=<?= $g['guardian_id'] ?>&pupil_id=<?= $row['pupil_id'] ?>" onclick="return confirm('Unlink this pupil?')"><i class="fas fa-times text-danger"></i></a>
                        </div>
                    <?php endforeach; ?>
                </td>

                <td>
                    <!-- Action buttons for each guardian -->
                    <a href="?action=delete&id=<?= $g['guardian_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

</body>
</html>

<?php
// All logic done, now close connection
$conn->close();
?>