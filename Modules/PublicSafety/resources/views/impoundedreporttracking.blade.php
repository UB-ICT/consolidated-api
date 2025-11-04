<<!DOCTYPE html>
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

  <Body>
    <div class="container">
      <!-- HEADER -->
      <div class="header">
        <h1>University of Belize</h1>
        <p>Public Safety Department</p>
        <p class="case">Bicycle Lost / Impounded Report Tracking Form</p>
      </div>

      <div class="section">
        <div class="section-content">
          <div class="info-row">
            <span class="info-label">Person Name:</span>
            <span class="info-value">{{ $impoundedReport['personName'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Student ID #:</span>
            <span class="info-value">{{ $impoundedReport['studentID'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Phone #:</span>
            <span class="info-value">{{ $impoundedReport['phoneNumber'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Address:</span>
            <span class="info-value">{{ $impoundedReport['address'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Today Date:</span>
            <span class="info-value">{{ $impoundedReport['todayDate'] ?? 'N/A' }}</span>
          </div>
        </div>
      </div>
      <div class="section">
        <h2>Bicycle Information Form:</h2>
        <div class="section-content">
          <div class="info-row">
            <span class="info-label">Brand:</span>
            <span class="info-value">{{ $impoundedReport['brand'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Model:</span>
            <span class="info-value">{{ $impoundedReport['model'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">color:</span>
            <span class="info-value">{{ $impoundedReport['color'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">style:</span>
            <span class="info-value">{{ $impoundedReport['style'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Serial Number:</span>
            <span class="info-value">{{ $impoundedReport['serialNumber'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Purchase Date:</span>
            <span class="info-value">{{ $impoundedReport['purchaseDate'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Purchase Price:</span>
            <span class="info-value">{{ $impoundedReport['purchasePrice'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Location of Bike Stolen:</span>
            <span class="info-value">{{ $impoundedReport['locationOfBikeStolen'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">What Time Bike Stolen:</span>
            <span class="info-value">{{ $impoundedReport['whatTimeBikeStolen'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Bicycle Rack:</span>
            <span class="info-value">{{ $impoundedReport['bicycleRack'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">When was bike was stolen:</span>
            <span class="info-value">{{ $impoundedReport['whenWasBikeWasStolen'] ?? 'N/A' }}</span>
          </div>
        </div>
      </div>
      <div class="section">
        <h2>Impound Report Tracking Form:</h2>
        <div class="section-content">
          <div class="info-row">
            <span class="info-label">Name of Finder:</span>
            <span class="info-value">{{ $impoundedReport['nameOfFinder'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Location Found:</span>
            <span class="info-value">{{ $impoundedReport['locationFound'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Brand:</span>
            <span class="info-value">{{ $impoundedReport['trackingBrand'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label"> Model </span>
            <span class="info-value">{{ $impoundedReport['trackingModel'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">color:</span>
            <span class="info-value">{{ $impoundedReport['trackingColor'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">style:</span>
            <span class="info-value">{{ $impoundedReport['trackingStyle'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Serial Number:</span>
            <span class="info-value">{{ $impoundedReport['trackingSerialNumber'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Supervisor Who Received Item(s):</span>
            <span class="info-value">{{ $impoundedReport['supervisorWhoreceivedItems'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Remarks:</span>
            <span class="info-value">{{ $impoundedReport['trackingFormRemarks'] ?? 'N/A' }}</span>
          </div>
        </div>
      </div>
      <div class="section">
        <h2>Disposition of Property</h2>
        <div class="section-content">
          <div class="info-row">
            <span class="info-label">Date Returned to Owner:</span>
            <span class="info-value">{{ $impoundedReport['dateReturnedToOwner2'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner Name:</span>
            <span class="info-value">{{ $impoundedReport['ownerName2'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner Address:</span>
            <span class="info-value">{{ $impoundedReport['ownerAddress2'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner DOB:</span>
            <span class="info-value">{{ $impoundedReport['ownerDOB2'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner ID Number:</span>
            <span class="info-value">{{ $impoundedReport['ownerIDNumber2'] ?? 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner Telephone:</span>
            <span class="info-value">{{ $impoundedReport['ownerTelephone2'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Remarks:</span>
            <span class="info-value">{{ $impoundedReport['remarks2'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Owner Signature</span>
            <span class="info-value">{{ $impoundedReport['ownerSignature2'] ?? 'N/A' }}</span>
          </div>

          <div class="info-row">
            <span class="info-label">Signature of DPS Rep:</span>
            <span class="info-value">{{ $impoundedReport['signatureDPS2'] ?? 'N/A' }}</span>
          </div>
        </div>
      </div>
    </div>
  </Body>

  </html>