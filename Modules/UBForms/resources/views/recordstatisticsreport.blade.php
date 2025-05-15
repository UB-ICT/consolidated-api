<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University of Belize Key Statistics Report</title>
    <link rel="stylesheet" href="{{ public_path('./../../ub-api/Modules/UBForms/public/css/recordsStyle.css') }}">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="header-logo">
                <img src="{{public_path('./../../ub-api/Modules/UBForms/public/images/UB-Logo.png')}}" alt="University Logo">
            </div>
            <div class="header-text">
                <h1>University of Belize Annual Report</h1>
                <p class="academic-year">Academic Year: {{ $report->academicYearID ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
    <section class="content">
        <h2>Report Details</h2>
        <div>
            <b>Department:</b> {{ $report->department ?? 'N/A' }}
        </div>
        <br>
        <div>
            <b>Report By: </b>{{ auth()->user()->name ?? 'N/A' }}
        </div>
    </section>

    @if(isset($report->currentStudentEnrollmentTrend))
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
                <td>{{ $report->currentStudentEnrollmentTrend['associates'] ?? 0 }}</td>
                <td>{{ $report->currentStudentEnrollmentTrend['undergraduate'] ?? 0 }}</td>
                <td>{{ $report->currentStudentEnrollmentTrend['graduate'] ?? 0 }}</td>
                <td>{{ $report->currentStudentEnrollmentTrend['Total'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    @if(isset($report->studentEnrollmentTrend))
    <div class="section-title">2. Student Enrolment Trend (Academic Level)</div>
    <table>
        <thead>
            <tr>
                <th>Degree Program</th>
                @foreach (($report->studentEnrollmentTrend ?? []) as $trend)
                    <th>{{ $trend['academicYear'] ?? 'N/A' }}</th>

                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (['associate', 'undergraduate', 'graduate', 'other'] as $category)
                <tr>
                    <td>{{ ucfirst($category) }}</td>
                    @foreach (($report->studentEnrollmentTrend ?? []) as $trend)
                        <td>{{ $trend[$category] ?? 0 }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td><strong>Total</strong></td>
                @foreach (($report->studentEnrollmentTrend ?? []) as $trend)
                    <td>{{ $trend['Total'] ?? 0 }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    @endif

    @if(isset($report->enrollmentTrendPerFaculty))
    <div class="section-title">3. Student Enrolment Trend (Per Faculty)</div>
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                @foreach (($report->enrollmentTrendPerFaculty ?? []) as $yearData)
                    <th>{{ $yearData['academicYear'] ?? 'N/A' }}</th>
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
                    @foreach (($report->enrollmentTrendPerFaculty ?? []) as $yearData)
                        <td>{{ $yearData[$facultyKey] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
        
    <!-- @if(isset($report->graduationStatistics))
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
                foreach (($report->graduationStatistics ?? []) as $yearData) {
                    if (isset($yearData['academicYear'])) {
                        $facultiesByYear[$yearData['academicYear']] = $yearData['faculties'] ?? [];
                    }
                }
            @endphp

            @if(isset($facultiesByYear['2021/2022']))
                @foreach (($facultiesByYear['2021/2022'] ?? []) as $faculty)
                    <tr>
                        <td>{{ $faculty['degree'] ?? 'N/A' }}</td>

                        @foreach (['2021/2022', '2022/2023', '2023/2024'] as $year)
                            @php
                                $currentFaculty = collect($facultiesByYear[$year] ?? [])->firstWhere('degree', $faculty['degree'] ?? '');
                            @endphp
                            <td>{{ $currentFaculty['Associates'] ?? '' }}</td>
                            <td>{{ $currentFaculty['Bachelors'] ?? '' }}</td>
                            <td>{{ $currentFaculty['Honors'] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    @endif -->
    @if(isset($report->graduationStatistics))
    <div class="section-title">4. Graduation Statistics</div>
    <table>
        <thead>
            <tr>
                <th>Faculty</th>
                @foreach (['2021/2022', '2022/2023', '2023/2024'] as $year)
                    <th>{{ $year['academicYear'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($report->graduationStatistics as $faculty)
                <tr>
                    <td>{{ $faculty['degree'] ?? 'N/A' }}</td>
                    <td>{{ $faculty['Associates'] ?? 0 }}</td>
                    <td>{{ $faculty['Bachelors'] ?? 0 }}</td>
                    <td>{{ $faculty['Honors'] ?? 0 }}</td>
                </tr>
                
            @endforeach
        </tbody>
    </table>

    @if(isset($report->graduates['GraduatesByAge']))
    <div class="section-title">5. Graduates by Age</div>
    <table class="table-left-align">
        <tr>
            <td>{{ $report->graduates['GraduatesByAge'] ?? 'N/A' }}</td>
        </tr>
    </table>
    @endif

    @if(isset($report->graduates['GraduatesByDistrict']))
    <div class="section-title">6. Graduates by Districts</div>
    <table class="table-left-align">
        <tr>
            <td>{{ $report->graduates['GraduatesByDistrict'] ?? 'N/A' }}</td>
        </tr>
    </table>
    @endif
    
    @if(isset($report->studentOrigin))
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
                <td>{{ $report->studentOrigin['Belize'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Central American Countries</td>
                <td>{{ $report->studentOrigin['CentralAmericanCountries'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Other Countries</td>
                <td>{{ $report->studentOrigin['OtherCountries'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    @if(isset($report->campusStatistics))
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
                <td>{{ $report->campusStatistics['BelizeCity'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Belmopan</td>
                <td>{{ $report->campusStatistics['Belmopan'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Punta Gorda</td>
                <td>{{ $report->campusStatistics['PuntaGorda'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Central Farm</td>
                <td>{{ $report->campusStatistics['CentralFarm'] ?? 0 }}</td>
            </tr>
            <tr>
                <td>Satellite Programs</td>
                <td>{{ $report->campusStatistics['SatellitePrograms'] ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <footer class="footer">
        <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
    </footer>
</body>
</html>