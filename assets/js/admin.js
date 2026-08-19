// Admin Dashboard Client Controller

document.addEventListener('DOMContentLoaded', function() {
    setupAdminTabNavigation();
    loadCatalogTable();

    // Add Book Form Handler
    const form = document.getElementById('addBookForm');
    if (form) {
        form.addEventListener('submit', handleAddBookSubmit);
    }

    // Tab Listeners
    document.getElementById('floor-tab')?.addEventListener('click', function() {
        setTimeout(initFloorEditor, 100);
    });

    document.getElementById('analytics-tab')?.addEventListener('click', function() {
        if (typeof loadAnalyticsDashboard === 'function') {
            loadAnalyticsDashboard();
        }
    });
});

function setupAdminTabNavigation() {
    const tabs = [
        { btn: 'catalog-tab', content: 'catalog-content' },
        { btn: 'barcode-tab', content: 'barcode-content' },
        { btn: 'floor-tab', content: 'floor-content' },
        { btn: 'analytics-tab', content: 'analytics-content' }
    ];

    tabs.forEach(t => {
        const el = document.getElementById(t.btn);
        if (el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.admin-sidebar .nav-link').forEach(link => link.classList.remove('active'));
                el.classList.add('active');

                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                const target = document.getElementById(t.content);
                if (target) target.style.display = 'block';
            });
        }
    });
}

function loadCatalogTable() {
    const container = document.getElementById('books-table');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger"></div></div>';

    fetch('../../backend/api.php?action=search_books')
        .then(r => r.json())
        .then(data => {
            const books = data.books || [];
            if (books.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No books cataloged yet. Use "+ Add New Book" or Barcode Scanner.</div>';
                return;
            }

            let html = `
                <div class="table-responsive shadow-sm rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ISBN</th>
                                <th>Title & Author</th>
                                <th>Category</th>
                                <th>Total / Avail</th>
                                <th>Location (Shelf/Aisle)</th>
                                <th>2D Map (X, Y)</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            books.forEach(b => {
                html += `
                    <tr>
                        <td><code>${b.isbn}</code></td>
                        <td><strong>${b.title}</strong><br><small class="text-muted">${b.author}</small></td>
                        <td><span class="badge bg-light text-dark border">${b.category || 'General'}</span></td>
                        <td><span class="badge bg-info">${b.available_copies} / ${b.total_copies}</span></td>
                        <td><small>${b.location_shelf || 'Shelf A1'} | ${b.location_aisle || 'Aisle 1'}</small></td>
                        <td><span class="badge bg-secondary">(${b.map_x || 100}, ${b.map_y || 100})</span></td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        });
}

function handleAddBookSubmit(e) {
    e.preventDefault();
    const isbn = document.getElementById('isbn').value;
    const title = document.getElementById('title').value;
    const author = document.getElementById('author').value;
    const quantity = document.getElementById('quantity').value;
    const category = document.getElementById('category')?.value || 'General';

    fetch('../../backend/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_book&isbn=${encodeURIComponent(isbn)}&title=${encodeURIComponent(title)}&author=${encodeURIComponent(author)}&quantity=${quantity}&category=${encodeURIComponent(category)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Book added successfully!');
            // Close modal if Bootstrap instance available
            const modalEl = document.getElementById('addBookModal');
            if (modalEl && window.bootstrap) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
            document.getElementById('addBookForm').reset();
            loadCatalogTable();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// 2D Floor Editor Interactive Canvas
function initFloorEditor() {
    const canvas = document.getElementById('floorEditor');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let selectedBook = null;

    function renderEditor() {
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Blueprint Grid
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 1;
        for (let x = 0; x < canvas.width; x += 50) {
            ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, canvas.height); ctx.stroke();
        }
        for (let y = 0; y < canvas.height; y += 50) {
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(canvas.width, y); ctx.stroke();
        }

        // Draw Interactive Blueprint Shelves
        ctx.fillStyle = '#334155';
        ctx.fillRect(100, 100, 200, 400);
        ctx.fillRect(400, 100, 200, 400);
        ctx.fillRect(700, 100, 200, 400);

        ctx.fillStyle = '#f4a000';
        ctx.font = '700 14px Outfit';
        ctx.fillText('SECTION A', 150, 80);
        ctx.fillText('SECTION B', 450, 80);
        ctx.fillText('SECTION C', 750, 80);

        // Fetch & draw current book pins
        fetch('../../backend/api.php?action=get_floor_map')
            .then(r => r.json())
            .then(data => {
                (data.books || []).forEach(b => {
                    ctx.fillStyle = '#ff4d6d';
                    ctx.beginPath();
                    ctx.arc(b.map_x || 100, b.map_y || 100, 10, 0, Math.PI * 2);
                    ctx.fill();

                    ctx.fillStyle = '#ffffff';
                    ctx.font = '500 12px Inter';
                    ctx.fillText(`${b.title} (#${b.book_id})`, (b.map_x || 100) + 14, (b.map_y || 100) + 4);
                });
            });
    }

    renderEditor();

    // Click canvas to update location of selected book
    canvas.onclick = function(e) {
        const rect = canvas.getBoundingClientRect();
        const x = Math.round(e.clientX - rect.left);
        const y = Math.round(e.clientY - rect.top);

        const bookId = prompt("Enter Book ID to position at coordinates (" + x + ", " + y + "):");
        if (bookId) {
            fetch('../../backend/api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_book_location&book_id=${bookId}&map_x=${x}&map_y=${y}`
            })
            .then(r => r.json())
            .then(res => {
                alert(res.message);
                renderEditor();
            });
        }
    };
}
