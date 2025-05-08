<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Faculty;
use App\Models\Staff;
use App\Models\Finance;
use App\Models\HumanResources;
use App\Models\Records;
use App\Models\User;


class ReportController extends Controller
{
    
    // 
    public function getReports($reportTypes)
    {

        // $reportTypes = "faculty-finance-human_resources-records-staff";

        // Retrieve 'reportTypes' query parameter which should be an array
        // $reportTypes = $request->query('reportTypes', []);

        $reportTypes = explode("-", $reportTypes);       

        $reportData = [];

        // Get Faculty Report and add data to $reportData
        if(in_array('faculty', $reportTypes)){
           
            $facultyData = Faculty::all();

            foreach($facultyData as $faculty){
                // $faculty->userData = $faculty->user();
                array_push($reportData, [
                    "reportType" => 'faculty',
                    "_id" => $faculty->_id,
                    "name" => $faculty->user()->name,
                    "formSubmitted" => $faculty->formSubmitted
                ]);
            }

            // return $facultyData;
        }

        // Get Finance Report and add data to $reportData
        if(in_array('finance', $reportTypes)){
           
            $financeData = Finance::all();

            foreach($financeData as $finance){
                array_push($reportData, [
                    "reportType" => 'finance',
                    "_id" => $finance->_id,
                    "name" => $finance->user()->name,
                    "formSubmitted" => $finance->formSubmitted
                ]);
            }

            // return $financeData;
        }
  
        // Get Human Resources Report and add data to $reportData
        if(in_array('human_resources', $reportTypes)){
           
            $HumanResourcesData = HumanResources::all();

            foreach($HumanResourcesData as $HumanResources){
                array_push($reportData, [
                    "reportType" => 'HumanResources',
                    "_id" => $HumanResources->_id,
                    "name" => $HumanResources->user()->name,
                    "formSubmitted" => $HumanResources->formSubmitted
                ]);
            }

            // return $HumanResourcesData;
        }
  
        // Get Records Report and add data to $reportData
        if(in_array('records', $reportTypes)){
           
            $recordsData = Records::all();

            foreach($recordsData as $records){
                array_push($reportData, [
                    "reportType" => 'records',
                    "_id" => $records->_id,
                    "name" => $records->user()->name,
                    "formSubmitted" => $records->formSubmitted
                ]);
            }

            // return $recordsData;
        }

        // Get Staff Report and add data to $reportData
        if(in_array('staff', $reportTypes)){
           
            $staffData = Staff::all();

            foreach($staffData as $staff){
                array_push($reportData, [
                    "reportType" => 'staff',
                    "_id" => $staff->_id,
                    "name" => $staff->user()->name,
                    "formSubmitted" => $staff->formSubmitted
                ]);
            }

            // return $staffData;
        }
  
        // Return the list of report IDs
        $response = [
            'success' => true,
            'message' => 'Reports Retrieved.',
            'data' => [
                'reportData' => $reportData 
            ],
        ];

        return response($response, 200);

    }
        
}
