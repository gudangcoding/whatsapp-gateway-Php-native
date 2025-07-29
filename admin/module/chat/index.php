<?php
// Ambil daftar chat (nomor dan nama) dari database
include_once __DIR__ . '/../../helper/koneksi.php';

// Ambil daftar chat (nomor unik yang pernah chat)
$chatList = [];
$sql = "SELECT nomor, MAX(tanggal) as last_date FROM receive_chat GROUP BY nomor ORDER BY last_date DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ambil nama dari tabel contacts jika ada, jika tidak pakai nomor
        $nama = $row['nomor'];
        $qNama = $conn->query("SELECT name FROM contacts WHERE number = '{$row['nomor']}' LIMIT 1");
        if ($qNama && $qNama->num_rows > 0) {
            $dNama = $qNama->fetch_assoc();
            $nama = $dNama['name'];
        }
        $chatList[] = [
            'nomor' => $row['nomor'],
            'nama' => $nama
        ];
    }
}
?>
<h1 class="mb-4 fw-bold text-secondary">Chat WhatsApp</h1>
<div class="bg-white shadow rounded">
    <div class="row g-0" style="height: 600px;">
        <!-- Sidebar Chat List -->
        <div class="col-md-4 border-end sidebar-container" id="sidebarContainer">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <input type="text" id="searchInput" class="form-control me-2" placeholder="Cari chat...">
                <button class="btn btn-outline-secondary btn-sm" id="toggleSidebar" title="Sembunyikan sidebar">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </div>
            <div class="chat-list" id="chatList" style="height: calc(100% - 80px); overflow-y: auto;">
                <?php if (empty($chatList)): ?>
                    <div class="text-center text-secondary py-4">
                        <i class="bi bi-chat-dots fs-1"></i>
                        <p class="mt-2">Belum ada chat</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($chatList as $chat): ?>
                    <div class="chat-item p-3 border-bottom" onclick="selectChat('<?php echo htmlspecialchars($chat['nomor']); ?>', '<?php echo htmlspecialchars(addslashes($chat['nama'])); ?>')">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="bi bi-person text-white"></i>
                                </div>
                            </div>
                            <div class="chat-info flex-grow-1">
                                <div class="chat-name fw-semibold"><?php echo htmlspecialchars($chat['nama']); ?></div>
                                <div class="chat-last text-muted small" id="last-<?php echo htmlspecialchars($chat['nomor']); ?>">
                                    <?php echo htmlspecialchars($chat['nomor']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="col-md-8 d-flex flex-column main-chat-area" id="mainChatArea">
            <!-- Chat Header -->
            <div class="main-header p-3 border-bottom bg-light">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary btn-sm me-3 d-md-none" id="showSidebarMobile" title="Tampilkan sidebar">
                            <i class="bi bi-list"></i>
                        </button>
                        <div class="avatar me-3">
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="bi bi-person text-white"></i>
                            </div>
                        </div>
                        <div class="chat-name fw-semibold" id="chatName">Pilih chat untuk memulai</div>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm d-none d-md-block" id="showSidebar" title="Tampilkan sidebar" style="display: none !important;">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div class="chat-body flex-grow-1 p-3" id="chatBody" style="height: 400px; overflow-y: auto; background-color: #f8f9fa;">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mt-2">Pilih chat untuk melihat pesan</p>
                </div>
            </div>
            
            <!-- Chat Input -->
            <div class="chat-footer p-3 border-top bg-white">
                <div class="d-flex">
                    <input type="text" id="messageInput" class="form-control me-2" placeholder="Ketik pesan..." disabled>
                    <button class="btn btn-success" onclick="sendMessage()" disabled>
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedNomor = null;
let selectedNama = null;
let sidebarCollapsed = false;

// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarContainer = document.getElementById('sidebarContainer');
    const mainChatArea = document.getElementById('mainChatArea');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const showSidebar = document.getElementById('showSidebar');
    const showSidebarMobile = document.getElementById('showSidebarMobile');

    // Toggle sidebar
    toggleSidebar.addEventListener('click', function() {
        sidebarCollapsed = true;
        sidebarContainer.style.display = 'none';
        mainChatArea.classList.remove('col-md-8');
        mainChatArea.classList.add('col-md-12');
        showSidebar.style.display = 'block';
        toggleSidebar.style.display = 'none';
    });

    // Show sidebar
    showSidebar.addEventListener('click', function() {
        sidebarCollapsed = false;
        sidebarContainer.style.display = 'block';
        mainChatArea.classList.remove('col-md-12');
        mainChatArea.classList.add('col-md-8');
        showSidebar.style.display = 'none';
        toggleSidebar.style.display = 'block';
    });

    // Mobile sidebar toggle
    showSidebarMobile.addEventListener('click', function() {
        if (sidebarCollapsed) {
            sidebarCollapsed = false;
            sidebarContainer.style.display = 'block';
            showSidebarMobile.style.display = 'none';
        } else {
            sidebarCollapsed = true;
            sidebarContainer.style.display = 'none';
            showSidebarMobile.style.display = 'block';
        }
    });

    // Load last messages
    <?php foreach ($chatList as $chat): ?>
    fetch('API/get_last_message.php?nomor=<?php echo urlencode($chat['nomor']); ?>')
        .then(res => res.json())
        .then(data => {
            if (data && data.pesan) {
                const lastElement = document.getElementById('last-<?php echo htmlspecialchars($chat['nomor']); ?>');
                if (lastElement) {
                    lastElement.textContent = data.pesan.length > 30 ? data.pesan.substring(0, 30) + '...' : data.pesan;
                }
            }
        })
        .catch(err => console.error('Error loading last message:', err));
    <?php endforeach; ?>
});

