// QuaggaJS Barcode Scanner Integration

let isScanning = false;

function startScanning() {
    const videoTarget = document.querySelector('#video');
    const resultBox = document.getElementById('scan-result');

    if (!videoTarget) {
        alert('Video preview element not found.');
        return;
    }

    if (typeof Quagga === 'undefined') {
        resultBox.innerHTML = `
            <div class="alert alert-warning">
                <strong>Camera Barcode Scanner Mode:</strong><br>
                QuaggaJS CDN loaded or simulation ready. Enter ISBN below to test barcode auto-lookup:
                <div class="input-group mt-2">
                    <input type="text" id="manual-isbn-input" class="form-control" placeholder="e.g., 9780131103627" value="9780131103627">
                    <button class="btn btn-primary" onclick="processScannedISBN(document.getElementById('manual-isbn-input').value)">Lookup ISBN</button>
                </div>
            </div>
        `;
        return;
    }

    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: videoTarget,
            constraints: {
                width: 400,
                height: 300,
                facingMode: "environment"
            }
        },
        decoder: {
            readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"]
        }
    }, function(err) {
        if (err) {
            console.error('Quagga Initialization Error:', err);
            resultBox.innerHTML = `<div class="alert alert-danger">Camera error: ${err.name || 'Access denied'}. Use manual ISBN entry below.</div>`;
            return;
        }
        Quagga.start();
        isScanning = true;
        resultBox.innerHTML = '<div class="alert alert-info">Scanning... Point camera at ISBN barcode on book.</div>';
    });

    Quagga.onDetected(function(data) {
        if (!isScanning) return;
        const code = data.codeResult.code;
        console.log("Barcode detected:", code);
        stopScanning();
        processScannedISBN(code);
    });
}

function stopScanning() {
    if (typeof Quagga !== 'undefined' && isScanning) {
        Quagga.stop();
        isScanning = false;
    }
}

function processScannedISBN(code) {
    const resultBox = document.getElementById('scan-result');
    if (resultBox) {
        resultBox.innerHTML = '<div class="alert alert-info"><div class="spinner-border spinner-border-sm"></div> Fetching metadata from Open Library API...</div>';
    }

    fetch('../../backend/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=scan_isbn&isbn=${encodeURIComponent(code)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.book) {
            const b = data.book;
            resultBox.innerHTML = `
                <div class="card border-success shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="card-title text-success">✓ Book Metadata Fetched!</h5>
                        <p class="mb-1"><strong>Title:</strong> ${b.title}</p>
                        <p class="mb-1"><strong>Author:</strong> ${b.author}</p>
                        <p class="mb-1"><strong>Publisher:</strong> ${b.publisher} (${b.year})</p>
                        <p class="mb-2"><strong>ISBN:</strong> ${b.isbn}</p>
                        <button class="btn btn-success" onclick="addScannedBookToCatalog('${b.isbn}', '${escapeHtml(b.title)}', '${escapeHtml(b.author)}', '${escapeHtml(b.publisher)}', '${b.year}')">
                            + Add Scanned Book to Catalog
                        </button>
                    </div>
                </div>
            `;
        } else {
            resultBox.innerHTML = `<div class="alert alert-warning">Book details not found for ISBN: ${code}</div>`;
        }
    })
    .catch(err => {
        console.error(err);
        resultBox.innerHTML = '<div class="alert alert-danger">Error querying Open Library API.</div>';
    });
}

function addScannedBookToCatalog(isbn, title, author, publisher, year) {
    fetch('../../backend/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_book&isbn=${encodeURIComponent(isbn)}&title=${encodeURIComponent(title)}&author=${encodeURIComponent(author)}&publisher=${encodeURIComponent(publisher)}&year=${year}&quantity=3`
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (typeof loadCatalogTable === 'function') {
            loadCatalogTable();
        }
    });
}

function escapeHtml(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}
