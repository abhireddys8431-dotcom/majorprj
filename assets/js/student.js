// Student Portal Client Controller

document.addEventListener('DOMContentLoaded', function() {
    // Tab Event Listeners
    setupTabNavigation();

    // Initial default search
    searchBooks();

    // Render floor map when map tab is activated
    const mapTab = document.getElementById('map-tab');
    if (mapTab) {
        mapTab.addEventListener('click', function() {
            setTimeout(drawFloorMap, 100);
        });
    }

    // Load recommendations when rec tab clicked
    const recTab = document.getElementById('recommendations-tab');
    if (recTab) {
        recTab.addEventListener('click', loadRecommendations);
    }

    // Load user transaction history when My Books tab clicked
    const issuedTab = document.getElementById('issued-tab');
    if (issuedTab) {
        issuedTab.addEventListener('click', loadMyBooks);
    }
});

function setupTabNavigation() {
    const tabs = [
        { btn: 'search-tab', content: 'search-content' },
        { btn: 'map-tab', content: 'map-content' },
        { btn: 'recommendations-tab', content: 'recommendations-content' },
        { btn: 'issued-tab', content: 'issued-content' }
    ];

    tabs.forEach(t => {
        const el = document.getElementById(t.btn);
        if (el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                // Toggle active class
                document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
                el.classList.add('active');

                // Toggle tab content visibility
                document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
                const target = document.getElementById(t.content);
                if (target) target.style.display = 'block';
            });
        }
    });
}

