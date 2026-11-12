<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Pengajuan ID Card - {{ $workPermit->full_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #edf2f7;
            color: #172b43;
            font-family: Arial, sans-serif;
        }

        main {
            width: min(760px, 100%);
            margin: 0 auto;
        }

        .page-title {
            margin: 0 0 6px;
            font-size: 25px;
            font-weight: 800;
        }

        .page-subtitle {
            margin: 0;
            color: #607086;
            font-size: 13px;
            line-height: 1.7;
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 18px 0;
        }

        .toolbar a,
        .toolbar button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border: 0;
            border-radius: 9px;
            background: #103b62;
            color: white;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .alert {
            padding: 11px 13px;
            border-radius: 9px;
            background: #eaf7ef;
            color: #17643a;
            font-size: 13px;
        }

        .zone-info {
            margin: 8px 0 0;
            color: #607086;
            font-size: 12px;
        }

        .zone-selector {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 14px 0 0;
            padding: 12px 14px;
            border: 1px solid #dce5ef;
            border-radius: 10px;
            background: white;
        }

        .zone-selector label {
            color: #34465e;
            font-size: 12px;
            font-weight: 700;
        }

        .zone-selector select {
            min-width: 220px;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            color: #172b43;
            font: inherit;
            font-size: 13px;
        }

        .zone-selector-note {
            flex-basis: 100%;
            margin: 0;
            color: #65758a;
            font-size: 11px;
        }

        .print-status {
            display: inline-flex;
            align-items: center;
            padding: 10px 14px;
            border-radius: 9px;
            background: #eaf7ef;
            color: #17643a;
            font-size: 12px;
            font-weight: 700;
        }

        .card {
            width: 55mm;
            height: 85mm;
            margin: 0 auto 18px;
            overflow: hidden;
            border: 1px solid #dce2e8;
            border-radius: 4mm;
            background: white;
            box-shadow: 0 10px 30px #1232;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .top {
            min-height: 13mm;
            padding: 2.5mm 2.5mm;
            background-color: var(--zone);
            color: var(--zone-ink, #ffffff);
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2mm;
        }

        .logo {
            flex: 0 0 auto;
            width: 9mm;
            height: 9mm;
            padding: .5mm;
            border-radius: 2mm;
            background: white;
            object-fit: contain;
        }

        .brand-copy {
            min-width: 0;
            text-align: left;
        }

        .brand-copy p {
            margin: 0 0 1mm;
            font-size: 5pt;
            font-weight: 700;
            letter-spacing: .12em;
        }

        .brand-copy h2 {
            margin: 0;
            font-size: 8pt;
            line-height: 1.1;
            letter-spacing: .02em;
        }

        .portrait {
            display: block;
            width: 18mm;
            height: 21mm;
            margin: 1.5mm auto 1mm;
            border: 1.5px solid var(--zone);
            border-radius: 2mm;
            object-fit: cover;
        }

        .no-photo {
            display: grid;
            place-items: center;
            padding: 3mm;
            background: #edf2f7;
            color: #64748b;
            font-size: 7pt;
            text-align: center;
        }

        .body {
            padding: 0 3mm 1.5mm;
            text-align: center;
        }

        .name {
            margin: .5mm 0 .5mm;
            color: #172b43;
            font-size: 9pt;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .company {
            margin: 0 0 .5mm;
            color: #3e526b;
            font-size: 6.5pt;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .job {
            margin: 0 0 1.2mm;
            color: #64748b;
            font-size: 6pt;
            overflow-wrap: anywhere;
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1mm;
            padding: 1.5mm;
            border: 1px solid #e5eaf0;
            border-radius: 2mm;
            text-align: left;
        }

        .detail-label {
            display: block;
            margin-bottom: .5mm;
            color: #8190a3;
            font-size: 5.5pt;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .detail-value {
            display: block;
            color: #263b54;
            font-size: 6pt;
            font-weight: 700;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .number {
            margin: 1mm 0;
            color: #183d60;
            font-size: 7pt;
            font-weight: 900;
            letter-spacing: .06em;
        }

        .location {
            padding: 1.2mm 1.5mm;
            border-radius: 2mm;
            background: #f1f5f9;
            color: #263b54;
            font-size: 7pt;
            font-weight: 700;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .location-label {
            display: block;
            margin-bottom: .5mm;
            color: #8190a3;
            font-size: 5.5pt;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .status {
            display: inline-block;
            margin-top: 1mm;
            padding: .5mm 2mm;
            border-radius: 20px;
            background: #e8f6ee;
            color: #17643a;
            font-size: 6pt;
            font-weight: 900;
            letter-spacing: .1em;
        }

        .bottom {
            height: 1.5mm;
            margin-top: 1mm;
            background-color: var(--zone);
        }

        .note {
            margin: 0 auto;
            color: #718096;
            font-size: 12px;
            line-height: 1.7;
            text-align: center;
        }

        @media (max-width: 520px) {
            body {
                padding: 16px;
            }
        }

        @media print {
            @page {
                size: 55mm 85mm;
                margin: 0;
            }

            body {
                padding: 0;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            main {
                width: 55mm;
                margin: 0;
            }

            .card {
                width: 55mm;
                height: 85mm;
                min-height: 85mm;
                margin: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    @php
    $zones = config('mitra.zones', []);
    $detectedZone = $zones[$workPermit->zone_key] ?? null;
    $configuredLocationColor = $detectedZone['color'] ?? null;
    $locationColorCandidate = $configuredLocationColor ?: $workPermit->zone_color;
    $locationColor = is_string($locationColorCandidate) && preg_match('/^#[0-9a-fA-F]{6}$/', $locationColorCandidate)
    ? strtolower($locationColorCandidate)
    : '#475569';
    $selectedZoneKey = collect($zones)->search(fn ($zone) => strtolower($zone['color'] ?? '') === strtolower($workPermit->id_card_header_color ?? ''));
    if ($selectedZoneKey === false) {
    $selectedZoneKey = collect($zones)->search(fn ($zone) => strtolower($zone['color'] ?? '') === strtolower($workPermit->zone_color ?? ''));
    }
    if ($selectedZoneKey === false) {
    $selectedZoneKey = isset($zones[$workPermit->zone_key]) ? $workPermit->zone_key : 'zona-hijau';
    }
    $selectedZoneKey = old('header_zone', $selectedZoneKey);
    $headerColor = strtolower($zones[$selectedZoneKey]['color'] ?? '#16a34a');
    $headerTextColor = $selectedZoneKey === 'zona-kuning' ? '#172b43' : '#ffffff';
    $zoneLabel = $zones[$selectedZoneKey]['label'] ?? 'ZONA HIJAU';
    $startLabel = $workPermit->work_starts_at?->format('d/m/Y') ?? $workPermit->start_date->format('d/m/Y');
    $endLabel = $workPermit->work_ends_at?->format('d/m/Y') ?? $workPermit->end_date->format('d/m/Y');
    $cardNumber = 'IK-' . str_pad((string) $workPermit->id, 6, '0', STR_PAD_LEFT);
    @endphp
    <main>
        <section class="no-print">
            <h1 class="page-title">Pratinjau ID Mitra Kerja</h1>
            <p class="page-subtitle">Periksa data pekerja, pilih zona kartu, lalu cetak kartu.</p>
            <p class="zone-info">Zona kartu aktif: <strong>{{ $zoneLabel }}</strong>.</p>
            @unless ($workPermit->printed_at)
            <p class="zone-info">Setelah proses cetak berhasil, klik “Tandai Sudah Dicetak” untuk menyimpan kartu ke riwayat.</p>
            @endunless
            <nav class="toolbar">
                <a href="{{ route('operator.id-cards.index') }}">← Kembali ke daftar pekerja</a>
                <button type="button" onclick="window.print()">Cetak kartu</button>
                @if ($workPermit->printed_at)
                <span class="print-status">Sudah dicetak pada {{ $workPermit->printed_at->format('d/m/Y H:i') }}</span>
                @else
                <form method="POST" action="{{ route('operator.id-cards.mark-printed', $workPermit) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit">Tandai Sudah Dicetak</button>
                </form>
                @endif
                <a href="{{ route('operator.id-cards.printed') }}">Lihat kartu sudah dicetak</a>
            </nav>
            <form class="zone-selector" method="POST" action="{{ route('operator.id-cards.update-zone', $workPermit) }}">
                @csrf
                @method('PATCH')
                <label for="header-zone">Pilih Zona Header</label>
                <select id="header-zone" name="header_zone" onchange="this.form.submit()" required>
                    @foreach ($zones as $key => $zone)
                    <option value="{{ $key }}" @selected($selectedZoneKey===$key)>{{ $zone['label'] }}</option>
                    @endforeach
                </select>
                <p class="zone-selector-note">Pilih salah satu zona. Warna header kartu akan otomatis mengikuti warna zona yang dipilih.</p>
                @error('header_zone') <p class="error" role="alert">{{ $message }}</p> @enderror
            </form>
            @if (session('success'))
            <div class="alert" role="status">{{ session('success') }}</div>
            @endif
        </section>

        <article class="card" style="--zone: {{ $headerColor }}; --zone-ink: {{ $headerTextColor }};">
            <header class="top" style="background-color: {{ $headerColor }}; color: {{ $headerTextColor }};">
                <div class="brand">
                    <img class="logo" src="{{ asset(config('mitra.logo')) }}" alt="Logo perusahaan">
                    <div class="brand-copy">
                        <p>KARTU IDENTITAS</p>
                        <h2>SISTEM PENGAJUAN ID CARD</h2>
                    </div>
                </div>
            </header>

            @if ($workPermit->photo_path)
            <img class="portrait" style="border-color: {{ $headerColor }};" src="{{ route('permits.photo', $workPermit) }}" alt="Foto {{ $workPermit->full_name }}">
            @else
            <div class="portrait no-photo">Foto belum tersedia<br>Data pengajuan lama</div>
            @endif

            <div class="body">
                <h3 class="name">{{ $workPermit->full_name }}</h3>
                <p class="company">PT: {{ $workPermit->company_name }}</p>
                <p class="job">Jabatan: {{ $workPermit->job_title }}</p>

                <div class="details">
                    <div>
                        <span class="detail-label">Tanggal mulai</span>
                        <span class="detail-value">{{ $startLabel }}</span>
                    </div>
                    <div>
                        <span class="detail-label">Tanggal selesai</span>
                        <span class="detail-value">{{ $endLabel }}</span>
                    </div>
                </div>

                <div class="location">
                    <span class="location-label">Lokasi kerja</span>
                    {{ $workPermit->work_location }}
                </div>
            </div>
            <div class="bottom" style="background-color: {{ $headerColor }};"></div>
        </article>

        <p class="note no-print">Saat mencetak, aktifkan background graphics, gunakan skala 100%, dan matikan header/footer browser. Ukuran cetak kartu B1: 55 × 85 mm.</p>
    </main>
</body>

</html>