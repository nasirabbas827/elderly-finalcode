<?php
session_start();
include('config.php');

if ($_SESSION["user_type"] !== 'family_member') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION["id"];
$message = "";

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $dob = $_POST["date_of_birth"];
    $gender = $_POST["gender"];
    $admission_date = $_POST["admission_date"];
    $room_number = $_POST["room_number"];
    $health_status = $_POST["health_status"];
    $status = 'active'; // Set to active by default
    $family_member_id = $user_id;

    $profile_picture = "";
    if (!empty($_FILES["profile_picture"]["name"])) {
        $profile_picture = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], "../admin/uploads/" . $profile_picture);
    }

    if ($id > 0) {
        $sql = "UPDATE elderly_members SET first_name=?, last_name=?, date_of_birth=?, gender=?, admission_date=?, family_member_id=?, room_number=?, health_status=?, status=?";
        if ($profile_picture) {
            $sql .= ", profile_picture=?";
        }
        $sql .= " WHERE member_id=? AND family_member_id=?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($profile_picture) {
            mysqli_stmt_bind_param($stmt, "ssssssssssii", $first_name, $last_name, $dob, $gender, $admission_date, $family_member_id, $room_number, $health_status, $status, $profile_picture, $id, $user_id);
        } else {
            mysqli_stmt_bind_param($stmt, "ssssssssssi", $first_name, $last_name, $dob, $gender, $admission_date, $family_member_id, $room_number, $health_status, $status, $id, $user_id);
        }
        mysqli_stmt_execute($stmt);
        $message = "Elderly member updated successfully.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO elderly_members (first_name, last_name, date_of_birth, gender, admission_date, family_member_id, room_number, health_status, status, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssssss", $first_name, $last_name, $dob, $gender, $admission_date, $family_member_id, $room_number, $health_status, $status, $profile_picture);
        mysqli_stmt_execute($stmt);
        $message = "Elderly member added successfully.";
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM elderly_members WHERE member_id = $id AND family_member_id = $user_id");
    $message = "Elderly member deleted.";
}

// Fetch data for logged-in user only
$result = mysqli_query($conn, "SELECT * FROM elderly_members WHERE family_member_id = $user_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Elderly Members</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <h2 class="mb-4">Elderly Members</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="btn btn-success mb-3" data-toggle="modal" data-target="#memberModal" onclick="addMember()">Add New Member</button>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Name</th>
            <th>DOB</th>
            <th>Gender</th>
            <th>Room</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['first_name'] . ' ' . $row['last_name'] ?></td>
                <td><?= $row['date_of_birth'] ?></td>
                <td><?= $row['gender'] ?></td>
                <td><?= $row['room_number'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick='editMember(<?= json_encode($row) ?>)'>Edit</button>
                    <a href="?delete=<?= $row['member_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                <a href="member_report.php?id=<?= $row['member_id'] ?>" class="btn btn-sm btn-info">View Report</a>
</td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="memberModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="member_id" id="member_id">
            <div class="modal-header">
                <h5 class="modal-title">Elderly Member Form</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body row">
                <div class="form-group col-md-6">
                    <label>First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Gender</label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value="">Select</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Admission Date</label>
                    <input type="date" name="admission_date" id="admission_date" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Room Number</label>
                    <input type="text" name="room_number" id="room_number" class="form-control" required>
                </div>
                <div class="form-group col-md-12">
                    <label>Health Status</label>
                    <textarea name="health_status" id="health_status" class="form-control"></textarea>
                </div>
                <div class="form-group col-md-12">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control">
                    <img id="profilePreview" src="" class="img-thumbnail mt-2" width="100" style="display:none;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function addMember() {
    document.querySelector('form').reset();
    document.getElementById("member_id").value = '';
    document.getElementById("profilePreview").style.display = 'none';
}

function editMember(data) {
    document.getElementById("member_id").value = data.member_id;
    document.getElementById("first_name").value = data.first_name;
    document.getElementById("last_name").value = data.last_name;
    document.getElementById("date_of_birth").value = data.date_of_birth;
    document.getElementById("gender").value = data.gender;
    document.getElementById("admission_date").value = data.admission_date;
    document.getElementById("room_number").value = data.room_number;
    document.getElementById("health_status").value = data.health_status;

    if (data.profile_picture) {
        document.getElementById("profilePreview").src = "../admin/uploads/" + data.profile_picture;
        document.getElementById("profilePreview").style.display = 'block';
    } else {
        document.getElementById("profilePreview").style.display = 'none';
    }

    $('#memberModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
