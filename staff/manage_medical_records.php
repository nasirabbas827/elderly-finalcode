<?php
session_start();
include('config.php');

if ($_SESSION["user_type"] !== 'staff') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION["id"];
$message = "";

// Insert/Update Logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $elderly_id = $_POST["elderly_id"];
    $record_date = $_POST["record_date"];
    $record_type = $_POST["record_type"];
    $diagnosis = $_POST["diagnosis"];
    $medications = $_POST["medications"];
    $notes = $_POST["notes"];
    $severity = $_POST["severity"];

    if ($id > 0) {
        // Update only if this record belongs to logged-in user
        $stmt = $conn->prepare("UPDATE medical_records SET elderly_id=?, staff_id=?, record_date=?, record_type=?, diagnosis=?, medications=?, notes=?, severity=? WHERE id=? AND staff_id=?");
        $stmt->bind_param("iissssssii", $elderly_id, $user_id, $record_date, $record_type, $diagnosis, $medications, $notes, $severity, $id, $user_id);
        $stmt->execute();
        $message = "Record updated.";
    } else {
        // Insert new record, staff_id is current user
        $stmt = $conn->prepare("INSERT INTO medical_records (elderly_id, staff_id, record_date, record_type, diagnosis, medications, notes, severity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $elderly_id, $user_id, $record_date, $record_type, $diagnosis, $medications, $notes, $severity);
        $stmt->execute();
        $message = "Medical record added.";
    }
}

// Delete (only own records)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ? AND staff_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $message = "Medical record deleted.";
}

// Fetch all medical records (all staff)
$records = mysqli_query($conn, "
    SELECT mr.*, em.first_name AS elderly_name
    FROM medical_records mr
    JOIN elderly_members em ON mr.elderly_id = em.member_id
    ORDER BY mr.record_date DESC
");

$elderly = mysqli_query($conn, "SELECT member_id, first_name FROM elderly_members");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Medical Records</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <h2 class="mb-4">Medical Records</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" onclick="addRecord()">Add New Record</button>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Elderly</th>
            <th>Date</th>
            <th>Type</th>
            <th>Diagnosis</th>
            <th>Severity</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($records)): ?>
            <tr>
                <td><?= htmlspecialchars($row['elderly_name']) ?></td>
                <td><?= $row['record_date'] ?></td>
                <td><?= $row['record_type'] ?></td>
                <td><?= htmlspecialchars($row['diagnosis']) ?></td>
                <td><?= $row['severity'] ?></td>
                <td>
                    <?php if ($row['staff_id'] == $user_id): ?>
                        <button class="btn btn-sm btn-primary" onclick='editRecord(<?= json_encode($row) ?>)'>Edit</button>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</a>
                    <?php else: ?>
                        <span class="text-muted">No actions</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <input type="hidden" name="record_id" id="record_id">
            <div class="modal-header">
                <h5 class="modal-title">Medical Record</h5>
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
                <div class="form-group col-md-4">
                    <label>Record Date</label>
                    <input type="date" name="record_date" id="record_date" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Record Type</label>
                    <select name="record_type" id="record_type" class="form-control" required>
                        <option value="checkup">Checkup</option>
                        <option value="treatment">Treatment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label>Severity</label>
                    <select name="severity" id="severity" class="form-control" required>
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label>Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosis" class="form-control" required></textarea>
                </div>
                <div class="form-group col-md-12">
                    <label>Medications</label>
                    <textarea name="medications" id="medications" class="form-control"></textarea>
                </div>
                <div class="form-group col-md-12">
                    <label>Notes</label>
                    <textarea name="notes" id="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function addRecord() {
    document.querySelector('form').reset();
    document.getElementById("record_id").value = '';
    $('#recordModal').modal('show');
}

function editRecord(data) {
    document.getElementById("record_id").value = data.id;
    document.getElementById("elderly_id").value = data.elderly_id;
    document.getElementById("record_date").value = data.record_date;
    document.getElementById("record_type").value = data.record_type;
    document.getElementById("severity").value = data.severity;
    document.getElementById("diagnosis").value = data.diagnosis;
    document.getElementById("medications").value = data.medications;
    document.getElementById("notes").value = data.notes;
    $('#recordModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
