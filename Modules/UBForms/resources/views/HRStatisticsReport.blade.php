<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Report PDF</title>
    <link rel="stylesheet" href="{{ public_path('/css/HRStyle.css') }}">
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
                    <p class="academic-year">Academic Year: {{$report->academicYearID}}</p>
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
                        <td>{{ $report->numberOfStaff['FulltimeFaculty']['EducationAndArts'] }}</td>
                        <td>{{ $report->numberOfStaff['AdjunctFaculty']['EducationAndArts'] }}</td>
                        <td>{{ $report->numberOfStaff['NonTeachingStaff']['EducationAndArts'] }}</td>
                    </tr>
                    <tr>
                        <td>Management and Social Sciences</td>
                        <td>{{ $report->numberOfStaff['FulltimeFaculty']['ManagementAndSocialSciences'] }}</td>
                        <td>{{ $report->numberOfStaff['AdjunctFaculty']['ManagementAndSocialSciences'] }}</td>
                        <td>{{ $report->numberOfStaff['NonTeachingStaff']['ManagementAndSocialSciences'] }}</td>
                    </tr>
                    <tr>
                        <td>Health Sciences</td>
                        <td>{{ $report->numberOfStaff['FulltimeFaculty']['HealthSciences'] }}</td>
                        <td>{{ $report->numberOfStaff['AdjunctFaculty']['HealthSciences'] }}</td>
                        <td>{{ $report->numberOfStaff['NonTeachingStaff']['HealthSciences'] }}</td>
                    </tr>
                    <tr>
                        <td>Science and Technology</td>
                        <td>{{ $report->numberOfStaff['FulltimeFaculty']['ScienceAndTechnology'] }}</td>
                        <td>{{ $report->numberOfStaff['AdjunctFaculty']['ScienceAndTechnology'] }}</td>
                        <td>{{ $report->numberOfStaff['NonTeachingStaff']['ScienceAndTechnology'] }}</td>
                    </tr>
                    <!-- Total Row -->
                    <tr class="total-row">
                        <td>Total</td>
                        <td>{{ $report->numberOfStaff['FulltimeFaculty']['Total'] }}</td></td>
                        <td>{{ $report->numberOfStaff['AdjunctFaculty']['Total'] }}</td>
                        <td>{{ $report->numberOfStaff['NonTeachingStaff']['Total'] }}</td>
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
