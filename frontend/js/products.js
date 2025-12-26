let isLoggedIn = false;

async function checkLoginStatus() {
    const response = await ajax(`${API_BASE}/session_check.php`, 'GET');
    
    if (response.success && response.logged_in) {
        isLoggedIn = true;
        updateHeaderForLoggedInUser(response.data);
    } else {
        isLoggedIn = false;
        updateHeaderForGuestUser();
    }
}

function updateHeaderForLoggedInUser(userData) {
    const headerNav = document.querySelector('.header-nav');
    if (headerNav) {
        headerNav.innerHTML = `
            <span class="user-info">Welcome, ${userData.username}</span>
            <a href="#" onclick="handleLogout(event)">Logout</a>
        `;
    }
}

function updateHeaderForGuestUser() {
    const headerNav = document.querySelector('.header-nav');
    if (headerNav) {
        headerNav.innerHTML = `
            <a href="login.html">Login</a>
            <a href="signup.html">Sign Up</a>
        `;
    }
}

async function handleLogout(event) {
    event.preventDefault();
    
    const response = await ajax(`${API_BASE}/auth.php`, 'POST', {
        action: 'logout'
    });
    
    if (response.success) {
        clearStorage();
        window.location.href = 'index.html';
    }
}

async function loadProductsByCategory(categoryId) {
    showLoading('products-list');
    
    const response = await ajax(`${API_BASE}/products.php?category_id=${categoryId}`, 'GET');
    
    if (response.success) {
        renderProducts(response.data);
    } else {
        document.getElementById('products-list').innerHTML = 
            '<div class="empty-state"><p>Failed to load products</p></div>';
    }
}

function renderProducts(products) {
    const container = document.getElementById('products-list');
    
    if (!products || products.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No products found</h3><p>Select a category to view products</p></div>';
        return;
    }
    
    container.innerHTML = '';
    
    products.forEach(product => {
        const card = createProductCard(product);
        container.appendChild(card);
    });
}

function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    
    const imageUrl = product.image 
        ? `/test_project/uploads/products/${product.image}` 
        : 'https://via.placeholder.com/250x200?text=No+Image';
    
    const priceHTML = isLoggedIn 
        ? `<div class="product-price">${formatPrice(product.price)}</div>`
        : `<div class="price-hidden">Login to see price</div>`;
    
    card.innerHTML = `
        <img src="${imageUrl}" alt="${product.name}" class="product-image" onerror="this.src='https://via.placeholder.com/250x200?text=No+Image'">
        <div class="product-name">${product.name}</div>
        <div class="product-description">${product.description || 'No description available'}</div>
        ${priceHTML}
    `;
    
    return card;
}

if (document.getElementById('products-list')) {
    checkLoginStatus();
    
    const container = document.getElementById('products-list');
    container.innerHTML = '<div class="empty-state"><h3>Welcome!</h3><p>Select a category from the left to view products</p></div>';
}

