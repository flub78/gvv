<div class="container mt-5" style="max-width: 600px;">
    <div class="row">
        <div class="col-md-12">
            <?php if ($status === 'success'): ?>
                <!-- Success Message -->
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading">
                        <i class="fa fa-check-circle" style="color: green;"></i> Payment Successful!
                    </h4>
                    <p>
                        Thank you! Your payment has been processed by HelloAsso. You have been redirected back to GVV.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>What's next?</strong> You can:
                    </p>
                    <ul class="mt-2 mb-0">
                        <li>Check your email for a payment receipt from HelloAsso</li>
                        <li>Return to the <a href="<?php echo site_url('payments/test_helloasso'); ?>">payment test page</a> to make another test payment</li>
                        <li>Go back to the <a href="<?php echo site_url('welcome'); ?>">main page</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Failure/Cancellation Message -->
                <div class="alert alert-warning" role="alert">
                    <h4 class="alert-heading">
                        <i class="fa fa-exclamation-triangle" style="color: orange;"></i> Payment Not Completed
                    </h4>
                    <p>
                        <?php echo $message; ?>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>What's next?</strong> You can:
                    </p>
                    <ul class="mt-2 mb-0">
                        <li>Try the <a href="<?php echo site_url('payments/test_helloasso'); ?>">payment test page</a> again</li>
                        <li>Go back to the <a href="<?php echo site_url('welcome'); ?>">main page</a></li>
                        <li>Contact support if you need assistance</li>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Debug Information -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Callback Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge <?php echo $status === 'success' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo htmlspecialchars(strtoupper($status)); ?>
                            </span>
                        </dd>

                        <dt class="col-sm-4">Timestamp:</dt>
                        <dd class="col-sm-8"><?php echo date('Y-m-d H:i:s'); ?></dd>

                        <dt class="col-sm-4">Requested URL:</dt>
                        <dd class="col-sm-8">
                            <small><code><?php echo htmlspecialchars(current_url()); ?></code></small>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap CSS & JavaScript -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
    body {
        background-color: #f8f9fa;
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    code {
        background-color: #f4f4f4;
        padding: 2px 4px;
        border-radius: 3px;
    }
</style>
