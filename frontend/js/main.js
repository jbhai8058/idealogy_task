
const API_BASE = '/test_project/api';


async function ajax(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('AJAX Error:', error);
        return {
            success: false,
            message: 'Network error occurred'
        };
    }
}

function showAlert(message, type = 'info', containerId = 'alert-container') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    container.innerHTML = '';
    container.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading...</p></div>';
}

function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

function getStorage(key) {
    try {
        const value = sessionStorage.getItem(key);
        return value ? JSON.parse(value) : null;
    } catch (e) {
        return null;
    }
}

function setStorage(key, value) {
    try {
        sessionStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.error('Storage error:', e);
    }
}

function removeStorage(key) {
    sessionStorage.removeItem(key);
}

function clearStorage() {
    sessionStorage.clear();
}

