<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Georgia', serif;
            background: #fff;
            width: 800px;
            height: 570px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .certificate {
            width: 780px;
            height: 550px;
            border: 12px double #b8860b;
            padding: 40px 60px;
            position: relative;
            text-align: center;
            background: #fffdf4;
        }

        .certificate::before {
            content: '';
            position: absolute;
            inset: 8px;
            border: 2px solid #d4a017;
            pointer-events: none;
        }

        .corner {
            position: absolute;
            width: 40px;
            height: 40px;
            border-color: #b8860b;
            border-style: solid;
        }

        .corner-tl { top: 20px; left: 20px; border-width: 3px 0 0 3px; }
        .corner-tr { top: 20px; right: 20px; border-width: 3px 3px 0 0; }
        .corner-bl { bottom: 20px; left: 20px; border-width: 0 0 3px 3px; }
        .corner-br { bottom: 20px; right: 20px; border-width: 0 3px 3px 0; }

        .platform-name {
            font-size: 13px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #8b6914;
            margin-bottom: 10px;
        }

        .certificate-title {
            font-size: 36px;
            color: #2c2c2c;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            color: #666;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .divider {
            width: 120px;
            height: 1px;
            background: #b8860b;
            margin: 0 auto 20px;
        }

        .presented-to {
            font-size: 13px;
            color: #888;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .student-name {
            font-size: 42px;
            color: #1a1a1a;
            font-style: italic;
            margin-bottom: 14px;
            line-height: 1.1;
        }

        .completion-text {
            font-size: 13px;
            color: #555;
            margin-bottom: 8px;
        }

        .course-name {
            font-size: 22px;
            color: #2c2c2c;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 16px;
        }

        .signature-block {
            text-align: center;
            flex: 1;
        }

        .signature-line {
            width: 160px;
            height: 1px;
            background: #2c2c2c;
            margin: 0 auto 4px;
        }

        .signature-label {
            font-size: 11px;
            color: #666;
            letter-spacing: 1px;
        }

        .uid-block {
            text-align: center;
            flex: 1;
        }

        .uid-label {
            font-size: 10px;
            color: #aaa;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .uid-value {
            font-size: 10px;
            color: #999;
            font-family: 'Courier New', monospace;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <div class="platform-name">{{ $platformName }}</div>

        <div class="certificate-title">Certificate</div>
        <div class="subtitle">of Completion</div>

        <div class="divider"></div>

        <div class="presented-to">This certifies that</div>
        <div class="student-name">{{ $studentName }}</div>

        <div class="completion-text">has successfully completed the course</div>
        <div class="course-name">{{ $courseName }}</div>

        <div class="footer">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-label">{{ $instructorName }}</div>
                <div class="signature-label" style="color:#aaa; font-size:10px;">Instructor</div>
            </div>

            <div class="uid-block">
                <div class="uid-label">Issued</div>
                <div class="uid-value">{{ $completionDate }}</div>
            </div>

            <div class="uid-block">
                <div class="uid-label">Certificate ID</div>
                <div class="uid-value">{{ strtoupper(substr($certificateUid, 0, 8)) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
