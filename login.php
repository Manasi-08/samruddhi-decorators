<?php
session_start();
include_once('admin/config.php');

$errors = array();

// LOGIN USER
if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Email validation
    if (empty($email)) {

        $errors['email'] = "Email is required";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors['email'] = "Please enter a valid email";
    }

    // Password validation
    if (empty($password)) {

        $errors['password'] = "Password is required";
    }

    // Login process
    if (empty($errors)) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email_id, password, contact, type
             FROM user
             WHERE email_id = ?
             LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $email);

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_bind_result(
                    $stmt,
                    $dbId,
                    $dbName,
                    $dbEmail,
                    $dbPassword,
                    $dbContact,
                    $dbType
                );

                $userFound = mysqli_stmt_fetch($stmt);

                mysqli_stmt_close($stmt);

                if ($userFound) {

                    $is_valid = false;

                    // Check new secure password
                    if (password_verify($password, $dbPassword)) {

                        $is_valid = true;

                    }

                    // Check old Base64 password
                    elseif (
                        hash_equals(
                            (string)$dbPassword,
                            base64_encode($password)
                        )
                    ) {

                        $is_valid = true;

                        // Upgrade old password to secure hash
                        $newHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $update = mysqli_prepare(
                            $conn,
                            "UPDATE user SET password = ? WHERE id = ?"
                        );

                        if ($update) {

                            mysqli_stmt_bind_param(
                                $update,
                                "si",
                                $newHash,
                                $dbId
                            );

                            mysqli_stmt_execute($update);
                            mysqli_stmt_close($update);
                        }
                    }

                    // Successful login
                    if ($is_valid) {

                        session_regenerate_id(true);

                        $_SESSION['name'] = $dbName;
                        $_SESSION['userid'] = $dbId;
                        $_SESSION['email'] = $dbEmail;
                        $_SESSION['mobile'] = $dbContact;
                        $_SESSION['user_type'] = (int)$dbType;

                        // User login
                        if ((int)$dbType === 0) {

                            header("Location: index.php");
                            exit();

                        }

                        // Admin login
                        elseif ((int)$dbType === 1) {

                            header("Location: admin/index.php");
                            exit();

                        } else {

                            $errors['login'] =
                                "Invalid account type.";
                        }

                    } else {

                        $errors['login'] =
                            "Incorrect Email or Password";
                    }

                } else {

                    $errors['login'] =
                        "Incorrect Email or Password";
                }

            } else {

                mysqli_stmt_close($stmt);

                $errors['login'] =
                    "Something went wrong. Please try again.";
            }

        } else {

            $errors['login'] =
                "Database error. Please try again.";
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
                    <h3>Login</h3>
                </div>

                <ol class="breadcrumb p-0 m-0">

                    <li class="breadcrumb-item">
                        <a href="index.php">Home</a>
                    </li>

                    <li class="breadcrumb-item active">
                        Login
                    </li>

                </ol>

            </div>

        </div>

    </div>

</section>


<!-- LOGIN FORM -->

<section class="section contact-form">

    <div class="container">

        <div class="row">

            <div class="col-12">

                <div class="section-title">

                    <h3>
                        <span class="alternate">Login</span>
                    </h3>

                </div>

            </div>

        </div>


        <form method="POST"
              action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>"
              class="row form">


            <!-- Success Message -->

            <?php if (isset($_SESSION['success'])) { ?>

                <div class="col-12">

                    <div class="alert alert-success">

                        <?php
                        echo htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']);
                        ?>

                    </div>

                </div>

            <?php } ?>


            <!-- Login Error -->

            <?php if (isset($errors['login'])) { ?>

                <div class="col-12">

                    <div class="alert alert-danger">

                        <?php echo htmlspecialchars($errors['login']); ?>

                    </div>

                </div>

            <?php } ?>


            <!-- Email Field -->

            <div class="col-md-12 form-group">

                <input
                    type="email"
                    class="form-control main"
                    name="email"
                    id="email"
                    placeholder="Email Address"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    required
                >

                <span class="error">
                    <?php echo $errors['email'] ?? ''; ?>
                </span>

            </div>


            <!-- Password Field -->

            <div class="col-md-12 form-group">

                <div class="password-wrapper">

                    <input
                        type="password"
                        class="form-control main"
                        name="password"
                        id="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >

                    <span class="toggle-password"
                          onclick="togglePassword()"
                          title="Show Password">

                        <i class="fa fa-eye" id="eyeIcon"></i>

                    </span>

                </div>

                <span class="error">
                    <?php echo $errors['password'] ?? ''; ?>
                </span>

            </div>


            <!-- Login Button -->

            <div class="col-12 text-center">

                <button
                    type="submit"
                    name="login"
                    class="btn btn-main-md">

                    Login

                </button>

            </div>


            <!-- Register Link -->

            <p class="text-center">

                Not a member?

                <a href="register.php">Sign Up</a>

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
    color: #555;
    font-size: 18px;
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

function togglePassword() {

    var password = document.getElementById("password");

    var eyeIcon = document.getElementById("eyeIcon");

    if (password.type === "password") {

        password.type = "text";

        eyeIcon.classList.remove("fa-eye");

        eyeIcon.classList.add("fa-eye-slash");

    } else {

        password.type = "password";

        eyeIcon.classList.remove("fa-eye-slash");

        eyeIcon.classList.add("fa-eye");

    }
}

</script>


<?php include 'footer.php'; ?>
