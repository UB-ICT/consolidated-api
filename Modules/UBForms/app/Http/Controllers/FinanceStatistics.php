<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Finance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

/*
This is the Finance Statistics Controller responsible for doing 5 functions. 

The function initialize() creates the fields in mongo db for all  annual reports 
that are to be submitted by Staff.

The function store(), is similar but you would need to pass in all the fields with the 
information to properly create it in the database. 

The function getReport() retrieves the report based on the report ID. 

The function delReport() deleted the report based on the report ID. 

The last function updateReport() updates a report, it only updates the fields that are passed. 


Author: SW

*/

class FinanceStatistics extends Controller
{
    //Initialize 

    private function initializeReport(string $email){
        return $reportData = Finance::create([
            'email' => $email,
            'academicYearID' => "2023-2024",
            'department' => "",
            'deadline' => "",
            'income' => ['fundingFromGoB' => '', 'tuitionFees' => '', 'contracts' => '', 'researchGrants' => '', 'endowmentAndInvestmentIncome' => '', 'other' => '', 'total' => ''],
            'expenditure' => ['teachingStaffCosts' => '', 'nonTeachingStaffCosts' => '', 'administrationCosts' => '', 'capitalExpenditures' => '', 'otherExpenditures' => ''],
            'investments' => ['projectInvestment1' => '', 'projectInvestment2' => '', 'projectInvestment3' => ''],
            'formSubmitted' => false,
        ]);
    }

    public function initialize(Request $request){

        try{

            $data = $request->all(); //Adding this in the event things need to be validated later on  

            $user = $request->user();            

            $reportData = $this->initializeReport($user->email);

            //removed part of the original initialization function that was not needed

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

            $reportData = Finance::create([
                'academicYearID' => $data['academicYearID'],
                'department' => $data['department'],
                'deadline' => $data['deadline'],
                'income' => $data['income'],
                'expenditure' => $data['expenditure'],
                'investments' => $data['investments'],
                'formSubmitted' => $data['formSubmitted']
            ]);

            $response = [
                'success' => true,
                'message' => "Finance Statistics Report Created Successfully",
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
            $report = Finance::where('_id', $reportID)->first();

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
            $report = Finance::where('_id', $data['_id'])->first();//Changed reportID to '_id'

            if ($report) {

                $report->academicYearID = $request->has('academicYearID') ? $data['academicYearID'] : $report->academicYearID;
                $report->department = $request->has('department') ? $data['department'] : $report->department;
                $report->deadline = $request->has('deadline') ? $data['deadline'] : $report->deadline;
                $report->income = $request->has('income') ? $data['income'] : $report->income;
                $report->expenditure = $request->has('expenditure') ? $data['expenditure'] : $report->expenditure;
                $report->investments = $request->has('investments') ? $data['investments'] : $report->investments;
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
            $report = Finance::where('_id', $id)->first();

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

            $report = Finance::where('email', $user->email)->first();

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

    public function generateFinancePdf(Request $request, string $reportID){ //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Finance::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // Get the user based on the email from the report
        $user = User::where('email', $report->email)->first();        

        // Generate PDF using data directly
        // $pdf = PDF::loadHTML($this->generateReportPdfHtml($report));
        $pdf = PDF::loadView('FinanceStatisticsReport', ['report' => $report, 'user' => $user]);

        // Return PDF as a response
        return $pdf->download('report_' . $report->id . '.pdf');
    }

    public function viewFinanceReport(Request $request, string $reportID){ //Look into this a little more

        // Fetch data from MongoDB based on report ID
        $report = Finance::find($reportID);

        // return $report;
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $user = User::where('email', $report->email)->first();  

        return view('FinanceStatisticsReport', ['report' => $report, 'user' => $user]);
        
    }

}
