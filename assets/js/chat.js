let activeChatId = null;
let messageInterval = null;
// Recording variables
let mediaRecorder;
let audioChunks = [];
let recordingInterval;
let recordingTime = 0;

document.addEventListener('DOMContentLoaded', () => {
    // Voice Recording Logic
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    const recordBtn = document.getElementById('record-btn');
    const cancelRecordingBtn = document.getElementById('cancel-recording');
    const sendRecordingBtn = document.getElementById('send-recording');

    if (messageInput) {
        messageInput.addEventListener('input', function() {
            if (this.value.trim().length > 0) {
                if(sendBtn) sendBtn.style.display = 'inline-flex'; 
                if(recordBtn) recordBtn.style.display = 'none';
            } else {
                if(sendBtn) sendBtn.style.display = 'none';
                if(recordBtn) recordBtn.style.display = 'inline-flex';
            }
        });
    }

    if (recordBtn) {
        recordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            startRecording();
        });
    }

    if (cancelRecordingBtn) {
        cancelRecordingBtn.addEventListener('click', (e) => {
            e.preventDefault();
            stopRecording(false);
        });
    }

    if (sendRecordingBtn) {
        sendRecordingBtn.addEventListener('click', (e) => {
            e.preventDefault();
            stopRecording(true);
        });
    }

    fetchUsers();
    setInterval(fetchUsers, 5000); // Refresh users list every 5 seconds

    // Re-enabled event listener, removed preventDefault since it is not a submit button but good practice
    document.getElementById('send-btn').addEventListener('click', (e) => {
        e.preventDefault();
        sendMessage();
    });
    
    document.getElementById('message-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent default Enter behavior
            sendMessage();
        }
    });

    // Add User Button Logic
    document.getElementById('btn-add-user').addEventListener('click', () => {
        // For now, redirect to register page to create a new user
        // In a real app, this might open a modal to search for users or create a group
        if(confirm('Do you want to register a new user?')) {
            window.open('register.php', '_blank');
        }
    });

    document.getElementById('btn-charts').addEventListener('click', () => {
        document.getElementById('charts-view').style.display = 'flex';
        loadCharts();
    });

    document.getElementById('close-charts').addEventListener('click', () => {
        document.getElementById('charts-view').style.display = 'none';
    });

    // Search Users Logic
    const searchInput = document.getElementById('search-users');
    if (searchInput) {
        searchInput.addEventListener('input', fetchUsers);
    }
});

function fetchUsers() {
    fetch('ajax/fetch_users.php')
        .then(response => response.json())
        .then(users => {
            const chatList = document.getElementById('chat-list');
            const searchInput = document.getElementById('search-users');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

            chatList.innerHTML = '';
            
            users.forEach(user => {
                // Filter by name or email
                if (searchTerm && !user.name.toLowerCase().includes(searchTerm) && !user.email.toLowerCase().includes(searchTerm)) {
                    return;
                }

                const div = document.createElement('div');
                div.className = 'chat-item';
                div.onclick = () => openChat(user);
                
                const img = user.profile_image ? `uploads/profile/${user.profile_image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random`;
                const statusColor = user.status === 'online' ? 'green' : 'gray';

                div.innerHTML = `
                    <img src="${img}" alt="User" class="avatar">
                    <div class="chat-item-info">
                        <div class="chat-item-name">${user.name} <span style="color:${statusColor}; font-size:10px;">●</span></div>
                        <div class="chat-item-last-msg" style="font-size: 12px; color: #888;">${user.email}</div>
                        <div class="chat-item-last-msg">${user.status}</div>
                    </div>
                `;
                chatList.appendChild(div);
            });
        });
}

