<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Belize Key Statistics Report</title>
    <link rel="stylesheet" href="{{ public_path('/css/recordsStyle.css') }}">
</head>
<body>
    <div class="header">
            <div class="header-content">
                <div class="header-logo">
                    <img src="{{public_path('/images/UB-Logo.png')}}" alt="University Logo">
                </div>
                <div class="header-text">
                    <h1>University of Belize Annual Report</h1>
                    <p class="academic-year">Academic Year:{{ $report->academicYearID }}</p>
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
    <div class="section-title">1. Students Enrolment for the Academic Year under review</div>
        <table>
            <thead>
                <tr>
                    <th>Associate</th>
                    <th>Undergraduate</th>
                    <th>Graduate</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $report->currentStudentEnrollmentTrend['associates'] }}</td>
                    <td>{{ $report->currentStudentEnrollmentTrend['undergraduate'] }}</td>
                    <td>{{ $report->currentStudentEnrollmentTrend['graduate'] }}</td>
                    <td>{{ $report->currentStudentEnrollmentTrend['Total'] }}</td>
                </tr>
            </tbody>
        </table>

    <div class="section-title">2. Student Enrolment Trend (Academic Level)</div>
    <table>
        <thead>
            <tr>
                <th>Degree Program</th>
                @foreach ($report->studentEnrollmentTrend as $trend)
                    <th>{{ $trend['academicYear'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (['associate', 'undergraduate', 'graduate', 'other'] as $category)
                <tr>
                    <td>{{ ucfirst($category) }}</td>
                    @foreach ($report->studentEnrollmentTrend as $trend)
                        <td>{{ $trend[$category] }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                @foreach ($report->studentEnrollmentTrend as $trend)
                    <td>{{ $trend['Total'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    <div class="section-title">3. Student Enrolment Trend (Per Faculty)</div>
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                @foreach ($report->enrollmentTrendPerFaculty as $yearData)
                    <th>{{ $yearData['academicYear'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ([
                'educationAndArts' => 'Education and Arts',
                'managementAndSocialScience' => 'Management and Social Science',
                'healthScience' => 'Health Science',
                'scienceAndTechnology' => 'Science and Technology'
            ] as $facultyKey => $facultyName)
                <tr>
                    <td>{{ $facultyName }}</td>
                    @foreach ($report->enrollmentTrendPerFaculty as $yearData)
                        <td>{{ $yearData[$facultyKey] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
        
    <div class="section-title">4. Graduation Statistics</div>
        <table>
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th colspan="3">Academic Year 2021/2022</th>
                    <th colspan="3">Academic Year 2022/2023</th>
                    <th colspan="3">Academic Year 2023/2024</th>
                </tr>
                <tr>
                    <th></th>
                    <th>Associate</th>
                    <th>Bachelor</th>
                    <th>Honors*</th>
                    <th>Associate</th>
                    <th>Bachelor</th>
                    <th>Honors*</th>
                    <th>Associate</th>
                    <th>Bachelor</th>
                    <th>Honors*</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Prepare a mapping of faculties by academic year
                    $facultiesByYear = [];
                    foreach ($report->graduationStatistics as $yearData) {
                        $facultiesByYear[$yearData['academicYear']] = $yearData['faculties'];
                    }
                @endphp

                @foreach ($facultiesByYear['2021/2022'] as $faculty)
                    <tr>
                        <td>{{ $faculty['degree'] }}</td>

                        @foreach (['2021/2022', '2022/2023', '2023/2024'] as $year)
                            @php
                                $currentFaculty = collect($facultiesByYear[$year] ?? [])->firstWhere('degree', $faculty['degree']);
                            @endphp
                            <td>{{ $currentFaculty['Associates'] ?? '' }}</td>
                            <td>{{ $currentFaculty['Bachelors'] ?? '' }}</td>
                            <td>{{ $currentFaculty['Honors'] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    <div class="section-title">5. Graduates by Age</div>
    <table class="table-left-align">
        <tr>
            <td>{{ $report->graduates['GraduatesByAge'] }}</td>
        </tr>
    </table>

    <div class="section-title">6. Graduates by Districts</div>
    <table class="table-left-align">
        <tr>
            <td>{{ $report->graduates['GraduatesByDistrict'] }}</td>
        </tr>
    </table>
    
    <div class="section-title">7. Origin of Students</div>
    <table>
        <thead>
            <tr>
                <th>Location</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Belize</td>
                <td>{{ $report->studentOrigin['Belize'] }}</td>
            </tr>
            <tr>
                <td>Central American Countries</td>
                <td>{{ $report->studentOrigin['CentralAmericanCountries'] }}</td>
            </tr>
            <tr>
                <td>Other Countries</td>
                <td>{{ $report->studentOrigin['OtherCountries'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">8. Campus Statistics</div>
    <table>
        <thead>
            <tr>
                <th>Campus</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Belize City</td>
                <td>{{ $report->campusStatistics['BelizeCity'] }}</td>
            </tr>
            <tr>
                <td>Belmopan</td>
                <td>{{ $report->campusStatistics['Belmopan'] }}</td>
            </tr>
            <tr>
                <td>Punta Gorda</td>
                <td>{{ $report->campusStatistics['PuntaGorda'] }}</td>
            </tr>
            <tr>
                <td>Central Farm</td>
                <td>{{ $report->campusStatistics['CentralFarm'] }}</td>
            </tr>
            <tr>
                <td>Satellite Programs</td>
                <td>{{ $report->campusStatistics['SatellitePrograms'] }}</td>
            </tr>
        </tbody>
    </table>

    <footer class="footer">
        <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
    </footer>

</body>
</html>