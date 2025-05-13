<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Annual Report PDF</title>
    <link rel="stylesheet" href="{{ public_path('/css/staffStyle.css') }}">
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
                    <p class="academic-year">Academic Year: {{$report->academicYearID}} </p>
                </div>
            </div>
        </div>

        <section class="content">
            <h2>Report Details</h2>
            <div>
                <b>Division: Department, Centers/Institutes:</b> {{ $report->department }}
            </div>
            <br>
            <div>
                <b>Report By:</b> {{ $user->name }}
            </div>
            <br>
            <!-- <div> -->
                <!-- <b>Reports To:</b> {{ $report->reportsTo }}
            </div> -->
        </section>

        <section class="content">
            <h2>I. Mission Statement:</h2>
            <p>{{ $report->missionStatement }}</p>
        </section>

        <section class="content">
        <h2>II. Strategic Goals</h2>
            <p><b>Strategic Goals Under Review:</b> {{ $report->strategicGoals['strategicGoalsUnderReview'] }}</p>
            <p><b>Implementation Plans:</b> {{ $report->strategicGoals['implmentationPlans'] }}</p>
            <p><b>Plans to Achieve Not Completed Goals:</b> {{ $report->strategicGoals['plansToAchieveNotCompletedGoals'] }}</p>
            <p><b>Strategic Goals:</b> {{ $report->strategicGoals['strategicGoals'] }}</p>
         </section>
        <section class="content">
            <h2>III. Accomplishments for the Reporting Period</h2>
            <p><b>Accomplishment List:</b> {{ $report->accomplishments['accomplishmentList'] }}</p>
            <p><b>Accomplishment Advancement:</b> {{ $report->accomplishments['accomplishmentAdvancement'] }}</p>
            <p><b>Impactful Change:</b> {{ $report->accomplishments['impactfulChange'] }}</p>
            <p><b>Why:</b> {{ $report->accomplishments['why'] }}</p>
            <p><b>Applicable Opportunities:</b> {{ $report->accomplishments['applicableOpportunities'] }}</p>
        </section>

        <section class="content">
            <h2>IV. Research & Partnerships</h2>
            <p><b>External Funding:</b> {{ $report->researchPartnerships['externalFunding'] }}</p>
            <p><b>Research Publications:</b> {{ $report->researchPartnerships['researchPublications'] }}</p>
            <p><b>Partnership Agencies:</b> {{ $report->researchPartnerships['partnershipAgencies'] }}</p>
            <p><b>Scholarships:</b> {{ $report->researchPartnerships['scholarships'] }}</p>
        </section>

        <section class="content">
            <h2>V. Student Success</h2>
            <p><b>Student Learning:</b> {{ $report->studentSuccess['studentLearning'] }}</p>
            <p><b>Student Clubs:</b> {{ $report->studentSuccess['studentClubs'] }}</p>
            <p><b>Student 1:</b> {{ $report->studentSuccess['student1'] }} <b>Reason:</b> {{ $report->studentSuccess['reason1'] }}</p>
            <p><b>Student 2:</b> {{ $report->studentSuccess['student2'] }} <b>Reason:</b> {{ $report->studentSuccess['reason2'] }}</p>
            <p><b>Student 3:</b> {{ $report->studentSuccess['student3'] }} <b>Reason:</b> {{ $report->studentSuccess['reason3'] }}</p>
        </section>

        <section class="content">
        <h2>VI. Activities for the Year</h2>
        @foreach ($report->activities as $activity)
            <p><strong>Event Name:</strong> {{ $activity['eventName'] }}</p>
            <p><strong>Persons in Picture:</strong> {{ $activity['personsInPicture'] }}</p>
            <p><strong>Event Summary:</strong> {{ $activity['eventSummary'] }}</p>
            <p><strong>Event Month:</strong> {{ $activity['eventMonth'] }}</p>

            <!--<img src="{{ public_path('/photos/5d0E3Hl44oGmfbFLxAuF.png') }}" alt="Event Picture" style="max-width: 100%; height: auto;">-->
            
            <!--using public path for the dompdf generator to be able to generate pdfs properly-->
            @if (isset($activity['pictureURL']) && is_array($activity['pictureURL']))
                @foreach($activity['pictureURL'] as $pictureURL)
                    @if (isset($pictureURL['eventPicture']) && !empty($pictureURL['eventPicture']))
                        @php
                            $baseUrl = request()->getSchemeAndHttpHost();
                        @endphp
                        <img src="{{ public_path($pictureURL['eventPicture']) }}" alt="Event Picture" style="max-width: 100%; height: auto;">
                        <p><a href="{{ $baseUrl . $pictureURL['eventPicture'] }}"><b>Download Image</b></a></p>
                    @endif
                @endforeach
            @else
                <p>No pictures available for this event.</p>
            @endif
            <hr>
        @endforeach
        </section>

        <section class="content">
            <h2>VII. Administrative Department Data</h2>
            <p><b>Full-Time Staff:</b> {{ $report->administrativeData['fullTimeStaff'] }}</p>
            <p><b>Part-Time Staff:</b> {{ $report->administrativeData['partTimeStaff'] }}</p>
            <p><b>Significant Staff Changes:</b> {{ $report->administrativeData['significantStaffChanges'] }}</p>
        </section>

        <section class="content">
            <h2>VIII. Financial Budget</h2>
            <p><b>Funding Sources:</b> {{ $report->financialBudget['fundingSources'] }}</p>
            <p><b>Significant Budget Changes:</b> {{ $report->financialBudget['significantBudgetChanges'] }}</p>
        </section>

        <section class="content">
            <h2>IX. Division Meetings</h2>
            @if(isset($report->meetings) && count($report->meetings) > 0)
                @foreach ($report->meetings as $meeting)
                    <p><strong>Meeting Type:</strong> {{ $meeting['meetingType'] }}</p>
                    <p><strong>Meeting Date:</strong> {{ $meeting['meetingDate'] }}</p>

                    <!-- Handling meeting minutes URL -->
                    @if(isset($meeting['meetingMinutesURL']) && is_array($meeting['meetingMinutesURL']))
                        @foreach ($meeting['meetingMinutesURL'] as $minutesURL)
                            @if(isset($minutesURL['meetingURL']) && !empty($minutesURL['meetingURL']))
                                <!-- Display link to view or download meeting minutes -->
                                <p><strong>Meeting Minutes URL:</strong> 
                                    <a href="{{ $minutesURL['meetingURL'] }}">View Minutes</a>
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
            <h2>X. Other Comments</h2>
            <p>{{ $report->otherComments }}</p>
        </section>

        <footer class="footer">
            <p>&copy; 2024 University of Belize. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>
