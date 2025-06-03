<?php
session_start();
include('config.php');

if ($_SESSION["user_type"] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION["id"];
$message = "";

// Insert/Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $elderly_id = $_POST["elderly_id"];
    $report_time = $_POST["report_time"];
    $description = $_POST["description"];
    $notified_admin = isset($_POST["notified_admin"]) ? 1 : 0;
    $notified_family = isset($_POST["notified_family"]) ? 1 : 0;

    if ($report_id > 0) {
        $stmt = $conn->prepare("UPDATE emergency_reports SET elderly_id=?, report_time=?, description=?, notified_admin=?, notified_family=? WHERE id=? AND reported_by=?");
        $stmt->bind_param("issiisi", $elderly_id, $report_time, $description, $notified_admin, $notified_family, $report_id, $staff_id);
        $stmt->execute();
        $message = "Report updated.";
    } else {
        $status = 'open'; // Set default status
        $stmt = $conn->prepare("INSERT INTO emergency_reports (elderly_id, reported_by, report_time, description, notified_admin, notified_family, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iississ", $elderly_id, $staff_id, $report_time, $description, $notified_admin, $notified_family, $status);
        $stmt->execute();
        $message = "Report added.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM emergency_reports WHERE id = $id AND reported_by = $staff_id");
    $message = "Report deleted.";
}

// Fetch reports by this staff
$records = mysqli_query($conn, "
    SELECT er.*, em.first_name AS elderly_name
    FROM emergency_reports er
    JOIN elderly_members em ON er.elderly_id = em.member_id
    WHERE er.reported_by = $staff_id
    ORDER BY er.report_time DESC
");

$elderly = mysqli_query($conn, "SELECT member_id, first_name FROM elderly_members");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <h2>Emergency Reports</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-danger mb-3" onclick="addReport()">Add Emergency Report</button>

    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Reported At</th>
            <th>Elderly</th>
            <th>Description</th>
            <th>Admin Notified</th>
            <th>Family Notified</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($records)): ?>
            <tr>
                <td><?= $row['report_time'] ?></td>
                <td><?= htmlspecialchars($row['elderly_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= $row['notified_admin'] ? 'Yes' : 'No' ?></td>
                <td><?= $row['notified_family'] ? 'Yes' : 'No' ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick='editReport(<?= json_encode($row) ?>)'>Edit</button>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this report?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="report_id" id="report_id">
            <div class="modal-header">
                <h5 class="modal-title">Emergency Report</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body row">
                <div class="form-group col-md-6">
                    <label>Elderly Member</label>
                    <select name="elderly_id" id="elderly_id" class="form-control" required>
                        <option value="">Select</option>
                        <?php mysqli_data_seek($elderly, 0); while ($e = mysqli_fetch_assoc($elderly)): ?>
                            <option value="<?= $e['member_id'] ?>"><?= htmlspecialchars($e['first_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Report Time</label>
                    <input type="datetime-local" name="report_time" id="report_time" class="form-control" required>
                </div>
                <div class="form-group col-md-12">
                    <label>Description</label>
                    <textarea name="description" id="description" class="form-control" required></textarea>
                </div>
                <div class="form-group col-md-6 d-flex align-items-center">
                    <div class="form-check mr-3">
                        <input class="form-check-input" type="checkbox" name="notified_admin" id="notified_admin">
                        <label class="form-check-label" for="notified_admin">Admin Notified</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notified_family" id="notified_family">
                        <label class="form-check-label" for="notified_family">Family Notified</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-danger">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function addReport() {
    document.querySelector('form').reset();
    document.getElementById("report_id").value = '';
    $('#reportModal').modal('show');
}

function editReport(data) {
    document.getElementById("report_id").value = data.id;
    document.getElementById("elderly_id").value = data.elderly_id;
    document.getElementById("report_time").value = data.report_time.replace(' ', 'T');
    document.getElementById("description").value = data.description;
    document.getElementById("notified_admin").checked = data.notified_admin == 1;
    document.getElementById("notified_family").checked = data.notified_family == 1;
    $('#reportModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
