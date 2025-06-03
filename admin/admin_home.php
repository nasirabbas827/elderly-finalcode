<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// User status count
$user_status_sql = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
$user_status_result = mysqli_query($conn, $user_status_sql);
$user_status_data = [];
while ($row = mysqli_fetch_assoc($user_status_result)) {
    $user_status_data[$row['status']] = $row['count'];
}

// Appointment count
$appointments_sql = "SELECT COUNT(*) as count FROM appointments WHERE status = 'approved'";
$appointments_result = mysqli_query($conn, $appointments_sql);
$total_appointments = mysqli_fetch_assoc($appointments_result)['count'];

// Daily activities count
$activities_sql = "SELECT COUNT(*) as count FROM daily_activities WHERE status = 'completed'";
$activities_result = mysqli_query($conn, $activities_sql);
$total_activities = mysqli_fetch_assoc($activities_result)['count'];

// Donations count
$donations_sql = "SELECT COUNT(*) as count FROM donations WHERE acknowledged = 'approved'";
$donations_result = mysqli_query($conn, $donations_sql);
$total_donations = mysqli_fetch_assoc($donations_result)['count'];

// Elderly members count
$elderly_sql = "SELECT COUNT(*) as count FROM elderly_members WHERE status = 'active'";
$elderly_result = mysqli_query($conn, $elderly_sql);
$total_elderly = mysqli_fetch_assoc($elderly_result)['count'];

// Emergency reports count
$emergency_sql = "SELECT COUNT(*) as count FROM emergency_reports WHERE status = 'open'";
$emergency_result = mysqli_query($conn, $emergency_sql);
$total_emergency = mysqli_fetch_assoc($emergency_result)['count'];

// Medical records count
$medical_sql = "SELECT COUNT(*) as count FROM medical_records WHERE severity = 'critical'";
$medical_result = mysqli_query($conn, $medical_sql);
$total_critical_medical = mysqli_fetch_assoc($medical_result)['count'];

// Staff count
$staff_sql = "SELECT COUNT(*) as count FROM staff";
$staff_result = mysqli_query($conn, $staff_sql);
$total_staff = mysqli_fetch_assoc($staff_result)['count'];

// Pending user approvals
$pending_users_sql = "SELECT id, username, email, phone, user_type FROM users WHERE status = 'pending'";
$pending_users_result = mysqli_query($conn, $pending_users_sql);

// Data for charts
$activity_types_sql = "SELECT activity_type, COUNT(*) as count FROM daily_activities GROUP BY activity_type";
$activity_types_result = mysqli_query($conn, $activity_types_sql);
$activity_types_data = [];
while ($row = mysqli_fetch_assoc($activity_types_result)) {
    $activity_types_data[$row['activity_type']] = $row['count'];
}

// Fetch open emergency reports
$emergency_notifications_sql = "
    SELECT er.id, er.report_time, er.description, e.first_name, e.last_name, u.username 
    FROM emergency_reports er 
    JOIN elderly_members e ON er.elderly_id = e.member_id 
    JOIN users u ON er.reported_by = u.id 
    WHERE er.status = 'open'
    ORDER BY er.report_time DESC
";
$emergency_notifications_result = mysqli_query($conn, $emergency_notifications_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        canvas {
            max-height: 250px;
        }
        .card-deck {
            display: flex;
            flex-wrap: wrap;
        }
        .card {
            flex: 1 0 0%;
            margin: 10px;
        }
    </style>
</head>
<body>
<?php include('admin_navbar.php'); ?>



<div class="container mt-5 mb-5">
    <h2 class="text-center text-primary mb-4">Admin Dashboard</h2>

<div class="mb-4">
    <?php if (mysqli_num_rows($emergency_notifications_result) > 0): ?>
        <?php while ($notification = mysqli_fetch_assoc($emergency_notifications_result)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Emergency Report (ID: <?php echo $notification['id']; ?>)</strong><br>
                <p class="mb-0">
                    Reported by: <?php echo htmlspecialchars($notification['username']); ?><br>
                    For: <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?><br>
                    Time: <?php echo htmlspecialchars($notification['report_time']); ?><br>
                    Description: <?php echo nl2br(htmlspecialchars($notification['description'])); ?>
                </p>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            No open emergency reports at this time.
        </div>
    <?php endif; ?>
</div>

<script>
// Fallback JavaScript for alert dismissal
document.querySelectorAll('.alert-dismissible .close').forEach(button => {
    button.addEventListener('click', function() {
        const alert = this.closest('.alert');
        alert.classList.remove('show');
        alert.classList.add('fade');
        setTimeout(() => alert.remove(), 150); // Remove after fade transition
    });
});
</script>

    <!-- Dashboard cards -->
    <div class="card-deck mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Approved Users</h5>
                <p class="card-text display-4"><?php echo $user_status_data['approved'] ?? 0; ?></p>
            </div>
        </div>
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Approved Appointments</h5>
                <p class="card-text display-4"><?php echo $total_appointments; ?></p>
            </div>
        </div>
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">Completed Activities</h5>
                <p class="card-text display-4"><?php echo $total_activities; ?></p>
            </div>
        </div>
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Approved Donations</h5>
                <p class="card-text display-4"><?php echo $total_donations; ?></p>
            </div>
        </div>
    </div>
    <div class="card-deck mb-4">
        <div class="card text-white bg-dark">
            <div class="card-body">
                <h5 class="card-title">Active Elderly Members</h5>
                <p class="card-text display-4"><?php echo $total_elderly; ?></p>
            </div>
        </div>
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Open Emergency Reports</h5>
                <p class="card-text display-4"><?php echo $total_emergency; ?></p>
            </div>
        </div>
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <h5 class="card-title">Critical Medical Records</h5>
                <p class="card-text display-4"><?php echo $total_critical_medical; ?></p>
            </div>
        </div>
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Staff</h5>
                <p class="card-text display-4"><?php echo $total_staff; ?></p>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">User Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="userStatusPieChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Activity Types Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="activityTypesPieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending users -->
    <div class="card mb-5">
        <div class="card-header bg-primary text-white">
            <h5>Pending User Approvals</h5>
        </div>
        <div class="card-body p-0">
            <?php if (mysqli_num_rows($pending_users_result) > 0): ?>
                <table class="table table-bordered table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>User Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($pending_users_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                                <td>
                                    <form method="POST" action="approve_user.php" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="action" value="authorize" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-3 alert alert-info">No users awaiting approval.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // User Status Pie Chart
    const userStatusCtx = document.getElementById('userStatusPieChart').getContext('2d');
    new Chart(userStatusCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [
                    <?php echo $user_status_data['pending'] ?? 0; ?>,
                    <?php echo $user_status_data['approved'] ?? 0; ?>,
                    <?php echo $user_status_data['rejected'] ?? 0; ?>
                ],
                backgroundColor: ['#ffcc00', '#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Activity Types Pie Chart
    const activityTypesCtx = document.getElementById('activityTypesPieChart').getContext('2d');
    new Chart(activityTypesCtx, {
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
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>
</body>
</html>