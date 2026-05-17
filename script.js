/**
 * ThinkNoodles - Frontend JavaScript
 * Handles all interactive features
 */

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    updateDashboard();
    setupEventListeners();
});

// Setup modal event listeners
function setupEventListeners() {
    // Close modals on outside click
    document.getElementById('sendModal').addEventListener('click', function(e) {
        if (e.target === this) closeSendModal();
    });
    document.getElementById('bankTransferModal').addEventListener('click', function(e) {
        if (e.target === this) closeBankTransferModal();
    });
    document.getElementById('requestModal').addEventListener('click', function(e) {
        if (e.target === this) closeRequestModal();
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSendModal();
            closeBankTransferModal();
            closeRequestModal();
        }
    });
}

// Update dashboard with current user data
function updateDashboard() {
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_data'
    })
    .then(response => response.json())
    .then(data => {
        const user_id = data.current_user;
        const user = data.user_info;
        const users = data.users;
        const transactions = data.transactions;

        // Update header
        document.getElementById('userName').textContent = user.name;
        document.getElementById('bankBadge').textContent = user.bank;
        document.getElementById('phoneNumber').textContent = user.phone;

        // Update balance
        const balance = parseFloat(user.balance);
        document.getElementById('balanceAmount').textContent = balance.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Update progress bar (100% = ₱10,000)
        const maxBalance = 10000;
        const percent = (balance / maxBalance) * 100;
        document.getElementById('progressBar').style.width = Math.min(percent, 100) + '%';

        // Update user selector
        document.getElementById('userSelect').value = user_id;

        // Remove current user from recipient/request selects
        updateRecipientSelect(user_id, users);
        updateRequestFromSelect(user_id, users);

        // Update transactions
        updateTransactionList(transactions, users);
    });
}

// Update recipient dropdown (exclude current user)
function updateRecipientSelect(currentUserId, users) {
    const select = document.getElementById('recipient');
    const currentValue = select.value;
    select.innerHTML = '<option value="">Select recipient...</option>';
    
    for (const [id, user] of Object.entries(users)) {
        if (id !== currentUserId) {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = user.name;
            select.appendChild(option);
        }
    }
    
    if (currentValue && currentValue !== currentUserId) {
        select.value = currentValue;
    }
}

// Update request from dropdown (exclude current user)
function updateRequestFromSelect(currentUserId, users) {
    const select = document.getElementById('requestFrom');
    const currentValue = select.value;
    select.innerHTML = '<option value="">Select person...</option>';
    
    for (const [id, user] of Object.entries(users)) {
        if (id !== currentUserId) {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = user.name;
            select.appendChild(option);
        }
    }
    
    if (currentValue && currentValue !== currentUserId) {
        select.value = currentValue;
    }
}

// Update transaction list
function updateTransactionList(transactions, users) {
    const list = document.getElementById('transactionList');
    
    if (transactions.length === 0) {
        list.innerHTML = '<p class="empty-state">No transactions yet</p>';
        return;
    }

    list.innerHTML = transactions.map(txn => {
        let html = '';
        
        if (txn.type === 'transfer') {
            const fromUser = users[txn.from]?.name || 'Unknown';
            const toUser = users[txn.to]?.name || 'Unknown';
            const isOutgoing = txn.from === getCurrentUser();
            
            html = `
                <div class="transaction-item ${isOutgoing ? 'sent' : 'received'}">
                    <div class="transaction-info">
                        <div class="transaction-type">
                            ${isOutgoing ? '📤' : '📥'} 
                            ${isOutgoing ? 'Sent to ' + toUser : 'Received from ' + fromUser}
                        </div>
                        <div class="transaction-time">${txn.timestamp}</div>
                        ${txn.message ? '<div style="font-size: 13px; color: #6B7280; margin-top: 4px;">"' + txn.message + '"</div>' : ''}
                    </div>
                    <div class="transaction-amount ${isOutgoing ? 'sent' : 'received'}">
                        ${isOutgoing ? '-' : '+'}₱${parseFloat(txn.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                    </div>
                </div>
            `;
        } else if (txn.type === 'bank_transfer') {
            html = `
                <div class="transaction-item transfer">
                    <div class="transaction-info">
                        <div class="transaction-type">🏦 Transfer to ${txn.bank}</div>
                        <div class="transaction-time">${txn.timestamp}</div>
                        <div style="font-size: 12px; margin-top: 4px;">
                            <span style="display: inline-block; background: ${txn.status === 'pending' ? '#FCD34D' : '#D1FAE5'}; padding: 4px 8px; border-radius: 6px; color: ${txn.status === 'pending' ? '#92400E' : '#047857'}; font-weight: 600;">
                                ${txn.status === 'pending' ? '⏳ Pending' : '✅ Completed'}
                            </span>
                        </div>
                    </div>
                    <div class="transaction-amount transfer">-₱${parseFloat(txn.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</div>
                </div>
            `;
        } else if (txn.type === 'request') {
            const fromUser = users[txn.from]?.name || 'Unknown';
            html = `
                <div class="transaction-item transfer">
                    <div class="transaction-info">
                        <div class="transaction-type">📥 Payment request from ${fromUser}</div>
                        <div class="transaction-time">${txn.timestamp}</div>
                        <div style="font-size: 13px; color: #6B7280; margin-top: 4px;">${txn.reason}</div>
                    </div>
                    <div class="transaction-amount transfer">₱${parseFloat(txn.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</div>
                </div>
            `;
        }
        
        return html;
    }).join('');
}

