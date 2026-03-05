<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - {{ $table->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .qr-card {
            background: white;
            width: 148mm; /* A5 width */
            min-height: 210mm; /* A5 height */
            padding: 20mm 15mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .restaurant-name {
            font-size: 28px;
            font-weight: 800;
            color: #b91c1c;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .qr-container {
            background: white;
            border: 3px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            display: inline-block;
        }

        .qr-container svg {
            display: block;
        }

        .table-name {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .table-capacity {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .instructions {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 16px 24px;
            max-width: 360px;
        }

        .instructions-title {
            font-size: 14px;
            font-weight: 600;
            color: #b91c1c;
            margin-bottom: 8px;
        }

        .instructions ol {
            text-align: left;
            padding-left: 18px;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.8;
        }

        .footer {
            margin-top: 24px;
            font-size: 10px;
            color: #9ca3af;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .no-print button {
            font-family: 'Poppins', sans-serif;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-print {
            background: #b91c1c;
            color: white;
        }

        .btn-print:hover {
            background: #991b1b;
        }

        .btn-back {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-back:hover {
            background: #d1d5db;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                min-height: auto;
            }

            .qr-card {
                box-shadow: none;
                border-radius: 0;
                width: 148mm;
                min-height: 210mm;
                padding: 15mm 12mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A5 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    {{-- Print / Back Buttons --}}
    <div class="no-print">
        <button class="btn-back" onclick="window.close()">
            ✕ Tutup
        </button>
        <button class="btn-print" onclick="window.print()">
            🖨 Cetak QR Code
        </button>
    </div>

    {{-- QR Card --}}
    <div class="qr-card">
        <div class="restaurant-name">{{ $settings['site_name'] ?? 'Warung Order' }}</div>
        <div class="subtitle">Scan untuk melihat menu & pesan langsung</div>

        <div class="qr-container">
            {!! QrCode::size(250)->color(185, 28, 28)->generate($url) !!}
        </div>

        <div class="table-name">{{ $table->name }}</div>
        <div class="table-capacity">Kapasitas: {{ $table->capacity }} orang</div>

        <div class="instructions">
            <div class="instructions-title">📱 Cara Pemesanan</div>
            <ol>
                <li>Buka kamera HP atau aplikasi scanner</li>
                <li>Arahkan ke QR Code di atas</li>
                <li>Pilih menu dan jumlah pesanan</li>
                <li>Kirim pesanan, tunggu disajikan</li>
            </ol>
        </div>

        <div class="footer">
            {{ $settings['site_name'] ?? 'Warung Order' }} &bull; {{ $settings['address'] ?? '' }}
        </div>
    </div>
</body>
</html>