function openChat(user) {
    activeChatId = user.id;
    document.getElementById('default-view').style.display = 'none';
    const chatView = document.getElementById('chat-view');
    chatView.style.display = 'flex';

    // Mobile Responsive Logic
    document.querySelector('.app-container').classList.add('mobile-chat-active');

    document.getElementById('active-chat-name').innerText = user.name;
    document.getElementById('active-chat-status').innerText = user.status;      
    document.getElementById('active-chat-avatar').src = user.profile_image ? `uploads/profile/${user.profile_image}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random`;

    // Show back button on mobile (handled by CSS, but ensure style isn't overridden inline if it was)
    // The CSS will handle display:block for #back-to-list within media query if we set it up right, 
    // but originally I set it to display:none in HTML. Better to toggle class or let CSS handle "display: none -> display: block".
    // Since I added style="display:none" in HTML, I should remove it or override here.
    const backBtn = document.getElementById('back-to-list');
    if(backBtn && window.innerWidth <= 768) {
        backBtn.style.display = 'block';
    }

    if (messageInterval) clearInterval(messageInterval);
    fetchMessages();
    messageInterval = setInterval(fetchMessages, 2000);
}

function closeChat() {
    activeChatId = null;
    document.getElementById('chat-view').style.display = 'none';
    document.getElementById('default-view').style.display = 'flex'; // Or keep hidden on mobile?
    
    // Mobile Responsive Logic
    document.querySelector('.app-container').classList.remove('mobile-chat-active');
    
    const backBtn = document.getElementById('back-to-list');
    if(backBtn) backBtn.style.display = 'none';
    
    if (messageInterval) clearInterval(messageInterval);
}

function getTickIcon(status) {
    // Colors
    const colorGray = '#8696a0'; // WhatsApp gray
    const colorBlue = '#53bdeb'; // WhatsApp blue

    // Single Tick (Sent) - Fixed ViewBox and Path
    const singleTick = `
        <svg viewBox="0 0 16 16" width="16" height="16" fill="${colorGray}">
             <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
        </svg>`;

    // Double Tick (Delivered or Read) - Fixed ViewBox and Path
    const doubleTick = (color) => `
        <svg viewBox="0 0 16 16" width="16" height="16" fill="${color}">
            <path d="M8.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L2.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093L8.95 4.992a.252.252 0 0 1 .02-.022zm-.92 5.14.92.92a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 1 0-1.091-1.028L9.477 9.417l-.485-.486-.943 1.179z"/>
        </svg>`;

    if (status === 'read' || status === 'seen') return doubleTick(colorBlue);
    if (status === 'delivered') return doubleTick(colorGray);
    return singleTick;
}

function fetchMessages() {
    if (!activeChatId) return;

    const chatMessages = document.getElementById('chat-messages');
    // Check if user is near bottom before updating
    const isAtBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 100;

    fetch(`ajax/fetch_messages.php?receiver_id=${activeChatId}`)
        .then(response => response.json())
        .then(messages => {
            // Store current scroll position if not at bottom
            const currentScrollTop = chatMessages.scrollTop;
            
            chatMessages.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;
                
                let content = '';
                if (msg.file) {
                    // Check file type extension if possible, or just default to image logic for now. 
                    // To handle all file types properly, backend should return file_type or we deduce it.
                    // For now, assume images. For docs, we should show a link/icon.
                    const ext = msg.file.split('.').pop().toLowerCase();
                    if(['jpg','jpeg','png','gif','webp'].includes(ext)) {
                        content += `<img src="uploads/chat_files/${msg.file}" onclick="window.open(this.src)" class="image-msg">`;
                    } else if(['mp4','avi','mov','mkv','webm'].includes(ext)) {
                        content += `<video src="uploads/chat_files/${msg.file}" controls class="image-msg" style="max-width:300px; width:100%;"></video>`;
                    } else if(['mp3','wav','ogg','weba'].includes(ext)) {
                        content += `<audio src="uploads/chat_files/${msg.file}" controls style="width:100%"></audio>`;
                    } else {
                        content += `<div style="background:#f0f2f5; padding:10px; border-radius:5px; margin-bottom:5px;">
                                        <a href="uploads/chat_files/${msg.file}" target="_blank" style="text-decoration:none; color:#333; display:flex; align-items:center;">
                                            <i class="fas fa-file-alt" style="margin-right:10px; font-size:20px;"></i> ${msg.file}
                                        </a>
                                    </div>`;
                    }
                }
                if (msg.message) {
                    content += `<p>${msg.message}</p>`;
                }
                
                let footer = `<div style="display:flex; justify-content:flex-end; align-items:center; margin-top:3px; gap:5px;">
                    <span class="message-time" style="font-size:10px; color:#999;">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    <i class="fas fa-trash" style="font-size:10px; color:#ccc; cursor:pointer;" onclick="deleteMessage(${msg.id}, this)" title="Delete"></i>`;
                
                if (msg.sender_id == currentUserId) {
                    footer += getTickIcon(msg.status || 'sent');
                }
                footer += `</div>`;

                div.innerHTML = content + footer;
                chatMessages.appendChild(div);
            });
            
            // Only scroll to bottom if user was already at bottom or it's the first load (empty content)
            if (isAtBottom || chatMessages.scrollTop === 0) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            } else {
                // Restore previous position
                chatMessages.scrollTop = currentScrollTop;
            }
        });
}

