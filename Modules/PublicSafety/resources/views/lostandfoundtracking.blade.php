<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lost and Found Tracking Form</title>

  <style>
    /* Reset */
    html,
    body {
      margin: 0;
      padding: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      color: #333;
    }

    body {
      margin: 30px;
      background: #f9f9f9;
    }

    .container {
      width: 100%;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    /* Header */
    .header {
      background: #3d004a;
      color: #fff;
      text-align: center;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
    }

    .header h1 {
      margin: 0;
      font-size: 26px;
      letter-spacing: 1px;
    }

    .header p {
      margin: 5px 0;
      font-size: 15px;
    }

    .header .case {
      font-weight: bold;
      margin-top: 8px;
      font-size: 16px;
    }

    /* Section */
    .section {
      margin-bottom: 25px;
    }

    .section h2 {
      background: #7e317b;
      color: #fff;
      padding: 8px 12px;
      font-size: 18px;
      border-radius: 5px 5px 0 0;
      margin: 0;
    }

    .section-content {
      border: 1px solid #ddd;
      border-top: none;
      padding: 15px;
      border-radius: 0 0 5px 5px;
      background: #fafafa;
    }

    /* Table-like info */
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px solid #eee;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      font-weight: bold;
      width: 40%;
      color: #444;
    }

    .info-value {
      width: 58%;
      text-align: left;
    }

    /* Print-friendly */
    @media print {
      body {
        margin: 0;
        background: #fff;
      }

      .container {
        box-shadow: none;
        border-radius: 0;
        padding: 0;
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
    <!-- HEADER -->
    <div class="header">
      <h1>University of Belize</h1>
      <p>Public Safety Department</p>
      <p class="case">Lost and Found Tracking Form</p>
    </div>

    <div class="section">
      <h2>Lost and Found Tracking Form</h2>
      <div class="section-content">
        <div class="info-row">
          <span class="info-label">Facility Name: </span>
          <span class="info-value">{{ $lostAndFoundTracking['facilityName'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Time: </span>
          <span class="info-value">{{ $lostAndFoundTracking['time'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label"> Today's Date: </span>
          <span class="info-value">{{ $lostAndFoundTracking['todaysDate'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Serial Number: </span>
          <span class="info-value">{{ $lostAndFoundTracking['serialNumber'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Location Found: </span>
          <span class="info-value">{{ $lostAndFoundTracking['locationFound'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Room No: </span>
          <span class="info-value">{{ $lostAndFoundTracking['roomNo'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Found By: </span>
          <span class="info-value">{{ $lostAndFoundTracking['foundBy'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Supervisor Who Received Item: </span>
          <span class="info-value">{{ $lostAndFoundTracking['supervisorWhoReceivedItem'] ?? 'N/A'}}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Item Description: </span>
          <span class="info-value">{{ $lostAndFoundTracking['itemDescription'] ?? 'N/A'}}</span>
        </div>
      </div>

      <!-- FILES -->
      <div class="section">
        <h2> Lost and Found Tracking Pictures</h2>
        <div class="section-content">
          <div class="info-row">
            @if(isset($lostAndFoundTracking['lostAndFoundTrackingFiles']) && is_array($lostAndFoundTracking['lostAndFoundTrackingFiles']))
              @foreach($lostAndFoundTracking['lostAndFoundTrackingFiles'] as $file)
                @if(isset($file['url']) && !empty($file['url']))
                  <img src="{{ storage_path($file['url']) }}" alt="Lost and Found Tracking File"
                    style="max-width: 300px; height: auto; margin-bottom: 20px;">
                @endif
              @endforeach
            @else
              <p>No files uploaded.</p>
            @endif
          </div>
        </div>
      </div>

      <div class="section">
        <h2>Disposition of property</h2>
        <div class="section-content">
          <div class="info-row">
            <span class="info-label">Date Returned to owner: </span>
            <span class="info-value">{{ $lostAndFoundTracking['dateReturnedToOwner'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Time Returned to Owner: </span>
            <span class="info-value">{{ $lostAndFoundTracking['timeReturnedToOwner'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner: </span>
            <span class="info-value">{{ $lostAndFoundTracking['owner'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner DOB: </span>
            <span class="info-value">{{ $lostAndFoundTracking['ownerDOB'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner Address: </span>
            <span class="info-value">{{ $lostAndFoundTracking['ownerAddress'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label"> Owner ID Number:</span>: </span>
            <span class="info-value">{{ $lostAndFoundTracking['ownerIDNumber'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner Telephone: </span>
            <span class="info-value">{{ $lostAndFoundTracking['ownerTelephone'] ?? 'N/A'}}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Remarks: </span>
            <span class="info-value">{{ $lostAndFoundTracking['remarks'] ?? 'N/A'}}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Return to Owner Signature: </span>
            <span class="info-value">{{ $lostAndFoundTracking['returnedToOwnerSignature'] ?? 'N/A'}}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Owner Acknowledgement Signature: </span>
            <span class="info-value">{{ $lostAndFoundTracking['ownerAcknowledgementSignature'] ?? 'N/A'}}</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</body>

</html>