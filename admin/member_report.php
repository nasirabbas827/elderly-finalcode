<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$member_id = intval($_GET['id'] ?? 0);

// Validate ownership
$stmt = $conn->prepare("SELECT * FROM elderly_members WHERE member_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member_result = $stmt->get_result();
$member = $member_result->fetch_assoc();
$stmt->close();

if (!$member) {
    echo "<div class='alert alert-danger'>Member not found or unauthorized.</div>";
    exit;
}

// Fetch related data
$stmt_appointments = $conn->prepare("SELECT * FROM appointments WHERE elderly_id = ? ORDER BY appointment_date DESC");
$stmt_appointments->bind_param("i", $member_id);
$stmt_appointments->execute();
$appointments = $stmt_appointments->get_result();
$stmt_appointments->close();

$stmt_activities = $conn->prepare("SELECT * FROM daily_activities WHERE elderly_id = ? ORDER BY date DESC");
$stmt_activities->bind_param("i", $member_id);
$stmt_activities->execute();
$activities = $stmt_activities->get_result();
$stmt_activities->close();

$stmt_medical = $conn->prepare("SELECT * FROM medical_records WHERE elderly_id = ? ORDER BY record_date DESC");
$stmt_medical->bind_param("i", $member_id);
$stmt_medical->execute();
$medical = $stmt_medical->get_result();
$stmt_medical->close();

$stmt_emergencies = $conn->prepare("SELECT * FROM emergency_reports WHERE elderly_id = ? ORDER BY report_time DESC");
$stmt_emergencies->bind_param("i", $member_id);
$stmt_emergencies->execute();
$emergencies = $stmt_emergencies->get_result();
$stmt_emergencies->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Full Report</title>
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
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .report-table th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }
        .report-table td {
            vertical-align: middle;
        }
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
        .btn-primary, .btn-dark, .btn-secondary {
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
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        @media print {
            .no-print { display: none; }
            .profile-card, .report-card { box-shadow: none; }
            body { background-color: white; }
        }
        @media (max-width: 768px) {
            .profile-card, .report-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Report: <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h2>
        <button class="btn btn-secondary no-print" onclick="window.print()">Print Report</button>
    </div>

    <div class="row">
        <!-- Profile Section (Left) -->
        <div class="col-lg-4 col-md-12">
            <div class="profile-card">
                <img
                    src="<?= !empty($member['profile_picture']) ? htmlspecialchars('../admin/uploads/' . $member['profile_picture']) : '../images/default_profile.png' ?>"
                    alt="Profile Picture"
                    class="profile-picture mx-auto d-block"
                >
                <div class="profile-info text-center">
                    <h3>Profile Details</h3>
                    <p><strong>Gender:</strong> <?= htmlspecialchars(ucfirst($member['gender'])) ?></p>
                    <p><strong>DOB:</strong> <?= htmlspecialchars($member['date_of_birth']) ?></p>
                    <p><strong>Admission Date:</strong> <?= htmlspecialchars($member['admission_date']) ?></p>
                    <p><strong>Room:</strong> <?= htmlspecialchars($member['room_number']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($member['status'])) ?></p>
                    <p><strong>Health Status:</strong><br> <?= nl2br(htmlspecialchars($member['health_status'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Report Tables (Right) -->
        <div class="col-lg-8 col-md-12">
            <div class="report-card">
                <!-- Appointments -->
                <div class="section-title">Appointments</div>
                <?php if ($appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered report-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($a = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($a['type'])) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($a['status'])) ?></td>
                                        <td><?= nl2br(htmlspecialchars($a['notes'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No appointments recorded.</p>
                <?php endif; ?>

                <!-- Daily Activities -->
                <div class="section-title">Daily Activities</div>
                <?php if ($activities->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered report-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($act = $activities->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($act['date']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($act['activity_type'])) ?></td>
                                        <td><?= nl2br(htmlspecialchars($act['description'])) ?></td>
                                        <td><?= htmlspecialchars($act['start_time']) ?></td>
                                        <td><?= htmlspecialchars($act['end_time']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($act['status'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No daily activities recorded.</p>
                <?php endif; ?>

                <!-- Medical Records -->
                <div class="section-title">Medical Records</div>
                <?php if ($medical->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered report-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Diagnosis</th>
                                    <th>Severity</th>
                                    <th>Medications</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($m = $medical->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['record_date']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($m['record_type'])) ?></td>
                                        <td><?= nl2br(htmlspecialchars($m['diagnosis'])) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($m['severity'])) ?></td>
                                        <td><?= nl2br(htmlspecialchars($m['medications'])) ?></td>
                                        <td><?= nl2br(htmlspecialchars($m['notes'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No medical records available.</p>
                <?php endif; ?>

                <!-- Emergency Reports -->
                <div class="section-title">Emergency Reports</div>
                <?php if ($emergencies->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered report-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Notified Admin</th>
                                    <th>Notified Family</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($e = $emergencies->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($e['report_time']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($e['description'])) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($e['status'])) ?></td>
                                        <td><?= $e['notified_admin'] ? 'Yes' : 'No' ?></td>
                                        <td><?= $e['notified_family'] ? 'Yes' : 'No' ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No emergency reports recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="text-right mt-4 no-print">
        <a href="manage_elderly_members.php" class="btn btn-dark">Back</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>