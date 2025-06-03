<?php
include('config.php');

// Define variables and initialize
$email = $password = $user_type = "";
$email_err = $password_err = $user_type_err = $login_msg = "";

// On form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate user type
    if (empty($_POST["user_type"])) {
        $user_type_err = "Please select a user type.";
    } else {
        $user_type = $_POST["user_type"];
    }

    // If no errors, check credentials
    if (empty($email_err) && empty($password_err) && empty($user_type_err)) {
        $sql = "SELECT id, email, password, user_type, status FROM users WHERE email = ? AND user_type = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_user_type);
        $param_email = $email;
        $param_user_type = $user_type;
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password, $db_user_type, $status);
            if (mysqli_stmt_fetch($stmt)) {
                if ($status !== 'approved') {
                    $login_msg = '<div class="alert alert-warning">Your account is not approved yet.</div>';
                } elseif (password_verify($password, $hashed_password)) {
                    session_start();
                    $_SESSION["id"] = $id;
                    $_SESSION["email"] = $email;
                    $_SESSION["user_type"] = $db_user_type;

                    // Redirect by user type
                    if ($db_user_type == 'staff') {
                        header("Location: staff/staff_dashboard.php");
                    } elseif ($db_user_type == 'donor') {
                        header("Location: donor/donor_dashboard.php");
                    } elseif ($db_user_type == 'family_member') {
                        header("Location: family/family_dashboard.php");
                    } else {
                        $login_msg = '<div class="alert alert-danger">Invalid user type assigned.</div>';
                    }
                    exit();
                } else {
                    $password_err = "Incorrect password.";
                }
            }
        } else {
            $email_err = "No account found with that email and user type.";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h2 class="text-center">User Login</h2>
            <p class="text-center">Please fill in your credentials to log in.</p>

            <?php echo $login_msg; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>

                <div class="form-group">
                    <label>User Type</label>
                    <select name="user_type" class="form-control <?php echo (!empty($user_type_err)) ? 'is-invalid' : ''; ?>" required>
                        <option value="">Select user type</option>
                        <option value="staff" <?php echo ($user_type == 'staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="donor" <?php echo ($user_type == 'donor') ? 'selected' : ''; ?>>Donor</option>
                        <option value="family_member" <?php echo ($user_type == 'family_member') ? 'selected' : ''; ?>>Family Member</option>
                    </select>
                    <span class="invalid-feedback"><?php echo $user_type_err; ?></span>
                </div>

                <div class="form-group text-center">
                    <input type="submit" value="Log in" class="btn btn-primary">
                </div>
            </form>

            <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>
</body>
</html>
