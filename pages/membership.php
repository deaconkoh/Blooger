<?php
session_start();
require_once "../inc/db.inc.php"; // Correct path to inc directory
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "../inc/head.inc.php"; ?>
</head>
<body>
    <?php
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            include "../inc/login_nav.inc.php";  // Show logged-in navigation
        } else {
            include "../inc/nav.inc.php";  // Show default navigation
        }
    ?>
    
    <div class="container text-center my-5">
        <h1 class="fw-bold">Membership Plans</h1>
    </div>
    
    <div class="container mb-5">
        <div class="d-flex justify-content-center gap-4 flex-wrap">
            <!-- Medium Member Plan -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card shadow-sm text-center p-4">
                    <div class="mb-3 text-warning">&#11088;</div>
                    <h4 class="fw-bold">Blooger Member</h4>
                    <p class="text-muted">$5/month or $60/year</p>
                    <?php 
                        if (isset($_SESSION['user_id'])) {
                            echo '<a href="payment.php?type=Blooger Member&price=60" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        } else {
                            echo '<a href="login.php" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        }
                    ?>

                    <hr>
                    <ul class="list-unstyled text-start mx-auto w-75">
                        <li>&#10003; Read member-only stories</li>
                        <li>&#10003; Support writers you read most</li>
                        <li>&#10003; Listen to audio narrations</li>
                        <li>&#10003; Read offline with the Blooger app</li>

                    </ul>
                </div>
            </div>
            <!-- Friend of Blooger Plan -->
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card shadow-sm text-center p-4">
                    <div class="mb-3 text-warning">&#129505;</div>
                    <h4 class="fw-bold">Friend of Blooger</h4>
                    <p class="text-muted">$10/month or $120/year</p>

                    <?php 
                        if (isset($_SESSION['user_id'])) {
                            echo '<a href="payment.php?type=Friend of Blooger&price=120" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        } else {
                            echo '<a href="login.php" class="btn btn-dark rounded-pill px-3 py-2">Upgrade Now</a>';
                        }
                    ?>

                    <hr>
                    <ul class="list-unstyled text-start mx-auto w-75">
                        <li>&#11088; All Medium member benefits</li>
                        <li>&#10003; Give 4x more to the writers you read</li>
                        <li>&#10003; Share member-only stories with anyone</li>
                        <li>&#10003; Earn money for your writing</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    include "../inc/footer.inc.php";
    ?>
</body>
</html>