let isSending = false;

function sendMessage() {
    if (isSending) return;
    
    const input = document.getElementById('message-input');
    const fileInput = document.getElementById('file-input');
    const message = input.value.trim();
    const file = fileInput.files.length > 0 ? fileInput.files[0] : null;

    if (!activeChatId) {
        alert("Please select a user to chat with.");
        return;
    }

    if (!message && !file) {
        // Empty message and no file
        return;
    }

    isSending = true;
    const sendBtn = document.getElementById('send-btn');
    if(sendBtn) sendBtn.disabled = true;

    const formData = new FormData();
    formData.append('receiver_id', activeChatId);
    formData.append('message', message);
    if(file) {
        formData.append('file', file);
    }
    
    // Optimistic UI update (optional, but good for UX) - forcing input clear immediately to prevent double sends
    input.value = '';
    
    // Reset button visibility (Show Mic, Hide Send)
    const recordBtn = document.getElementById('record-btn');
    if(sendBtn) sendBtn.style.display = 'none';
    if(recordBtn) recordBtn.style.display = 'inline-flex';
    
    // Clear preview immediately to make UI responsive
    document.getElementById('file-preview-container').style.display = 'none';
    if(fileInput) fileInput.value = ''; // Clear file input immediately

    fetch('ajax/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        return response.text();
    })
    .then(text => {
        isSending = false;
        if(sendBtn) sendBtn.disabled = false;
        
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // input.value = ''; // Already cleared above
                fileInput.value = ''; // Reset file input
                document.getElementById('file-preview-container').style.display = 'none'; // Hide preview
                fetchMessages();
            } else {
                console.error('Send Error:', data.error);
                alert('Error sending message: ' + data.error);
            }
        } catch (e) {
            console.error('SERVER RESPONSE ERROR:', text);
            alert('Server Error: ' + text.substring(0, 100)); // Show part of error
        }
    })
    .catch(error => {
        isSending = false;
        if(sendBtn) sendBtn.disabled = false;
        console.error('Fetch Error:', error)
    });
}