// Get current user
function getCurrentUser() {
    return document.getElementById('userSelect').value;
}

// Switch user
function switchUser() {
    const userId = document.getElementById('userSelect').value;
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=set_user&user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateDashboard();
        }
    });
}

// ========== SEND MONEY ==========
function openSendModal() {
    document.getElementById('sendModal').classList.add('active');
    document.getElementById('recipient').value = '';
    document.getElementById('sendAmount').value = '';
    document.getElementById('sendMessage').value = '';
}

function closeSendModal() {
    document.getElementById('sendModal').classList.remove('active');
}

function setSendAmount(amount) {
    document.getElementById('sendAmount').value = amount;
}

function sendMoney(e) {
    e.preventDefault();
    
    const recipient = document.getElementById('recipient').value;
    const amount = document.getElementById('sendAmount').value;
    const message = document.getElementById('sendMessage').value;

    if (!recipient || !amount) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }

    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=send_money&to=' + recipient + '&amount=' + amount + '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ ' + data.message, 'success');
            closeSendModal();
            updateDashboard();
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    });
}

// ========== BANK TRANSFER ==========
function openBankTransferModal() {
    document.getElementById('bankTransferModal').classList.add('active');
    document.getElementById('bankAmount').value = '';
    document.querySelectorAll('input[name="bank"]').forEach(radio => radio.checked = false);
}

function closeBankTransferModal() {
    document.getElementById('bankTransferModal').classList.remove('active');
}

function setBankAmount(amount) {
    document.getElementById('bankAmount').value = amount;
}

function bankTransfer(e) {
    e.preventDefault();
    
    const bank = document.querySelector('input[name="bank"]:checked')?.value;
    const amount = document.getElementById('bankAmount').value;

    if (!bank || !amount) {
        showNotification('Please select a bank and enter amount', 'error');
        return;
    }

    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=bank_transfer&bank=' + bank + '&amount=' + amount
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ ' + data.message, 'success');
            closeBankTransferModal();
            updateDashboard();
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    });
}

// ========== REQUEST MONEY ==========
function openRequestModal() {
    document.getElementById('requestModal').classList.add('active');
    document.getElementById('requestFrom').value = '';
    document.getElementById('requestAmount').value = '';
    document.getElementById('requestReason').value = '';
}

function closeRequestModal() {
    document.getElementById('requestModal').classList.remove('active');
}

function requestMoney(e) {
    e.preventDefault();
    
    const from = document.getElementById('requestFrom').value;
    const amount = document.getElementById('requestAmount').value;
    const reason = document.getElementById('requestReason').value;

    if (!from || !amount || !reason) {
        showNotification('Please fill in all fields', 'error');
        return;
    }

    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=request_money&from=' + from + '&amount=' + amount + '&reason=' + encodeURIComponent(reason)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ ' + data.message, 'success');
            closeRequestModal();
            updateDashboard();
        } else {
            showNotification('❌ ' + data.message, 'error');
        }
    });
}

// ========== NOTIFICATIONS ==========
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = 'notification active ' + type;
    
    setTimeout(() => {
        notification.classList.remove('active');
    }, 4000);
}
