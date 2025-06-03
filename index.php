<?php
session_start();
include('config.php');

// Fetch key metrics
$elderly_sql = "SELECT COUNT(*) as count FROM elderly_members WHERE status = 'active'";
$elderly_result = mysqli_query($conn, $elderly_sql);
$total_elderly = mysqli_fetch_assoc($elderly_result)['count'];

$staff_sql = "SELECT COUNT(*) as count FROM staff";
$staff_result = mysqli_query($conn, $staff_sql);
$total_staff = mysqli_fetch_assoc($staff_result)['count'];

$activities_sql = "SELECT COUNT(*) as count FROM daily_activities WHERE status = 'completed'";
$activities_result = mysqli_query($conn, $activities_sql);
$total_activities = mysqli_fetch_assoc($activities_result)['count'];

$emergency_sql = "SELECT COUNT(*) as count FROM emergency_reports WHERE status = 'open'";
$emergency_result = mysqli_query($conn, $emergency_sql);
$total_emergency = mysqli_fetch_assoc($emergency_result)['count'];

// Activity type distribution for chart
$activity_types_sql = "SELECT activity_type, COUNT(*) as count FROM daily_activities GROUP BY activity_type";
$activity_types_result = mysqli_query($conn, $activity_types_sql);
$activity_types_data = [];
while ($row = mysqli_fetch_assoc($activity_types_result)) {
    $activity_types_data[$row['activity_type']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elderly Care Home Management System</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --accent: #ff6f61;
            --highlight: #6f42c1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, #e0e7ff 100%);
            color: var(--dark);
        }
        .navbar {
            background-color: var(--dark);
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .navbar-brand:hover, .nav-link:hover {
            color: var(--accent) !important;
        }
        .hero-section {
            height: 600px;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./images/hotel.jpg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 0 0 30px 30px;
            animation: fadeIn 1.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .hero-section .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            padding: 12px 30px;
            font-size: 1.2rem;
            transition: transform 0.3s, background-color 0.3s;
        }
        .hero-section .btn-primary:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
            transform: scale(1.05);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-img-top {
            height: 220px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        .search-form {
            background-color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            margin-bottom: 40px;
            border-left: 5px solid var(--accent);
        }
        .section {
            padding: 80px 0;
        }
        .section-title {
            color: var(--highlight);
            margin-bottom: 40px;
            font-weight: 700;
            position: relative;
        }
        .section-title::after {
            content: '';
            width: 60px;
            height: 4px;
            background-color: var(--accent);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        .feature-icon {
            font-size: 3em;
            color: var(--accent);
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        .card:hover .feature-icon {
            color: var(--highlight);
        }
        .chart-container {
            max-width: 450px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        footer {
            background-color: var(--dark);
            color: white;
            padding: 30px 0;
            border-top: 4px solid var(--accent);
        }
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        .btn-success:hover {
            background-color: var(--highlight);
            border-color: var(--highlight);
        }
        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 8px rgba(255, 111, 97, 0.3);
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <h1 class="display-4 fw-bold">Elderly Care Home Management System</h1>
        <p class="lead mb-4">Compassionate Care for Your Loved Ones</p>
        <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
            <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>Login to Explore</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard</a>
        <?php endif; ?>
    </div>

    <!-- About Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">About Our Care System</h2>
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="lead">The Elderly Care Home Management System is designed to streamline the management of care homes, ensuring the well-being of elderly residents through efficient scheduling, medical tracking, and emergency response.</p>
                    <p>Our platform supports caregivers, medical staff, and families by providing real-time updates on daily activities, medical records, and emergency reports, fostering a safe and nurturing environment for seniors.</p>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h4 class="text-center mb-4">Daily Activity Distribution</h4>
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Key Features</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-heartbeat feature-icon"></i>
                            <h5 class="card-title">Health Monitoring</h5>
                            <p class="card-text">Track medical records and health status of residents with detailed reports and alerts for critical conditions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-calendar-check feature-icon"></i>
                            <h5 class="card-title">Activity Scheduling</h5>
                            <p class="card-text">Manage daily activities like meals, medications, and events with easy-to-use scheduling tools.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <i class="fas fa-exclamation-triangle feature-icon"></i>
                            <h5 class="card-title">Emergency Response</h5>
                            <p class="card-text">Quickly report and track emergencies with notifications to staff and family members.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Section -->
    <div class="section bg-light">
        <div class="container">
            <h2 class="text-center section-title">Our Impact</h2>
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Active Residents</h5>
                            <p class="card-text display-4"><?php echo $total_elderly; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Dedicated Staff</h5>
                            <p class="card-text display-4"><?php echo $total_staff; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Completed Activities</h5>
                            <p class="card-text display-4"><?php echo $total_activities; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Open Emergency Reports</h5>
                            <p class="card-text display-4"><?php echo $total_emergency; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="section">
        <div class="container">
            <h2 class="text-center section-title">Get in Touch</h2>
            <form method="post" action="contact.php" class="search-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                    </div>
                    <div class="col-md-6">
                        <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                    </div>
                    <div class="col-md-12 mt-2">
                        <textarea name="message" class="form-control" rows="5" placeholder="Your Message" required></textarea>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class=" mt-2 btn btn-primary w-100"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">© 2025 Elderly Care Home Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activity Distribution Pie Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Meal', 'Medication', 'Exercise', 'Event', 'Other'],
                datasets: [{
                    data: [
                        <?php echo $activity_types_data['meal'] ?? 0; ?>,
                        <?php echo $activity_types_data['medication'] ?? 0; ?>,
                        <?php echo $activity_types_data['exercise'] ?? 0; ?>,
                        <?php echo $activity_types_data['event'] ?? 0; ?>,
                        <?php echo $activity_types_data['other'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#007bff',  // --primary (blue)
                        '#28a745',  // --success (green)
                        '#ffc107',  // --warning (yellow)
                        '#dc3545',  // --danger (red)
                        '#6c757d'   // --secondary (gray)
                    ],
                    borderColor: 'rgba(255,255,255,0.8)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14,
                                family: 'Segoe UI'
                            },
                            color: 'var(--dark)'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'var(--dark)',
                        titleFont: { size: 16 },
                        bodyFont: { size: 14 }
                    }
                }
            }
        });
    </script>
</body>
</html>