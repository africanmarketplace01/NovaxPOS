<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store QR Code — {{ $store->store_name ?? 'Store' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('fontawesome/css/all.min.css') }}" rel="stylesheet">
    <style>
        :root { --primary: #0066CC; --accent: #66FF33; --dark: #003366; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; }

        .qr-page { max-width: 500px; margin: 2rem auto; }
        .qr-card {
            background: #fff; border-radius: 16px; padding: 2.5rem 2rem;
            text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,.1);
            border: 2px solid #e2e8f0;
        }
        .qr-store-name { font-size: 1.6rem; font-weight: 800; color: var(--dark); margin-bottom: .25rem; }
        .qr-store-name span { color: var(--primary); }
        .qr-subtitle { font-size: .875rem; color: #64748b; margin-bottom: 1.5rem; }
        #qr-canvas-wrapper { margin: 1.5rem auto; display: inline-block; padding: 1rem; border: 2px dashed #e2e8f0; border-radius: 12px; }
        .qr-url { font-size: .8rem; font-family: monospace; color: var(--primary); word-break: break-all; margin-top: 1rem; }
        .qr-tag { display: inline-block; background: rgba(0,102,204,.08); color: var(--primary); border: 1px solid rgba(0,102,204,.2); border-radius: 999px; padding: .3em 1em; font-size: .75rem; font-weight: 600; margin-top: .75rem; }

        /* Print area */
        .print-card {
            background: #fff; border-radius: 12px; padding: 2rem 1.5rem;
            text-align: center; border: 2px solid var(--dark); margin-top: 1.5rem;
        }
        .print-card .brand { font-size: 1.2rem; font-weight: 900; color: var(--dark); margin-bottom: .75rem; letter-spacing: -.01em; }
        .print-card .brand span { color: var(--primary); }
        .print-card .cta { font-size: .9rem; color: #475569; margin-top: .75rem; font-weight: 500; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .qr-page { margin: 0 auto; max-width: 100%; }
            .qr-card { box-shadow: none; }
            .print-card { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="qr-page">

    {{-- Controls --}}
    <div class="no-print mb-3 d-flex gap-2 justify-content-end">
        <button onclick="window.print()" class="btn btn-sm" style="background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:600">
            <i class="fas fa-print me-2"></i>Print QR Code
        </button>
        <a href="{{ route('store.index') }}" class="btn btn-sm btn-light" style="border-radius:8px">
            <i class="fas fa-store me-1"></i> View Store
        </a>
        <button onclick="downloadQR()" class="btn btn-sm btn-light" style="border-radius:8px">
            <i class="fas fa-download me-1"></i> Download PNG
        </button>
    </div>

    {{-- QR card --}}
    <div class="qr-card">
        <div class="qr-store-name">
            {{ $store->store_name ?? 'My Store' }}<span>.</span>
        </div>
        <div class="qr-subtitle">Scan to visit our online store</div>

        <div id="qr-canvas-wrapper">
            <canvas id="qr-canvas"></canvas>
        </div>

        <div class="qr-url">{{ $storeUrl }}</div>
        <div class="qr-tag"><i class="fas fa-shopping-bag me-1"></i>Online Store</div>
    </div>

    {{-- Small printable label variant --}}
    <div class="print-card mt-4" id="print-label">
        <div class="brand">{{ $store->store_name ?? 'Store' }}<span>.</span></div>
        <canvas id="qr-canvas-small"></canvas>
        <div class="cta">🛒 Scan to shop online</div>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:.5rem;font-family:monospace">{{ $storeUrl }}</div>
    </div>

    {{-- Size selector --}}
    <div class="no-print mt-3 d-flex align-items-center gap-3 justify-content-center">
        <span style="font-size:.82rem;color:#64748b">QR size:</span>
        <button onclick="generateQR(180)" class="btn btn-sm btn-light" style="border-radius:6px">Small</button>
        <button onclick="generateQR(260)" class="btn btn-sm btn-light" style="border-radius:6px">Medium</button>
        <button onclick="generateQR(340)" class="btn btn-sm btn-light" style="border-radius:6px">Large</button>
    </div>
</div>

{{-- QR Library --}}
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
    var storeUrl = {{ json_encode($storeUrl) }};
    var currentSize = 240;

    function generateQR(size) {
        currentSize = size || currentSize;
        QRCode.toCanvas(document.getElementById('qr-canvas'), storeUrl, {
            width: currentSize,
            margin: 2,
            color: { dark: '#003366', light: '#ffffff' }
        }, function(err) { if (err) console.error(err); });

        QRCode.toCanvas(document.getElementById('qr-canvas-small'), storeUrl, {
            width: 140,
            margin: 1,
            color: { dark: '#003366', light: '#ffffff' }
        }, function(err) {});
    }

    function downloadQR() {
        var canvas = document.getElementById('qr-canvas');
        var link = document.createElement('a');
        link.download = 'store-qr-{{ Str::slug($store->store_name ?? "store") }}.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    // Init
    generateQR(240);
</script>
</body>
</html>