function deleteMessage(id, element) {
    if (!confirm('Delete this message?')) return;

    const formData = new FormData();
    formData.append('message_id', id);

    fetch('ajax/delete_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const msgEl = element.closest('.message');
            msgEl.style.transition = 'opacity 0.3s';
            msgEl.style.opacity = '0';
            setTimeout(() => msgEl.remove(), 300);
        } else {
            console.error('Delete error:', data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

function previewFile() {
    const fileInput = document.getElementById('file-input');
    const previewContainer = document.getElementById('file-preview-container');
    const previewImage = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');

    if (fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        fileName.textContent = file.name;
        previewContainer.style.display = 'block';

        // Only preview images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.style.display = 'block';
                previewImage.src = e.target.result;
            }
            reader.readAsDataURL(file);
        } else {
            // For non-images, show a generic icon or just hide the image element
            previewImage.style.display = 'none';
            // potentially add a generic file icon here if desired
        }
    } else {
        cancelFile();
    }
}

function cancelFile() {
    const fileInput = document.getElementById('file-input');
    if (fileInput) fileInput.value = '';
    
    document.getElementById('file-preview-container').style.display = 'none';
    document.getElementById('file-preview').src = '';
    document.getElementById('file-name').textContent = '';
    
    // Close attachment menu if open
    const menu = document.getElementById('attachment-menu');
    if (menu) menu.style.display = 'none';
}

function toggleAttachmentMenu() {
    const menu = document.getElementById('attachment-menu');
    if (menu.style.display === 'flex') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'flex';
    }
}

function triggerFileInput(type) {
    const fileInput = document.getElementById('file-input');
    const menu = document.getElementById('attachment-menu');
    
    // Set accepted file types based on selection
    switch(type) {
        case 'document':
            fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf';
            break;
        case 'photos': // Photos & Videos
            fileInput.accept = 'image/*,video/*';
            break;
        case 'camera':
            fileInput.accept = 'image/*';
            fileInput.capture = 'environment';
            break; 
        case 'audio':
            fileInput.accept = 'audio/*';
            break;
        default:
            fileInput.accept = '*/*';
    }

    // Hide menu and trigger click
    menu.style.display = 'none';
    fileInput.click();
}

// Close attachment menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('attachment-menu');
    const btn = document.getElementById('attachment-btn');
    
    if (menu && menu.style.display === 'flex' && !menu.contains(event.target) && !btn.contains(event.target)) {
        menu.style.display = 'none';
    }
});

// Voice Recording Logic Functions
let shouldSendRecording = false;

function startRecording() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Audio recording is not supported in this browser. Please use a modern browser or check permissions.');
        return;
    }

    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            shouldSendRecording = false;
            
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                // Stop all tracks to release microphone
                stream.getTracks().forEach(track => track.stop());
                
                if (shouldSendRecording && audioChunks.length > 0) {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    sendVoiceMessage(audioBlob);
                }
            };

            mediaRecorder.start();
            
            // UI Updates
            const overlay = document.getElementById('recording-overlay');
            if(overlay) overlay.style.display = 'flex';
            
            recordingTime = 0;
            updateRecordingTimer();
            recordingInterval = setInterval(() => {
                recordingTime++;
                updateRecordingTimer();
            }, 1000);
        })
        .catch(err => {
            console.error('Error accessing microphone:', err);
            alert('Could not access microphone: ' + err.message);
        });
}

function stopRecording(send) {
    shouldSendRecording = send;
    
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }
    
    // UI Cleanup
    const overlay = document.getElementById('recording-overlay');
    if(overlay) overlay.style.display = 'none';
    
    if(recordingInterval) clearInterval(recordingInterval);
    recordingTime = 0;
}

function updateRecordingTimer() {
    const timerEl = document.getElementById('recording-timer');
    if(!timerEl) return;
    
    const minutes = Math.floor(recordingTime / 60).toString().padStart(2, '0');
    const seconds = (recordingTime % 60).toString().padStart(2, '0');
    timerEl.textContent = `${minutes}:${seconds}`;
}

function sendVoiceMessage(blob) {
    if (!activeChatId) return;

    const formData = new FormData();
    formData.append('receiver_id', activeChatId);
    // Append blob as a file. Filename is important for backend extension checking.
    formData.append('file', blob, 'voice_message.weba'); 
    formData.append('message', ''); // Empty message text

    // Show sending indicator if needed, or rely on optimisitic UI
    
    fetch('ajax/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchMessages();
        } else {
            console.error('Error sending audio:', data.error);
            alert('Failed to send voice message: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error sending audio:', error);
        alert('Network error while sending voice message.');
    });
}

