
async function handleLogin(event) {
    event.preventDefault();
    
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        showAlert('Please enter both username and password', 'error');
        return;
    }
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in...';
    
    const response = await ajax(`${API_BASE}/auth.php`, 'POST', {
        action: 'login',
        username: username,
        password: password
    });
    
    if (response.success) {
        setStorage('user', response.data);
        
        showAlert('Login successful! Redirecting...', 'success');
        
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
    } else {
        showAlert(response.message || 'Login failed', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function handleSignup(event) {
    event.preventDefault();
    
    const name = document.getElementById('name').value.trim();
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (!name || !username || !email || !password) {
        showAlert('All fields are required', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('Passwords do not match', 'error');
        return;
    }
    
    if (password.length < 6) {
        showAlert('Password must be at least 6 characters', 'error');
        return;
    }
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing up...';
    
    const response = await ajax(`${API_BASE}/signup.php`, 'POST', {
        name: name,
        username: username,
        email: email,
        password: password
    });
    
    if (response.success) {
        showAlert('Registration successful! Redirecting to login...', 'success');
        
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    } else {
        showAlert(response.message || 'Registration failed', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function checkAlreadyLoggedIn() {
    const response = await ajax(`${API_BASE}/session_check.php`, 'GET');
    
    if (response.success && response.logged_in) {
        window.location.href = 'index.html';
    }
}

if (window.location.pathname.includes('login.html') || window.location.pathname.includes('signup.html')) {
    checkAlreadyLoggedIn();
}

