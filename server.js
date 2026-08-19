const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const querystring = require('querystring');

const PORT = 3000;

// In-Memory / Local Storage Database State Engine
let db = {
    users: [
        { user_id: 1, name: 'System Administrator', email: 'admin@library.com', password: 'admin123', roll_number: 'ADMIN001', role: 'admin' },
        { user_id: 2, name: 'Alex Student', email: 'student@library.com', password: 'student123', roll_number: 'STU2025001', role: 'student' }
    ],
    books: [
        { book_id: 1, isbn: '9780131103627', title: 'The C Programming Language', author: 'Brian W. Kernighan, Dennis M. Ritchie', publisher: 'Prentice Hall', publication_year: 1988, category: 'Computer Science', description: 'Classic guide to C programming language and UNIX operating systems.', total_copies: 5, available_copies: 4, location_shelf: 'Shelf A1', location_aisle: 'Aisle 1', map_x: 150, map_y: 120 },
        { book_id: 2, isbn: '9780262033848', title: 'Introduction to Algorithms', author: 'Thomas H. Cormen, Charles E. Leiserson', publisher: 'MIT Press', publication_year: 2009, category: 'Computer Science', description: 'Comprehensive textbook covering dynamic programming, graph algorithms, and data structures.', total_copies: 4, available_copies: 3, location_shelf: 'Shelf A2', location_aisle: 'Aisle 1', map_x: 280, map_y: 180 },
        { book_id: 3, isbn: '9780132350884', title: 'Clean Code: A Handbook of Agile Software Craftsmanship', author: 'Robert C. Martin', publisher: 'Prentice Hall', publication_year: 2008, category: 'Software Engineering', description: 'Best practices for writing readable, maintainable, and efficient software code.', total_copies: 6, available_copies: 6, location_shelf: 'Shelf B1', location_aisle: 'Aisle 2', map_x: 420, map_y: 250 },
        { book_id: 4, isbn: '9780596007126', title: 'Head First Design Patterns', author: 'Eric Freeman, Elisabeth Robson', publisher: "O'Reilly Media", publication_year: 2004, category: 'Software Engineering', description: 'Visual guide to software design patterns including Singleton, Observer, and Factory patterns.', total_copies: 3, available_copies: 2, location_shelf: 'Shelf B2', location_aisle: 'Aisle 2', map_x: 550, map_y: 310 },
        { book_id: 5, isbn: '9781449331818', title: 'Learning Python', author: 'Mark Lutz', publisher: "O'Reilly Media", publication_year: 2013, category: 'Programming', description: 'In-depth introduction to core Python programming language and data structures.', total_copies: 8, available_copies: 7, location_shelf: 'Shelf C1', location_aisle: 'Aisle 3', map_x: 680, map_y: 420 }
    ],
    transactions: [
        { transaction_id: 101, user_id: 2, book_id: 1, issue_date: new Date().toISOString(), due_date: '2026-08-19', return_date: null, status: 'issued', fine_amount: 0.00 }
    ],
    reading_history: [
        { history_id: 1, user_id: 2, book_id: 1, time_spent_minutes: 45 }
    ],
    active_session: { user_id: 2, name: 'Alex Student', email: 'student@library.com', role: 'student' }
};

const mimeTypes = {
    '.html': 'text/html',
    '.php': 'text/html',
    '.css': 'text/css',
    '.js': 'application/javascript',
    '.json': 'application/json',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.svg': 'image/svg+xml'
};

function parseBody(req) {
    return new Promise((resolve) => {
        let body = '';
        req.on('data', chunk => { body += chunk.toString(); });
        req.on('end', () => {
            if (req.headers['content-type'] && req.headers['content-type'].includes('application/json')) {
                try { resolve(JSON.parse(body)); } catch(e) { resolve({}); }
            } else {
                resolve(querystring.parse(body));
            }
        });
    });
}

