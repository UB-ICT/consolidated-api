<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Records;
use App\Models\User;
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
    private function initializeReport(string $email){
        return $reportData = Records::create([
            'email' => $email,
            'academicYearID' => "2023-2024", //temporary
            'department' => "",
            'deadline' => "",
            'currentStudentEnrollmentTrend' => ['associates' => '', 'undergraduate' => '', 'graduate' => '','Total' => ''], //Added Total to the array
            'studentEnrollmentTrend' => Array(
              ['academicYear' => '2021/2022', 'associate' => '', 'undergraduate' => '', 'graduate' => '', 'other' => '','Total' => ''], //Added Total to the array
              ['academicYear' => '2022/2023', 'associate' => '', 'undergraduate' => '', 'graduate' => '', 'other' => '','Total' => ''], //Added Total to the array
              ['academicYear' => '2023/2024', 'associate' => '', 'undergraduate' => '', 'graduate' => '', 'other' => '','Total' => ''], //Added Total to the array
            ),
            'enrollmentTrendPerFaculty' => Array(
              ['academicYear' => '2021/2022', 'educationAndArts' => '', 'managementAndSocialScience' => '', 'healthScience' => '', 'scienceAndTechnology' => ''],
              ['academicYear' => '2022/2023', 'educationAndArts' => '', 'managementAndSocialScience' => '', 'healthScience' => '', 'scienceAndTechnology' => ''],
              ['academicYear' => '2023/2024', 'educationAndArts' => '', 'managementAndSocialScience' => '', 'healthScience' => '', 'scienceAndTechnology' => ''],
            ),
            'graduationStatistics'=> Array(
              [
              'academicYear' => "2021/2022",
              'faculties' => Array(
                [ 'degree' => 'Education and Arts', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ], //A little ocd about this part but its okay
                [ 'degree' => 'Management and Social Science', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Health Science', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Science and Technology', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
              )],
              [
              'academicYear' => "2022/2023",
              'faculties' => Array(
                [ 'degree' => 'Education and Arts', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Management and Social Science', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Health Science', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Science and Technology', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
              )],
              ['academicYear' => "2023/2024",
              'faculties' => Array(
                [ 'degree' => 'Education and Arts', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Management and Social Science', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Health Science', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
                [ 'degree' => 'Science and Technology', 'Associates' => '', 'Bachelors' => '', 'Honors' => '' ],
              )]
            ),
            'studentOrigin' => ['Belize' => '', 'CentralAmericanCountries' => '', 'OtherCountries' => ''], //7.Origin of Students 
            'campusStatistics' => ['BelizeCity' => '', 'Belmopan' => '', 'PuntaGorda' => '', 'CentralFarm' => '', 'SatellitePrograms' => ''], //8.Campus Statistics
            'graduates' => ['GraduatesByAge' => '', 'GraduatesByDistrict' => ''],//5 and 6 merged into one
            'formSubmitted' => false,
        ]);
    }

    public function initialize(Request $request){

        try{

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
        }catch(\Exception $e){
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

    public function store(Request $request){

        $data = $request->all(); //Adding this in the event things need to be validated later on    

        try{

            $reportData = Records::create([
                'academicYearID' => $data['academicYearID'],
                'department' => $data['department'],
                'deadline' => $data['deadline'],
                'currentStudentEnrollmentTrend' => $data['currentStudentEnrollmentTrend'],
                'studentEnrollmentTrend' => $data['studentEnrollmentTrend'],
                'enrollmentTrendPerFaculty' => $data['enrollmentTrendPerFaculty'],
                'graduationStatistics'=> $data['graduationStatistics'],
                'graduatesByAge' => $data['graduatesByAge'], //New
                'graduatesByDistricts' => $data['graduatesByDistricts'], //New
                'studentOrigin' => $data['studentOrigin'],
                'campusStatistics' => $data['campusStatistics'],
                'graduates' => $data['graduates'],
                'formSubmitted' => $data['formSubmitted']
            ]);

            $response = [
                'success' => true,
                'message' => "Records Statistics Report Created Successfully",
                'data' => [
                'reportID' => $reportData->_id
                ],            
            ]; 
            
        }catch(\Exception $e){
            // If an error occurs, create an error response
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,            
            ]; 
        }
        
        return response($response, 201);        

    }

    //Read

    public function getReport(Request $request, string $reportID){
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

    public function updateReport(Request $request){
        try {

            $data = $request->all();
            // $id = $request->input('reportID');

            // Retrieve data based on conditions (assuming $request has the id parameter)
            $report = Records::where('email', $data['email'])->first();

            //updated from git
            if ($report) {

                $report->academicYearID = $request->has('academicYearID') ? $data['academicYearID'] : $report->academicYearID;
                $report->department = $request->has('department') ? $data['department'] : $report->department;
                $report->deadline = $request->has('deadline') ? $data['deadline'] : $report->deadline;
                $report->currentStudentEnrollmentTrend = $request->has('currentStudentEnrollmentTrend') ? $data['currentStudentEnrollmentTrend'] : $report->currentStudentEnrollmentTrend;
                $report->studentEnrollmentTrend = $request->has('studentEnrollmentTrend') ? $data['studentEnrollmentTrend'] : $report->studentEnrollmentTrend;
                $report->enrollmentTrendPerFaculty = $request->has('enrollmentTrendPerFaculty') ? $data['enrollmentTrendPerFaculty'] : $report->enrollmentTrendPerFaculty;
                $report->graduationStatistics = $request->has('graduationStatistics') ? $data['graduationStatistics'] : $report->graduationStatistics;
                $report->studentOrigin = $request->has('studentOrigin') ? $data['studentOrigin'] : $report->studentOrigin;
                $report->campusStatistics = $request->has('campusStatistics') ? $data['campusStatistics'] : $report->campusStatistics;
                $report->graduates = $request->has('graduates') ? $data['graduates'] : $report->graduates;
                $report->formSubmitted = $request->has('formSubmitted') ? $data['formSubmitted'] : $report->formSubmitted;

                $report->save();
                    // Format success response
                $response = [
                    'success' => true,
                    'message' => 'Report data updated successfully',
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

    //Delete
    
    public function delReport(Request $request){
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

    public function getReportByUser(Request $request){
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

    public function generateRecordsPdf(Request $request, string $reportID){ //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Records::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // Get the user based on the email from the report
        $user = User::where('email', $report->email)->first();        

        // Generate PDF using data directly
        // $pdf = PDF::loadHTML($this->generateReportPdfHtml($report));
        $pdf = PDF::loadView('RecordsStatisticsReport', ['report' => $report, 'user' => $user])
                    ->setPaper('a4', 'landscape'); // Set the paper size to A4 and orientation to landscape

        // Return PDF as a response
        return $pdf->download('report_' . $report->id . '.pdf');
    }

    public function viewFacultyReport(Request $request, string $reportID){ //Look into this a little more

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
