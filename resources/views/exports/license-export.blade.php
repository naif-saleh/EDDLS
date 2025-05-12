<!-- resources/views/exports/license-export.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even):not(:first-child) {
            background-color: #f9f9f9;
        }
        .section-title {
            background-color: #004085;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
        }
        .spacer-row td {
            border: none;
            height: 20px;
        }
    </style>
</head>
<body>

    <table>
        <thead>
            <tr>
                <th class="section-title" colspan="2">
                    License Information - {{ $license->tenant->name }}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>License Key</strong></td>
                <td>{{ $license->license_key }}</td>
            </tr>
            <tr>
                <td><strong>Valid From</strong></td>
                <td>{{ $license->valid_from }}</td>
            </tr>
            <tr>
                <td><strong>Valid Until</strong></td>
                <td>{{ $license->valid_until }}</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td>{{ $license->is_active ? 'Active' : 'Inactive' }}</td>
            </tr>

            <tr class="spacer-row"><td colspan="2"></td></tr>

            <tr>
                <th class="section-title" colspan="2">Resource Limits</th>
            </tr>
            <tr>
                <td><strong>Max Campaigns</strong></td>
                <td>{{ $license->max_campaigns }}</td>
            </tr>
            <tr>
                <td><strong>Max Agents</strong></td>
                <td>{{ $license->max_agents }}</td>
            </tr>
            <tr>
                <td><strong>Max Providers</strong></td>
                <td>{{ $license->max_providers }}</td>
            </tr>
            <tr>
                <td><strong>Max Distributor Calls</strong></td>
                <td>{{ $license->max_dist_calls }}</td>
            </tr>
            <tr>
                <td><strong>Max Dialer Calls</strong></td>
                <td>{{ $license->max_dial_calls }}</td>
            </tr>
            <tr>
                <td><strong>Max Contacts Per Campaign</strong></td>
                <td>{{ $license->max_contacts_per_campaign }}</td>
            </tr>
        </tbody>
    </table>

</body>
</html>
