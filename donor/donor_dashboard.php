<?php
session_start();
include('config.php');

if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== 'donor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["id"];

// Fetch user profile
$stmtUser = $conn->prepare("SELECT username, email, phone, age, bio, profile_picture, status FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$user = $userResult->fetch_assoc();
$stmtUser->close();

// Fetch donations
$stmtDonations = $conn->prepare("SELECT donation_id, amount, donation_date, donation_type, payment_reference_id, purpose, acknowledged FROM donations WHERE donor_id = ? ORDER BY donation_date DESC");
$stmtDonations->bind_param("i", $user_id);
$stmtDonations->execute();
$donations = $stmtDonations->get_result();
$stmtDonations->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard</title>
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
        .donations-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .donations-card:hover {
            transform: translateY(-5px);
        }
        .donations-table th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
        }
        .donations-table td {
            vertical-align: middle;
        }
        .acknowledged-pending {
            color: #ffc107;
            font-weight: 500;
        }
        .acknowledged-approved {
            color: #28a745;
            font-weight: 500;
        }
        .acknowledged-not-approved {
            color: #dc3545;
            font-weight: 500;
        }
        h2 {
            color: #333;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }
        .btn-primary {
            border-radius: 8px;
            padding: 8px 20px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        @media (max-width: 768px) {
            .profile-card, .donations-card {
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

        <!-- Donations Section (Right) -->
        <div class="col-lg-8 col-md-12">
            <div class="donations-card">
                <h3>Your Donations</h3>
                <?php if ($donations->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered donations-table">
                            <thead>
                                <tr>
                                    <th>Donation ID</th>
                                    <th>Amount ($)</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Payment Ref</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($don = $donations->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($don['donation_id']) ?></td>
                                        <td>$<?= number_format($don['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($don['donation_date']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($don['donation_type'])) ?></td>
                                        <td><?= htmlspecialchars($don['payment_reference_id'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars(ucfirst($don['purpose'])) ?></td>
                                        <td class="<?=
                                            $don['acknowledged'] === 'approved' ? 'acknowledged-approved' :
                                            ($don['acknowledged'] === 'pending' ? 'acknowledged-pending' : 'acknowledged-not-approved')
                                        ?>">
                                            <?= ucfirst($don['acknowledged']) ?>
                                        </td>
                                        <td>
                                            <?php if ($don['acknowledged'] === 'approved'): ?>
                                                <a href="download_receipt.php?donation_id=<?= $don['donation_id'] ?>" class="btn btn-sm btn-primary">Get Receipt</a>
                                            <?php else: ?>
                                                <small class="text-muted">Not Available</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">You have not made any donations yet.</p>
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