// Fitur search chat
document.getElementById('searchInput').addEventListener('input', function() {
    let val = this.value.toLowerCase();
    document.querySelectorAll('.chat-item').forEach(function(item) {
        let name = item.querySelector('.chat-name').textContent.toLowerCase();
        if (name.includes(val)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

function selectChat(nomor, nama) {
    selectedNomor = nomor;
    selectedNama = nama;
    
    // Update header
    document.getElementById('chatName').textContent = nama;
    
    // Enable input
    document.getElementById('messageInput').disabled = false;
    document.querySelector('.chat-footer button').disabled = false;
    
    // Update active chat item
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Auto-collapse sidebar on mobile after selecting chat
    if (window.innerWidth < 768 && !sidebarCollapsed) {
        document.getElementById('sidebarContainer').style.display = 'none';
        document.getElementById('showSidebarMobile').style.display = 'block';
        sidebarCollapsed = true;
    }
    
    // Load messages
    let chatBody = document.getElementById('chatBody');
    chatBody.innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-arrow-clockwise spin"></i> Memuat pesan...</div>';
    
    fetch('API/get_chat.php?nomor=' + encodeURIComponent(nomor))
        .then(res => res.json())
        .then(data => {
            chatBody.innerHTML = '';
            if (data && data.length > 0) {
                data.forEach(function(msg) {
                    let div = document.createElement('div');
                    div.className = 'message mb-2 ' + (msg.from_me === '1' ? 'sent' : 'received');
                    
                    let messageContent = document.createElement('div');
                    messageContent.className = 'message-content';
                    messageContent.textContent = msg.pesan;
                    
                    let timeDiv = document.createElement('div');
                    timeDiv.className = 'message-time small text-muted mt-1';
                    timeDiv.textContent = new Date(msg.tanggal).toLocaleTimeString('id-ID', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    
                    div.appendChild(messageContent);
                    div.appendChild(timeDiv);
                    chatBody.appendChild(div);
                });
                chatBody.scrollTop = chatBody.scrollHeight;
            } else {
                chatBody.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-chat-dots fs-1"></i><p class="mt-2">Belum ada pesan</p></div>';
            }
        })
        .catch(err => {
            chatBody.innerHTML = '<div class="text-center text-danger py-5"><i class="bi bi-exclamation-triangle fs-1"></i><p class="mt-2">Gagal memuat pesan</p></div>';
            console.error('Error loading chat:', err);
        });
}

function sendMessage() {
    let input = document.getElementById('messageInput');
    let text = input.value.trim();
    
    if (!selectedNomor) {
        alert('Pilih chat terlebih dahulu!');
        return;
    }
    
    if (!text) {
        return;
    }
    
    // Tampilkan pesan langsung (optimis)
    let chatBody = document.getElementById('chatBody');
    let msg = document.createElement('div');
    msg.className = 'message mb-2 sent';
    
    let messageContent = document.createElement('div');
    messageContent.className = 'message-content';
    messageContent.textContent = text;
    
    let timeDiv = document.createElement('div');
    timeDiv.className = 'message-time small text-muted mt-1';
    timeDiv.textContent = new Date().toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    msg.appendChild(messageContent);
    msg.appendChild(timeDiv);
    chatBody.appendChild(msg);
    chatBody.scrollTop = chatBody.scrollHeight;
    input.value = '';

    // Kirim ke server
    fetch('API/send-message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ nomor: selectedNomor, pesan: text })
    })
    .then(res => res.json())
    .then(data => {
        if (!data || !data.success) {
            alert('Gagal mengirim pesan: ' + (data && data.error ? data.error : 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Gagal mengirim pesan: ' + err.message);
        console.error('Error sending message:', err);
    });
}

// Enter key to send message
document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) {
        // On desktop, show sidebar by default if not manually collapsed
        if (!sidebarCollapsed) {
            document.getElementById('sidebarContainer').style.display = 'block';
            document.getElementById('showSidebarMobile').style.display = 'none';
        }
    } else {
        // On mobile, hide sidebar by default
        if (!sidebarCollapsed) {
            document.getElementById('sidebarContainer').style.display = 'none';
            document.getElementById('showSidebarMobile').style.display = 'block';
            sidebarCollapsed = true;
        }
    }
});
</script>

<style>
.sidebar-container {
    transition: all 0.3s ease;
}

.main-chat-area {
    transition: all 0.3s ease;
}

.chat-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.chat-item:hover {
    background-color: #f8f9fa;
}

.chat-item.active {
    background-color: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.message {
    display: flex;
    margin-bottom: 10px;
}

.message.sent {
    justify-content: flex-end;
}

.message.received {
    justify-content: flex-start;
}

.message-content {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    word-wrap: break-word;
}

.message.sent .message-content {
    background-color: #d1f7c4;
    color: #000;
}

.message.received .message-content {
    background-color: #fff;
    border: 1px solid #e0e0e0;
    color: #000;
}

.message-time {
    font-size: 0.75rem;
    margin-top: 2px;
}

.message.sent .message-time {
    text-align: right;
}

.message.received .message-time {
    text-align: left;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .col-md-4 {
        height: 300px;
    }
    
    .col-md-8 {
        height: 300px;
    }
    
    .sidebar-container {
        position: absolute;
        z-index: 1000;
        background: white;
        height: 100%;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    
    .main-chat-area {
        width: 100%;
    }
}

/* Smooth transitions */
.sidebar-container, .main-chat-area {
    transition: all 0.3s ease-in-out;
}
</style>
