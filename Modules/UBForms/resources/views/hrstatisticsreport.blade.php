<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Report PDF</title>
    <link rel="stylesheet" href="{{ public_path('./../../ub-api/Modules/UBForms/public/css/HRStyle.css') }}">
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
                    <p class="academic-year">Academic Year: {{$report['academicYearID']}}</p>
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
                <b>Report By: </b>{{ $user->name }}
            </div>
        </section>

        <section class="content">
            <h2>Number of Staff for the Academic Year under Review</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th rowspan="2">Faculties</th>
                        <th colspan="3">Staff</th>
                    </tr>
                    <tr>
                        <th>Full-time Faculty</th>
                        <th>Adjunct Faculty</th>
                        <th>Non-teaching Staff</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Education and Arts</td>
                        <td>{{ $report['numberOfStaff']['fulltimeFaculty']['educationAndArts'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['adjunctFaculty']['educationAndArts'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['nonTeachingStaff']['educationAndArts'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Management and Social Sciences</td>
                        <td>{{ $report['numberOfStaff']['fulltimeFaculty']['managementAndSocialSciences'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['adjunctFaculty']['managementAndSocialSciences'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['nonTeachingStaff']['managementAndSocialSciences'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Health Sciences</td>
                        <td>{{ $report['numberOfStaff']['fulltimeFaculty']['healthSciences'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['adjunctFaculty']['healthSciences'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['nonTeachingStaff']['healthSciences'] ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Science and Technology</td>
                        <td>{{ $report['numberOfStaff']['fulltimeFaculty']['scienceAndTechnology'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['adjunctFaculty']['scienceAndTechnology'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['nonTeachingStaff']['scienceAndTechnology'] ?? 0 }}</td>
                    </tr>
                    <!-- total Row -->
                    <tr class="total-row">
                        <td>Total</td>
                        <td>{{ $report['numberOfStaff']['fulltimeFaculty']['total'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['adjunctFaculty']['total'] ?? 0 }}</td>
                        <td>{{ $report['numberOfStaff']['nonTeachingStaff']['total'] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <footer class="footer">
            <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
        </footer>
    </div>
</body>

</html>