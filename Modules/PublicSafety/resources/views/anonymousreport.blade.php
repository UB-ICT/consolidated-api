<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>End of Shift Report (Patrol Officers)</title>

    <style>
        /* Reset */
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #333;
        }

        body {
            margin: 30px;
            background: #f9f9f9;
        }

        .container {
            width: 100%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        /* Header */
        .header {
            background: #3d004a;
            color: #fff;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 1px;
        }

        .header p {
            margin: 5px 0;
            font-size: 15px;
        }

        .header .case {
            font-weight: bold;
            margin-top: 8px;
            font-size: 16px;
        }

        /* Section */
        .section {
            margin-bottom: 25px;
        }

        .section h2 {
            background: #7e317b;
            color: #fff;
            padding: 8px 12px;
            font-size: 18px;
            border-radius: 5px 5px 0 0;
            margin: 0;
        }

        .section-content {
            border: 1px solid #ddd;
            border-top: none;
            padding: 15px;
            border-radius: 0 0 5px 5px;
            background: #fafafa;
        }

        /* Table-like info */
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            width: 40%;
            color: #444;
        }

        .info-value {
            width: 58%;
            text-align: left;
        }

        /* Print-friendly */
        @media print {
            body {
                margin: 0;
                background: #fff;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
        }

        @page {
            size: A4;
            margin: 20mm;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>University of Belize</h1>
            <p>Public Safety Department</p>
            <p> Patrol Officer</p>
            <p class="case">End of Shift Report</p>
        </div>

        {{-- Anonymous Report details --}}
        <div class="section">
            <h2>Anonymous Report</h2>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Anonymous Report ID: </span>
                    <span class="info-value">{{ $anonymousReport['id'] ?? 'N/As'}}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Category: </span>
                    <span class="info-value">{{ $anonymousReport['category'] ?? 'N/A'}}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Location: </span>
                    <span class="info-value">{{ $anonymousReport['location'] ?? 'N/A'}}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Report: </span>
                    <span class="info-value">{{ $anonymousReport['reports'] ?? 'N/A'}}</span>
                </div>
            </div>
        </div>
    </div>
</body>