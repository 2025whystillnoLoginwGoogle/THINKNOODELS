<?php
/**
 * ThinkNoodles - Dynamic Payment Dashboard
 * PHP Backend with Session Management
 */

session_start();

// Initialize demo users and transactions on first load
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        'user_a' => [
            'name' => 'User A',
            'balance' => 5000,
            'bank' => 'GCash',
            'phone' => '0917-123-4567'
        ],
        'user_b' => [
            'name' => 'User B',
            'balance' => 3000,
            'bank' => 'GoTyme',
            'phone' => '0918-765-4321'
        ],
        'user_c' => [
            'name' => 'User C',
            'balance' => 7500,
            'bank' => 'GCash',
            'phone' => '0915-987-6543'
        ]
    ];

    $_SESSION['current_user'] = 'user_a';
    $_SESSION['transactions'] = [];
}

// Set Content-Type for JSON responses
header('Content-Type: application/json');

// Handle AJAX requests
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_data':
        handleGetData();
        break;
    case 'set_user':
        handleSetUser();
        break;
    case 'send_money':
        handleSendMoney();
        break;
    case 'bank_transfer':
        handleBankTransfer();
        break;
    case 'request_money':
        handleRequestMoney();
        break;
    default:
        // Return HTML if not an AJAX request
        header('Content-Type: text/html; charset=utf-8');
        renderHTML();
}

/**
 * Get current dashboard data
 */
