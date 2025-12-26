let currentCategory = null;

async function loadCategories() {
    showLoading('categories-list');
    
    const response = await ajax(`${API_BASE}/categories.php`, 'GET');
    
    if (response.success) {
        renderCategories(response.data);
    } else {
        document.getElementById('categories-list').innerHTML = 
            '<div class="empty-state"><p>Failed to load categories</p></div>';
    }
}

function renderCategories(categories) {
    const container = document.getElementById('categories-list');
    
    if (!categories || categories.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>No categories found</p></div>';
        return;
    }
    
    container.innerHTML = '';
    
    categories.forEach(category => {
        const li = document.createElement('li');
        li.className = 'category-item';
        li.textContent = category.name;
        li.dataset.categoryId = category.id;
        
        li.addEventListener('click', () => {
            selectCategory(category.id, li);
        });
        
        container.appendChild(li);
    });
}

function selectCategory(categoryId, element) {
    document.querySelectorAll('.category-item').forEach(item => {
        item.classList.remove('active');
    });    
    element.classList.add('active');
    
    currentCategory = categoryId;
    
    if (typeof loadProductsByCategory === 'function') {
        loadProductsByCategory(categoryId);
    }
}
    
if (document.getElementById('categories-list')) {
    loadCategories();
}

