<!DOCTYPE html>
<html>
<head>
    <title>Pesan dari Website Bersekolah</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .header {
            background-color: #406386;
            color: white;
            padding: 15px;
            border-radius: 6px 6px 0 0;
            margin-bottom: 20px;
        }
        .content {
            padding: 0 10px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .field-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .field-value {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9fafb;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Pesan Baru dari Form Kontak Website Bersekolah</h2>
        </div>
        
        <div class="content">
            <div class="field-label">Nama:</div>
            <div class="field-value">{{ $data['nama'] }}</div>
            
            <div class="field-label">Email:</div>
            <div class="field-value">{{ $data['email'] }}</div>
            
            <div class="field-label">Pesan:</div>
            <div class="field-value">{{ $data['pesan'] }}</div>
        </div>
        
        <div class="footer">
            <p>Pesan ini dikirim melalui form kontak website Beasiswa Bersekolah.</p>
            <p>Tanggal: {{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>
</body>
</html>
