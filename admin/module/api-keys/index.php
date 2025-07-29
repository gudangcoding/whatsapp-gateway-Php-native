<?php
require_once '../../helper/auth.php';
require_once '../../helper/koneksi.php';

// Require authentication
requireAuth();

$user_id = getCurrentUserId();
$user_info = getCurrentUserInfo();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-key me-2"></i>API Keys Management
            </h1>
            <p class="text-muted">Kelola API key untuk integrasi dengan sistem Anda</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">
            <i class="bi bi-plus-circle me-2"></i>Generate New API Key
        </button>
    </div>

    <!-- API Key Info Card -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle me-2 text-info"></i>Informasi API
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Base URL:</strong> <code>http://localhost:3000/api</code></p>
                            <p><strong>Authentication:</strong> <code>X-API-Key</code> header</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Package:</strong> <?php echo ucfirst($user_info['package_type']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $user_info['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($user_info['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Your API Keys</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="apiKeysTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>API Key</th>
                            <th>Created</th>
                            <th>Last Used</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- API Documentation -->
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-book me-2"></i>API Documentation
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="apiDocs">
                        <!-- Send Message -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sendMessage">
                                    Send Message
                                </button>
                            </h2>
                            <div id="sendMessage" class="accordion-collapse collapse show" data-bs-parent="#apiDocs">
                                <div class="accordion-body">
                                    <h6>POST /api/send-message</h6>
                                    <p>Send WhatsApp message to a specific number.</p>
                                    <pre><code>curl -X POST http://localhost:3000/api/send-message \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "pengirim": "6281234567890",
    "nomor": "6289876543210",
    "pesan": "Hello from API!"
  }'</code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Send Media -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sendMedia">
                                    Send Media
                                </button>
                            </h2>
                            <div id="sendMedia" class="accordion-collapse collapse" data-bs-parent="#apiDocs">
                                <div class="accordion-body">
                                    <h6>POST /api/send-media</h6>
                                    <p>Send media file (image, document) via WhatsApp.</p>
                                    <pre><code>curl -X POST http://localhost:3000/api/send-media \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "pengirim": "6281234567890",
    "nomor": "6289876543210",
    "url": "https://example.com/image.jpg",
    "caption": "Check this image",
    "filetype": "jpg"
  }'</code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Get Chat History -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#getChatHistory">
                                    Get Chat History
                                </button>
                            </h2>
                            <div id="getChatHistory" class="accordion-collapse collapse" data-bs-parent="#apiDocs">
                                <div class="accordion-body">
                                    <h6>GET /api/user/chat-history</h6>
                                    <p>Get chat history for the authenticated user.</p>
                                    <pre><code>curl -X GET "http://localhost:3000/api/user/chat-history?limit=50&offset=0" \
  -H "X-API-Key: YOUR_API_KEY"</code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Get User Devices -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#getDevices">
                                    Get User Devices
                                </button>
                            </h2>
                            <div id="getDevices" class="accordion-collapse collapse" data-bs-parent="#apiDocs">
                                <div class="accordion-body">
                                    <h6>GET /api/user/devices</h6>
                                    <p>Get list of WhatsApp devices owned by the user.</p>
                                    <pre><code>curl -X GET http://localhost:3000/api/user/devices \
  -H "X-API-Key: YOUR_API_KEY"</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add API Key Modal -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate New API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addApiKeyForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="apiKeyName" class="form-label">API Key Name</label>
                        <input type="text" class="form-control" id="apiKeyName" name="name" required 
                               placeholder="e.g., Production API, Development API">
                        <div class="form-text">Give your API key a descriptive name for easy identification.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- API Key Result Modal -->
<div class="modal fade" id="apiKeyResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">API Key Generated</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Copy your API key now. You won't be able to see it again!
                </div>
                <div class="mb-3">
                    <label class="form-label">Your API Key:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="generatedApiKey" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadApiKeys();
    
    // Handle form submission
    $('#addApiKeyForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('#apiKeyName').val()
        };
        
        $.ajax({
            url: 'API/api-keys.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    $('#generatedApiKey').val(response.api_key);
                    $('#addApiKeyModal').modal('hide');
                    $('#apiKeyResultModal').modal('show');
                    loadApiKeys(); // Reload table
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('Failed to generate API key');
            }
        });
    });
});

function loadApiKeys() {
    $.ajax({
        url: 'API/api-keys.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const tbody = $('#apiKeysTable tbody');
                tbody.empty();
                
                if (response.api_keys.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-key fs-1 d-block mb-2"></i>
                                No API keys found. Generate your first API key to get started.
                            </td>
                        </tr>
                    `);
                } else {
                    response.api_keys.forEach(function(key) {
                        const maskedKey = key.api_key.substring(0, 8) + '...' + key.api_key.substring(key.api_key.length - 8);
                        const statusBadge = key.is_active ? 
                            '<span class="badge bg-success">Active</span>' : 
                            '<span class="badge bg-danger">Revoked</span>';
                        
                        tbody.append(`
                            <tr>
                                <td>${key.name}</td>
                                <td>
                                    <code>${maskedKey}</code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="showFullKey('${key.api_key}')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                                <td>${new Date(key.created_at).toLocaleDateString()}</td>
                                <td>${key.last_used ? new Date(key.last_used).toLocaleDateString() : 'Never'}</td>
                                <td>${statusBadge}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editApiKey(${key.id}, '${key.name}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="revokeApiKey(${key.id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
            } else {
                alert('Error loading API keys: ' + response.error);
            }
        },
        error: function() {
            alert('Failed to load API keys');
        }
    });
}

function showFullKey(apiKey) {
    const modal = `
        <div class="modal fade" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Full API Key</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Keep this API key secure and don't share it with others.
                        </div>
                        <div class="input-group">
                            <input type="text" class="form-control" value="${apiKey}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('${apiKey}')">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $(modal).modal('show');
}

function editApiKey(id, currentName) {
    const newName = prompt('Enter new name for API key:', currentName);
    if (newName && newName !== currentName) {
        $.ajax({
            url: 'API/api-keys.php',
            type: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({
                id: id,
                name: newName
            }),
            success: function(response) {
                if (response.success) {
                    loadApiKeys();
                    alert('API key updated successfully');
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('Failed to update API key');
            }
        });
    }
}

function revokeApiKey(id) {
    if (confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
        $.ajax({
            url: 'API/api-keys.php?id=' + id,
            type: 'DELETE',
            success: function(response) {
                if (response.success) {
                    loadApiKeys();
                    alert('API key revoked successfully');
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('Failed to revoke API key');
            }
        });
    }
}

function copyApiKey() {
    copyToClipboard($('#generatedApiKey').val());
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('API key copied to clipboard!');
    }, function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('API key copied to clipboard!');
    });
}
</script> 