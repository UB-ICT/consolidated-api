<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Non-Academic Report PDF</title>
    <!-- <link rel="stylesheet" href="{{ public_path('./../../ub-api/Modules/UBForms/public/css/staffStyle.css') }}"> -->
    <style>
        /* Basic Reset */
        html,
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            /* Ensure padding is included in width */
        }

        /* Background for PDF */
        body {
            color: #333333;
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        /* Page Container */
        .container {
            width: 100%;
        }

        /* New header Styling */
        .header {
            padding: 15px;
            /* Increased padding to ensure enough space */
            background-color: #3d004a;
            /* Purple background for the header */
            color: #ffffff;
            /* White text color */
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            /* Clear floats */
            text-align: center;
            /* Center content in the header */
        }

        /* Centering the container for logo and text */
        .header-content {
            display: inline-block;
            /* Ensure it only takes up as much space as needed */
            text-align: left;
            /* Align items inside this container to the left */
            vertical-align: middle;
            /* Align vertically with surrounding content */
        }

        /* Header Logo Styling */
        .header-logo {
            display: inline-block;
            /* Keep logo inline with the text */
            margin-right: 15px;
            /* Space between the logo and the text */
            vertical-align: middle;
            /* Align vertically with the text */
        }

        .header-logo img {
            max-width: 100px;
            /* Adjust logo size */
            height: auto;
            /* Maintain aspect ratio */
            margin-top: 10px;
            /* Adjust as needed to align with text */
        }

        /* Header Text Styling */
        .header-text {
            display: inline-block;
            /* Ensure text is inline with the logo */
            vertical-align: middle;
            /* Align vertically with the logo */
        }

        .header-text h1 {
            font-size: 24px;
            margin: 0;
        }

        .header-text p {
            margin: 0;
            font-size: 16px;
            text-align: center;
            /* Center text for alignment */
        }

        /* Academic Year Styling */
        .academic-year {
            font-size: 16px;
            font-weight: bold;
            display: block;
            /* Ensure it takes full width for centering */
            margin-top: 5px;
            /* Space between the h1 and the p */
        }

        /* Content Styles */
        .content {
            padding: 10px;
            background-color: #ffffff;
            /* White background for the content */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .content h2 {
            color: #000000;
            /* Changed to black */
            font-size: 24px;
            border-bottom: 2px solid #7e317b;
            padding-bottom: 10px;
            margin-top: 0;
            background-color: gold;
            /* Gold background color */
        }

        .content p {
            margin: 10px 0;
        }

        .content p strong {
            color: #333333;
        }

        .section {
            margin-bottom: 20px;
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        /* Print-Specific Styles */
        @media print {
            .container {
                background-color: #ffffff;
                /* White background for the content area */
                box-shadow: none;
                /* Remove shadow for print */
                padding: 10mm;
                /* Adjust padding for print if needed */
                margin: 0;
                /* Remove margin for print */
                border-radius: 0;
                /* Remove border radius for print */
            }
        }

        /* Dompdf Specific Page Setup */
        @page {
            size: A4;
            margin: 20mm;
            /* Adjust margins as needed */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-logo">
                    <!-- <img src="{{public_path('./../../ub-api/Modules/UBForms/public/images/UB-Logo.png')}}"
                        alt="University Logo"> -->
                </div>
                <div class="header-text">
                    <h1>University of Belize Annual Report</h1>
                    <p class="academic-year">Academic Year: {{$report['academicYearID']}} </p>
                </div>
            </div>
        </div>

        <section class="content">
            <h2>Report Details</h2>
            <div>
                <b>Division: Department, Centers/Institutes:</b> {{ $report['department'] }}
            </div>
            <br>
            <div>
                <b>Report By: </b>{{ $report['name'] ?? 'N/A' }}
            </div>
            <br>
    </div>
    </section>

    <section class="content">
        <h2>I. Mission Statement:</h2>
        <p>{{ $report['missionStatement'] }}</p>
    </section>

    <section class="content">
        <h2>II. Strategic Goals</h2>
        @if(isset($report['strategicGoals']))
        <p><b>Strategic Goals Under Review:</b> {{ $report['strategicGoals']['strategicGoalsUnderReview'] ?? 'N/A' }}
        </p>
        <p><b>Implementation Plans:</b> {{ $report['strategicGoals']['implmentationPlans'] ?? 'N/A' }}</p>
        <p><b>Plans to Achieve Not Completed Goals:</b>
            {{ $report['strategicGoals']['plansToAchieveNotCompletedGoals'] ?? 'N/A' }}
        </p>
        <p><b>Strategic Goals:</b> {{ $report['strategicGoals']['strategicGoals'] ?? 'N/A' }}</p>
        @else
        <p>No strategic goals data available.</p>
        @endif
    </section>

    <section class="content avoid-break">
        <h2>III. Accomplishments for the Reporting Period</h2>
        @if(isset($report['accomplishments']) && is_array($report['accomplishments']))
        <p><b>Accomplishment List:</b> {{ $report['accomplishments']['accomplishmentList'] ?? 'N/A' }}</p>
        <p><b>Accomplishment Advancement:</b> {{ $report['accomplishments']['accomplishmentAdvancement'] ?? 'N/A' }}
        </p>
        <p><b>Impactful Change:</b> {{ $report['accomplishments']['impactfulChange'] ?? 'N/A' }}</p>
        <p><b>Why:</b> {{ $report['accomplishments']['why'] ?? 'N/A' }}</p>
        <p><b>Applicable Opportunities:</b> {{ $report['accomplishments']['applicableOpportunities'] ?? 'N/A' }}</p>
        @else
        <p>No accomplishments data available.</p>
        @endif
    </section>

    <section class="content">
        <h2>IV. Research & Partnerships</h2>
        <p><b>External Funding:</b> {{ $report['researchPartnerships']['externalFunding'] }}</p>
        <p><b>Research Publications:</b> {{ $report['researchPartnerships']['researchPublications'] }}</p>
        <p><b>Partnership Agencies:</b> {{ $report['researchPartnerships']['partnershipAgencies'] }}</p>
        <p><b>Scholarships:</b> {{ $report['researchPartnerships']['scholarships'] }}</p>
    </section>

    <section class="content">
        <h2>V. Student Success</h2>
        <p><b>a. List of Clubs</b> {{ $report['studentSuccess']['studentClubs'] }}</p>
        <p><b>b. State results of any student surveys at UB, including surveys on student success, student satisfaction, etc.</b> {{ $report['studentSuccess']['studentsurveys'] }}</p>
        <p><b>c. All new initiatives at UB regarding student success.
            </b> {{ $report['studentSuccess']['initiatives'] }}
    </section>

    <section class="content">
        <h2>XII. Activities for the Year</h2>
        @if(isset($report['activities']) && is_array($report['activities']))
        @foreach ($report['activities'] as $activity)
        @if(is_array($activity))
        <div class="activity">
            <p><strong>Event Name:</strong> {{ $activity['eventName'] ?? '' }}</p>
            <p><strong>Persons in Picture:</strong> {{ $activity['personsInPicture'] ?? '' }}</p>

            <br><br>
            @if(isset($activity['pictureURL']) && is_array($activity['pictureURL']))
            @foreach ($activity['pictureURL'] as $picture)
            @if(isset($picture['eventPicture']))

            <img src="{{ storage_path($picture['eventPicture']) }}"
                alt="Event Picture"
                style="max-width: 200px; height: auto;">
            @endif
            @endforeach
            @endif
        </div>
        <hr>
        @endif
        @endforeach
        @else
        <p>No activities reported.</p>
        @endif
    </section>

    <section class="content">
        <h2>VII. Administrative Department Data</h2>
        <p><b>Full-Time Staff:</b> {{ $report['administrativeData']['fullTimeStaff'] }}</p>
        <p><b>Part-Time Staff:</b> {{ $report['administrativeData']['partTimeStaff'] }}</p>
        <p><b>Significant Staff Changes:</b> {{ $report['administrativeData']['significantStaffChanges'] }}</p>
    </section>

    <section class="content">
        <h2>VIII. Financial Budget</h2>
        <p><b>Funding Sources:</b> {{ $report['financialBudget']['fundingSources'] }}</p>
        <p><b>Significant Budget Changes:</b> {{ $report['financialBudget']['significantBudgetChanges'] }}</p>
    </section>

    <section class="content">
        <h2>IX. Division Meetings</h2>
        @if(isset($report['meetings']) && count($report['meetings']) > 0)
        @foreach ($report['meetings'] as $meeting)
        <p><strong>Meeting Type:</strong> {{ $meeting['meetingType'] }}</p>
        <p><strong>Meeting Date:</strong> {{ $meeting['meetingDate'] }}</p>

        <!-- Meeting minutes will be merged into the main PDF -->
        @if(isset($meeting['meetingMinutesURL']) && is_array($meeting['meetingMinutesURL']) && count($meeting['meetingMinutesURL']) > 0)
        <p><strong>Meeting Minutes:</strong> Included in this report</p>

        <!-- Meeting PDFs will be merged into the final PDF -->
        @foreach ($meeting['meetingMinutesURL'] as $minutesURL)
        @if(isset($minutesURL['meetingURL']) && !empty($minutesURL['meetingURL']))
        @php
        $fileName = basename($minutesURL['meetingURL']);
        $filePath = storage_path('app/private/uploads/meetings/' . $fileName);
        $fileExists = file_exists($filePath);
        @endphp
        @if($fileExists)
        <div style="margin: 10px 0; padding: 10px; background-color: #e8f5e8; border: 1px solid #4caf50; border-radius: 4px;">
            <p><strong>✓ Meeting Minutes:</strong> {{ $fileName }} ({{ number_format(filesize($filePath)) }} bytes)</p>
            <p><em>This meeting PDF will be merged into the final report.</em></p>
        </div>
        @else
        <div style="margin: 10px 0; padding: 10px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
            <p><strong>⚠ Meeting Minutes:</strong> {{ $fileName }}</p>
            <p><em>File not found - will not be included in the final report.</em></p>
        </div>
        @endif
        @endif
        @endforeach
        @else
        <p>No meeting minutes available.</p>
        @endif
        <hr>
        @endforeach
        @else
        <p>No faculty meetings reported for this year.</p>
        @endif
    </section>

    <section class="content">
        <h2>X. Other Comments</h2>
        <p>{{ $report['otherComments'] }}</p>
    </section>

    <footer class="footer">
        <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
    </footer>
    </div>
</body>

</html>