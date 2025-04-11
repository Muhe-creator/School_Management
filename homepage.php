<?php
session_start(); // Start or resume the session to manage login status

if (!isset($_SESSION['user'])) {
    // If no user is logged in, redirect to login page
    header("Location: index.php");
    exit; // Stop further script execution
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Define character encoding and make page responsive on all devices -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Page title shown in browser tab -->
    <title>SA Primary School - Home</title>

    <!-- Link to custom stylesheet and Bootstrap for UI components -->
    <link rel="stylesheet" href="homepage.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-light"> <!-- Use a light background color for the body -->

    <!-- Header Section: School name and navigation bar -->
    <header>
        <div class="container d-flex flex-wrap justify-content-between align-items-center">
            <h2 class="mb-0">St Alphonsus Primary School</h2> <!-- School Title -->
            <nav>
                <!-- Navigation links displayed in a horizontal list -->
                <ul class="d-flex flex-wrap align-items-center mb-0">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#news">News & Events</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                    <br><br>
                    <!-- Logout button redirects back to login screen -->
                    <a href="logout.php" class="btn btn-outline-light float-end">Logout</a>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Welcome Banner Section -->
    <section id="home" class="welcome">
        <div class="container">
            <div class="describe">
                <!-- Main welcome message and school mission -->
                <h1>Welcome to St Alphonsus Primary School</h1>
                <h5 class="lh-lg">We are committed to providing every child with high-quality education and a happy
                    growth environment.
                </h5>
                <!-- Button linking to About Us section -->
                <a href="#about" class="btn">Learn More</a>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about py-5">
        <div class="container">
            <!-- Section heading and description -->
            <h2 class="mb-4"><b>About Us</b></h2>
            <h6 class="lh-lg">St Alphonsus Primary School was established in 1990 and has a wealth of teaching
                experience and outstanding teaching staff.<br>We focus on the overall development of our students and offer a variety
                of courses and activities.</h6>
            
            <!-- Responsive 3-column image layout -->
            <div class="row g-4 mt-4">
                <!-- Classroom Image -->
                <div class="col-12 col-md-4 text-center">
                    <img src="classroom.jpg" class="img-fluid rounded shadow about-img w-100" alt="Classroom">
                    <div class="caption mt-2 fw-bold text-secondary">Classroom</div>
                </div>

                <!-- Outdoor Image -->
                <div class="col-12 col-md-4 text-center">
                    <img src="outdoor.png" class="img-fluid rounded shadow about-img w-100" alt="Outdoor Space">
                    <div class="caption mt-2 fw-bold text-secondary">Outdoor Space</div>
                </div>

                <!-- Teaching Image -->
                <div class="col-12 col-md-4 text-center">
                    <img src="teaching.jpg" class="img-fluid rounded shadow about-img w-100" alt="Teaching">
                    <div class="caption mt-2 fw-bold text-secondary">Teaching</div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Dashboard Section -->
    <section class="container py-5">
        <div class="text-center mb-5">
            <!-- Welcome text with current logged-in user's name -->
            <h2 class="display-5"><b>SA Primary School Management System</b></h2>
            <p class="lead fs-4"><b>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</b> Please choose a section to manage.</p>
        </div>

        <!-- Grid layout with 6 cards for management modules -->
        <div class="row g-4">
            <!-- Teachers Module -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Teachers</h5>
                        <p class="card-text">View and manage teacher information.</p>
                        <a href="teacher.php" class="btn btn-primary">Go to Teachers</a>
                    </div>
                </div>
            </div>

            <!-- Classes Module -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-school"></i> Classes</h5>
                        <p class="card-text">View classes, capacity, and assigned teachers.</p>
                        <a href="class.php" class="btn btn-primary">Go to Classes</a>
                    </div>
                </div>
            </div>

            <!-- Students Module -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-user-graduate"></i> Students</h5>
                        <p class="card-text">View and manage student information.</p>
                        <a href="student.php" class="btn btn-primary">Go to Students</a>
                    </div>
                </div>
            </div>

            <!-- Library Module -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-book"></i> Library</h5>
                        <p class="card-text">Check current book loans and availability.</p>
                        <a href="library.php" class="btn btn-primary">Go to Library</a>
                    </div>
                </div>
            </div>

            <!-- Guardians Module -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-users"></i> Guardians</h5>
                        <p class="card-text">Check student-guardian relationships.</p>
                        <a href="guardian.php" class="btn btn-primary">Go to Guardians</a>
                    </div>
                </div>
            </div>

            <!-- Medical Module -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="card-title"><i class="fas fa-notes-medical"></i> Medical</h5>
                        <p class="card-text">Access students' medical profiles and records.</p>
                        <a href="medical.php" class="btn btn-primary">Go to Medical</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <div class="container">
            <!-- Copyright -->
            <p>&copy; 2025 St Alphonsus Primary School. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JavaScript for interactivity (dropdowns, modals, etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
