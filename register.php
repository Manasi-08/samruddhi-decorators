
<?php
session_start();
include_once('admin/config.php');

$errors = array();

function input_data($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// REGISTER USER
if (isset($_POST['register'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $contact = trim($_POST['contact'] ?? '');

    $type = 0;

    // Name validation
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } elseif (!preg_match("/^[a-zA-Z ]{2,60}$/", $name)) {
        $errors['name'] = "Please enter a valid name";
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email";
    }

    // Password validation

// Password validation
if (empty($password)) {

    $errors['password'] = "Password is required";

} elseif (strlen($password) < 8) {

    $errors['password'] =
        "Password must contain at least 8 characters";

} elseif (!preg_match('/[A-Z]/', $password)) {

    $errors['password'] =
        "Password must contain at least one uppercase letter";

} elseif (!preg_match('/[a-z]/', $password)) {

    $errors['password'] =
        "Password must contain at least one lowercase letter";

} elseif (!preg_match('/[0-9]/', $password)) {

    $errors['password'] =
        "Password must contain at least one number";

} elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {

    $errors['password'] =
        "Password must contain at least one special character";
}

    // Confirm password

if (empty($confirm_password)) {

    $errors['confirm-password'] =
        "Confirm password is required";

} elseif ($password !== $confirm_password) {

    $errors['confirm-password'] =
        "Password and confirm password do not match";
}


    // Contact validation
    if (empty($contact)) {
        $errors['contact'] = "Contact number is required";
    } elseif (!preg_match('/^[0-9]{10}$/', $contact)) {
        $errors['contact'] =
            "Please enter a valid 10 digit contact number";
    }

    // Database operation
    if (empty($errors)) {

        // Check existing email
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM user WHERE email_id = ?"
        );

        if (!$check) {
            $errors['general'] = "Database error. Please try again.";
        } else {

            mysqli_stmt_bind_param($check, "s", $email);
            mysqli_stmt_execute($check);

            $result = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($result) > 0) {

                $errors['email'] =
                    "An account with this email already exists.";

            } else {

                // Secure password hashing
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert user
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO user
                    (name, email_id, password, contact, type)
                    VALUES (?, ?, ?, ?, ?)"
                );

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssssi",
                        $name,
                        $email,
                        $hashed_password,
                        $contact,
                        $type
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        $_SESSION['success'] =
                            "Registration successful! Please sign in.";

                        mysqli_stmt_close($stmt);
                        mysqli_stmt_close($check);

                        header("Location: login.php");
                        exit();

                    } else {
                        $errors['general'] =
                            "Registration failed. Please try again.";
                    }

                    mysqli_stmt_close($stmt);

                } else {
                    $errors['general'] =
                        "Something went wrong. Please try again.";
                }
            }

            mysqli_stmt_close($check);
        }
    }
}
?>

<?php include 'header.php'; ?>
<?php include 'menu.php'; ?>

<!-- PAGE TITLE -->

<section class="page-title bg-title overlay-dark">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">

                <div class="title">
                    <h3>Register</h3>
                </div>

                <ol class="breadcrumb p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item active">
                        Register
                    </li>
                </ol>

            </div>
        </div>
    </div>
</section>

<!-- REGISTRATION FORM -->

<section class="section contact-form">
    <div class="container">

        <div class="row">
            <div class="col-12">
                <div class="section-title">
                    <h3>
                        <span class="alternate">Create Account</span>
                    </h3>
                </div>
            </div>
        </div>

        <form method="POST"
              action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
              class="row form">

            <!-- General error -->

            <?php if (isset($errors['general'])) { ?>
                <div class="col-12">
                    <div class="alert alert-danger">
                        <?php echo input_data($errors['general']); ?>
                    </div>
                </div>
            <?php } ?>

            <!-- Name -->

            <div class="col-md-12 form-group">

                <input
                    type="text"
                    class="form-control main"
                    name="name"
                    id="name"
                    placeholder="Full Name"
                    value="<?php echo isset($_POST['name']) ? input_data($_POST['name']) : ''; ?>"
                    required
                >

                <span class="error">
                    <?php echo $errors['name'] ?? ''; ?>
                </span>

            </div>

            <!-- Email -->

            <div class="col-md-12 form-group">

                <input
                    type="email"
                    class="form-control main"
                    name="email"
                    id="email"
                    placeholder="Email Address"
                    value="<?php echo isset($_POST['email']) ? input_data($_POST['email']) : ''; ?>"
                    required
                >

                <span class="error">
                    <?php echo $errors['email'] ?? ''; ?>
                </span>

            </div>

            <!-- Password -->

            <div class="col-md-12 form-group">

                <div class="password-wrapper">

                    <input
                        type="password"
                        class="form-control main"
                        name="password"
                        id="password"
                        placeholder="Password"
                        autocomplete="new-password"
                        required
                    >

                    <span class="toggle-password"
                          onclick="togglePassword('password','eye1')">

                        <i class="fa fa-eye" id="eye1"></i>

                    </span>

                </div>

                <span class="error">
                    <?php echo $errors['password'] ?? ''; ?>
                </span>

            </div>

            <!-- Confirm Password -->

            <div class="col-md-12 form-group">

                <div class="password-wrapper">

                    <input
                        type="password"
                        class="form-control main"
                        name="confirm-password"
                        id="confirm-password"
                        placeholder="Confirm Password"
                        autocomplete="new-password"
                        required
                    >

                    <span class="toggle-password"
                          onclick="togglePassword('confirm-password','eye2')">

                        <i class="fa fa-eye" id="eye2"></i>

                    </span>

                </div>

                <span class="error">
                    <?php echo $errors['confirm-password'] ?? ''; ?>
                </span>

            </div>

            <!-- Contact -->

            <div class="col-md-12 form-group">

                <input
                    type="tel"
                    class="form-control main"
                    name="contact"
                    id="contact"
                    placeholder="Contact Number"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    value="<?php echo isset($_POST['contact']) ? input_data($_POST['contact']) : ''; ?>"
                    required
                >

                <span class="error">
                    <?php echo $errors['contact'] ?? ''; ?>
                </span>

            </div>

            <!-- Register Button -->

            <div class="col-12 text-center">

                <button
                    type="submit"
                    name="register"
                    class="btn btn-main-md">

                    Register

                </button>

            </div>

            <p class="text-center">
                Already have an account?
                <a href="login.php">Sign In</a>
            </p>

        </form>

    </div>
</section>

<!-- CSS -->

<style>

.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px;
    color: #555;
    z-index: 5;
}

.toggle-password:hover {
    color: #06416c;
}

.error {
    display: block;
    color: red;
    font-size: 13px;
    margin-top: 5px;
}

</style>

<!-- JAVASCRIPT -->

<script>

function togglePassword(inputId, iconId) {

    var password = document.getElementById(inputId);
    var icon = document.getElementById(iconId);

    if (password.type === "password") {

        password.type = "text";

        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");

    }
}

</script>

<?php include 'footer.php'; ?>
