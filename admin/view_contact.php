<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

// 1. Handle Delete Message via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_contact') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_admin_csrf($postedCsrf)) {
        $_SESSION['error'] = "Security token mismatch. Please try again.";
        header("Location: view_contact.php");
        exit();
    }

    $cId = filter_var($_POST['contact_id'] ?? null, FILTER_VALIDATE_INT);
    if ($cId && $cId > 0) {
        $delStmt = mysqli_prepare($conn, "DELETE FROM contact WHERE id = ?");
        mysqli_stmt_bind_param($delStmt, "i", $cId);
        if (mysqli_stmt_execute($delStmt)) {
            $_SESSION['success'] = "Contact inquiry #MSG-$cId deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete message: " . mysqli_error($conn);
        }
        mysqli_stmt_close($delStmt);
    } else {
        $_SESSION['error'] = "Invalid inquiry ID submitted.";
    }

    header("Location: view_contact.php");
    exit();
}

// 2. Fetch All Real Contact Inquiries
$query = mysqli_query($conn, "SELECT * FROM contact ORDER BY id DESC");
$csrfToken = admin_csrf_token();
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Customer Contact Inquiries</h1>
        <div class="page-subtitle-text">Review real customer questions, quotation requests, and website inquiries.</div>
    </div>
</div>

<!-- Notifications / Alerts -->
<?php if (isset($_SESSION['success'])) { ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="fa fa-check-circle fs-5 text-success"></i>
            <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } elseif (isset($_SESSION['error'])) { ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="fa fa-exclamation-circle fs-5 text-danger"></i>
            <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<!-- Contact Table Card -->
<div class="custom-card">
    <div class="custom-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title-custom"><i class="fa fa-envelope-o text-primary me-2"></i> Messages & Quotation Requests</h5>
        <span class="badge bg-primary-subtle text-primary fw-bold">Live Submissions</span>
    </div>
    <div class="custom-card-body p-3">
        <div class="table-responsive-custom">
            <table id="contact" class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">No.</th>
                        <th>Customer Name</th>
                        <th>Email Address</th>
                        <th>Phone / Contact</th>
                        <th style="width: 45%;">Message Preview</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                if ($query && mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $msgData = htmlspecialchars(json_encode([
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'contact' => $row['contact'],
                            'message' => $row['message']
                        ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?php echo $i++; ?></td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($row['name']); ?></div>
                            <small class="text-muted">ID: #MSG-<?php echo $row['id']; ?></small>
                        </td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="text-primary fw-semibold text-decoration-none">
                                <i class="fa fa-envelope-o me-1"></i><?php echo htmlspecialchars($row['email']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="tel:<?php echo htmlspecialchars($row['contact']); ?>" class="text-dark fw-semibold text-decoration-none">
                                <i class="fa fa-phone text-success me-1"></i><?php echo htmlspecialchars($row['contact']); ?>
                            </a>
                        </td>
                        <td>
                            <div class="bg-light p-2 rounded border text-dark small text-truncate" style="max-width: 380px;">
                                <i class="fa fa-commenting-o text-muted me-1"></i>
                                <?php echo htmlspecialchars($row['message']); ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <!-- Read Message Button -->
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2" 
                                        onclick="readMessage(<?php echo $msgData; ?>)" title="Read Full Inquiry">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <!-- Delete Message Button -->
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" 
                                        onclick="confirmDeleteMsg(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')" title="Delete Inquiry">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No customer inquiries recorded in the database.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 1. Read Message Modal -->
<div class="modal fade" id="readMessageModal" tabindex="-1" aria-labelledby="readMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title font-weight-bold" id="readMessageModalLabel">
                    <i class="fa fa-envelope-open-o text-primary me-2"></i> Message from <span id="modalMsgSender"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between mb-3 small border-bottom pb-2">
                    <div>
                        <span class="text-muted">Email:</span>
                        <a href="#" id="modalMsgEmailLink" class="fw-semibold ms-1"></a>
                    </div>
                    <div>
                        <span class="text-muted">Phone:</span>
                        <span id="modalMsgPhone" class="fw-semibold ms-1"></span>
                    </div>
                </div>
                <label class="text-muted small fw-bold mb-1">Full Message Content:</label>
                <div class="p-3 bg-light rounded-3 border text-dark fs-6" id="modalMsgContent" style="white-space: pre-wrap; line-height: 1.6;"></div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Delete Message Confirmation Modal -->
<div class="modal fade" id="deleteMsgModal" tabindex="-1" aria-labelledby="deleteMsgModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="view_contact.php">
                <input type="hidden" name="action" value="delete_contact">
                <input type="hidden" name="csrf_token" value="<?php echo admin_escape($csrfToken); ?>">
                <input type="hidden" name="contact_id" id="deleteContactId" value="">

                <div class="modal-header border-bottom bg-danger text-white">
                    <h5 class="modal-title font-weight-bold" id="deleteMsgModalLabel">
                        <i class="fa fa-trash me-2"></i> Delete Inquiry
                    </h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-2 fs-6 text-dark">Are you sure you want to delete this message from:</p>
                    <div class="fw-bold fs-5 text-danger mb-3" id="deleteContactSender"></div>
                    <p class="text-muted small mb-0">This message will be permanently removed from the database.</p>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3">Delete Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#contact').DataTable({
        "pageLength": 10,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search inquiries..."
        }
    });
});

function readMessage(data) {
    document.getElementById('modalMsgSender').textContent = data.name;
    document.getElementById('modalMsgEmailLink').textContent = data.email;
    document.getElementById('modalMsgEmailLink').href = 'mailto:' + data.email;
    document.getElementById('modalMsgPhone').textContent = data.contact;
    document.getElementById('modalMsgContent').textContent = data.message;

    var myModal = new bootstrap.Modal(document.getElementById('readMessageModal'));
    myModal.show();
}

function confirmDeleteMsg(id, name) {
    document.getElementById('deleteContactId').value = id;
    document.getElementById('deleteContactSender').textContent = name + ' (#MSG-' + id + ')';

    var myModal = new bootstrap.Modal(document.getElementById('deleteMsgModal'));
    myModal.show();
}
</script>

<?php include 'footer.php'; ?>
