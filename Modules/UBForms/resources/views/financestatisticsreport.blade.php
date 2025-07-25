<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Report PDF</title>
    <!-- <link rel="stylesheet" href="{{ public_path('./../../ub-api/Modules/UBForms/public/css/financeStyle.css') }}"> -->
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

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            border-bottom: 2px solid #7e317b;
            padding-bottom: 5px;
            background-color: #f4f4f4;
            /* Light grey background for section titles */
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            /* Collapse table borders */
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #dddddd;
            /* Light grey border for table cells */
            padding: 8px;
            /* Padding inside table cells */
            text-align: left;
            /* Align text to the left */
        }

        th {
            background-color: #f4f4f4;
            /* Light grey background for header cells */
            font-weight: bold;
            /* Bold text for table headers */
        }

        /* Alternate Row Colors */
        tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
            /* Light grey background for odd rows */
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
                    <img src="{{public_path('./../../ub-api/Modules/UBForms/public/images/UB-Logo.png')}}"
                        alt="University Logo">
                </div>
                <div class="header-text">
                    <h1>University of Belize Annual Report</h1>
                    <p class="academic-year">Academic Year 2024-2025</p>
                </div>
            </div>
        </div>

        <section class="content">
            <h2>Report Details</h2>
            <div>
                <b>Department:</b> {{ $report['department'] }}
            </div>
            <br>
            <div>
                <b>Report By: </b>{{ $report['name'] }}
            </div>
        </section>

        <div class="section-title">1. Finance - Income (Bz$)</div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Funding from Government of Belize (GoB)</td>
                    <td>{{ number_format($report['income']['fundingFromGoB'], 2) }}</td>
                </tr>
                <tr>
                    <td>Tuition Fees by Faculty</td>
                    <td>{{ number_format($report['income']['tuitionFees'], 2) }}</td>
                </tr>
                <tr>
                    <td>Contracts</td>
                    <td>{{ number_format($report['income']['contracts'], 2) }}</td>
                </tr>
                <tr>
                    <td>Research Grants</td>
                    <td>{{ number_format($report['income']['researchGrants'], 2) }}</td>
                </tr>
                <tr>
                    <td>Endowment and Investment Income</td>
                    <td>{{ number_format($report['income']['endowmentAndInvestmentIncome'], 2) }}</td>
                </tr>
                <tr>
                    <td>Other</td>
                    <td>{{ number_format($report['income']['other'], 2) }}</td>
                </tr>
                <tr>
                    <th>Total</th>
                    <th>{{ number_format($report['income']['total'], 2) }}</th>
                </tr>
            </tbody>
        </table>

        <div class="section-title">2. Finance – Expenditures (Bz$)</div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Teaching Staff Costs</td>
                    <td>{{ number_format($report['expenditure']['teachingStaffCosts'], 2) }}</td>
                </tr>
                <tr>
                    <td>Non-Teaching Staff Costs</td>
                    <td>{{ number_format($report['expenditure']['nonTeachingStaffCosts'], 2) }}</td>
                </tr>
                <tr>
                    <td>Administration Costs</td>
                    <td>{{ number_format($report['expenditure']['administrationCosts'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">3. Capital Expenditures (Bz$)</div>
        <div class="text-box">
            {{ $report['expenditure']['capitalExpenditures'] }}
        </div>

        <div class="section-title">4. Major Capital Expenditure Projects / Investments (buildings etc.)</div>
        <ul>
            @foreach (explode('; ', $report['expenditure']['capitalExpenditures']) as $project)
                <li>{{ $project }}</li>
            @endforeach
        </ul>

        <div class="section-title">5. Other Expenditures (Bz$)</div>
        <div class="text-box">
            {{ $report['expenditure']['otherExpenditures'] }}
        </div>

        <footer class="footer">
            <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
        </footer>
    </div>
</body>

</html>