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
            <p class="case">Bomb Threat - {{ $bombReport['caseNumber'] ?? 'N/A' }}</p>
        </div>

        <!-- INCIDENT DETAILS -->
        <div class="section">
            <h2>Bomb Threat Details</h2>
            <div class="section-content">
                <div class="info-row">
                    <span class="info-label">Date: </span>
                    <span class="info-value">{{ $bombReport['date'] ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time Received: </span>
                    <span class="info-value">{{ $bombReport['timeReceived'] ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time Ended: </span>
                    <span class="info-value">{{ $bombReport['timeEnded'] ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Exact Wording: </span>
                    <span class="info-value">{{ $bombReport['exactWording'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- QUESTIONS ASKED -->
        <div class="section">
            <h2>Questions Asked</h2>
            <div class="section-content">

                <div class="info-row">
                    <span class="info-label">Bomb Location: </span>
                    <span class="info-value">{{ $bombReport['bombLocation'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">When Will It Go Off: </span>
                    <span class="info-value">{{ $bombReport['whenWillItGoOff'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">What Do They Look Like: </span>
                    <span class="info-value">{{ $bombReport['WhatDoesItLooksLike'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">What Kind Of Bomb: </span>
                    <span class="info-value">{{ $bombReport['whatKindOfBomb'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">What Will Make It Explode: </span>
                    <span class="info-value">{{ $bombReport['whatWillMakeItExplode'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Did You Place The Bomb: </span>
                    <span class="info-value">{{ $bombReport['didYouPlaceTheBomb'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Why: </span>
                    <span class="info-value">{{ $bombReport['why'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Name: </span>
                    <span class="info-value">{{ $bombReport['name'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Pay Phone: </span>
                    <span class="info-value">{{ $bombReport['payPhone'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Location: </span>
                    <span class="info-value">{{ $bombReport['location'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Phone Number: </span>
                    <span class="info-value">{{ $bombReport['phoneNumber'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Sex: </span>
                    <span class="info-value">{{ $bombReport['sex'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Race: </span>
                    <span class="info-value">{{ $bombReport['race'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Age: </span>
                    <span class="info-value">{{ $bombReport['age'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Caller's Voice: </span>
                    <span class="info-value">
                        {{ !empty($bombReport['callersVoice']) ? implode(', ', $bombReport['callersVoice']) : 'N/A' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Background Sounds: </span>
                    <span class="info-value">
                        {{ !empty($bombReport['backgroundSounds']) ? implode(', ', $bombReport['backgroundSounds']) : 'N/A' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Threat Language: </span>
                    <span class="info-value">
                        {{ !empty($bombReport['threatLanguage']) ? implode(', ', $bombReport['threatLanguage']) : 'N/A' }}
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Accent: </span>
                    <span class="info-value">
                        {{ !empty($bombReport['accent']) ? implode(', ', $bombReport['accent']) : 'N/A' }}
                    </span>
                </div>

                @if(!empty($bombReport['accentRegion']))
                <div class="info-row">
                    <span class="info-label">Accent Region: </span>
                    <span class="info-value">{{ $bombReport['accentRegion'] }}</span>
                </div>
                @endif

                <div class="info-row">
                    <span class="info-label">Additional Information: </span>
                    <span class="info-value">{{ $bombReport['additionalInformation'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Office Number Receive Calls: </span>
                    <span class="info-value">{{ $bombReport['officeNumberReceiveCalls'] ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Person Receive Calls: </span>
                    <span class="info-value">{{ $bombReport['personReceiveCalls'] ?? 'N/A' }}</span>
                </div>

            </div>
        </div>

    </div>
</body>

</html>