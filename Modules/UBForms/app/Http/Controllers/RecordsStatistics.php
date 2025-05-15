<?php

namespace Modules\UBForms\Http\Controllers;

use Illuminate\Http\Request;
use Modules\UBForms\Models\Records;
use Modules\UBForms\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

/*
This is the Records Statistics Controller responsible for doing 5 functions. 

The function initialize() creates the fields in mongo db for all  annual reports 
that are to be submitted by Staff.

The function store(), is similar but you would need to pass in all the fields with the 
information to properly create it in the database. 

The function getReport() retrieves the report based on the report ID. 

The function delReport() deleted the report based on the report ID. 

The last function updateReport() updates a report, it only updates the fields that are passed. 


Author: SW

*/

class RecordsStatistics extends Controller
{
    //Initialize function
    //Updated from Github
    private function initializeReport(string $email)
    {
        return $reportData = Records::create([
            'email' => $email,
            'academicYearID' => "2023-2024", //temporary
            'department' => "",
            'deadline' => "",
            'currentStudentEnrollmentTrend' => ['associates' => 0, 'undergraduate' => 0, 'graduate' => 0, 'Total' => 0],
            'studentEnrollmentTrend' => array(
                ['academicYear' => '2021/2022', 'associate' => 0, 'undergraduate' => 0, 'graduate' => 0, 'other' => 0, 'Total' => 0],
                ['academicYear' => '2022/2023', 'associate' => 0, 'undergraduate' => 0, 'graduate' => 0, 'other' => 0, 'Total' => 0],
                ['academicYear' => '2023/2024', 'associate' => 0, 'undergraduate' => 0, 'graduate' => 0, 'other' => 0, 'Total' => 0],
            ),
            'enrollmentTrendPerFaculty' => array(
                ['academicYear' => '2021/2022', 'educationAndArts' => 0, 'managementAndSocialScience' => 0, 'healthScience' => 0, 'scienceAndTechnology' => 0],
                ['academicYear' => '2022/2023', 'educationAndArts' => 0, 'managementAndSocialScience' => 0, 'healthScience' => 0, 'scienceAndTechnology' => 0],
                ['academicYear' => '2023/2024', 'educationAndArts' => 0, 'managementAndSocialScience' => 0, 'healthScience' => 0, 'scienceAndTechnology' => 0],
            ),
            'graduationStatistics' => array(
                [
                    'academicYear' => "2021/2022",
                    'faculties' => array(
                        ['degree' => 'Education and Arts', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0], //A little ocd about this part but its okay
                        ['degree' => 'Management and Social Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Health Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Science and Technology', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                    )
                ],
                [
                    'academicYear' => "2022/2023",
                    'faculties' => array(
                        ['degree' => 'Education and Arts', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Management and Social Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Health Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Science and Technology', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                    )
                ],
                [
                    'academicYear' => "2023/2024",
                    'faculties' => array(
                        ['degree' => 'Education and Arts', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Management and Social Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Health Science', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                        ['degree' => 'Science and Technology', 'Associates' => 0, 'Bachelors' => 0, 'Honors' => 0],
                    )
                ]
            ),
            'studentOrigin' => ['Belize' => 0, 'CentralAmericanCountries' => 0, 'OtherCountries' => 0], //7.Origin of Students 
            'campusStatistics' => ['BelizeCity' => 0, 'Belmopan' => 0, 'PuntaGorda' => 0, 'CentralFarm' => 0, 'SatellitePrograms' => 0], //8.Campus Statistics
            'graduates' => ['graduatesByAge' => 0, 'graduatesByDistrict' => 0],//5 and 6 merged into one
            'formSubmitted' => false,
        ]);
    }

    public function initialize(Request $request)
    {

        try {

            $data = $request->all(); //Adding this in the event things need to be validated later on  

            $user = $request->user();

            $reportData = $this->initializeReport($user->email);

            $response = [
                'success' => true,
                'message' => "Initialization Successfull",
                'data' => [
                    'reportID' => $reportData->_id
                ],
            ];
        } catch (\Exception $e) {
            // If an error occurs, create an error response
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        return response($response, 201);

    }

    //Create

    public function store(Request $request)
    {
        $data = $request->all();

        try {
            // Ensure currentStudentEnrollmentTrend has Total
            if (isset($data['currentStudentEnrollmentTrend'])) {
                $data['currentStudentEnrollmentTrend']['Total'] = $data['currentStudentEnrollmentTrend']['Total'] ??
                    ((int) ($data['currentStudentEnrollmentTrend']['associates'] ?? 0) +
                        (int) ($data['currentStudentEnrollmentTrend']['undergraduate'] ?? 0) +
                        (int) ($data['currentStudentEnrollmentTrend']['graduate'] ?? 0));
            }

            if (isset($data['studentEnrollmentTrend'])) {
                foreach ($data['studentEnrollmentTrend'] as &$trend) {
                    $trend['Total'] = $trend['Total'] ??
                        ((int) ($trend['associate'] ?? 0) +
                            (int) ($trend['undergraduate'] ?? 0) +
                            (int) ($trend['graduate'] ?? 0) +
                            (int) ($trend['other'] ?? 0));
                }
            }

            $reportData = Records::create([
                'academicYearID' => $data['academicYearID'],
                'department' => $data['department'],
                'deadline' => $data['deadline'],
                'currentStudentEnrollmentTrend' => $data['currentStudentEnrollmentTrend'],
                'studentEnrollmentTrend' => $data['studentEnrollmentTrend'],
                'enrollmentTrendPerFaculty' => $data['enrollmentTrendPerFaculty'],
                'graduationStatistics' => $data['graduationStatistics'],
                'studentOrigin' => $data['studentOrigin'],
                'campusStatistics' => $data['campusStatistics'],
                'graduates' => $data['graduates'],
                'formSubmitted' => $data['formSubmitted']
            ]);

            $response = [
                'success' => true,
                'message' => "Records Statistics Report Created Successfully",
                'data' => ['reportID' => $reportData->_id],
            ];

        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }

        return response($response, 201);
    }

    //Read

    public function getReport(Request $request, string $reportID)
    {
        try {

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Records::where('_id', $reportID)->first();

            if ($report) {
                // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'reportData' => $report
                    ]
                ];
            } else {
                // Report not found
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);

    }

    //Update

    public function updateReport(Request $request)
    {
        try {
            $data = $request->all();

            $report = Records::where('email', $data['email'])->first();

            if ($report) {
                // Update currentStudentEnrollmentTrend with Total if it exists in request
                if ($request->has('currentStudentEnrollmentTrend')) {
                    $currentTrend = $data['currentStudentEnrollmentTrend'];
                    $currentTrend['Total'] = $currentTrend['Total'] ??
                        (($currentTrend['associates'] ?? 0) +
                            ($currentTrend['undergraduate'] ?? 0) +
                            ($currentTrend['graduate'] ?? 0));
                    $report->currentStudentEnrollmentTrend = $currentTrend;
                }

                // Update studentEnrollmentTrend with Totals if it exists in request
                if ($request->has('studentEnrollmentTrend')) {
                    $trends = $data['studentEnrollmentTrend'];
                    foreach ($trends as &$trend) {
                        $trend['Total'] = $trend['Total'] ??
                            (($trend['associate'] ?? 0) +
                                ($trend['undergraduate'] ?? 0) +
                                ($trend['graduate'] ?? 0) +
                                ($trend['other'] ?? 0));
                    }
                    $report->studentEnrollmentTrend = $trends;
                }

                // Update other fields
                $report->academicYearID = $request->has('academicYearID') ? $data['academicYearID'] : $report->academicYearID;
                $report->department = $request->has('department') ? $data['department'] : $report->department;
                $report->deadline = $request->has('deadline') ? $data['deadline'] : $report->deadline;
                $report->enrollmentTrendPerFaculty = $request->has('enrollmentTrendPerFaculty') ? $data['enrollmentTrendPerFaculty'] : $report->enrollmentTrendPerFaculty;
                $report->graduationStatistics = $request->has('graduationStatistics') ? $data['graduationStatistics'] : $report->graduationStatistics;
                $report->studentOrigin = $request->has('studentOrigin') ? $data['studentOrigin'] : $report->studentOrigin;
                $report->campusStatistics = $request->has('campusStatistics') ? $data['campusStatistics'] : $report->campusStatistics;
                $report->graduates = $request->has('graduates') ? $data['graduates'] : $report->graduates;
                $report->formSubmitted = $request->has('formSubmitted') ? $data['formSubmitted'] : $report->formSubmitted;

                $report->save();

                $response = [
                    'success' => true,
                    'message' => 'Report data updated successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    //Delete

    public function delReport(Request $request)
    {
        try {

            // $data = $request->all();
            $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Records::where('_id', $id)->first();

            if ($report) {

                $report->delete();
                // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data deleted successfully',
                    'data' => null
                ];
            } else {
                // Report not found
                $response = [
                    'success' => false,
                    'message' => 'Report not found',
                    'data' => null
                ];
            }

        } catch (\Exception $e) {
            // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);

    }

    public function getReportByUser(Request $request)
    {
        try {

            // $data = $request->all();
            // $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)

            $user = $request->user();

            $report = Records::where('email', $user->email)->first();

            if ($report) {
                // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data found successfully',
                    'data' => [
                        'reportData' => $report
                    ]
                ];
            } else {
                $report = $this->initializeReport($user->email);

                // Report not found
                $response = [
                    'success' => true,
                    'message' => 'Report Initialized.',
                    'data' => [
                        'reportData' => $report
                    ],
                ];
            }

        } catch (\Exception $e) {
            // Exception occurred
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        // Return response with HTTP status code 201 (Created)
        return response($response, 200);

    }

    public function generateRecordsPdf(Request $request, string $reportID)
    { //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Records::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // Get the user based on the email from the report
        $user = User::where('email', $report->email)->first();

        // Generate PDF using data directly
        $pdf = PDF::loadView('UBForms::recordstatisticsreport', ['report' => $report, 'user' => $user])
            ->setPaper('a4', 'landscape');

        // Return PDF as a response
        return $pdf->download('report_' . $report->id . '.pdf');
    }

    public function viewFacultyReport(Request $request, string $reportID)
    { //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Records::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $user = User::where('email', $report->email)->first();

        return view('RecordsStatisticsReport', ['report' => $report, 'user' => $user]);

    }


}
