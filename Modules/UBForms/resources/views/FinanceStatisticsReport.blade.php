<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Report PDF</title>
    <link rel="stylesheet" href="{{ public_path('/css/financeStyle.css') }}">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-logo">
                    <img src="{{public_path('/images/UB-Logo.png')}}" alt="University Logo">
                </div>
                <div class="header-text">
                    <h1>University of Belize Annual Report</h1>
                    <p class="academic-year">Academic Year 2023-2024</p>
                </div>
            </div>
        </div>

        <section class="content">
            <h2>Report Details</h2>
            <div>
                <b>Department:</b> {{ $report->department }}
            </div>
            <br>
            <div>
                <b>Report By: </b>{{ $user->name }}
            </div>
        </section>

        <div class="section-title">1. Finance – Income (Bz$)</div>
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
                    <td>{{ number_format($report->income['fundingFromGoB'], 2) }}</td>
                </tr>
                <tr>
                    <td>Tuition Fees by Faculty</td>
                    <td>{{ number_format($report->income['tuitionFees'], 2) }}</td>
                </tr>
                <tr>
                    <td>Contracts</td>
                    <td>{{ number_format($report->income['contracts'], 2) }}</td>
                </tr>
                <tr>
                    <td>Research Grants</td>
                    <td>{{ number_format($report->income['researchGrants'], 2) }}</td>
                </tr>
                <tr>
                    <td>Endowment and Investment Income</td>
                    <td>{{ number_format($report->income['endowmentAndInvestmentIncome'], 2) }}</td>
                </tr>
                <tr>
                    <td>Other</td>
                    <td>{{ number_format($report->income['other'], 2) }}</td>
                </tr>
                <tr>
                    <th>Total</th>
                    <th>{{ number_format($report->income['total'], 2) }}</th>
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
                    <td>{{ number_format($report->expenditure['teachingStaffCosts'], 2) }}</td>
                </tr>
                <tr>
                    <td>Non-Teaching Staff Costs</td>
                    <td>{{ number_format($report->expenditure['nonTeachingStaffCosts'], 2) }}</td>
                </tr>
                <tr>
                    <td>Administration Costs</td>
                    <td>{{ number_format($report->expenditure['administrationCosts'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">3. Capital Expenditures (Bz$)</div>
        <div class="text-box">
            {{ $report->expenditure['capitalExpenditures'] }}
        </div>

        <div class="section-title">4. Major Capital Expenditure Projects / Investments (buildings etc.)</div>
        <ul>
            @foreach (explode('; ', $report->expenditure['capitalExpenditures']) as $project)
                <li>{{ $project }}</li>
            @endforeach
        </ul>

        <div class="section-title">5. Other Expenditures (Bz$)</div>
        <div class="text-box">
            {{ $report->expenditure['otherExpenditures'] }}
        </div>

        <footer class="footer">
            <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>
