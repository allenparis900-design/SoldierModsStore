let selectedPlatform = 'roblox';
let selectedType = 'password';
let generationHistory = [];

// Platform Selection
function selectPlatform(platform) {
    selectedPlatform = platform;
    document.querySelectorAll('.platform-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.platform-btn').classList.add('active');
}

// Type Selection
function selectType(type) {
    selectedType = type;
    document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.type-btn').classList.add('active');
    
    // Update result title
    const resultTitle = document.getElementById('resultTitle');
    resultTitle.textContent = type === 'password' ? 'Generated Password' : 'Generated Username';
}

// Update Length Display
function updateLength() {
    const length = document.getElementById('length').value;
    document.getElementById('lengthValue').textContent = length;
}

// Update Complexity
function updateComplexity() {
    const complexity = document.getElementById('complexity').value;
    const includeNumbers = document.getElementById('includeNumbers');
    const includeSymbols = document.getElementById('includeSymbols');
    
    if (complexity === 'simple') {
        includeNumbers.checked = false;
        includeSymbols.checked = false;
    } else if (complexity === 'medium') {
        includeNumbers.checked = true;
        includeSymbols.checked = false;
    } else {
        includeNumbers.checked = true;
        includeSymbols.checked = true;
    }
}

// Generate Credential
function generateCredential() {
    const length = parseInt(document.getElementById('length').value);
    const includeNumbers = document.getElementById('includeNumbers').checked;
    const includeSymbols = document.getElementById('includeSymbols').checked;
    const avoidAmbiguous = document.getElementById('avoidAmbiguous').checked;
    
    let charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    
    if (includeNumbers) charset += '0123456789';
    if (includeSymbols) charset += '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    if (avoidAmbiguous) {
        charset = charset.replace(/[0OIl1]/g, '');
    }
    
    let credential = '';
    if (selectedType === 'username') {
        // Generate username (letters and numbers only, no symbols)
        let usernameCharset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
        if (avoidAmbiguous) usernameCharset = usernameCharset.replace(/[0OIl1]/g, '');
        
        for (let i = 0; i < length; i++) {
            credential += usernameCharset.charAt(Math.floor(Math.random() * usernameCharset.length));
        }
    } else {
        // Generate password
        for (let i = 0; i < length; i++) {
            credential += charset.charAt(Math.floor(Math.random() * charset.length));
        }
    }
    
    // Display result
    document.getElementById('resultInput').value = credential;
    
    // Update strength indicator for passwords
    if (selectedType === 'password') {
        updateStrengthIndicator(credential);
    } else {
        document.getElementById('strengthIndicator').textContent = '';
    }
    
    // Add to history
    addToHistory(credential, selectedType);
    
    // Update result info
    const platform = selectedPlatform.charAt(0).toUpperCase() + selectedPlatform.slice(1);
    document.getElementById('resultInfo').textContent = `${platform} ${selectedType}`;
}

// Update Strength Indicator
function updateStrengthIndicator(password) {
    let strength = 'Weak';
    let color = '#ff6b6b';
    
    if (password.length >= 12) {
        if (/[a-z]/.test(password) && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
            if (/[^a-zA-Z0-9]/.test(password)) {
                strength = 'Very Strong';
                color = '#00d084';
            } else {
                strength = 'Strong';
                color = '#51cf66';
            }
        } else {
            strength = 'Medium';
            color = '#ffd43b';
        }
    } else if (password.length >= 8) {
        strength = 'Medium';
        color = '#ffd43b';
    }
    
    const indicator = document.getElementById('strengthIndicator');
    indicator.textContent = strength;
    indicator.style.color = color;
    indicator.style.borderColor = color;
}

// Add to History
function addToHistory(credential, type) {
    const timestamp = new Date().toLocaleTimeString();
    generationHistory.unshift({ credential, type, timestamp });
    
    // Keep only last 10 items
    if (generationHistory.length > 10) {
        generationHistory.pop();
    }
    
    updateHistoryDisplay();
}

// Update History Display
function updateHistoryDisplay() {
    const historyList = document.getElementById('historyList');
    
    if (generationHistory.length === 0) {
        historyList.innerHTML = '<p class="empty-message">No generations yet</p>';
        return;
    }
    
    historyList.innerHTML = generationHistory.map((item, index) => `
        <div class="history-item">
            <span title="${item.credential}">${item.credential.substring(0, 20)}${item.credential.length > 20 ? '...' : ''}</span>
            <span>${item.type} @ ${item.timestamp}</span>
        </div>
    `).join('');
}

// Copy to Clipboard
function copyToClipboard() {
    const resultInput = document.getElementById('resultInput');
    resultInput.select();
    document.execCommand('copy');
    
    // Show feedback
    const copyBtn = event.target;
    const originalText = copyBtn.textContent;
    copyBtn.textContent = '✓ Copied!';
    setTimeout(() => {
        copyBtn.textContent = originalText;
    }, 2000);
}

// Clear History
function clearHistory() {
    if (confirm('Are you sure you want to clear all history?')) {
        generationHistory = [];
        updateHistoryDisplay();
    }
}

// Toggle Tutorial
function toggleTutorial() {
    const tutorialContent = document.getElementById('tutorialContent');
    tutorialContent.classList.toggle('show');
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updateHistoryDisplay();
});