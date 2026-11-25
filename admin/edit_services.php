<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

$errors = [];
$serviceId = filter_var($_GET['services_id'] ?? ($_POST['services_id'] ?? null), FILTER_VALIDATE_INT);

if (!$serviceId || $serviceId <= 0) {
    $_SESSION['error'] = "Invalid service ID selected.";
    header('Location: view_services.php');
    exit();
}

// Fetch current service details with prepared statement
$fetchStmt = mysqli_prepare($conn, "SELECT id, name, price, description, photo FROM services WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($fetchStmt, "i", $serviceId);
mysqli_stmt_execute($fetchStmt);
$serviceRes = mysqli_stmt_get_result($fetchStmt);
$service = mysqli_fetch_assoc($serviceRes);
mysqli_stmt_close($fetchStmt);

if (!$service) {
    $_SESSION['error'] = "Service record not found.";
    header('Location: view_services.php');
    exit();
}

$name = $service['name'];
$price = $service['price'];
$description = $service['description'];
$photo = $service['photo'];
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

    $updateFilename = $photo; // Default to existing photo

    // Check if new photo was uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['photo']['name']);
        $ext = strtolower($fileInfo['extension'] ?? '');
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts, true)) {
            $errors['photo'] = 'Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowedMimes, true)) {
                $errors['photo'] = 'Uploaded file is not a valid image.';
            } else {
                $newFilename = 'srv_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $targetPath = __DIR__ . '/uploads/' . $newFilename;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $updateFilename = $newFilename;
                } else {
                    $errors['photo'] = 'Failed to save the new image.';
                }
            }
        }
    }

    if (empty($errors)) {
        $cleanPrice = floatval($price);
        $upStmt = mysqli_prepare(
            $conn,
            "UPDATE services SET name = ?, price = ?, description = ?, photo = ? WHERE id = ?"
        );

        if (!$upStmt) {
            $errors['db'] = "Database error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($upStmt, "sdssi", $name, $cleanPrice, $description, $updateFilename, $serviceId);
            if (mysqli_stmt_execute($upStmt)) {
                mysqli_stmt_close($upStmt);
                $_SESSION['success'] = "Service package '" . htmlspecialchars($name) . "' updated successfully!";
                header('Location: view_services.php');
                exit();
            } else {
                $errors['db'] = "Failed to update record: " . mysqli_stmt_error($upStmt);
                mysqli_stmt_close($upStmt);
            }
        }
    }
}
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Edit Service Package</h1>
        <div class="page-subtitle-text">Update package details, pricing, description, or replacement photo.</div>
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
                <h5 class="card-title-custom">
                    <i class="fa fa-pencil-square-o text-primary me-2"></i> Update Service Package #SRV-<?php echo $serviceId; ?>
                </h5>
                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">Edit Mode</span>
            </div>
            <div class="custom-card-body p-4">
                <form method="POST" action="edit_services.php?services_id=<?php echo $serviceId; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="services_id" value="<?php echo $serviceId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_escape($csrfToken); ?>">

                    <div class="row g-3">
                        <!-- Service Name -->
                        <div class="col-md-8">
                            <div class="form-group-custom">
                                <label class="form-label-custom">Service / Package Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control-custom" name="txt_name" 
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
                                <textarea class="form-control-custom" name="txt_description" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
                                <?php if (isset($errors['description'])) { ?>
                                    <span class="form-text-error text-danger small"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['description']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Current Image Display & Replacement Upload -->
                        <div class="col-12">
                            <label class="form-label-custom">Current Photo</label>
                            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border mb-3">
                                <div class="rounded-3 border overflow-hidden" style="width: 100px; height: 75px; background: #e2e8f0;">
                                    <?php if (!empty($photo)) { ?>
                                        <img src="uploads/<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php } else { ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="fa fa-image fs-4"></i></div>
                                    <?php } ?>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($photo ?: 'No image on file'); ?></div>
                                    <small class="text-muted">Upload a new file below only if you wish to replace this photo.</small>
                                </div>
                            </div>

                            <div class="form-group-custom">
                                <label class="form-label-custom">Replace Photo (Optional)</label>
                                <input type="file" class="form-control-custom" name="photo" accept=".jpg,.jpeg,.png,.webp">
                                <small class="text-muted d-block mt-1">Allowed formats: JPG, JPEG, PNG, WEBP.</small>
                                <?php if (isset($errors['photo'])) { ?>
                                    <span class="form-text-error text-danger small"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['photo']); ?></span>
                                <?php } ?>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="col-12 mt-4 text-end">
                            <a href="view_services.php" class="btn btn-outline-secondary rounded-pill px-4 me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary-custom px-4">
                                <i class="fa fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
