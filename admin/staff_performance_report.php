<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$staff_id = intval($_GET['staff_id'] ?? 0);

// Validate staff member
$stmt = $conn->prepare("SELECT s.*, u.username FROM staff s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff_result = $stmt->get_result();
$staff = $staff_result->fetch_assoc();
$stmt->close();

if (!$staff) {
    echo "<div class='alert alert-danger'>Staff member not found.</div>";
    exit;
}

$user_id = $staff['user_id'];

// Fetch daily activities
$stmt_activities = $conn->prepare("
    SELECT da.*, e.first_name, e.last_name 
    FROM daily_activities da 
    JOIN elderly_members e ON da.elderly_id = e.member_id 
    WHERE da.staff_id = ? 
    ORDER BY da.date DESC, da.start_time DESC
");
$stmt_activities->bind_param("i", $user_id);
$stmt_activities->execute();
$activities = $stmt_activities->get_result();
$stmt_activities->close();

// Fetch emergency reports
$stmt_emergencies = $conn->prepare("
    SELECT er.*, e.first_name, e.last_name 
    FROM emergency_reports er 
    JOIN elderly_members e ON er.elderly_id = e.member_id 
    WHERE er.reported_by = ? 
    ORDER BY er.report_time DESC
");
$stmt_emergencies->bind_param("i", $user_id);
$stmt_emergencies->execute();
$emergencies = $stmt_emergencies->get_result();
$stmt_emergencies->close();

// Fetch medical records
$stmt_medical = $conn->prepare("
    SELECT mr.*, e.first_name, e.last_name 
    FROM medical_records mr 
    JOIN elderly_members e ON mr.elderly_id = e.member_id 
    WHERE mr.staff_id = ? 
    ORDER BY mr.record_date DESC
");
$stmt_medical->bind_param("i", $user_id);
$stmt_medical->execute();
$medical = $stmt_medical->get_result();
$stmt_medical->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Performance Report for <?= htmlspecialchars($staff['username']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin-top: 30px;
            margin-bottom: 30px;
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
        .btn-dark, .btn-secondary {
            border-radius: 8px;
            padding: 8px 20px;
            transition: background-color 0.3s ease;
        }
        .btn-dark:hover {
            background-color: #343a40;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        @media print {
            .no-print { display: none; }
            .report-card { box-shadow: none; }
            body { background-color: white; }
        }
        @media (max-width: 768px) {
            .report-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Staff Performance Report: <?= htmlspecialchars($staff['username']) ?></h2>
        <button class="btn btn-secondary no-print" onclick="window.print()">Print Report</button>
    </div>

    <div class="report-card">
        <!-- Daily Activities -->
        <div class="section-title">Daily Activities</div>
        <?php if ($activities->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered report-table">
                    <thead>
                        <tr>
                            <th>Elderly Member</th>
                            <th>Date</th>
                            <th>Activity Type</th>
                            <th>Description</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($act = $activities->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($act['first_name'] . ' ' . $act['last_name']) ?></td>
                                <td><?= htmlspecialchars($act['date']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($act['activity_type'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($act['description'])) ?></td>
                                <td><?= htmlspecialchars($act['start_time'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($act['end_time'] ?? '-') ?></td>
                                <td><?= htmlspecialchars(ucfirst($act['status'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No daily activities recorded for this staff member.</p>
        <?php endif; ?>

        <!-- Emergency Reports -->
        <div class="section-title">Emergency Reports</div>
        <?php if ($emergencies->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered report-table">
                    <thead>
                        <tr>
                            <th>Elderly Member</th>
                            <th>Report Time</th>
                            <th>Description</th>
                            <th>Notified Admin</th>
                            <th>Notified Family</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($er = $emergencies->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($er['first_name'] . ' ' . $er['last_name']) ?></td>
                                <td><?= htmlspecialchars($er['report_time']) ?></td>
                                <td><?= nl2br(htmlspecialchars($er['description'])) ?></td>
                                <td><?= $er['notified_admin'] ? 'Yes' : 'No' ?></td>
                                <td><?= $er['notified_family'] ? 'Yes' : 'No' ?></td>
                                <td><?= htmlspecialchars(ucfirst($er['status'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No emergency reports recorded for this staff member.</p>
        <?php endif; ?>

        <!-- Medical Records -->
        <div class="section-title">Medical Records</div>
        <?php if ($medical->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered report-table">
                    <thead>
                        <tr>
                            <th>Elderly Member</th>
                            <th>Date</th>
                            <th>Record Type</th>
                            <th>Diagnosis</th>
                            <th>Medications</th>
                            <th>Severity</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($mr = $medical->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($mr['first_name'] . ' ' . $mr['last_name']) ?></td>
                                <td><?= htmlspecialchars($mr['record_date']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($mr['record_type'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($mr['diagnosis'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($mr['medications'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars(ucfirst($mr['severity'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($mr['notes'] ?? '-')) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">No medical records recorded for this staff member.</p>
        <?php endif; ?>
    </div>

    <div class="text-right mt-4 no-print">
        <a href="manage_staff.php" class="btn btn-dark">Back to Staff Management</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>