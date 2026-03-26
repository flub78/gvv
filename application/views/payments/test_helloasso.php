<div class="container mt-5" style="max-width: 600px;">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <i class="fa fa-credit-card"></i> HelloAsso Payment Test
            </h1>

            <!-- Success Message -->
            <?php if (isset($message_type) && $message_type === 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Payment Initiated!</strong>
                    <p><?php echo $message; ?></p>
                    
                    <?php if (isset($redirect_url)): ?>
                        <hr>
                        <p>
                            <strong>Reference:</strong> <?php echo htmlspecialchars($reference_returned); ?><br>
                            <strong>Session ID:</strong> <?php echo htmlspecialchars($session_id); ?>
                        </p>
                        <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn btn-primary btn-lg" target="_blank">
                            <i class="fa fa-arrow-right"></i> Continue to HelloAsso Payment Page
                        </a>
                        <p class="text-muted mt-3">
                            <small>You will be redirected to HelloAsso to complete the payment. After payment, you will return to this page.</small>
                        </p>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($message_type) && $message_type === 'error'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Payment Failed!</strong>
                    <p><?php echo htmlspecialchars($message); ?></p>
                    <?php if (isset($error_code) && $error_code > 0): ?>
                        <small class="text-muted">Error Code: <?php echo $error_code; ?></small>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Validation Errors -->
            <?php if (isset($errors) && is_array($errors) && count($errors) > 0): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Validation Errors:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-wpforms"></i> Payment Request
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Preserve form data on error
                    $reference = isset($form_data) ? $form_data['reference'] : '';
                    $payer_name = isset($form_data) ? $form_data['payer_name'] : '';
                    $amount = isset($form_data) ? $form_data['amount'] : '';
                    $payer_email = isset($form_data) ? $form_data['payer_email'] : '';
                    ?>

                    <form method="post" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="<?php echo $csrf_token_name; ?>" value="<?php echo $csrf_hash; ?>">

                        <!-- Reference Field -->
                        <div class="mb-3">
                            <label for="reference" class="form-label">
                                <i class="fa fa-tag"></i> Reference
                                <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="reference" 
                                name="reference" 
                                placeholder="e.g., REF-001, INVOICE-2024-001"
                                value="<?php echo htmlspecialchars($reference); ?>"
                                required
                            >
                            <small class="form-text text-muted">
                                Your internal reference or order ID
                            </small>
                        </div>

                        <!-- Payer Name Field -->
                        <div class="mb-3">
                            <label for="payer_name" class="form-label">
                                <i class="fa fa-user"></i> Payer Name
                                <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="payer_name" 
                                name="payer_name" 
                                placeholder="e.g., Jean Dupont"
                                value="<?php echo htmlspecialchars($payer_name); ?>"
                                required
                            >
                            <small class="form-text text-muted">
                                Full name of the person making the payment
                            </small>
                        </div>

                        <!-- Amount Field -->
                        <div class="mb-3">
                            <label for="amount" class="form-label">
                                <i class="fa fa-euro-sign"></i> Amount (EUR)
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="number" 
                                    class="form-control" 
                                    id="amount" 
                                    name="amount" 
                                    placeholder="e.g., 5.00"
                                    step="0.01"
                                    min="0.50"
                                    max="1000"
                                    value="<?php echo htmlspecialchars($amount); ?>"
                                    required
                                >
                                <span class="input-group-text">EUR</span>
                            </div>
                            <small class="form-text text-muted">
                                Minimum: €0.50 | Maximum: €1000
                            </small>
                        </div>

                        <!-- Payer Email Field (Optional) -->
                        <div class="mb-3">
                            <label for="payer_email" class="form-label">
                                <i class="fa fa-envelope"></i> Email Address
                                <span class="text-muted">(Optional)</span>
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="payer_email" 
                                name="payer_email" 
                                placeholder="e.g., jean.dupont@example.com"
                                value="<?php echo htmlspecialchars($payer_email); ?>"
                            >
                            <small class="form-text text-muted">
                                Email for payment receipt from HelloAsso
                            </small>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo site_url('welcome'); ?>" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fa fa-redo"></i> Clear
                            </button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-credit-card"></i> Initiate Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Information Box -->
            <div class="alert alert-info mt-4">
                <h5 class="alert-heading">
                    <i class="fa fa-info-circle"></i> About This Test
                </h5>
                <p class="mb-2">
                    This is a <strong>development feature</strong> for testing HelloAsso payment integration.
                </p>
                <ul class="mb-2">
                    <li>You are connected to the <strong>HelloAsso Sandbox</strong> environment.</li>
                    <li>No real charges will be made.</li>
                    <li>Payment data submitted will be sent to HelloAsso for processing.</li>
                    <li>After submission, you will be redirected to HelloAsso to complete the test payment.</li>
                </ul>
                <p class="mb-0 text-muted">
                    <small><strong>User:</strong> <?php echo htmlspecialchars($username); ?></small>
                </p>
            </div>

            <!-- Debug Info (If available) -->
            <?php if (isset($session_id)): ?>
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Response Details</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Reference:</dt>
                            <dd class="col-sm-8"><code><?php echo htmlspecialchars($reference_returned); ?></code></dd>

                            <dt class="col-sm-4">Session ID:</dt>
                            <dd class="col-sm-8"><code><?php echo htmlspecialchars($session_id); ?></code></dd>

                            <?php if (isset($redirect_url)): ?>
                                <dt class="col-sm-4">Redirect URL:</dt>
                                <dd class="col-sm-8">
                                    <small>
                                        <a href="<?php echo htmlspecialchars($redirect_url); ?>" target="_blank">
                                            <?php echo htmlspecialchars(substr($redirect_url, 0, 60)) . '...'; ?>
                                        </a>
                                    </small>
                                </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap JavaScript -->
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
