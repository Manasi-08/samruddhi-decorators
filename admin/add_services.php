<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

$errors = [];
$name = '';
$price = '';
$description = '';
$csrfToken = admin_csrf_token();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_admin_csrf($postedCsrf)) {
        $errors['csrf'] = 'Security validation failed. Please try submitting again.';
    }

    $name = trim($_POST["txt_name"] ?? '');
    $price = trim($_POST["txt_price"] ?? '');
    $description = trim($_POST["txt_description"] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Service package title is required.';
    } elseif (strlen($name) < 2 || strlen($name) > 150) {
        $errors['name'] = 'Title must be between 2 and 150 characters.';
    }

    if (empty($price)) {
        $errors['price'] = 'Package price is required.';
    } elseif (!is_numeric($price) || floatval($price) <= 0) {
        $errors['price'] = 'Please enter a valid numeric price greater than zero.';
    }

    if (empty($description)) {
        $errors['description'] = 'Service package description is required.';
    }

    $uploadedFilename = '';
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors['photo'] = 'Please upload a service image file.';
    } else {
        $fileInfo = pathinfo($_FILES['photo']['name']);
        $ext = strtolower($fileInfo['extension'] ?? '');
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts, true)) {
            $errors['photo'] = 'Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.';
        } else {
            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowedMimes, true)) {
                $errors['photo'] = 'Uploaded file is not a valid image.';
            } else {
                $uploadedFilename = 'srv_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = __DIR__ . '/uploads/' . $uploadedFilename;

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $errors['photo'] = 'Failed to save the uploaded image to uploads folder.';
                }
            }
        }
    }

    if (empty($errors)) {
        $insertStmt = mysqli_prepare(
            $conn,
            "INSERT INTO services (name, price, description, photo) VALUES (?, ?, ?, ?)"
        );

        if (!$insertStmt) {
            $errors['db'] = "Database prepare error: " . mysqli_error($conn);
        } else {
            $cleanPrice = floatval($price);
            mysqli_stmt_bind_param($insertStmt, "sdss", $name, $cleanPrice, $description, $uploadedFilename);
            if (mysqli_stmt_execute($insertStmt)) {
                mysqli_stmt_close($insertStmt);
                $_SESSION['success'] = "Decoration service package '" . htmlspecialchars($name) . "' added successfully!";
                header('Location: view_services.php');
                exit();
            } else {
                $errors['db'] = "Failed to save service: " . mysqli_stmt_error($insertStmt);
                mysqli_stmt_close($insertStmt);
            }
        }
    }
}
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Add New Service Package</h1>
        <div class="page-subtitle-text">Create and publish a new decoration package for customer bookings.</div>
    </div>
    <div>
        <a href="view_services.php" class="btn-secondary-custom">
            <i class="fa fa-arrow-left"></i> Back to Catalog
        </a>
    </div>
</div>

<?php if (isset($errors['db'])) { ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($errors['db']); ?>
    </div>
<?php } elseif (isset($errors['csrf'])) { ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($errors['csrf']); ?>
    </div>
<?php } ?>

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="custom-card">
            <div class="custom-card-header">
                <h5 class="card-title-custom"><i class="fa fa-plus-circle text-primary me-2"></i> Service Details Form</h5>
                <span class="text-muted" style="font-size: 0.85rem;">All fields required</span>
            </div>
            <div class="custom-card-body p-4">
                <form method="POST" action="add_services.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_escape($csrfToken); ?>">

                    <div class="row g-3">
                        <!-- Service Name -->
                        <div class="col-md-8">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Service / Package Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control-custom" name="txt_name" 
                                       placeholder="e.g. Premium Wedding Stage Decoration" 
                                       value="<?php echo htmlspecialchars($name); ?>" required>
                                <?php if (isset($errors['name'])) { ?>
                                    <span class="form-text-error text-danger small"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['name']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="col-md-4">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Package Price (&#8377;) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control-custom" name="txt_price" 
                                       placeholder="e.g. 5000" 
                                       value="<?php echo htmlspecialchars($price); ?>" required>
                                <?php if (isset($errors['price'])) { ?>
                                    <span class="form-text-error text-danger small"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['price']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Description & Deliverables <span class="text-danger">*</span></label>
                                <textarea class="form-control-custom" name="txt_description" rows="4" 
                                          placeholder="Detailed summary of flowers, backdrop, lighting, stage setup included in package..." required><?php echo htmlspecialchars($description); ?></textarea>
                                <?php if (isset($errors['description'])) { ?>
                                    <span class="form-text-error text-danger small"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['description']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Image File Upload -->
                        <div class="col-12">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Display Photograph / Banner <span class="text-danger">*</span></label>
                                <input type="file" class="form-control-custom" name="photo" accept=".jpg,.jpeg,.png,.webp" required>
                                <small class="text-muted d-block mt-1">Recommended size: 800x600px. Allowed formats: JPG, JPEG, PNG, WEBP.</small>
                                <?php if (isset($errors['photo'])) { ?>
                                    <span class="form-text-error text-danger small"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['photo']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Action Submit Buttons -->
                        <div class="col-12 mt-4 text-end">
                            <a href="view_services.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary-custom px-4">
                                <i class="fa fa-check-circle me-1"></i> Save & Publish Package
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
