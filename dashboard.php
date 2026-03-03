<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

// Fetch current user details
$stmt = $conn->prepare("SELECT name, profile_image, about, profile_pos_x, profile_pos_y FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

if (!$current_user) {
    // Session exists but user deleted or invalid
    session_destroy();
    header("Location: index.php");
    exit();
}

$profile_x = $current_user['profile_pos_x'] ?? 50;
$profile_y = $current_user['profile_pos_y'] ?? 50;
$object_pos = "{$profile_x}% {$profile_y}%";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Web Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        
        body { background: #fff; height: 100vh; overflow: hidden; position: relative; }
        
        /* .green-bg { display: none; } Remove or hide green background since app covers full screen */
        
        .app-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 100%;
            height: 100vh;
            margin: 0;
            background: #fff;
            display: flex;
            box-shadow: none;
            border-radius: 0;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 30%;
            min-width: 320px;
            background: white;
            border-right: 1px solid #e9edef;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            height: 60px;
            background: #f0f2f5;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 1px solid #d1d7db;
        }

        .profile-info { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; object-position: center; }
        
        .sidebar-actions button, .sidebar-actions a {
            background: none; border: none; color: #54656f; font-size: 18px; 
            margin-left: 15px; cursor: pointer; padding: 8px; border-radius: 50%;
        }
        .sidebar-actions button:hover, .sidebar-actions a:hover { background: rgba(0,0,0,0.05); }

        .search-bar { background: #fff; padding: 7px 12px; border-bottom: 1px solid #f0f2f5; }
        .search-bar input {
            width: 100%; padding: 7px 32px 7px 65px; background: #f0f2f5; 
            border: none; border-radius: 8px; font-size: 14px; outline: none;
        }

        .chat-list { flex: 1; overflow-y: auto; background: white; }
        .chat-item {
            display: flex; padding: 0 15px; height: 72px; cursor: pointer; 
            align-items: center; border-bottom: 1px solid #f0f2f5; transition: 0.2s;
        }
        .chat-item:hover { background: #f5f6f6; }
        .chat-item-info { flex: 1; border-bottom: 1px solid #f0f2f5; height: 100%; display: flex; flex-direction: column; justify-content: center; margin-left:15px;}
        .chat-item-name { font-size: 16px; color: #111b21; display:flex; justify-content: space-between; margin-bottom: 3px; }
        .chat-item-last-msg { font-size: 13px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Main Area */
        .main-area { flex: 1; display: flex; flex-direction: column; background: #efeae2; position: relative; }
        
        .default-view {
            height: 100%; display: flex; flex-direction: column; justify-content: center; 
            align-items: center; background: #f0f2f5; border-bottom: 6px solid #25d366; text-align: center;
        }
        .default-view h2 { font-weight: 300; color: #41525d; margin-top: 30px; font-size: 32px; }
        .default-view p { color: #667781; font-size: 14px; margin-top: 15px; line-height: 20px; }

        .chat-view { display: none; flex-direction: column; height: 100%; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); }

        .chat-header {
            height: 60px; background: #f0f2f5; padding: 10px 16px; display: flex; 
            justify-content: space-between; align-items: center; border-left: 1px solid #d1d7db;
        }
        .chat-user-info { display: flex; align-items: center; gap: 15px; cursor: pointer; }
        .chat-user-info h3 { font-size: 16px; font-weight: 400; color: #111b21; margin:0;}
        .status { font-size: 13px; color: #667781; }

        .chat-messages { flex: 1; padding: 20px 60px; overflow-y: auto; display: flex; flex-direction: column; }
        
        .message {
            max-width: 65%; padding: 6px 7px 8px 9px; border-radius: 7.5px; 
            margin-bottom: 7px; position: relative; font-size: 14.2px; line-height: 19px;
            box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
        }
        .message.sent { align-self: flex-end; background: #d9fdd3; }
        .message.received { align-self: flex-start; background: #fff; }
        .message-time { float: right; margin: -5px 0 -2px 10px; font-size: 11px; color: #667781; }

        .chat-input-area {
            min-height: 62px; background: #f0f2f5; padding: 5px 10px; 
            display: flex; align-items: center; gap: 10px;
        }
        
        .chat-input-area button.icon-btn, .chat-input-area label.icon-btn {
            background: none; border: none; font-size: 20px; color: #54656f; cursor: pointer; padding: 5px;
        }

        .chat-input-area input[type="text"] {
            flex: 1; padding: 12px 10px; border-radius: 8px; border: none; 
            font-size: 15px; outline: none; background: #fff; margin: 5px 10px;
        }

        .preview-container {
            display: none; padding: 10px; background: #e9edef; border-top: 1px solid #d1d7db;
            text-align: center; position: absolute; bottom: 60px; width: 100%; left: 0;
        }
        .preview-container img { max-height: 150px; border-radius: 5px; }
        .preview-close { position:absolute; top:5px; right:10px; cursor:pointer; font-size:20px; color:#d00; }
        
        .image-msg { max-width: 100%; border-radius: 5px; margin-top: 5px; cursor: pointer; }
        
        .attachment-menu {
            display: none;
            position: absolute;
            bottom: 70px;
            left: 10px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 10px;
            width: 200px;
            z-index: 100;
            flex-direction: column;
            gap: 5px;
        }

        .attachment-menu.active {
            display: flex;
            animation: fadeIn 0.2s ease-out;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            padding: 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.2s;
            color: #41525d;
            font-size: 14px;
        }

        .attachment-item:hover {
            background: #f0f2f5;
        }

        .attachment-item i {
            margin-right: 15px;
            font-size: 20px;
            width: 25px;
            text-align: center;
        }

        /* Specific icon colors matching WhatsApp Web */
        .icon-document { color: #7f66ff; background: linear-gradient(135deg, #7f66ff, #512da8); -webkit-background-clip: text; }
        .icon-photos { color: #007bff; background: linear-gradient(135deg, #007bff, #0056b3); -webkit-background-clip: text; }
        .icon-camera { color: #ff2e74; background: linear-gradient(135deg, #ff2e74, #d6004f); -webkit-background-clip: text; }
        .icon-audio { color: #fe4d4d; background: linear-gradient(135deg, #fe4d4d, #c62828); -webkit-background-clip: text; }
        .icon-contact { color: #009de2; background: linear-gradient(135deg, #009de2, #0077b6); -webkit-background-clip: text; }
        .icon-poll { color: #ffbc38; background: linear-gradient(135deg, #ffbc38, #ff8f00); -webkit-background-clip: text; }
        .icon-event { color: #ce2551; background: linear-gradient(135deg, #ce2551, #ad1457); -webkit-background-clip: text; } 
        .icon-sticker { color: #02a698; background: linear-gradient(135deg, #02a698, #00796b); -webkit-background-clip: text; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Profile Position Controls */
        .pos-controls { display: flex; gap: 5px; justify-content: center; margin-top: 10px; flex-wrap: wrap; }
        .pos-btn { width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ccc; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .pos-btn:hover { background: #f0f2f5; }
        
    </style>
</head>
<body>
    <div class="green-bg"></div>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="profile-info">
                   <img src="<?php echo $current_user['profile_image'] ? 'uploads/profile/'.$current_user['profile_image'] : 'https://ui-avatars.com/api/?name='.urlencode($current_user['name']).'&background=random'; ?>" alt="Profile" class="avatar" style="object-position: <?php echo $object_pos; ?>;">
                    <span><?php echo htmlspecialchars($current_user['name']); ?></span>
                </div>
                <div class="sidebar-actions">
                   <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            <div class="search-bar">
                <input type="text" id="search-users" placeholder="Search or start new chat">
            </div>
            
            <!-- Chat Filter Tabs -->
            <div class="chat-filters" style="display: flex; padding: 10px 16px; gap: 10px; border-bottom: 1px solid #e9edef;">
                <button class="filter-btn active" data-filter="all" style="background: #e9edef; border: none; padding: 5px 12px; border-radius: 20px; font-size: 13px; color: #54656f; cursor: pointer;">All</button>
                <button class="filter-btn" data-filter="unread" style="background: transparent; border: none; padding: 5px 12px; border-radius: 20px; font-size: 13px; color: #54656f; cursor: pointer;">Unread</button>
                <button class="filter-btn" data-filter="groups" style="background: transparent; border: none; padding: 5px 12px; border-radius: 20px; font-size: 13px; color: #54656f; cursor: pointer;">Groups</button>
            </div>

            <div class="chat-list" id="chat-list">
                <!-- Users will be loaded here via AJAX -->
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="main-area" id="main-area">
            <!-- Default View -->
            <div class="default-view" id="default-view">
                <img src="assets/images/whatsapp-bg.png" alt="WhatsApp Web" style="opacity: 0.1; max-width: 300px;">
                <h2>Welcome <?php echo htmlspecialchars($current_user['name']); ?></h2>
            </div>

            <!-- Chat View -->
            <div class="chat-view" id="chat-view" style="display: none; position: relative;">
                <div class="chat-header">
                    <i class="fas fa-arrow-left action-icon" id="back-to-list" style="display:none; margin-right: 15px; margin-left: 0;" onclick="closeChat()"></i>
                    <div class="chat-user-info" onclick="showContactInfo()">
                        <img src="https://ui-avatars.com/api/?name=User&background=random" alt="User" class="avatar" id="active-chat-avatar">
                        <div>
                            <h3 id="active-chat-name">User Name</h3>
                            <span id="active-chat-status" class="status">offline</span>
                        </div>
                    </div>
                    <div class="chat-actions" style="display:flex; align-items:center;">
                        <input type="checkbox" id="auto-scroll-toggle" checked style="display:none;">
                        <i class="fas fa-search action-icon" title="Search messages"></i>
                        <i class="fas fa-video action-icon" id="btn-video-call" title="Video call"></i>
                        <i class="fas fa-phone action-icon" id="btn-voice-call" title="Voice call"></i>
                        <div class="dropdown" style="position: relative;">
                            <i class="fas fa-ellipsis-v action-icon" id="chat-menu-btn" title="Menu"></i>
                            <div class="dropdown-menu" id="chat-dropdown-options" style="display: none; position: absolute; top: 30px; right: 0; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border-radius: 3px; width: 200px; z-index: 100;">
                                <a href="#" class="dropdown-item" onclick="showContactInfo(); return false;">Contact info</a>
                                <a href="#" class="dropdown-item" onclick="toggleSelectMessages(); return false;">Select messages</a>
                                <a href="#" class="dropdown-item" onclick="closeChat(); return false;">Close chat</a>
                                <a href="#" class="dropdown-item" onclick="toggleMute(); return false;">Mute notifications</a>
                                <a href="#" class="dropdown-item" onclick="clearChatMessages(); return false;">Clear messages</a>
                                <a href="#" class="dropdown-item" onclick="deleteEntireChat(); return false;">Delete chat</a>
                                <a href="#" class="dropdown-item" onclick="reportUser(); return false;">Report</a>
                                <a href="#" class="dropdown-item" id="block-user-btn" onclick="blockUser(); return false;">Block</a>
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                    .action-icon { margin-left: 20px; font-size: 18px; color: #54656f; cursor: pointer; }
                    .dropdown-item { display: block; padding: 10px 20px; color: #4a4a4a; text-decoration: none; font-size: 14.5px; }
                    .dropdown-item:hover { background: #f5f6f6; }
                </style>
                <div class="chat-messages" id="chat-messages">
                    <!-- Messages will be loaded here -->
                </div>
                
                <!-- File Preview -->
                <div id="file-preview-container" class="preview-container">
                    <span class="preview-close" onclick="cancelFile()">×</span>
                    <img id="file-preview" src="" alt="Preview">
                    <p id="file-name" style="font-size:12px;color:#555;margin-top:5px;"></p>
                </div>

                <div class="chat-input-area" style="position: relative;">
                    <!-- Attachment Menu -->
                    <div class="attachment-menu" id="attachment-menu">
                        <div class="attachment-item" onclick="triggerFileInput('document')">
                            <i class="fas fa-file icon-document"></i>
                            <span>Document</span>
                        </div>
                        <div class="attachment-item" onclick="triggerFileInput('photos')">
                            <i class="fas fa-image icon-photos"></i>
                            <span>Photos & videos</span>
                        </div>
                        <div class="attachment-item" onclick="triggerFileInput('camera')">
                            <i class="fas fa-camera icon-camera"></i>
                            <span>Camera</span>
                        </div>
                        <div class="attachment-item" onclick="triggerFileInput('audio')">
                            <i class="fas fa-microphone icon-audio"></i>
                            <span>Audio</span>
                        </div>
                        <div class="attachment-item">
                            <i class="fas fa-user icon-contact"></i>
                            <span>Contact</span>
                        </div>
                        <div class="attachment-item">
                            <i class="fas fa-poll icon-poll"></i>
                            <span>Poll</span>
                        </div>
                        <div class="attachment-item">
                            <i class="fas fa-calendar-alt icon-sticker"></i>
                            <span>Event</span>
                        </div>
                        <div class="attachment-item">
                            <i class="fas fa-sticky-note icon-sticker"></i>
                            <span>New sticker</span>
                        </div>
                    </div>

                    <button id="emoji-btn" class="icon-btn"><i class="far fa-smile"></i></button>
                    
                    <button id="attachment-btn" class="icon-btn" onclick="toggleAttachmentMenu()">
                        <i class="fas fa-plus"></i>
                    </button>
                    <!-- Hidden original file input, keeping it functional for now but hidden -->
                    <input type="file" id="file-input" style="display: none;" accept="image/*" onchange="previewFile()">
                    
                    <input type="text" id="message-input" placeholder="Type a message">
                    <button id="send-btn" class="icon-btn" style="display:none;"><i class="fas fa-paper-plane"></i></button>
                    <button id="record-btn" class="icon-btn"><i class="fas fa-microphone"></i></button>
                </div>
                <div id="recording-overlay" style="display: none; position: absolute; bottom: 0; left: 0; width: 100%; height: 60px; background: #f0f2f5; align-items: center; padding: 0 15px; z-index: 10;">
                    <i class="fas fa-trash-alt" id="cancel-recording" style="color: #ef5350; cursor: pointer; font-size: 18px;"></i>
                    <div style="flex: 1; text-align: center; color: #54656f; font-weight: 500;" id="recording-timer">00:00</div>
                    <i class="fas fa-check-circle" id="send-recording" style="color: #00a884; cursor: pointer; font-size: 24px;"></i>
                </div>
            </div>

            <!-- Charts View -->
            <div class="charts-view" id="charts-view" style="display: none;">
                <div class="charts-header">
                    <h2>Analytics Dashboard</h2>
                    <button id="close-charts"><i class="fas fa-times"></i></button>
                </div>
                <div class="charts-container">
                    <div class="chart-box">
                        <canvas id="messagesPerUserChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <canvas id="dailyMessagesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Call Modal -->
    <div id="call-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center;">
        <div class="call-content" style="background: #202c33; width: 800px; height: 600px; border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; position: relative;">
            <div class="call-header" style="position: absolute; top: 0; width: 100%; padding: 20px; text-align: center; color: white; z-index: 10; background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);">
                <h3 id="call-name" style="margin: 0;">User Name</h3>
                <p id="call-status" style="margin: 5px 0 0; opacity: 0.8;">Calling...</p>
            </div>
            
            <div class="video-container" style="flex: 1; display: flex; justify-content: center; align-items: center; position: relative; background: #000;">
                <!-- Remote video (Peer) -->
                <video id="remote-video" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                
                <!-- Local video (Self) -->
                <video id="local-video" autoplay playsinline muted style="position: absolute; bottom: 100px; right: 20px; width: 150px; height: 100px; border-radius: 8px; object-fit: cover; border: 2px solid white; z-index: 20; background: #333;"></video>
            </div>

            <div class="call-controls" style="padding: 20px; display: flex; justify-content: center; gap: 20px; background: rgba(0,0,0,0.5); position: absolute; bottom: 0; width: 100%; z-index: 10;">
                <button class="call-btn" id="btn-toggle-mic" style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #3b4a54; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="call-btn" id="btn-toggle-cam" style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #3b4a54; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-video"></i>
                </button>
                <button class="call-btn" id="btn-end-call" style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #ef5350; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transform: rotate(135deg);">
                    <i class="fas fa-phone"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div id="new-chat-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; bg: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; width: 400px; max-width: 90%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden; height: 80vh;">
            <div class="modal-header" style="background: #008069; color: white; padding: 15px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-arrow-left close-new-chat" style="cursor: pointer;"></i>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 500;">New Info</h2>
                </div>
                <i class="fas fa-search" style="cursor: pointer;"></i>
            </div>
            <div class="modal-search" style="padding: 10px; border-bottom: 1px solid #e9edef;">
                <input type="text" placeholder="Search name or number" style="width: 100%; padding: 8px 12px; border: none; background: #f0f2f5; border-radius: 8px; outline: none;">
            </div>
            <div class="modal-body" style="padding: 0; overflow-y: auto; flex: 1;">
                <div class="list-item" style="padding: 15px; display: flex; align-items: center; gap: 15px; cursor: pointer; hover: bg: #f5f6f6;">
                    <div style="width: 45px; height: 45px; background: #00a884; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>New group</span>
                </div>
                <div class="list-item" style="padding: 15px; display: flex; align-items: center; gap: 15px; cursor: pointer; hover: bg: #f5f6f6;">
                    <div style="width: 45px; height: 45px; background: #00a884; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span>New contact</span>
                </div>
                <div style="padding: 15px 20px; color: #008069; font-size: 14px; font-weight: 500;">CONTACTS ON WHATSAPP</div>
                <!-- Contacts will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profile-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; bg: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; width: 400px; max-width: 90%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden;">
            <div class="modal-header" style="background: #008069; color: white; padding: 20px; display: flex; align-items: flex-end; height: 120px;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 500;">Profile</h2>
                <span class="close-modal" style="position: absolute; top: 15px; right: 15px; color: white; cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px; overflow-y: auto; max-height: 70vh;">
                <div class="profile-section" style="text-align: center; margin-bottom: 25px;">
                    <div style="position: relative; display: inline-block;">
                        <img id="profile-img-preview" src="<?php echo $current_user['profile_image'] ? 'uploads/profile/'.$current_user['profile_image'] : 'https://ui-avatars.com/api/?name='.urlencode($current_user['name']).'&background=random'; ?>" alt="Profile" class="avatar-large" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; cursor: pointer;">
                        <label for="profile-upload" style="position: absolute; bottom: 15px; right: 0; background: #008069; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile-upload" accept="image/*" style="display: none;">
                    </div>
                    <div class="change-photo" style="color: #008069; font-size: 14px; cursor: pointer; text-transform: uppercase; font-weight: 500;">Change Profile Photo</div>

                    <!-- Profile Position Controls -->
                    <div style="margin-top: 20px;">
                        <label style="display: block; color: #008069; font-size: 14px; margin-bottom: 8px;">Image Position</label>
                        <div class="pos-controls">
                            <button class="pos-btn" onclick="previewProfilePos(50, 0)" title="Top"><i class="fas fa-arrow-up"></i></button>
                            <button class="pos-btn" onclick="previewProfilePos(50, 100)" title="Bottom"><i class="fas fa-arrow-down"></i></button>
                            <button class="pos-btn" onclick="previewProfilePos(0, 50)" title="Left"><i class="fas fa-arrow-left"></i></button>
                            <button class="pos-btn" onclick="previewProfilePos(100, 50)" title="Right"><i class="fas fa-arrow-right"></i></button>
                            <button class="pos-btn" onclick="previewProfilePos(50, 50)" title="Auto / Center" style="width: auto; padding: 0 10px; font-size: 13px;">Auto</button>
                        </div>
                    </div>
                </div>

                <div class="profile-field" style="margin-bottom: 25px;">
                    <label style="display: block; color: #008069; font-size: 14px; margin-bottom: 8px;">Your Name</label>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9edef; padding-bottom: 5px;">
                        <input type="text" id="edit-name" value="<?php echo htmlspecialchars($current_user['name']); ?>" readonly style="border: none; outline: none; flex: 1; font-size: 16px; color: #3b4a54; background: transparent;">
                        <i class="fas fa-pen edit-icon" data-target="edit-name" style="color: #8696a0; cursor: pointer;"></i>
                    </div>
                    <p style="color: #8696a0; font-size: 13px; margin-top: 5px;">This is not your username or pin. This name will be visible to your WhatsApp contacts.</p>
                </div>

                <div class="profile-field" style="margin-bottom: 25px;">
                    <label style="display: block; color: #008069; font-size: 14px; margin-bottom: 8px;">About</label>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e9edef; padding-bottom: 5px;">
                        <input type="text" id="edit-about" value="<?php echo isset($current_user['about']) ? htmlspecialchars($current_user['about']) : 'Hey there! I am using WhatsApp.'; ?>" readonly style="border: none; outline: none; flex: 1; font-size: 16px; color: #3b4a54; background: transparent;">
                        <i class="fas fa-pen edit-icon" data-target="edit-about" style="color: #8696a0; cursor: pointer;"></i>
                    </div>
                </div>

                <!-- Save Profile Button -->
                <button id="save-profile-btn" style="width: 100%; padding: 12px; background: #008069; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 15px; margin-top: 10px;">Save Profile</button>
            </div>
        </div>
    </div>

    <!-- Contact Info Modal -->
    <div id="contact-info-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content" style="background: white; width: 400px; max-width: 90%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden;">
             <!-- Header -->
            <div class="modal-header" style="background: #008069; color: white; padding: 20px; display: flex; align-items: flex-end; height: 120px; position: relative;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 500;">Contact Info</h2>
                <span class="close-modal" onclick="document.getElementById('contact-info-modal').style.display='none'" style="position: absolute; top: 15px; right: 15px; color: white; cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            
            <div class="modal-body" style="padding: 20px; overflow-y: auto; max-height: 70vh;">
                <!-- Image -->
                <div class="profile-section" style="text-align: center; margin-bottom: 25px;">
                    <img id="contact-img" src="" alt="Contact Profile" class="avatar-large" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                </div>

                <!-- Name -->
                <div class="profile-field" style="margin-bottom: 25px;">
                    <label style="display: block; color: #008069; font-size: 14px; margin-bottom: 8px;">Name</label>
                    <div style="border-bottom: 2px solid #e9edef; padding-bottom: 5px;">
                        <span id="contact-name" style="font-size: 17px; color: #3b4a54;"></span>
                    </div>
                </div>

                <!-- About / Status -->
                <div class="profile-field" style="margin-bottom: 25px;">
                    <label style="display: block; color: #008069; font-size: 14px; margin-bottom: 8px;">About</label>
                    <div style="border-bottom: 2px solid #e9edef; padding-bottom: 5px;">
                        <span id="contact-about" style="font-size: 15px; color: #3b4a54;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Context Menu -->
    <div id="message-context-menu" style="display: none; position: absolute; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.2); border-radius: 3px; z-index: 200; width: 180px;">
        <ul style="list-style: none; padding: 5px 0; margin: 0;">
            <li class="ctx-item" onclick="replyMessage()">Reply</li>
            <li class="ctx-item" onclick="starMessage()">Star message</li>
            <li class="ctx-item" onclick="copyMessage()">Copy message</li>
            <li class="ctx-item" onclick="forwardMessage()">Forward message</li>
            <li class="ctx-item" onclick="deleteMessage()">Delete message</li>
        </ul>
        <style>
            .ctx-item { padding: 10px 20px; font-size: 14.5px; color: #4a4a4a; cursor: pointer; }
            .ctx-item:hover { background: #f5f6f6; }
        </style>
    </div>

    <script type="module">
        import { EmojiButton } from 'https://cdn.jsdelivr.net/npm/@joeattardi/emoji-button@4.6.0/dist/index.js';

        document.addEventListener('DOMContentLoaded', () => {
            const picker = new EmojiButton({
                position: 'top-start',
                zIndex: 1000
            });
            const trigger = document.querySelector('#emoji-btn');
            
            if(trigger) {
                picker.on('emoji', selection => {
                    const input = document.querySelector('#message-input');
                    if(input) {
                        input.value += selection.emoji;
                        // Trigger input event to show send button if needed (though existing chat.js works off keypress/input)
                        input.dispatchEvent(new Event('input')); 
                        input.focus();
                    }
                });

                trigger.addEventListener('click', () => picker.togglePicker(trigger));
            }
            
            // Profile Modal Logic
            const profileAvatar = document.querySelector('.sidebar-header .profile-info');
            const profileModal = document.getElementById('profile-modal');
            const closeProfileModal = document.querySelector('.close-modal');

            if (profileAvatar) {
                profileAvatar.addEventListener('click', () => {
                    profileModal.style.display = 'flex';
                });
            }

            if (closeProfileModal) {
                closeProfileModal.addEventListener('click', () => {
                    profileModal.style.display = 'none';
                });
            }

            window.onclick = function(event) {
                if (event.target == profileModal) {
                    profileModal.style.display = "none";
                }
                const contactModal = document.getElementById('contact-info-modal');
                if (contactModal && event.target == contactModal) {
                    contactModal.style.display = "none";
                }
            }

            // Call Features
            const btnVideoCall = document.getElementById('btn-video-call');
            const btnVoiceCall = document.getElementById('btn-voice-call');
            const callModal = document.getElementById('call-modal');
            const btnEndCall = document.getElementById('btn-end-call');
            const localVideo = document.getElementById('local-video');
            const remoteVideo = document.getElementById('remote-video');
            const btnToggleMic = document.getElementById('btn-toggle-mic');
            const btnToggleCam = document.getElementById('btn-toggle-cam');
            
            let localStream;
            let audioEnabled = true;
            let videoEnabled = true;

            async function startCall(video = true) {
                console.log("Starting call...", video);
                if (!callModal) {
                    console.error("Call modal not found!");
                    return;
                }
                callModal.style.display = 'flex';
                
                try {
                    // Check if browser supports mediaDevices
                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        throw new Error("Browser does not support media devices.");
                    }

                    localStream = await navigator.mediaDevices.getUserMedia({ 
                        video: video, 
                        audio: true 
                    });
                    
                    if (localVideo) {
                        localVideo.srcObject = localStream;
                        // Mute local video to prevent feedback loop
                        localVideo.muted = true;
                    }
                    
                    // Simulate peer connection for UI demo
                    const statusElem = document.getElementById('call-status');
                    if (statusElem) statusElem.textContent = "Calling...";
                    
                    // Update button states
                    videoEnabled = video;
                    updateMediaControls();

                } catch (err) {
                    console.error("Error accessing media devices:", err);
                    alert("Could not access camera/microphone: " + err.message);
                    callModal.style.display = 'none';
                }
            }

            function endCall() {
                console.log("Ending call...");
                if (localStream) {
                    localStream.getTracks().forEach(track => track.stop());
                }
                if (localVideo) localVideo.srcObject = null;
                if (callModal) callModal.style.display = 'none';
            }

            function toggleMic() {
                if (localStream) {
                    audioEnabled = !audioEnabled;
                    localStream.getAudioTracks().forEach(track => track.enabled = audioEnabled);
                    updateMediaControls();
                }
            }

            function toggleCam() {
                if (localStream) {
                    videoEnabled = !videoEnabled;
                    localStream.getVideoTracks().forEach(track => track.enabled = videoEnabled);
                    updateMediaControls();
                }
            }

            function updateMediaControls() {
                if (btnToggleMic) {
                    btnToggleMic.style.background = audioEnabled ? '#3b4a54' : '#ef5350';
                    btnToggleMic.innerHTML = audioEnabled ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash"></i>';
                }
                if (btnToggleCam) {
                    btnToggleCam.style.background = videoEnabled ? '#3b4a54' : '#ef5350';
                    btnToggleCam.innerHTML = videoEnabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash"></i>';
                }
            }

            if (btnVideoCall) {
                btnVideoCall.addEventListener('click', (e) => {
                    e.preventDefault();
                    startCall(true);
                });
            } else {
                console.error("Video call button not found!");
            }
            
            if (btnVoiceCall) {
                btnVoiceCall.addEventListener('click', (e) => {
                    e.preventDefault();
                    startCall(false);
                });
            } else {
                console.error("Voice call button not found!");
            }

            if (btnEndCall) {
                btnEndCall.addEventListener('click', (e) => {
                     e.preventDefault();
                     endCall();
                });
            }

            if (btnToggleMic) {
                btnToggleMic.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleMic();
                });
            }

            if (btnToggleCam) {
                btnToggleCam.addEventListener('click', (e) => {
                    e.preventDefault();
                    toggleCam();
                });
            }

            // Chat Menu Logic - Consolidated
            function showContactInfo() {
                if(!activeChatId) return;
                const formData = new FormData();
                formData.append('action', 'get_contact_info');
                formData.append('user_id', activeChatId);
                
                fetch('ajax/chat_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        const user = data.data;
                        document.getElementById('contact-name').innerText = user.name;
                        // document.getElementById('contact-email').innerText = user.email; // Optional, might want to hide email
                        
                        // Show "About" if available, else show status
                        const aboutText = user.about || user.status || 'No status available';
                        document.getElementById('contact-about').innerText = aboutText;

                        const img = document.getElementById('contact-img');
                        img.src = user.profile_image ? `uploads/profile/${user.profile_image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random`;
                        
                        // Apply position if available
                        const posX = user.profile_pos_x !== undefined ? user.profile_pos_x : 50;
                        const posY = user.profile_pos_y !== undefined ? user.profile_pos_y : 50;
                        img.style.objectPosition = `${posX}% ${posY}%`;

                        document.getElementById('contact-info-modal').style.display = 'flex';
                    }
                });
            }

            // Expose these functions to window for onclick handlers
            window.showContactInfo = showContactInfo;
            
            window.toggleSelectMessages = function() {
                alert('Select Messages feature logic would go here.');
            };

            window.toggleMute = function() {
                alert('Notifications muted for this chat.');
            };
            
            window.closeChat = function() {
                 if(typeof closeChat === 'function') closeChat(); // Call chat.js function
                 else { // Fallback if chat.js function isn't globally available or scope issue
                    document.getElementById('chat-view').style.display = 'none';
                    document.getElementById('default-view').style.display = 'flex';
                    const backBtn = document.getElementById('back-to-list');
                    if(backBtn) backBtn.style.display = 'none';
                 }
            };

            window.clearChatMessages = function() {
                if(!confirm('Clear all messages in this chat?')) return;
                if(!activeChatId) return;

                const formData = new FormData();
                formData.append('action', 'clear_chat');
                formData.append('user_id', activeChatId);

                fetch('ajax/chat_actions.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) fetchMessages();
                    else alert('Error clearing chat');
                });
            };

            window.deleteEntireChat = function() {
                if(!confirm('Delete this chat?')) return;
                window.clearChatMessages(); // Reusing clear for now
            };

            window.reportUser = function() {
                 if(!confirm('Report this user?')) return;
                 const formData = new FormData();
                 formData.append('action', 'report_user');
                 formData.append('user_id', activeChatId);
                 
                 fetch('ajax/chat_actions.php', { method: 'POST', body: formData })
                 .then(res => res.json())
                 .then(data => alert('User reported.'));
            };

            window.blockUser = function() {
                 if(!confirm('Block this user?')) return;
                 const formData = new FormData();
                 formData.append('action', 'block_user');
                 formData.append('user_id', activeChatId);
                 
                 fetch('ajax/chat_actions.php', { method: 'POST', body: formData })
                 .then(res => res.json())
                 .then(data => {
                     if(data.success) {
                         alert('User blocked');
                     }
                 });
            };

            const chatMenuBtn = document.getElementById('chat-menu-btn');
            const chatDropdown = document.getElementById('chat-dropdown-options');

            if (chatMenuBtn) {
                chatMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    chatDropdown.style.display = chatDropdown.style.display === 'block' ? 'none' : 'block';
                });
            }

            document.addEventListener('click', (e) => {
                if (chatDropdown && !chatDropdown.contains(e.target) && e.target !== chatMenuBtn) {
                    chatDropdown.style.display = 'none';
                }
                const ctxMenu = document.getElementById('message-context-menu');
                if (ctxMenu) ctxMenu.style.display = 'none';
            });
            
            // New Chat Modal
            const newChatBtn = document.getElementById('btn-add-user');
            const newChatModal = document.getElementById('new-chat-modal');
            const closeNewChat = document.querySelector('.close-new-chat');

            if (newChatBtn) {
                newChatBtn.addEventListener('click', () => {
                    newChatModal.style.display = 'flex';
                });
            }

            if (closeNewChat) {
                closeNewChat.addEventListener('click', () => {
                   newChatModal.style.display = 'none';
                });
            }

            // Message Context Menu Logic
            const messagesContainer = document.getElementById('chat-messages');
            if (messagesContainer) {
                messagesContainer.addEventListener('contextmenu', (e) => {
                    const messageEl = e.target.closest('.message');
                    if (messageEl) {
                        e.preventDefault();
                        const ctxMenu = document.getElementById('message-context-menu');
                        ctxMenu.style.display = 'block';
                        ctxMenu.style.left = e.pageX + 'px';
                        ctxMenu.style.top = e.pageY + 'px';
                    }
                });
            }
        });
    </script>
    <script>
        const currentUserId = <?php echo $user_id; ?>;
    </script>
    <script src="assets/js/chat.js"></script>
    <script src="assets/js/charts.js"></script>    <script>
        // Profile Editing Logic
        document.addEventListener('DOMContentLoaded', () => {
            const editIcons = document.querySelectorAll('.edit-icon');
            const profileUpload = document.getElementById('profile-upload');
            const saveProfileBtn = document.getElementById('save-profile-btn');
            
            // Store pending position changes
            let pendingPos = { x: <?php echo $profile_x; ?>, y: <?php echo $profile_y; ?> };

            // Handle Text Editing (Name/About) - Enable editing on click
            editIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const targetId = this.dataset.target;
                    const input = document.getElementById(targetId);
                    
                    input.removeAttribute('readonly');
                    input.focus();
                    input.style.borderBottom = '1px solid #008069';
                    this.style.display = 'none'; 
                });
            });

            // Preview Profile Position
            window.previewProfilePos = function(x, y) {
                 pendingPos = { x, y };
                 const preview = document.getElementById('profile-img-preview');
                 if (preview) {
                    preview.style.objectPosition = `${x}% ${y}%`;
                 }
            };

            // Global Save Button
            if (saveProfileBtn) {
                saveProfileBtn.addEventListener('click', function() {
                    const nameInput = document.getElementById('edit-name');
                    const aboutInput = document.getElementById('edit-about');
                    
                    const name = nameInput.value.trim();
                    const about = aboutInput.value.trim();
                    
                    // Create FormData to send all updates
                    const formData = new FormData();
                    formData.append('name', name);
                    formData.append('about', about);
                    formData.append('pos_x', pendingPos.x);
                    formData.append('pos_y', pendingPos.y);

                    // We can reuse update_profile.php logic. 
                    // However, update_profile.php currently handles one type of update at a time based on isset keys.
                    // We might need to send multiple requests or update the PHP.
                    // Let's send multiple requests for now to minimize backend changes, or just sequence them.
                    // Actually, let's update update_profile.php to handle a combined request or just send 3 requests?
                    // Better: Send 3 separate non-blocking requests or chained.
                    // Or heck, let's just make update_profile.php smarter.
                    
                    // Sending name update
                    const p1 = fetch('api/update_profile.php', {
                        method: 'POST',
                        body: new URLSearchParams({ 'name': name })
                    });
                    
                    // Sending about update
                    const p2 = fetch('api/update_profile.php', {
                        method: 'POST',
                        body: new URLSearchParams({ 'about': about })
                    });

                     // Sending position update
                    const p3 = fetch('api/update_profile.php', {
                        method: 'POST',
                        body: new URLSearchParams({ 'pos_x': pendingPos.x, 'pos_y': pendingPos.y })
                    });

                    Promise.all([p1, p2, p3])
                    .then(responses => Promise.all(responses.map(r => r.json())))
                    .then(results => {
                         // Check results
                         const failed = results.filter(r => !r.success);
                         if(failed.length === 0) {
                             alert('Profile saved successfully!');
                             
                             // Reset UI state
                             nameInput.setAttribute('readonly', true);
                             nameInput.style.borderBottom = 'none';
                             aboutInput.setAttribute('readonly', true);
                             aboutInput.style.borderBottom = 'none';
                             
                             // Show edit icons again
                             document.querySelectorAll('.edit-icon').forEach(i => i.style.display = 'inline-block');
                             
                             // Update Sidebar
                             document.querySelector('.sidebar-header .profile-info span').textContent = name;
                             const sidebarAvatar = document.querySelector('.sidebar-header .profile-info img.avatar');
                             if(sidebarAvatar) {
                                 sidebarAvatar.style.objectPosition = `${pendingPos.x}% ${pendingPos.y}%`;
                             }
                             
                             // Close modal optional? No, let user close it.
                         } else {
                             alert('Some updates failed: ' + failed.map(f => f.message).join(', '));
                         }
                    })
                    .catch(err => console.error(err));
                });
            }

            // Handle Profile Image Upload
            if (profileUpload) {
                profileUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const formData = new FormData();
                        formData.append('profile_image', this.files[0]);

                        fetch('api/update_profile.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('profile-img-preview').src = data.image_url;
                                document.querySelector('.sidebar-header .profile-info img').src = data.image_url;
                            } else {
                                alert('Image upload failed: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            }
        });
    </script></body>
</html>