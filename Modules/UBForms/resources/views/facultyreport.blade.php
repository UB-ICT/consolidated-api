<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Report PDF</title>
    <style>
        /* Basic Reset */
        html,
        body {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        /* Header Styling */
        .header {
            padding: 15px;
            background-color: #3d004a;
            color: #ffffff;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
        }

        .header-content {
            display: inline-block;
            text-align: left;
            vertical-align: middle;
        }

        .header-logo {
            display: inline-block;
            margin-right: 15px;
            vertical-align: middle;
        }

        .header-logo img {
            max-width: 100px;
            height: auto;
            margin-top: 10px;
        }

        .header-text {
            display: inline-block;
            vertical-align: middle;
        }

        .header-text h1 {
            font-size: 24px;
            margin: 0;
        }

        .header-text p {
            margin: 0;
            font-size: 16px;
            text-align: center;
        }

        .academic-year {
            font-size: 16px;
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }

        /* Content Styles */
        .content {
            padding: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .content h2 {
            color: #000000;
            font-size: 24px;
            border-bottom: 2px solid #7e317b;
            padding-bottom: 10px;
            margin-top: 0;
            background-color: rgb(255, 215, 0);
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

        .footer {
            text-align: center;
            padding: 15px;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }

        @media print {
            .container {
                background-color: #ffffff;
                box-shadow: none;
                padding: 10mm;
                margin: 0;
                border-radius: 0;
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
        <div class="header">
            <div class="header-content">
                <div class="header-logo">
                    <!-- <img src="{{public_path(path: './Modules/UBForms/public/images/UB-Logo.png')}}"
                        alt="University Logo"> -->
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
                <b>Report By: </b>{{ $report['name'] }}
            </div>
            <br>
            <div>
                <b>List all units/departments/centers/institutes within the Faculty:</b>
                {{ is_array($report['units']) ? implode(', ', $report['units']) : ($report['units'] ?? 'No data available') }}
            </div>
        </section>

        <section class="content">
            <h2>I. Faculty Mission Statement:</h2>
            <p>{{ $report['missionStatement'] ?? 'No data available' }}</p>
        </section>

        <section class="content">
            <h2>II. Strategic Initiatives & Goals</h2>
            @if(isset($report['strategicGoals']))
            @if(isset($report['strategicGoals']['previousAcademicYear']) && $report['strategicGoals']['previousAcademicYear'] != '')
            <p><b>Previous Academic Year Goals:</b> {{ $report['strategicGoals']['previousAcademicYear'] }}</p>
            @else
            <p>No data for previous academic year goals.</p>
            @endif
            @if(isset($report['strategicGoals']['plans']) && $report['strategicGoals']['plans'] != '')
            <p><b>Plans:</b> {{ $report['strategicGoals']['plans'] }}</p>
            @else
            <p>No plans reported.</p>
            @endif
            @if(isset($report['strategicGoals']['completionRate']) && $report['strategicGoals']['completionRate'] != '')
            <p><b>Completion Rate:</b> {{ $report['strategicGoals']['completionRate'] }}</p>
            @else
            <p>No completion rate reported.</p>
            @endif
            @else
            <p>No strategic goals reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>III. Accomplishments for the reporting period</h2>
            @if(isset($report['accomplishments']))
            @if(isset($report['accomplishments']['accomplishmentList']) && $report['accomplishments']['accomplishmentList'] != '')
            <p><b>Accomplishment List:</b> {{ $report['accomplishments']['accomplishmentList'] }}</p>
            @endif
            @if(isset($report['accomplishments']['accomplishmentAdvancement']) && $report['accomplishments']['accomplishmentAdvancement'] != '')
            <p><b>Accomplishment Advancement:</b> {{ $report['accomplishments']['accomplishmentAdvancement'] }}</p>
            @endif
            @if(isset($report['accomplishments']['mostImpactfulChange']) && $report['accomplishments']['mostImpactfulChange'] != '')
            <p><b>Most Impactful Change:</b> {{ $report['accomplishments']['mostImpactfulChange'] }}</p>
            @endif
            @if(isset($report['accomplishments']['why']) && $report['accomplishments']['why'] != '')
            <p><b>Why:</b> {{ $report['accomplishments']['why'] }}</p>
            @endif
            @if(isset($report['accomplishments']['applicableOpportunities']) && $report['accomplishments']['applicableOpportunities'] != '')
            <p><b>Applicable Opportunities:</b> {{ $report['accomplishments']['applicableOpportunities'] }}</p>
            @endif
            @else
            <p>No accomplishments reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>IV. Research & Partnerships</h2>
            @if(isset($report['researchPartnerships']))
            @if(isset($report['researchPartnerships']['externalFunding']) && $report['researchPartnerships']['externalFunding'] != '')
            <p><b>External Funding:</b> {{ $report['researchPartnerships']['externalFunding'] }}</p>
            @endif
            @if(isset($report['researchPartnerships']['researchPublications']) && $report['researchPartnerships']['researchPublications'] != '')
            <p><b>Research Publications:</b> {{ $report['researchPartnerships']['researchPublications'] }}</p>
            @endif
            @if(isset($report['researchPartnerships']['partnershipAgencies']) && $report['researchPartnerships']['partnershipAgencies'] != '')
            <p><b>Partnership Agencies:</b> {{ $report['researchPartnerships']['partnershipAgencies'] }}</p>
            @endif
            @if(isset($report['researchPartnerships']['scholarships']) && $report['researchPartnerships']['scholarships'] != '')
            <p><b>Scholarships:</b> {{ $report['researchPartnerships']['scholarships'] }}</p>
            @endif
            @else
            <p>No research partnerships reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>V. Number of New and Revised Academic Programs</h2>
            @if(isset($report['revisedAcademics']))
            @if(isset($report['revisedAcademics']['programsOffered']) && $report['revisedAcademics']['programsOffered'] != '')
            <p><b>Programs Offered:</b> {{ $report['revisedAcademics']['programsOffered'] }}</p>
            @endif
            @if(isset($report['revisedAcademics']['newProgrammesAdded']) && $report['revisedAcademics']['newProgrammesAdded'] != '')
            <p><b>New Programs Added:</b> {{ $report['revisedAcademics']['newProgrammesAdded'] }}</p>
            @endif
            @if(isset($report['revisedAcademics']['revisedPrograms']) && $report['revisedAcademics']['revisedPrograms'] != '')
            <p><b>Revised Programs:</b> {{ $report['revisedAcademics']['revisedPrograms'] }}</p>
            @endif
            @else
            <p>No revised course data reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>VI. courses</h2>
            @if(isset($report['courses']))
            @if(isset($report['courses']['totalNewCourses']) && $report['courses']['totalNewCourses'] != '')
            <p><b>Total New Courses:</b> {{ $report['courses']['totalNewCourses'] }}</p>
            @endif
            @if(isset($report['courses']['totalCoursesOnline']) && $report['courses']['totalCoursesOnline'] != '')
            <p><b>Total Courses Online:</b> {{ $report['courses']['totalCoursesOnline'] }}</p>
            @endif
            @if(isset($report['courses']['totalCourseFaceToFace']) && $report['courses']['totalCourseFaceToFace'] != '')
            <p><b>Total Course Face to Face:</b> {{ $report['courses']['totalCourseFaceToFace'] }}</p>
            @endif
            @else
            <p>No course data reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>VII. List Eliminated Academic Programs</h2>
            <p>{{ $report['eliminatedAcademicPrograms'] ?? 'No data available' }}</p>
        </section>

        <section class="content">
            <h2>VIII.Retention of Students</h2>
            @if(isset($report['retentionOfStudents']))
            @if(isset($report['retentionOfStudents']['currentStudents']) && $report['retentionOfStudents']['currentStudents'] != '')
            <p><b>Current Students:</b> {{ $report['retentionOfStudents']['currentStudents'] }}</p>
            @endif
            @if(isset($report['retentionOfStudents']['transferStudents']) && $report['retentionOfStudents']['transferStudents'] != '')
            <p><b>Transfer Students:</b> {{ $report['retentionOfStudents']['transferStudents'] }}</p>
            @endif
            @else
            <p>No retention data reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>IX. Student Internships</h2>
            <p>{{ $report['studentInternships'] ?? 'No data available' }}</p>
        </section>



        <section class="content">
            <h2>X.Degrees Conferred</h2>
            @if(isset($report['degreesConferred']))
            @if(isset($report['degreesConferred']['degreesConferredForMostRecentAcademicYear']) && $report['degreesConferred']['degreesConferredForMostRecentAcademicYear'] != '')
            <p><b>Degrees Conferred (Most Recent Academic Year):</b>
                {{ $report['degreesConferred']['degreesConferredForMostRecentAcademicYear'] }}
            </p>
            @endif
            @if(isset($report['degreesConferred']['degreesConferredForMostRecentAcademicYearPerDepartment']) && $report['degreesConferred']['degreesConferredForMostRecentAcademicYearPerDepartment'] != '')
            <p><b>Degrees Conferred (Per Department):</b>
                {{ $report['degreesConferred']['degreesConferredForMostRecentAcademicYearPerDepartment'] }}
            </p>
            @endif
            @else
            <p>No degrees conferred data reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>XI. Student Success</h2>
            @if(isset($report['studentSuccess']))
            @if(isset($report['studentSuccess']['studentLearning']) && $report['studentSuccess']['studentLearning'] != '')
            <p><b>Student Learning:</b> {{ $report['studentSuccess']['studentLearning'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['studentClubs']) && $report['studentSuccess']['studentClubs'] != '')
            <p><b>Student Clubs:</b> {{ $report['studentSuccess']['studentClubs'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['student1']) && $report['studentSuccess']['student1'] != '')
            <p><b>Student 1:</b> {{ $report['studentSuccess']['student1'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['reason1']) && $report['studentSuccess']['reason1'] != '')
            <p><b>Reason 1:</b> {{ $report['studentSuccess']['reason1'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['student2']) && $report['studentSuccess']['student2'] != '')
            <p><b>Student 2:</b> {{ $report['studentSuccess']['student2'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['reason2']) && $report['studentSuccess']['reason2'] != '')
            <p><b>Reason 2:</b> {{ $report['studentSuccess']['reason2'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['student3']) && $report['studentSuccess']['student3'] != '')
            <p><b>Student 3:</b> {{ $report['studentSuccess']['student3'] }}</p>
            @endif
            @if(isset($report['studentSuccess']['reason3']) && $report['studentSuccess']['reason3'] != '')
            <p><b>Reason 3:</b> {{ $report['studentSuccess']['reason3'] }}</p>
            @endif
            @else
            <p>No student success data reported.</p>
            @endif
        </section>


        <section class="content">
            <h2>XII. Activities for the Year</h2>
            @if(isset($report['activities']) && is_array($report['activities']))
            @foreach ($report['activities'] as $activity)
            @if(is_array($activity))
            <div class="activity">
                <p><strong>Event Name:</strong> {{ $activity['eventName'] ?? '' }}</p>
                <p><strong>Persons in Picture:</strong> {{ $activity['personsInPicture'] ?? '' }}</p>

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
            <h2>XIII. Administrative Department Data</h2>
            @if(isset($report['administrativeData']))
            @if(isset($report['administrativeData']['fullTimeStaff']) && $report['administrativeData']['fullTimeStaff'] != '')
            <p><b>Full Time Staff:</b> {{ $report['administrativeData']['fullTimeStaff'] }}</p>
            @endif
            @if(isset($report['administrativeData']['partTimeStaff']) && $report['administrativeData']['partTimeStaff'] != '')
            <p><b>Part Time Staff:</b> {{ $report['administrativeData']['partTimeStaff'] }}</p>
            @endif
            @if(isset($report['administrativeData']['significantStaffChanges']) && $report['administrativeData']['significantStaffChanges'] != '')
            <p><b>Significant Staff Changes:</b> {{ $report['administrativeData']['significantStaffChanges'] }}</p>
            @endif
            @else
            <p>No administrative department data reported.</p>
            @endif
        </section>

        <section class="content">
            <h2>XIV. Financial Budget</h2>
            @if(isset($report['financialBudget']))
            @if(isset($report['financialBudget']['fundingSources']) && $report['financialBudget']['fundingSources'] != '')
            <p><b>Funding Sources:</b> {{ $report['financialBudget']['fundingSources'] }}</p>
            @endif
            @if(isset($report['financialBudget']['impactfulChanges']) && $report['financialBudget']['impactfulChanges'] != '')
            <p><b>Impactful Changes:</b> {{ $report['financialBudget']['impactfulChanges'] }}</p>
            @endif
            @else
            <p>No financial budget data reported.</p>
            @endif
        </section>

        <!--made a change to how the blades handle meetings -->
        <section class="content">
            <h2>XVI. Faculty Meetings</h2>
            @if(isset($report['meetings']) && count($report['meetings']) > 0)
            @foreach ($report['meetings'] as $meeting)
            <p><strong>Meeting Type:</strong> {{ $meeting['meetingType'] }}</p>
            <p><strong>Meeting Date:</strong> {{ $meeting['meetingDate'] }}</p>

            <!-- Handling meeting minutes URL -->
            @if(isset($meeting['meetingMinutesURL']) && is_array($meeting['meetingMinutesURL']))
            @foreach ($meeting['meetingMinutesURL'] as $minutesURL)
            @if(isset($minutesURL['meetingURL']) && !empty($minutesURL['meetingURL']))

            {{ $minutesURL['meetingURL'] }}
            <p><strong>Meeting Minutes URL:</strong>
                <a href="http://localhost:3031/{{ $minutesURL['meetingURL'] }}">View Minutes</a>
            </p>
            @else
            <p>No meeting minutes available.</p>
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
            <h2>XVII. Other Comments</h2>
            <p>{{ $report['otherComments'] ?? 'No data available' }}</p>
        </section>

        <footer class="footer">
            &copy; 2024 University of Belize
        </footer>
    </div>
</body>

</html>