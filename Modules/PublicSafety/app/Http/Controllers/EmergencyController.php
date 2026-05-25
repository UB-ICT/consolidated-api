<?php

namespace Modules\PublicSafety\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;


class EmergencyController extends Controller
{
    protected const COLLECTION_PREFIX = 'publicSafety_';
    protected string $collectionName = self::COLLECTION_PREFIX . 'emergency';

    public function initialize()
    {
        try {
            $id = 'emergency-' . Str::uuid();

            $defaultEmergencyReport = [
                'id' => $id,
                'latitude' => 0.0,
                'longitude' => 0.0,
                'accuracy' => 0.0,
                'emergencyReportStatus' => 'Active',

                // ⏰ Time only (HH:MM:SS)
                // 'timestamp' => Carbon::now()->format('H:i:s'),
                'timestamp' => Carbon::now()->toISOString(), // ✅ BEST

                'formSubmitted' => false,
                'isRead' => false,
                'uploadedBy' => $id,

                // 📅 Date only (YYYY-MM-DD)
                'created_at' => Carbon::now()->toDateString(),

                // (optional: keep full datetime or match created_at)
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];
            Log::info('Initializing Emergency Report:', $defaultEmergencyReport);

            FirestoreService::updateDocument(
                $this->collectionName,
                $id,
                $defaultEmergencyReport
            );

            return $defaultEmergencyReport;
        } catch (\Exception $e) {

            Log::error('Emergency report initialization failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Initialization failed'
            ], 500);
        }
    }

    public function index()
    {
        try {
            $emergencyReport = FirestoreService::getCollection($this->collectionName);
            $response = [
                'success' => true,
                'message' => 'emergency reports retrieved successfully',
                'data' => $emergencyReport
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        return response($response, 200);
    }

    //create/store
    public function store(Request $request)
    {
        try {
            // Validate incoming request
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'accuracy' => 'required|numeric',
                'timestamp' => 'required|string',
                'emergencyReportStatus' => 'required|string',
            ]);

            // Generate unique ID
            $id = 'emergency-' . Str::uuid();

            // Prepare data
            $emergencyData = array_merge($request->all(), [
                'id' => $id,
                'isRead' => false,
                'formSubmitted' => true,
                'created_at' => Carbon::now()->toDateString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]);

            // Log for debugging
            Log::info('Creating Emergency Report:', $emergencyData);

            // Save to Firestore (PHP syntax)
            FirestoreService::firestore()
                ->collection($this->collectionName)
                ->document($id) // ✅ use document() in PHP
                ->set($emergencyData);

            return response()->json([
                'success' => true,
                'message' => 'Emergency Report Created Successfully',
                'data' => $emergencyData
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create emergency: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //read
    public function show(string $emergencyReportID)
    {
        try {
            $emergencyReport = FirestoreService::getDocument($this->collectionName, $emergencyReportID);
            if ($emergencyReport) {
                $response = [
                    'success' => true,
                    'message' => 'Emergency Report found',
                    'data' => $emergencyReport
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Emergency Report not found',
                    'data' => null,
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
        // Return response with HTTP status code 201 (Created)
        return response()->json($emergencyReport, 200);
    }

    //update
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->only([
                'latitude',
                'longitude',
                'accuracy',
                'timestamp',
                'isRead',
                'emergencyReportStatus',
            ]);
            $data['updated_at'] = Carbon::now()->toDateTimeString(); // Always track update time
            // Update the document in Firestore
            $success = FirestoreService::updateDocument($this->collectionName, $id, $data);
            if ($success) {
                // Fetch the updated document to return
                $updatedReport = FirestoreService::getDocument($this->collectionName, $id);
                $response = [
                    'success' => true,
                    'message' => 'Emergency report updated successfully',
                    'data' => $updatedReport
                ];
                Log::info('Updated Emergency report: ', $updatedReport);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Emergency report not found',
                    'data' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error('Emergency report update error: ' . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
        return response($response, 200);
    }

    public function destroy(string $id)
    {
        try {
            $success = FirestoreService::deleteDocument($this->collectionName, $id);
            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Emergency report data deleted successfully',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Emergency report not found',
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
        // Return response with HTTP status code 201 (Created)
        return response()->json($response, 200);
    }

    public function unreadCount()
    {
        $count = FirestoreService::countWhere(
            $this->collectionName,
            'isRead',
            '=',
            false
        );

        return response()->json([
            'unreadCount' => $count
        ]);
    }


    public function markAsRead(string $reportID)
    {
        $updated = FirestoreService::updateDocument(
            $this->collectionName,
            $reportID,
            [
                'isRead' => true,
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        );

        if (!$updated) {
            return response()->json([
                'error' => 'Emergency not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Emergency marked as read'
        ]);
    }

    public function getTotalAlerts()
    {
        try {
            // Total alerts (no filter)
            $totalAlerts = FirestoreService::count($this->collectionName);

            Log::info('Total alerts: ' . $totalAlerts);

            // Active alerts
            $activeAlerts = FirestoreService::countWhere(
                $this->collectionName,
                'emergencyReportStatus',
                '=',
                'Active'
            );

            // Resolved alerts
            $resolvedAlerts = FirestoreService::countWhere(
                $this->collectionName,
                'emergencyReportStatus',
                '=',
                'Resolved'
            );

            return response()->json([
                'success' => true,
                'message' => 'Alert statistics retrieved successfullys',
                'data' => [
                    'total' => $totalAlerts,
                    'active' => $activeAlerts,
                    'resolved' => $resolvedAlerts,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
