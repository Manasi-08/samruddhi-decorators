<?php
// Prevent direct access without pay.php context
if (!isset($json) || empty($json)) {
    header("Location: /samruddhi_decorators/services.php");
    exit();
}
$checkoutOptions = json_decode($json, true);
$displayAmount = isset($checkoutOptions['amount']) ? ($checkoutOptions['amount'] / 100) : 0;
$serviceTitle = $checkoutOptions['order_data']['service_name'] ?? 'Decoration Service';
$eventDate = $checkoutOptions['order_data']['event_date'] ?? '';
$bookingRef = $checkoutOptions['order_data']['booking_id'] ?? '';
?>

<!-- Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<!-- Checkout Processing Visual State -->
<section class="section" style="min-height: 550px; padding: 60px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center">
                <div class="card border-0 shadow-lg p-4 p-md-5" style="border-radius: 16px;">
                    
                    <!-- Gateway Status Spinner / Icon -->
                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 84px; height: 84px; background: rgba(6, 65, 108, 0.08); color: #06416c; font-size: 36px;">
                            <i class="fa fa-shield"></i>
                        </div>
                    </div>

                    <h3 class="font-weight-bold text-dark mb-2">Connecting to Secure Payment</h3>
                    <p class="text-muted mb-4">Please wait while we load the Razorpay checkout gateway. Please do not refresh or close this window.</p>

                    <!-- Order Summary Box -->
                    <div class="bg-light p-3 rounded mb-4 text-left border">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Booking Reference:</span>
                            <span class="font-weight-bold text-dark">#BK-<?php echo htmlspecialchars((string)$bookingRef); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Service Package:</span>
                            <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($serviceTitle); ?></span>
                        </div>
                        <?php if (!empty($eventDate)) { ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Event Date:</span>
                            <span class="font-weight-bold text-dark"><?php echo date('d M Y', strtotime($eventDate)); ?></span>
                        </div>
                        <?php } ?>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold text-dark">Total Payable:</span>
                            <span class="h4 font-weight-bold text-success mb-0">&#8377;<?php echo number_format($displayAmount, 2); ?></span>
                        </div>
                    </div>

                    <!-- Manual Trigger & Cancel Options if popup was blocked -->
                    <div class="d-flex flex-column gap-2">
                        <button type="button" id="manualPayBtn" class="btn btn-main-md btn-block mb-2 py-3">
                            <i class="fa fa-lock mr-2"></i> Pay &#8377;<?php echo number_format($displayAmount, 2); ?> Now
                        </button>
                        <button type="button" id="cancelPayBtn" class="btn btn-outline-secondary btn-block py-2">
                            <i class="fa fa-times mr-2"></i> Cancel Payment & Return
                        </button>
                    </div>

                    <div class="mt-4 text-muted small">
                        <i class="fa fa-lock text-success mr-1"></i> 256-Bit SSL Encrypted & Secure Razorpay Payment
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hidden Verification Form -->
<form name="razorpayform" id="razorpayform" action="verify.php" method="POST">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="">
    <input type="hidden" name="razorpay_error" id="razorpay_error" value="">
    <input type="hidden" name="booking_id" id="booking_id" value="<?php echo htmlspecialchars((string)$bookingRef); ?>">
    <input type="hidden" name="csrf_token" id="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['booking_csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
</form>

<script>
(function () {
    var options = <?php echo $json; ?>;

    if (!options || !options.order_id || !options.order_data || !options.order_data.booking_id) {
        alert("Payment order details are missing. Redirecting back to services.");
        window.location.href = "services.php";
        return;
    }

    var form = document.getElementById("razorpayform");

    document.getElementById("booking_id").value = options.order_data.booking_id;
    document.getElementById("razorpay_order_id").value = options.order_id;

    // Successful payment response handler
    options.handler = function (response) {
        document.getElementById("razorpay_payment_id").value = response.razorpay_payment_id || "";
        document.getElementById("razorpay_order_id").value = response.razorpay_order_id || options.order_id;
        document.getElementById("razorpay_signature").value = response.razorpay_signature || "";
        form.submit();
    };

    // User dismissed or closed the checkout modal
    options.modal = {
        ondismiss: function () {
            document.getElementById("razorpay_error").value = "Payment window closed by customer";
            form.submit();
        },
        escape: true,
        backdropclose: false
    };

    var rzp = new Razorpay(options);

    // Payment failed event
    rzp.on("payment.failed", function (response) {
        var errorMsg = (response.error && response.error.description) 
            ? response.error.description 
            : "Payment failed";
        document.getElementById("razorpay_error").value = errorMsg;
        form.submit();
    });

    // Auto-open checkout modal on load
    window.addEventListener("DOMContentLoaded", function () {
        setTimeout(function () {
            try {
                rzp.open();
            } catch (e) {
                console.error("Razorpay popup trigger error:", e);
            }
        }, 300);
    });

    // Manual open if browser blocked popup
    var manualBtn = document.getElementById("manualPayBtn");
    if (manualBtn) {
        manualBtn.addEventListener("click", function () {
            rzp.open();
        });
    }

    // Cancel payment button
    var cancelBtn = document.getElementById("cancelPayBtn");
    if (cancelBtn) {
        cancelBtn.addEventListener("click", function () {
            if (confirm("Are you sure you want to cancel this payment?")) {
                document.getElementById("razorpay_error").value = "Payment cancelled by customer";
                form.submit();
            }
        });
    }
})();
</script>
