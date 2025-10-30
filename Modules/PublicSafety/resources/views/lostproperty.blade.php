<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lost Property Report Form</title>

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
      <p class="case">Lost Property Report Form</p>
    </div>

    <div class="section">
      <h2>Lost Property Report Form</h2>
      <div class="section-content">
        <div class="info-row">
          <span class="info-label">Complainant Name:</span>
          <span class="info-value">{{ $lostProperty['complainantName'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Complainant Address:</span>
          <span class="info-value">{{ $lostProperty['complainantAddress'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Complainant DOB:</span>
          <span class="info-value">{{ $lostProperty['complainantDOB'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Complainant Telephone:</span>
          <span class="info-value">{{ $lostProperty['complainantTelephone'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Complaint ID:</span>
          <span class="info-value">{{ $lostProperty['complaintID'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Complainant Email:</span>
          <span class="info-value">{{ $lostProperty['complainantEmail'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Date Lost:</span>
          <span class="info-value">{{ $lostProperty['dateLost'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Time Lost:</span>
          <span class="info-value">{{ $lostProperty['timeLost'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Complaint Affiliation:</span>
          <span class="info-value">{{ $lostProperty['complaintAffiliation'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Additional Description:</span>
          <span class="info-value">{{ $lostProperty['additionalDescription'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner:</span>
          <span class="info-value">{{ $lostProperty['owner'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner Signature:</span>
          <span class="info-value">{{ $lostProperty['ownerSignature'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Date Reported:</span>
          <span class="info-value">{{ $lostProperty['dateReported'] ?? 'N/A' }}</span>
        </div>
      </div>
    </div>

    <div class="section">
      <h2>Return Of Recovered Item To Owner</h2>
      <div class="section-content">
        <div class="info-row">
          <span class="info-label">Date Returned To Owner:</span>
          <span class="info-value">{{ $lostProperty['dateReturnedToOwner'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Time Returned To Owner:</span>
          <span class="info-value">{{ $lostProperty['timeReturnedToOwner'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner Name:</span>
          <span class="info-value">{{ $lostProperty['ownerName'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner DOB:</span>
          <span class="info-value">{{ $lostProperty['ownerDOB'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner Address:</span>
          <span class="info-value">{{ $lostProperty['ownerAddress'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner Telephone:</span>
          <span class="info-value">{{ $lostProperty['ownerTelephone'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner ID:</span>
          <span class="info-value">{{ $lostProperty['ownerID'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Remarks:</span>
          <span class="info-value">{{ $lostProperty['remarks'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Signature of DPS Rep:</span>
          <span class="info-value">{{ $lostProperty['signatureDPS'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Owner Signature:</span>
          <span class="info-value">{{ $lostProperty['ownerSignatureReturn'] ?? 'N/A' }}</span>
        </div>
      </div>

</body>

</html>