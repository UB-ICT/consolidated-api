<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report</title>

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
            <p class="case">Incident Log - {{ $incidentLog['caseNumber'] ?? 'N/A' }}</p>
        </div>

        <!-- INCIDENT DETAILS -->
        <div class="section">
            <h2>Incident Details</h2>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Case Number: </span>
                    <span class="info-value">{{ $incidentLog['caseNumber'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Date: </span>
                    <span class="info-value">{{ $incidentLog['date'] ?? 'N/A' }}</span>
                    <span class="info-label">Time Reported: </span>
                    <span class="info-value">{{ $incidentLog['timeReported'] ?? 'N/A' }}</span>
                    <span class="info-label">Time of Incident: </span>
                    <span class="info-value">{{ $incidentLog['timeOfIncident'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Location: </span>
                    <span class="info-value">{{ $incidentLog['location'] ?? 'N/A' }}</span>
                    <span class="info-label">Incident Type: </span>
                    <span class="info-value">{{ $incidentLog['incidentType'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Description: </span>
                    <span class="info-value">{{ $incidentLog['description'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Persons Involved: </span>
                    <span class="info-value">{{ $incidentLog['personsInvolved'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Action Taken: </span>
                    <span class="info-value">{{ $incidentLog['actionTaken'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Reported By: </span>
                    <span class="info-value">{{ $incidentLog['reportedBy'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Uploaded By: </span>
                    <span class="info-value">{{ $incidentLog['uploadedBy'] ?? 'N/A' }}</span>
                    <span class="info-label">Form Submitted: </span>
                    <span class="info-value">{{ $incidentLog['formSubmitted'] ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Officer Signature</h2>
            <div class="section-content">
                <div class="info-row">
                    @if(isset($incidentLog['officerSignature']) && is_array($incidentLog['officerSignature']))
                    @foreach ($incidentLog['officerSignature'] as $file)
                    @if (isset($file['url']) && !empty($file['url']))
                    <img src="{{ storage_path($file['url']) }}" alt="Signature of officer "
                        style="max-width: 300px; height: auto; margin-bottom: 20px;">
                    @endif
                    @endforeach
                    @else
                    <p>No Signature uploaded.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>
</body>