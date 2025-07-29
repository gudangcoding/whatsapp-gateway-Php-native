<?php
// Get user subscription data
$user_id = $_SESSION['user_id'];
$user_info = null;
$subscription_info = null;
$usage_info = null;

include 'helper/koneksi.php';

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();
$stmt->close();

// Get current subscription (check if table exists first)
$subscription_info = null;
try {
    $stmt = $conn->prepare("
        SELECT * FROM user_subscriptions 
        WHERE user_id = ? AND status = 'active' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription_info = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    // Table doesn't exist, subscription_info will remain null
    $subscription_info = null;
}

// Get usage limits (check if table exists first)
$usage_info = null;
try {
    $stmt = $conn->prepare("
        SELECT * FROM user_limits 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usage_info = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    // Table doesn't exist, usage_info will remain null
    $usage_info = null;
}

// Get message count for current month
$current_month = date('Y-m');
$stmt = $conn->prepare("
    SELECT COUNT(*) as message_count 
    FROM receive_chat 
    WHERE user_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
");
$stmt->bind_param('is', $user_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();
$message_count = $result->fetch_assoc()['message_count'] ?? 0;
$stmt->close();

// Get device count
$stmt = $conn->prepare("
    SELECT COUNT(*) as device_count 
    FROM user_devices 
    WHERE user_id = ? AND status = 'active'
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$device_count = $result->fetch_assoc()['device_count'] ?? 0;
$stmt->close();

// Package configurations
$packages = [
    'starter' => [
        'name' => 'Starter',
        'price' => 'Rp 99.000',
        'price_monthly' => 99000,
        'features' => [
            '1 Device WhatsApp',
            '1.000 Pesan/Bulan',
            'Auto Reply Basic',
            'Pesan Terjadwal',
            'API Access',
            'Email Support'
        ],
        'limits' => [
            'max_devices' => 1,
            'max_messages' => 1000,
            'max_auto_replies' => 5,
            'max_scheduled_messages' => 50
        ]
    ],
    'business' => [
        'name' => 'Business',
        'price' => 'Rp 299.000',
        'price_monthly' => 299000,
        'features' => [
            '5 Device WhatsApp',
            '10.000 Pesan/Bulan',
            'Auto Reply Advanced',
            'Pesan Terjadwal',
            'API Access',
            'Priority Support',
            'Analytics Dashboard'
        ],
        'limits' => [
            'max_devices' => 5,
            'max_messages' => 10000,
            'max_auto_replies' => 20,
            'max_scheduled_messages' => 200
        ]
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 'Rp 999.000',
        'price_monthly' => 999000,
        'features' => [
            'Unlimited Devices',
            'Unlimited Pesan',
            'Auto Reply Advanced',
            'Pesan Terjadwal',
            'API Access',
            'Priority Support',
            'Analytics Dashboard',
            'Custom Integration',
            'Dedicated Support'
        ],
        'limits' => [
            'max_devices' => -1, // unlimited
            'max_messages' => -1, // unlimited
            'max_auto_replies' => -1, // unlimited
            'max_scheduled_messages' => -1 // unlimited
        ]
    ]
];

$current_package = $user_info['package_type'] ?? 'starter';
$current_package_info = $packages[$current_package];
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-secondary">Subscription Management</h1>
            <p class="text-muted mb-0">Kelola paket langganan dan batasan penggunaan</p>
        </div>
        <?php if ($subscription_info): ?>
            <div class="text-end">
                <span class="badge bg-success fs-6"><?php echo ucfirst($current_package); ?> Plan</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Current Subscription Status -->
    <?php if ($subscription_info): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            Current Subscription
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-success fw-bold"><?php echo $current_package_info['name']; ?> Plan</h6>
                                <p class="text-muted mb-2"><?php echo $current_package_info['price']; ?>/bulan</p>
                                <div class="mb-3">
                                    <small class="text-muted">Start Date:</small><br>
                                    <strong><?php echo date('d M Y', strtotime($subscription_info['start_date'])); ?></strong>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">End Date:</small><br>
                                    <strong><?php echo date('d M Y', strtotime($subscription_info['end_date'])); ?></strong>
                                </div>
                                <?php
                                $days_left = (strtotime($subscription_info['end_date']) - time()) / (60 * 60 * 24);
                                if ($days_left <= 7 && $days_left > 0):
                                ?>
                                    <div class="alert alert-warning py-2 mb-0">
                                        <small><i class="bi bi-exclamation-triangle me-1"></i>
                                        Subscription expires in <?php echo ceil($days_left); ?> days
                                        </small>
                                    </div>
                                <?php elseif ($days_left <= 0): ?>
                                    <div class="alert alert-danger py-2 mb-0">
                                        <small><i class="bi bi-x-circle me-1"></i>
                                        Subscription expired
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-secondary fw-bold">Usage This Month</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center p-3">
                                            <div class="h4 text-primary mb-1"><?php echo $device_count; ?></div>
                                            <small class="text-muted">Active Devices</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3">
                                            <div class="h4 text-info mb-1"><?php echo number_format($message_count); ?></div>
                                            <small class="text-muted">Messages Sent</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>No Active Subscription</strong> - Please choose a plan to continue using our services.
        </div>
    <?php endif; ?>

    <!-- Usage Limits -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Usage Limits
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="h4 text-primary mb-1">
                                    <?php 
                                    $max_devices = $current_package_info['limits']['max_devices'];
                                    echo $max_devices == -1 ? '∞' : $max_devices;
                                    ?>
                                </div>
                                <small class="text-muted">Max Devices</small>
                                <div class="progress mt-2" style="height: 4px;">
                                    <?php 
                                    $device_percentage = $max_devices == -1 ? 0 : min(100, ($device_count / $max_devices) * 100);
                                    ?>
                                    <div class="progress-bar bg-primary" style="width: <?php echo $device_percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="h4 text-info mb-1">
                                    <?php 
                                    $max_messages = $current_package_info['limits']['max_messages'];
                                    echo $max_messages == -1 ? '∞' : number_format($max_messages);
                                    ?>
                                </div>
                                <small class="text-muted">Max Messages/Month</small>
                                <div class="progress mt-2" style="height: 4px;">
                                    <?php 
                                    $message_percentage = $max_messages == -1 ? 0 : min(100, ($message_count / $max_messages) * 100);
                                    ?>
                                    <div class="progress-bar bg-info" style="width: <?php echo $message_percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="h4 text-warning mb-1">
                                    <?php 
                                    $max_auto_replies = $current_package_info['limits']['max_auto_replies'];
                                    echo $max_auto_replies == -1 ? '∞' : $max_auto_replies;
                                    ?>
                                </div>
                                <small class="text-muted">Max Auto Replies</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="h4 text-success mb-1">
                                    <?php 
                                    $max_scheduled = $current_package_info['limits']['max_scheduled_messages'];
                                    echo $max_scheduled == -1 ? '∞' : $max_scheduled;
                                    ?>
                                </div>
                                <small class="text-muted">Max Scheduled Messages</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Plans -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Available Plans
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($packages as $package_key => $package): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border <?php echo $current_package === $package_key ? 'border-success' : ''; ?>">
                                    <?php if ($current_package === $package_key): ?>
                                        <div class="card-header bg-success text-white text-center">
                                            <small><i class="bi bi-check-circle me-1"></i>Current Plan</small>
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary"><?php echo $package['name']; ?></h5>
                                        <div class="h3 text-success mb-3"><?php echo $package['price']; ?><small class="text-muted">/bulan</small></div>
                                        
                                        <ul class="list-unstyled text-start mb-4">
                                            <?php foreach ($package['features'] as $feature): ?>
                                                <li class="mb-2">
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                    <?php echo $feature; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        
                                        <?php if ($current_package === $package_key): ?>
                                            <button class="btn btn-outline-success w-100" disabled>
                                                <i class="bi bi-check-circle me-2"></i>Current Plan
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-success w-100 upgrade-btn" data-package="<?php echo $package_key; ?>">
                                                <i class="bi bi-arrow-up-circle me-2"></i>Upgrade to <?php echo $package['name']; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Payment History
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Package</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get payment history (check if table exists first)
                                try {
                                    $stmt = $conn->prepare("
                                        SELECT p.*, us.package_type 
                                        FROM payments p 
                                        LEFT JOIN user_subscriptions us ON p.user_id = us.user_id 
                                        WHERE p.user_id = ? AND p.status = 'success'
                                        ORDER BY p.created_at DESC 
                                        LIMIT 10
                                    ");
                                    $stmt->bind_param('i', $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    
                                    if ($result->num_rows > 0):
                                        while ($payment = $result->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($payment['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo ucfirst($payment['package_type'] ?? 'Unknown'); ?></span>
                                            </td>
                                            <td><?php echo 'Rp ' . number_format($payment['amount']); ?></td>
                                            <td>
                                                <span class="badge bg-success">Success</span>
                                            </td>
                                            <td><?php echo ucfirst($payment['payment_method'] ?? 'Unknown'); ?></td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No payment history found</td>
                                        </tr>
                                    <?php 
                                    endif;
                                    $stmt->close();
                                } catch (Exception $e) {
                                    // Table doesn't exist
                                    ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Payment system not configured</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="upgradeModalLabel">Upgrade Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="upgrade-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle upgrade button clicks
    $('.upgrade-btn').on('click', function() {
        const package = $(this).data('package');
        const packageInfo = <?php echo json_encode($packages); ?>;
        const selectedPackage = packageInfo[package];
        
        let content = `
            <div class="text-center mb-4">
                <h4 class="text-primary">${selectedPackage.name} Plan</h4>
                <div class="h2 text-success mb-3">${selectedPackage.price}<small class="text-muted">/bulan</small></div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-secondary">Features:</h6>
                    <ul class="list-unstyled">
        `;
        
        selectedPackage.features.forEach(feature => {
            content += `<li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>${feature}</li>`;
        });
        
        content += `
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-secondary">Limits:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><strong>Devices:</strong> ${selectedPackage.limits.max_devices === -1 ? 'Unlimited' : selectedPackage.limits.max_devices}</li>
                        <li class="mb-2"><strong>Messages:</strong> ${selectedPackage.limits.max_messages === -1 ? 'Unlimited' : selectedPackage.limits.max_messages.toLocaleString()}/month</li>
                        <li class="mb-2"><strong>Auto Replies:</strong> ${selectedPackage.limits.max_auto_replies === -1 ? 'Unlimited' : selectedPackage.limits.max_auto_replies}</li>
                        <li class="mb-2"><strong>Scheduled Messages:</strong> ${selectedPackage.limits.max_scheduled_messages === -1 ? 'Unlimited' : selectedPackage.limits.max_scheduled_messages}</li>
                    </ul>
                </div>
            </div>
            
            <div class="text-center">
                <button type="button" class="btn btn-success btn-lg" onclick="processUpgrade('${package}')">
                    <i class="bi bi-credit-card me-2"></i>Proceed to Payment
                </button>
            </div>
        `;
        
        $('#upgrade-content').html(content);
        $('#upgradeModal').modal('show');
    });
});

function processUpgrade(package) {
    // Show loading
    $('#upgradeModal').modal('hide');
    
    // Show loading alert
    const loadingAlert = $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
        '<i class="bi bi-hourglass-split me-2"></i>Processing upgrade...' +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    $('.container-fluid').prepend(loadingAlert);
    
    // Make AJAX request
    const user_id = <?php echo $user_id; ?>;
    $.ajax({
        url: `../API/midtrans.php?package=${package}&action=upgrade&user_id=${user_id}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            loadingAlert.remove();
            
            if (response.status === 'success') {
                if (response.demo_mode) {
                    // Demo mode - show success message
                    const successAlert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        '<i class="bi bi-check-circle me-2"></i>' + response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('.container-fluid').prepend(successAlert);
                    
                    // Reload page after 2 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // Real payment mode - redirect to Midtrans
                    if (response.snap_token) {
                        // Redirect to Midtrans payment page
                        window.location.href = `../payment-success.php?order_id=${response.order_id}`;
                    }
                }
            } else {
                const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<i class="bi bi-exclamation-triangle me-2"></i>Error: ' + (response.error || 'Unknown error') +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('.container-fluid').prepend(errorAlert);
            }
        },
        error: function(xhr, status, error) {
            loadingAlert.remove();
            const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                '<i class="bi bi-exclamation-triangle me-2"></i>Error: ' + error +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            $('.container-fluid').prepend(errorAlert);
        }
    });
}
</script>
