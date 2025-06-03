<?php
session_start();
include('config.php');

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== 'family_member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["id"];

// Fetch user profile
$stmt_user = $conn->prepare("SELECT username, email, phone, age, bio, profile_picture, status FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();

// Fetch elderly members
$stmt_members = $conn->prepare("SELECT member_id, first_name, last_name, date_of_birth, health_status, status, profile_picture FROM elderly_members WHERE family_member_id = ? ORDER BY created_at DESC");
$stmt_members->bind_param("i", $user_id);
$stmt_members->execute();
$members = $stmt_members->get_result();
$stmt_members->close();

// Fetch recent appointments (limit to 5)
$stmt_appointments = $conn->prepare("SELECT a.id, a.appointment_date, a.type, a.status, e.first_name, e.last_name 
    FROM appointments a 
    JOIN elderly_members e ON a.elderly_id = e.member_id 
    WHERE a.family_member_id = ? 
    ORDER BY a.appointment_date DESC 
    LIMIT 5");
$stmt_appointments->bind_param("i", $user_id);
$stmt_appointments->execute();
$appointments = $stmt_appointments->get_result();
$stmt_appointments->close();

// Fetch recent emergency reports (limit to 5)
$stmt_emergencies = $conn->prepare("SELECT er.id, er.report_time, er.description, er.status, e.first_name, e.last_name 
    FROM emergency_reports er 
    JOIN elderly_members e ON er.elderly_id = e.member_id 
    WHERE e.family_member_id = ? 
    ORDER BY er.report_time DESC 
    LIMIT 5");
$stmt_emergencies->bind_param("i", $user_id);
$stmt_emergencies->execute();
$emergencies = $stmt_emergencies->get_result();
$stmt_emergencies->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Member Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>

        .container {
            max-width: 1200px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-5px);
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .profile-info h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .profile-info p {
            margin-bottom: 10px;
            color: #555;
        }
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-table th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }
        .dashboard-table td {
            vertical-align: middle;
        }
        .status-pending { color: #ffc107; font-weight: 500; }
        .status-approved, .status-completed { color: #28a745; font-weight: 500; }
        .status-rejected, .status-open { color: #dc3545; font-weight: 500; }
        h2 {
            color: #333;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }
        .section-title {
            color: #007bff;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .btn-primary, .btn-dark {
            border-radius: 8px;
            padding: 8px 20px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-dark:hover {
            background-color: #343a40;
        }
        @media (max-width: 768px) {
            .profile-card, .dashboard-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container">
    <h2>Welcome back, <?= htmlspecialchars($user['username']) ?>!</h2>

    <div class="row">
        <!-- Profile Section (Left) -->
        <div class="col-lg-4 col-md-12">
            <div class="profile-card">
                <img
                    src="<?= !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../images/default_profile.png' ?>"
                    alt="Profile Picture"
                    class="profile-picture mx-auto d-block"
                >
                <div class="profile-info text-center">
                    <h3>Your Profile</h3>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
                    <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($user['status'])) ?></p>
                    <p><strong>About You:</strong><br> <?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Dashboard Content (Right) -->
        <div class="col-lg-8 col-md-12">
            <div class="dashboard-card">
                <!-- Elderly Members -->
                <div class="section-title">Your Elderly Members</div>
                <?php if ($members->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dashboard-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>DOB</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($m = $members->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                                        <td><?= htmlspecialchars($m['date_of_birth']) ?></td>
                                        <td class="status-<?= strtolower($m['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($m['status'])) ?>
                                        </td>
                                        <td>
                                            <a href="member_full_report.php?id=<?= $m['member_id'] ?>" class="btn btn-sm btn-primary">View Report</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No elderly members associated with your account.</p>
                <?php endif; ?>

                <!-- Recent Appointments -->
                <div class="section-title">Recent Appointments</div>
                <?php if ($appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dashboard-table">
                            <thead>
                                <tr>
                                    <th>Elderly</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($a = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                        <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($a['type'])) ?></td>
                                        <td class="status-<?= strtolower($a['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($a['status'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent appointments.</p>
                <?php endif; ?>

                <!-- Recent Emergency Reports -->
                <div class="section-title">Recent Emergency Reports</div>
                <?php if ($emergencies->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dashboard-table">
                            <thead>
                                <tr>
                                    <th>Elderly</th>
                                    <th>Time</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($e = $emergencies->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($e['first_name']) ?></td>
                                        <td><?= htmlspecialchars($e['report_time']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($e['description'])) ?></td>
                                        <td class="status-<?= strtolower($e['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($e['status'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent emergency reports.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>