const server = http.createServer(async (req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;
    const query = parsedUrl.query;

    // Handle API Endpoint
    if (pathname.includes('/backend/api.php')) {
        res.setHeader('Content-Type', 'application/json');
        res.setHeader('Access-Control-Allow-Origin', '*');

        const bodyData = await parseBody(req);
        const action = query.action || bodyData.action;

        if (action === 'login') {
            const email = bodyData.email;
            const pwd = bodyData.password;
            const user = db.users.find(u => u.email === email && u.password === pwd);
            if (user) {
                db.active_session = { user_id: user.user_id, name: user.name, email: user.email, role: user.role };
                return res.end(JSON.stringify({ success: true, user, message: 'Login successful' }));
            }
            return res.end(JSON.stringify({ success: false, message: 'Invalid credentials' }));
        }

        if (action === 'register') {
            const { name, email, password, roll_number, role } = bodyData;
            const newId = db.users.length + 1;
            db.users.push({ user_id: newId, name, email, password, roll_number: roll_number || `STU${newId}`, role: role || 'student' });
            return res.end(JSON.stringify({ success: true, user_id: newId, message: 'Registration successful!' }));
        }

        if (action === 'logout') {
            db.active_session = null;
            return res.end(JSON.stringify({ success: true, message: 'Logged out' }));
        }

        if (action === 'get_user_info') {
            if (!db.active_session) return res.end(JSON.stringify({ success: false, message: 'Not logged in' }));
            return res.end(JSON.stringify({ success: true, user: db.active_session }));
        }

        if (action === 'search_books') {
            const keyword = (query.keyword || '').toLowerCase();
            const filter = query.filter || 'title';
            let results = db.books;
            if (keyword) {
                results = results.filter(b => (b[filter] || '').toString().toLowerCase().includes(keyword));
            }
            return res.end(JSON.stringify({ success: true, books: results }));
        }

        if (action === 'add_book') {
            const { isbn, title, author, publisher, year, category, quantity } = bodyData;
            const book_id = db.books.length + 1;
            const copies = parseInt(quantity) || 1;
            const newBook = {
                book_id,
                isbn: isbn || `ISBN${Date.now()}`,
                title: title || 'New Catalog Book',
                author: author || 'Unknown Author',
                publisher: publisher || 'Academic Press',
                publication_year: year || 2026,
                category: category || 'General',
                description: 'Catalog item added via Admin Console.',
                total_copies: copies,
                available_copies: copies,
                location_shelf: 'Shelf A1',
                location_aisle: 'Aisle 1',
                map_x: Math.floor(Math.random() * 600) + 100,
                map_y: Math.floor(Math.random() * 350) + 80
            };
            db.books.push(newBook);
            return res.end(JSON.stringify({ success: true, book_id, message: 'Book successfully added to catalog!' }));
        }

        if (action === 'issue_book') {
            const book_id = parseInt(bodyData.book_id);
            const book = db.books.find(b => b.book_id === book_id);
            if (!book) return res.end(JSON.stringify({ success: false, message: 'Book not found' }));
            if (book.available_copies <= 0) return res.end(JSON.stringify({ success: false, message: 'No available copies' }));

            book.available_copies--;
            const trans_id = db.transactions.length + 101;
            const user_id = db.active_session ? db.active_session.user_id : 2;
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 14);

            db.transactions.push({
                transaction_id: trans_id,
                user_id,
                book_id,
                issue_date: new Date().toISOString(),
                due_date: dueDate.toISOString().split('T')[0],
                return_date: null,
                status: 'issued',
                fine_amount: 0.00
            });

            db.reading_history.push({ history_id: db.reading_history.length + 1, user_id, book_id, time_spent_minutes: 15 });

            return res.end(JSON.stringify({ success: true, transaction_id: trans_id, due_date: dueDate.toISOString().split('T')[0], message: `Book issued! Due date: ${dueDate.toISOString().split('T')[0]}` }));
        }

        if (action === 'return_book') {
            const trans_id = parseInt(bodyData.transaction_id);
            const trans = db.transactions.find(t => t.transaction_id === trans_id);
            if (!trans || trans.status === 'returned') return res.end(JSON.stringify({ success: false, message: 'Invalid or returned transaction' }));

            trans.status = 'returned';
            trans.return_date = new Date().toISOString().split('T')[0];
            const book = db.books.find(b => b.book_id === trans.book_id);
            if (book) book.available_copies++;

            return res.end(JSON.stringify({ success: true, message: 'Book returned successfully!' }));
        }

        if (action === 'get_user_transactions') {
            const user_id = db.active_session ? db.active_session.user_id : 2;
            const userTrans = db.transactions.filter(t => t.user_id === user_id).map(t => {
                const b = db.books.find(bk => bk.book_id === t.book_id) || {};
                return { ...t, title: b.title || 'Unknown Title', author: b.author || 'Unknown Author', isbn: b.isbn || '' };
            });
            return res.end(JSON.stringify({ success: true, transactions: userTrans }));
        }

        if (action === 'get_floor_map') {
            return res.end(JSON.stringify({ success: true, books: db.books }));
        }

        if (action === 'update_book_location') {
            const { book_id, map_x, map_y } = bodyData;
            const book = db.books.find(b => b.book_id === parseInt(book_id));
            if (book) {
                book.map_x = parseInt(map_x);
                book.map_y = parseInt(map_y);
                return res.end(JSON.stringify({ success: true, message: `Updated pin for "${book.title}" to (${map_x}, ${map_y})` }));
            }
            return res.end(JSON.stringify({ success: false, message: 'Book not found' }));
        }

        if (action === 'get_recommendations') {
            return res.end(JSON.stringify({
                success: true,
                recommendations: [db.books[1], db.books[2], db.books[3]]
            }));
        }

        if (action === 'scan_isbn') {
            const isbn = bodyData.isbn || query.isbn || '9780131103627';
            const existing = db.books.find(b => b.isbn === isbn);
            if (existing) return res.end(JSON.stringify({ success: true, book: existing }));
            return res.end(JSON.stringify({
                success: true,
                book: { isbn, title: 'Scanned ISBN Catalog (' + isbn + ')', author: 'Open Library Contributor', publisher: 'Academic Press', year: '2025', description: 'Barcode scanned catalog item.' }
            }));
        }

        if (action === 'get_analytics') {
            return res.end(JSON.stringify({
                success: true,
                months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                issued_count: [12, 19, 15, 25, 22, 30, 28],
                categories: ['Computer Science', 'Software Eng.', 'Mathematics', 'Electronics', 'Physics'],
                category_count: [45, 30, 20, 15, 10],
                popular_books: [
                    { title: 'Clean Code', issue_count: 38 },
                    { title: 'Introduction to Algorithms', issue_count: 31 },
                    { title: 'The C Programming Language', issue_count: 27 }
                ]
            }));
        }

        return res.end(JSON.stringify({ success: false, error: 'Unknown API action' }));
    }

    // Static File Serving
    let reqPath = pathname === '/' ? '/index.html' : pathname;
    let filePath = path.join(__dirname, reqPath);

    if (reqPath.endsWith('.php')) {
        let htmlAlt = filePath.replace(/\.php$/, '.html');
        if (fs.existsSync(htmlAlt)) filePath = htmlAlt;
    }

    fs.stat(filePath, (err, stats) => {
        if (err || !stats.isFile()) {
            res.statusCode = 404;
            res.end('404 Not Found');
            return;
        }

        const ext = path.extname(filePath).toLowerCase();
        const contentType = mimeTypes[ext] || 'application/octet-stream';
        res.setHeader('Content-Type', contentType);

        fs.createReadStream(filePath).pipe(res);
    });
});

server.listen(PORT, () => {
    console.log(`=======================================================`);
    console.log(`🚀 Smart Library Management System Live Server Running!`);
    console.log(`🌐 Application URL: http://localhost:${PORT}`);
    console.log(`🔑 Admin Login:     admin@library.com / admin123`);
    console.log(`🔑 Student Login:   student@library.com / student123`);
    console.log(`=======================================================`);
});