// Search Books Function
function searchBooks() {
    const keywordInput = document.getElementById('search-input');
    const keyword = keywordInput ? keywordInput.value : '';
    const filterInput = document.getElementById('search-filter');
    const filter = filterInput ? filterInput.value : 'title';

    const resultsContainer = document.getElementById('search-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div></div>';
    }

    const apiUrl = getApiUrl(`backend/api.php?action=search_books&keyword=${encodeURIComponent(keyword)}&filter=${filter}`);

    fetch(apiUrl)
        .then(r => r.json())
        .then(data => {
            const books = data.books || [];
            if (!resultsContainer) return;

            if (books.length === 0) {
                resultsContainer.innerHTML = '<div class="alert alert-info shadow-sm">No books found matching your search query.</div>';
                return;
            }

            let html = '<div class="row g-4">';
            books.forEach(book => {
                const cover = book.cover_image_url || 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=400&q=80';
                html += `
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div style="height: 180px; overflow: hidden; background: #0f172a;" class="card-img-top position-relative">
                                <img src="${cover}" class="w-100 h-100" style="object-fit: cover; opacity: 0.85;" alt="${book.title}">
                                <span class="position-absolute top-0 end-0 bg-dark text-warning badge m-2">${book.category || 'General'}</span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-truncate" title="${book.title}">${book.title}</h5>
                                <p class="text-muted mb-1 small"><strong>Author:</strong> ${book.author}</p>
                                <p class="text-muted mb-2 small"><strong>ISBN:</strong> ${book.isbn}</p>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="badge ${book.available_copies > 0 ? 'bg-success' : 'bg-danger'}">
                                        ${book.available_copies > 0 ? book.available_copies + ' Available' : 'Issued Out'}
                                    </span>
                                    <button class="btn btn-primary btn-sm" onclick="issueBook(${book.book_id})" ${book.available_copies > 0 ? '' : 'disabled'}>
                                        Issue Book
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            resultsContainer.innerHTML = html;
        })
        .catch(err => {
            console.error('Search error:', err);
            if (resultsContainer) resultsContainer.innerHTML = '<div class="alert alert-danger">Error retrieving book catalog.</div>';
        });
}

// Issue Book Function
function issueBook(bookId) {
    if (!confirm('Confirm book checkout for 14 days?')) return;

    fetch(getApiUrl('backend/api.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=issue_book&book_id=${bookId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('🎉 ' + data.message);
            searchBooks();
        } else {
            alert('⚠️ ' + data.message);
        }
    })
    .catch(err => {
        console.error('Issue error:', err);
        alert('Server error issuing book.');
    });
}

// Load AI Recommendations
function loadRecommendations() {
    const container = document.getElementById('recommendations');
    if (!container) return;

    container.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-danger"></div></div>';

    fetch(getApiUrl('backend/api.php?action=get_recommendations'))
        .then(r => r.json())
        .then(data => {
            const books = data.recommendations || [];
            if (books.length === 0) {
                container.innerHTML = '<div class="col-12"><div class="alert alert-info">Start reading books to generate personalized recommendations!</div></div>';
                return;
            }

            let html = '';
            books.forEach(book => {
                html += `
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #ffffff 0%, #fff1f2 100%); border-left: 4px solid #c41e3a !important;">
                            <div class="card-body">
                                <span class="badge bg-danger mb-2">⭐ AI Pick</span>
                                <h5 class="card-title mb-1">${book.title}</h5>
                                <p class="text-muted small mb-2">by ${book.author}</p>
                                <p class="small text-secondary mb-3">${book.description ? book.description.substring(0, 80) + '...' : 'Highly rated in your study interest.'}</p>
                                <button class="btn btn-outline-danger btn-sm w-100" onclick="issueBook(${book.book_id})">Checkout Now</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        });
}

// Load Issued Books History
function loadMyBooks() {
    const container = document.getElementById('issued-list');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';

    fetch(getApiUrl('backend/api.php?action=get_user_transactions'))
        .then(r => r.json())
        .then(data => {
            const list = data.transactions || [];
            if (list.length === 0) {
                container.innerHTML = '<div class="alert alert-info">You currently have no active or past issued books.</div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Book Title</th><th>Issue Date</th><th>Due Date</th><th>Status</th><th>Action</th></tr></thead><tbody>';
            list.forEach(item => {
                const isOverdue = item.status === 'issued' && new Date(item.due_date) < new Date();
                const statusBadge = item.status === 'returned' ? '<span class="badge bg-secondary">Returned</span>' : (isOverdue ? '<span class="badge bg-danger">Overdue</span>' : '<span class="badge bg-success">Active Issue</span>');
                
                html += `
                    <tr>
                        <td><strong>${item.title}</strong><br><small class="text-muted">${item.author}</small></td>
                        <td>${item.issue_date ? item.issue_date.substring(0, 10) : 'N/A'}</td>
                        <td>${item.due_date}</td>
                        <td>${statusBadge}</td>
                        <td>
                            ${item.status === 'issued' ? `<button class="btn btn-warning btn-sm" onclick="returnBook(${item.transaction_id})">Return Book</button>` : '<span class="text-muted small">Completed</span>'}
                        </td>
                    </tr>
                `;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        });
}

function returnBook(transId) {
    if (!confirm('Return this book copy to library shelf?')) return;

    fetch(getApiUrl('backend/api.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=return_book&transaction_id=${transId}`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        loadMyBooks();
    });
}

// 2D Floor Map Visualizer Canvas
function drawFloorMap() {
    const canvas = document.getElementById('floorMap');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    // Background styling
    ctx.fillStyle = '#1e293b';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Architectural Grid Lines
    ctx.strokeStyle = '#334155';
    ctx.lineWidth = 1;
    for (let x = 0; x < canvas.width; x += 50) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, canvas.height);
        ctx.stroke();
    }
    for (let y = 0; y < canvas.height; y += 50) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(canvas.width, y);
        ctx.stroke();
    }

    // Draw Library Shelves (Layout Blueprint)
    ctx.fillStyle = '#475569';
    ctx.fillRect(80, 80, 140, 300);  // Shelf Block A
    ctx.fillRect(320, 80, 140, 300); // Shelf Block B
    ctx.fillRect(560, 80, 140, 300); // Shelf Block C

    ctx.fillStyle = '#94a3b8';
    ctx.font = '600 14px Outfit';
    ctx.fillText('AISLE 1 (CompSci)', 90, 65);
    ctx.fillText('AISLE 2 (Software)', 330, 65);
    ctx.fillText('AISLE 3 (General)', 570, 65);

    // Fetch and plot dynamic book markers
    fetch(getApiUrl('backend/api.php?action=get_floor_map'))
        .then(r => r.json())
        .then(data => {
            const books = data.books || [];
            books.forEach(b => {
                const x = b.map_x || 100;
                const y = b.map_y || 100;

                // Glowing target marker
                ctx.shadowBlur = 10;
                ctx.shadowColor = '#ff4d6d';
                ctx.fillStyle = '#c41e3a';
                ctx.beginPath();
                ctx.arc(x, y, 9, 0, Math.PI * 2);
                ctx.fill();

                ctx.shadowBlur = 0;
                ctx.fillStyle = '#ffffff';
                ctx.font = '500 11px Inter';
                ctx.fillText(b.title.substring(0, 18), x + 14, y + 4);
            });
        });
}

function getApiUrl(path) {
    return path;
}