function handleGetData() {
    $currentUserId = $_SESSION['current_user'];
    $users = $_SESSION['users'];
    
    // Sort transactions by timestamp (newest first)
    $transactions = $_SESSION['transactions'] ?? [];
    usort($transactions, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Filter transactions for current user
    $userTransactions = array_filter($transactions, function($t) use ($currentUserId) {
        if ($t['type'] === 'transfer') {
            return $t['from'] === $currentUserId || $t['to'] === $currentUserId;
        }
        return true;
    });
    
    echo json_encode([
        'success' => true,
        'current_user' => $currentUserId,
        'user_info' => $users[$currentUserId],
        'users' => $users,
        'transactions' => array_values($userTransactions)
    ]);
}

/**
 * Switch current user
 */
function handleSetUser() {
    $userId = $_POST['user_id'] ?? '';
    
    if (isset($_SESSION['users'][$userId])) {
        $_SESSION['current_user'] = $userId;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user']);
    }
}

/**
 * Send money between users
 */
function handleSendMoney() {
    $from = $_SESSION['current_user'];
    $to = $_POST['to'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $message = $_POST['message'] ?? '';
    
    // Validation
    if (!isset($_SESSION['users'][$to])) {
        echo json_encode(['success' => false, 'message' => 'Invalid recipient']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    if ($_SESSION['users'][$from]['balance'] < $amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        return;
    }
    
    // Process transfer
    $_SESSION['users'][$from]['balance'] -= $amount;
    $_SESSION['users'][$to]['balance'] += $amount;
    
    // Record transaction
    $transaction = [
        'type' => 'transfer',
        'from' => $from,
        'to' => $to,
        'amount' => $amount,
        'message' => $message,
        'timestamp' => date('M d, Y g:i A')
    ];
    
    $_SESSION['transactions'][] = $transaction;
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully sent ₱" . number_format($amount, 2) . " to " . $_SESSION['users'][$to]['name']
    ]);
}

/**
 * Simulate bank transfer (demo only - no actual processing)
 */
function handleBankTransfer() {
    $from = $_SESSION['current_user'];
    $bank = $_POST['bank'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    
    // Validation
    if (!in_array($bank, ['gcash', 'gotyme', 'bpi', 'bdo'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid bank']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    if ($amount < 500) {
        echo json_encode(['success' => false, 'message' => 'Minimum transfer amount is ₱500']);
        return;
    }
    
    if ($_SESSION['users'][$from]['balance'] < $amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        return;
    }
    
    // Deduct balance (demo only - no actual bank processing)
    $_SESSION['users'][$from]['balance'] -= $amount;
    
    // Record transaction
    $bankNames = [
        'gcash' => 'GCash',
        'gotyme' => 'GoTyme',
        'bpi' => 'BPI',
        'bdo' => 'BDO'
    ];
    
    $transaction = [
        'type' => 'bank_transfer',
        'from' => $from,
        'bank' => $bankNames[$bank],
        'amount' => $amount,
        'status' => 'pending',
        'timestamp' => date('M d, Y g:i A')
    ];
    
    $_SESSION['transactions'][] = $transaction;
    
    echo json_encode([
        'success' => true,
        'message' => "Transfer of ₱" . number_format($amount, 2) . " to " . $bankNames[$bank] . " initiated"
    ]);
}

/**
 * Request money from another user (demo - no actual notification)
 */
function handleRequestMoney() {
    $from = $_SESSION['current_user'];
    $to = $_POST['from'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    
    // Validation
    if (!isset($_SESSION['users'][$to])) {
        echo json_encode(['success' => false, 'message' => 'Invalid user']);
        return;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    // Record request (demo - no actual notification to recipient)
    $transaction = [
        'type' => 'request',
        'from' => $to,
        'to' => $from,
        'amount' => $amount,
        'reason' => $reason,
        'timestamp' => date('M d, Y g:i A')
    ];
    
    $_SESSION['transactions'][] = $transaction;
    
    echo json_encode([
        'success' => true,
        'message' => "Payment request of ₱" . number_format($amount, 2) . " sent to " . $_SESSION['users'][$to]['name']
    ]);
}

/**
 * Render HTML interface
 */
function renderHTML() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>💰 ThinkNoodles - Payment Dashboard</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <!-- Header with User Selector -->
            <div class="header">
                <div class="user-info">
                    <div class="user-name" id="userName">User A</div>
                    <div class="bank-info">
                        <span class="bank-badge" id="bankBadge">GCash</span>
                        <span class="phone" id="phoneNumber">0917-123-4567</span>
                    </div>
                </div>
                <select id="userSelect" class="user-selector" onchange="switchUser()">
                    <option value="user_a">👤 User A</option>
                    <option value="user_b">👤 User B</option>
                    <option value="user_c">👤 User C</option>
                </select>
            </div>

            <!-- Balance Card -->
            <div class="balance-card">
                <div class="balance-label">Your Balance</div>
                <div class="balance-amount" id="balanceAmount">₱0.00</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="progress-text" id="progressText">₱0 / ₱10,000</div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-action btn-send" onclick="openSendModal()">
                    <div class="btn-icon">📤</div>
                    <div class="btn-label">Send Money</div>
                </button>
                <button class="btn-action btn-bank" onclick="openBankTransferModal()">
                    <div class="btn-icon">🏦</div>
                    <div class="btn-label">Bank Transfer</div>
                </button>
                <button class="btn-action btn-request" onclick="openRequestModal()">
                    <div class="btn-icon">📥</div>
                    <div class="btn-label">Request Money</div>
                </button>
            </div>

            <!-- Transactions Section -->
            <div class="transactions-section">
                <div class="section-title">📋 Transaction History</div>
                <div id="transactionList">
                    <p class="empty-state">No transactions yet</p>
                </div>
            </div>
        </div>

        <!-- ========== MODALS ========== -->

        <!-- Send Money Modal -->
        <div id="sendModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">📤 Send Money</div>
                <form onsubmit="sendMoney(event)">
                    <div class="form-group">
                        <label for="recipient">Send to</label>
                        <select id="recipient" required>
                            <option value="">Select recipient...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quick amounts</label>
                        <div class="quick-amounts">
                            <button type="button" class="quick-btn" onclick="setSendAmount(100)">₱100</button>
                            <button type="button" class="quick-btn" onclick="setSendAmount(500)">₱500</button>
                            <button type="button" class="quick-btn" onclick="setSendAmount(1000)">₱1,000</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sendAmount">Amount</label>
                        <input type="number" id="sendAmount" placeholder="Enter amount" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="sendMessage">Message (optional)</label>
                        <input type="text" id="sendMessage" placeholder="Add a note...">
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Send Money</button>
                        <button type="button" class="btn btn-secondary" onclick="closeSendModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bank Transfer Modal -->
        <div id="bankTransferModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">🏦 Transfer to Bank</div>
                <form onsubmit="bankTransfer(event)">
                    <div class="form-group">
                        <label>Select Bank</label>
                        <div class="bank-options">
                            <input type="radio" id="bank_gcash" name="bank" value="gcash" class="bank-radio">
                            <label for="bank_gcash" class="bank-option">
                                <div class="bank-icon">📱</div>
                                <div class="bank-name">GCash</div>
                            </label>

                            <input type="radio" id="bank_gotyme" name="bank" value="gotyme" class="bank-radio">
                            <label for="bank_gotyme" class="bank-option">
                                <div class="bank-icon">🏦</div>
                                <div class="bank-name">GoTyme</div>
                            </label>

                            <input type="radio" id="bank_bpi" name="bank" value="bpi" class="bank-radio">
                            <label for="bank_bpi" class="bank-option">
                                <div class="bank-icon">💳</div>
                                <div class="bank-name">BPI</div>
                            </label>

                            <input type="radio" id="bank_bdo" name="bank" value="bdo" class="bank-radio">
                            <label for="bank_bdo" class="bank-option">
                                <div class="bank-icon">🏢</div>
                                <div class="bank-name">BDO</div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Quick amounts (min. ₱500)</label>
                        <div class="quick-amounts">
                            <button type="button" class="quick-btn" onclick="setBankAmount(1000)">₱1,000</button>
                            <button type="button" class="quick-btn" onclick="setBankAmount(5000)">₱5,000</button>
                            <button type="button" class="quick-btn" onclick="setBankAmount(10000)">₱10,000</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bankAmount">Amount</label>
                        <input type="number" id="bankAmount" placeholder="Enter amount (min. ₱500)" step="0.01" min="500" required>
                    </div>

                    <p style="font-size: 12px; color: #6B7280; margin-bottom: 16px;">
                        ⏳ Transfer typically completes in 1-3 minutes
                    </p>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Proceed to Transfer</button>
                        <button type="button" class="btn btn-secondary" onclick="closeBankTransferModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Request Money Modal -->
        <div id="requestModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">📥 Request Money</div>
                <form onsubmit="requestMoney(event)">
                    <div class="form-group">
                        <label for="requestFrom">Request from</label>
                        <select id="requestFrom" required>
                            <option value="">Select person...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="requestAmount">Amount</label>
                        <input type="number" id="requestAmount" placeholder="Enter amount" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="requestReason">Reason</label>
                        <textarea id="requestReason" placeholder="Why are you requesting this?" required></textarea>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-primary">Send Request</button>
                        <button type="button" class="btn btn-secondary" onclick="closeRequestModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notification -->
        <div id="notification" class="notification"></div>

        <script src="script.js"></script>
    </body>
    </html>
    <?php
}
?>
