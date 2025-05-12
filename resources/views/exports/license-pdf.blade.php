<!-- resources/views/exports/license-pdf.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>License Information</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #334155;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }

        .page-container {
            position: relative;
            width: 100%;
            min-height: 100%;
            background-color: #f8fafc;
        }

        .header-band {
            background-color: #1e40af;
            padding: 20px 0;
            color: white;
            width: 100%;
            position: relative;
        }

        .header-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .heading {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            padding: 0;
        }

        .company-name {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            margin-top: -20px;
            position: relative;
            z-index: 10;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .license-key-container {
            background-color: #f1f5f9;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .license-key-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .license-key {
            font-family: 'Courier New', monospace;
            background-color: #e2e8f0;
            padding: 12px;
            border-radius: 4px;
            word-break: break-all;
            font-size: 14px;
            color: #334155;
            border-left: 3px solid #1e40af;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-spacing: 0 15px;
        }

        .info-item {
            display: table-row;
        }

        .label, .value {
            display: table-cell;
            vertical-align: middle;
        }

        .label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
            width: 150px;
        }

        .value {
            font-size: 15px;
            color: #334155;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            border-radius: 9999px;
            padding: 5px 12px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            width: 80px;
        }

        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        thead tr {
            background-color: #1e40af;
            color: white;
        }

        th {
            text-align: left;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 600;
        }

        tbody tr:nth-child(odd) {
            background-color: #f8fafc;
        }

        tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        td {
            padding: 12px 20px;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }

        th:last-child, td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .footer {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 40px;
            color: #64748b;
            font-size: 12px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            margin-top: 40px;
        }

        .footer p {
            margin: 5px 0;
        }

        .page-number {
            position: absolute;
            bottom: 20px;
            right: 40px;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header-band">
            <div class="header-content">
                <h1 class="heading">License Certificate</h1>
                <div class="company-name">{{ $license->tenant->name }}</div>
            </div>
        </div>

        <div class="container">
            <div class="section">
                <h2 class="section-title">License Information</h2>

                <div class="license-key-container">
                    <div class="license-key-label">License Key</div>
                    <div class="license-key">{{ $license->license_key }}</div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Valid From</span>
                        <span class="value">{{ \Carbon\Carbon::parse($license->valid_from)->format('F d, Y') }}</span>
                    </div>

                    <div class="info-item">
                        <span class="label">Valid Until</span>
                        <span class="value">{{ \Carbon\Carbon::parse($license->valid_until)->format('F d, Y') }}</span>
                    </div>

                    <div class="info-item">
                        <span class="label">Status</span>
                        <span class="value">
                            <span class="status-badge {{ $license->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $license->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Resource Limits</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Resource</th>
                            <th>Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Max Campaigns</td>
                            <td>{{ $license->max_campaigns }}</td>
                        </tr>
                        <tr>
                            <td>Max Agents</td>
                            <td>{{ $license->max_agents }}</td>
                        </tr>
                        <tr>
                            <td>Max Providers</td>
                            <td>{{ $license->max_providers }}</td>
                        </tr>
                        <tr>
                            <td>Max Distributor Calls</td>
                            <td>{{ $license->max_dist_calls }}</td>
                        </tr>
                        <tr>
                            <td>Max Dialer Calls</td>
                            <td>{{ $license->max_dial_calls }}</td>
                        </tr>
                        <tr>
                            <td>Max Contacts Per Campaign</td>
                            <td>{{ $license->max_contacts_per_campaign }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <p><strong>License Certificate</strong> · {{ $license->tenant->name }}</p>
            <p>Generated on {{ \Carbon\Carbon::now()->format('F d, Y \a\t h:i A') }}</p>
        </div>

     </div>
</body>
</html>
