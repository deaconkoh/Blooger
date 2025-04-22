
<?php
    require "../inc/check_session.inc.php"; // Includes session check & user fetch logic
    include "../inc/head.inc.php";
    include "../inc/login_nav.inc.php";
    $membershipType = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : "Friend of Medium";
    $membershipPrice = isset($_GET['price']) ? htmlspecialchars($_GET['price']) : "120";
?>


<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>


<body>

    <div class="container my-5">
        <h2 class="text-center fw-bold">Payment</h2>
        <p class="text-center text-muted">
            You are signed up with <strong><?=htmlspecialchars($email)?></strong>. 
            <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Not you?</a>
        </p>

        <div class="card p-4 shadow-sm mx-auto" style="max-width: 600px;">
            <h5 class="fw-bold"><?php echo $membershipType; ?> (annual)</h5>
            <p class="text-muted">Billed Today</p>
            <h3 class="fw-bold">$<?php echo $membershipPrice; ?></h3>

            <hr>

            <h6 class="fw-bold mt-3">Credit/Debit Card</h6>
            <form>
                <div class="mb-3">
                    <label class="form-label">Card Number</label>
                    <input type="text" class="form-control" placeholder="5555 4444 4444 4444" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Expiration</label>
                        <input type="text" class="form-control" placeholder="MM/YY" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Security Code</label>
                        <input type="text" class="form-control" placeholder="123" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark w-100 mt-3">Pay</button>
            </form>

            <hr>

            <h6 class="fw-bold text-center mt-3">Express Checkout</h6>
        
            <button class="btn btn-outline-dark w-100">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" width="70">
            </button>
        </div>

        <p class="text-center text-muted mt-5 text-sm">
            <small>
                By starting a Medium membership, you agree to our <a href="#">Membership Terms of Service</a>.
                Your payment method will be charged a recurring $<?php echo $membershipPrice; ?> SGD yearly fee unless you cancel.
                No refunds for memberships canceled between billing cycles.
            </small>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
