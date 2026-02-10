<?php
session_start();
include 'db_connection.php';
require 'vendor/autoload.php'; // PHPMailer

// --- Handle AJAX POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // ---------- SIGNUP ----------
    if ($action === 'signup') {
        $first = trim($_POST['first']);
        $last = trim($_POST['last']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $country = $_POST['country'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $occupation = $_POST['occupation'] ?? '';
        $terms = $_POST['terms'] ?? '0';

        if (!$first || !$last || !$email || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }

        // Date of birth validation - minimum 5 years old, maximum 105 years old (from 1919)
        if ($dob) {
            $dobDate = new DateTime($dob);
            $today = new DateTime();
            $age = $today->diff($dobDate)->y;

            if ($age < 5) {
                echo json_encode(['success' => false, 'message' => 'You must be at least 5 years old to register.']);
                exit;
            }

            // Check if year is before 1919
            $year = (int)date('Y', strtotime($dob));
            if ($year < 1919) {
                echo json_encode(['success' => false, 'message' => 'Date of birth cannot be before 1919.']);
                exit;
            }

            // Check if date is in the future
            if ($dobDate > $today) {
                echo json_encode(['success' => false, 'message' => 'Date of birth cannot be in the future.']);
                exit;
            }
        }

        if ($terms !== '1') {
            echo json_encode(['success' => false, 'message' => 'You must accept terms.']);
            exit;
        }

        // Check existing email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            exit;
        }
        $stmt->close();

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));

        $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,email,password,country,dob,occupation,verification_token) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $first, $last, $email, $password_hash, $country, $dob, $occupation, $token);

        if ($stmt->execute()) {
            $stmt->close();

            // Send verification email
            $projectFolder = "isokoai"; // XAMPP folder
            $verifyLink = "https://isokoai.online/index.php?verify_email=$token";

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'cyizalandry5@gmail.com';
                $mail->Password = 'jmga unfr osfp fbtn';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('cyizalandry5@gmail.com', 'IsokoAI');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your IsokoAI Account';

                // Cleaner, shorter HTML email template
                $mail->Body = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Your IsOkoAI Account</title>
        <style>
            /* Reset and base styles */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: "Poppins", "Segoe UI", Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f8fafc;
                margin: 0;
                padding: 20px;
            }
            
            .email-container {
                max-width: 550px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            }
            
            /* Header */
            .email-header {
                background: linear-gradient(135deg, #006cff 0%, #00d4ff 100%);
                padding: 30px 20px;
                text-align: center;
            }
            
            .welcome-text {
                color: white;
                font-size: 24px;
                font-weight: 700;
                margin-top: 10px;
                letter-spacing: 0.5px;
            }
            
            .tagline {
                color: rgba(255, 255, 255, 0.9);
                font-size: 14px;
                margin-top: 5px;
                font-style: italic;
            }
            
            /* Content */
            .email-content {
                padding: 30px;
            }
            
            .greeting {
                font-size: 18px;
                color: #2d3748;
                margin-bottom: 15px;
                font-weight: 600;
            }
            
            .message {
                font-size: 15px;
                color: #4a5568;
                margin-bottom: 20px;
                line-height: 1.6;
            }
            
            .highlight {
                color: #006cff;
                font-weight: 600;
            }
            
            /* Verification Button */
            .verify-section {
                text-align: center;
                margin: 25px 0;
                padding: 25px;
                background: linear-gradient(to right, #f8fafc, #f1f5f9);
                border-radius: 10px;
                border-left: 4px solid #006cff;
            }
            
            .verify-button {
                display: inline-block;
                background: linear-gradient(135deg, #006cff 0%, #00d4ff 100%);
                color: white !important;
                padding: 14px 32px;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                letter-spacing: 0.5px;
                box-shadow: 0 4px 12px rgba(0, 108, 255, 0.2);
                transition: all 0.3s ease;
            }
            
            .verify-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0, 108, 255, 0.3);
            }
            
            .verification-link {
                display: block;
                margin-top: 15px;
                font-size: 13px;
                color: #718096;
                word-break: break-all;
                padding: 10px;
                background: #f7fafc;
                border-radius: 6px;
                font-family: monospace;
                border: 1px solid #e2e8f0;
            }
            
            /* Features Section */
            .ai-note {
                margin: 20px 0;
                padding: 15px;
                background: #f0f9ff;
                border-radius: 8px;
                border-left: 3px solid #006cff;
            }
            
            .ai-note-text {
                font-size: 14px;
                color: #4a5568;
                line-height: 1.5;
            }
            
            /* Footer */
            .email-footer {
                background: #08193a;
                color: white;
                padding: 25px;
                text-align: center;
            }
            
            /* SECTION ORDER SWAPPED: Company info now comes first */
            .company-info {
                margin-bottom: 15px;
                padding-bottom: 15px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .company-name {
                font-size: 16px;
                font-weight: 700;
                margin-bottom: 5px;
                color: #00d4ff;
            }
            
            .company-tagline {
                font-size: 12px;
                color: rgba(255, 255, 255, 0.7);
                margin-bottom: 10px;
                font-style: italic;
            }
            
            /* SECTION ORDER SWAPPED: Founder info now comes second (image removed) */
            .founder-section {
                display: block;
                margin-bottom: 15px;
            }
            
            .founder-name {
                font-size: 15px;
                font-weight: 600;
                margin-bottom: 3px;
            }
            
            .founder-title {
                font-size: 12px;
                color: rgba(255, 255, 255, 0.8);
            }
            
            .privacy-note {
                font-size: 11px;
                color: rgba(255, 255, 255, 0.6);
                margin-top: 10px;
                line-height: 1.4;
            }
            
            /* Responsive */
            @media (max-width: 600px) {
                .email-content {
                    padding: 20px;
                }
                
                .email-header {
                    padding: 20px 15px;
                }
                
                .welcome-text {
                    font-size: 20px;
                }
                
                .verify-section {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1 class="welcome-text">Welcome to IsŌkoAI</h1>
                <p class="tagline">IsŌko means "Source"</p>
            </div>
            
            <!-- Content -->
            <div class="email-content">
                <p class="greeting">Hi ' . htmlspecialchars($first) . ',</p>
                
                <p class="message">
                    Thanks for choosing <span class="highlight">IsŌkoAI</span>! 
                    Please verify your email to complete your registration.
                </p>
                
                <!-- Verification Section -->
                <div class="verify-section">
                    <a href="' . $verifyLink . '" class="verify-button">
                        Verify Email Address
                    </a>
                    
                    <p style="margin-top: 15px; font-size: 13px; color: #718096;">
                        Or copy and paste this link in your browser:
                    </p>
                    
                    <div class="verification-link">
                        ' . $verifyLink . '
                    </div>
                    
                    <p style="margin-top: 10px; font-size: 12px; color: #718096;">
                        <strong>Note:</strong> This link expires in 24 hours.
                    </p>
                </div>
            </div>
            
            <!-- Footer (SECTIONS SWAPPED) -->
            <div class="email-footer">
                <!-- Company info now comes FIRST -->
                <div class="company-info">
                    <div class="company-name">IsŌkoAI</div>
                    <div class="company-tagline">Together we can end the colonial legacies and biases in AI models for Africa!</div>
                </div>
                
                <!-- Founder info now comes SECOND (image removed) -->
                <div class="founder-section">
                    <div class="founder-name">Mpayimana Cyiza Landry</div>
                    <div class="founder-title">IsŌkoAI- Founder & Developer</div>
                </div>
                 <div style="margin-top: 10px; font-size: 11px; color: rgba(255, 255, 255, 0.7);">
                        © ' . date('Y') . ' All rights reserved.
                    </div>
                
                <p class="privacy-note">
                    This email was sent to ' . htmlspecialchars($email) . '. 
                    If you didn\'t create this account, please ignore this email.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';

                // Plain text fallback for email clients that don't support HTML
                $mail->AltBody = "Welcome to IsŌkoAI!\n\nHi $first,\n\nThanks for choosing IsŌkoAI!\n\nPlease verify your email by clicking this link:\n$verifyLink\n\nThis link expires in 24 hours.\n\nFeel free to use our AI model that understands Kinyarwanda and other languages.\n\nBest regards,\nMpayimana Cyiza Landry\nIsŌkoAI Developer & Founder\n\n© " . date('Y') . " IsŌkoAI. All rights reserved.";

                if ($mail->send()) {
                    echo json_encode(['success' => true, 'verify_link' => $verifyLink]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send verification email.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Mailer error: ' . $mail->ErrorInfo]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        exit;
    }

    // ---------- LOGIN ----------
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id,password,is_verified FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        $stmt->bind_result($id, $hash, $verified);
        $stmt->fetch();

        if (!password_verify($password, $hash)) {
            echo json_encode(['success' => false, 'message' => 'Incorrect password']);
            exit;
        }
        if (!$verified) {
            echo json_encode(['success' => false, 'message' => 'Email not verified. Check your inbox.']);
            exit;
        }

        $_SESSION['user_id'] = $id;
        $_SESSION['email'] = $email;
        echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
        exit;
    }
}

// ---------- EMAIL VERIFICATION ----------
if (isset($_GET['verify_email'])) {
    $token = $_GET['verify_email'];
    if ($token) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token=? AND is_verified=0");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET is_verified=1, verification_token=NULL WHERE verification_token=?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('Email verified successfully! You can now login.'); window.location='index.php';</script>";
            exit;
        } else {
            echo "<script>alert('Invalid or already used verification link.'); window.location='index.php';</script>";
            exit;
        }
    }
}

// Country list
$countries = [
    "Afghanistan",
    "Albania",
    "Algeria",
    "Andorra",
    "Angola",
    "Antigua and Barbuda",
    "Argentina",
    "Armenia",
    "Australia",
    "Austria",
    "Azerbaijan",
    "Bahamas",
    "Bahrain",
    "Bangladesh",
    "Barbados",
    "Belarus",
    "Belgium",
    "Belize",
    "Benin",
    "Bhutan",
    "Bolivia",
    "Bosnia and Herzegovina",
    "Botswana",
    "Brazil",
    "Brunei",
    "Bulgaria",
    "Burkina Faso",
    "Burundi",
    "Cabo Verde",
    "Cambodia",
    "Cameroon",
    "Canada",
    "Central African Republic",
    "Chad",
    "Chile",
    "China",
    "Colombia",
    "Comoros",
    "Congo",
    "Costa Rica",
    "Croatia",
    "Cuba",
    "Cyprus",
    "Czech Republic",
    "Democratic Republic of the Congo",
    "Denmark",
    "Djibouti",
    "Dominica",
    "Dominican Republic",
    "Ecuador",
    "Egypt",
    "El Salvador",
    "Equatorial Guinea",
    "Eritrea",
    "Estonia",
    "Eswatini",
    "Ethiopia",
    "Fiji",
    "Finland",
    "France",
    "Gabon",
    "Gambia",
    "Georgia",
    "Germany",
    "Ghana",
    "Greece",
    "Grenada",
    "Guatemala",
    "Guinea",
    "Guinea-Bissau",
    "Guyana",
    "Haiti",
    "Honduras",
    "Hungary",
    "Iceland",
    "India",
    "Indonesia",
    "Iran",
    "Iraq",
    "Ireland",
    "Israel",
    "Italy",
    "Jamaica",
    "Japan",
    "Jordan",
    "Kazakhstan",
    "Kenya",
    "Kiribati",
    "Korea, North",
    "Korea, South",
    "Kosovo",
    "Kuwait",
    "Kyrgyzstan",
    "Laos",
    "Latvia",
    "Lebanon",
    "Lesotho",
    "Liberia",
    "Libya",
    "Liechtenstein",
    "Lithuania",
    "Luxembourg",
    "Madagascar",
    "Malawi",
    "Malaysia",
    "Maldives",
    "Mali",
    "Malta",
    "Marshall Islands",
    "Mauritania",
    "Mauritius",
    "Mexico",
    "Micronesia",
    "Moldova",
    "Monaco",
    "Mongolia",
    "Montenegro",
    "Morocco",
    "Mozambique",
    "Myanmar",
    "Namibia",
    "Nauru",
    "Nepal",
    "Netherlands",
    "New Zealand",
    "Nicaragua",
    "Niger",
    "Nigeria",
    "North Macedonia",
    "Norway",
    "Oman",
    "Pakistan",
    "Palau",
    "Palestine",
    "Panama",
    "Papua New Guinea",
    "Paraguay",
    "Peru",
    "Philippines",
    "Poland",
    "Portugal",
    "Qatar",
    "Romania",
    "Russia",
    "Rwanda",
    "Saint Kitts and Nevis",
    "Saint Lucia",
    "Saint Vincent and the Grenadines",
    "Samoa",
    "San Marino",
    "Sao Tome and Principe",
    "Saudi Arabia",
    "Senegal",
    "Serbia",
    "Seychelles",
    "Sierra Leone",
    "Singapore",
    "Slovakia",
    "Slovenia",
    "Solomon Islands",
    "Somalia",
    "South Africa",
    "South Sudan",
    "Spain",
    "Sri Lanka",
    "Sudan",
    "Suriname",
    "Sweden",
    "Switzerland",
    "Syria",
    "Taiwan",
    "Tajikistan",
    "Tanzania",
    "Thailand",
    "Timor-Leste",
    "Togo",
    "Tonga",
    "Trinidad and Tobago",
    "Tunisia",
    "Turkey",
    "Turkmenistan",
    "Tuvalu",
    "Uganda",
    "Ukraine",
    "United Arab Emirates",
    "United Kingdom",
    "United States",
    "Uruguay",
    "Uzbekistan",
    "Vanuatu",
    "Vatican City",
    "Venezuela",
    "Vietnam",
    "Yemen",
    "Zambia",
    "Zimbabwe"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>IsŌkoAI — Login / Sign up</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="images/header.png" type="image/x-icon">
    <style>
        :root {
            --accent-1: #00d4ff;
            --accent-2: #006cff;
            --deep-blue: #08193a;
            --glass-light: rgba(255, 255, 255, 0.75);
            --glass-dark: rgba(255, 255, 255, 0.08);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: "Poppins", sans-serif;
            transition: background .3s, color .3s;
        }

        /* Themes */
        body.light {
            background: linear-gradient(135deg, #e9f2ff, #f6fbff);
            color: #0b1b32;
        }

        body.dark {
            background: radial-gradient(circle at top, var(--deep-blue), #001025);
            color: #e6f7ff;
        }

        /* Centering */
        .page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            position: relative;
        }

        /* Animated orbs */
        .orb {
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            filter: blur(90px);
            z-index: 0;
            top: 5%;
            left: 6%;
            opacity: .9;
            transform: translate3d(0, 0, 0);
            transition: background .3s;
        }

        .orb2 {
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            right: 6%;
            bottom: 6%;
            opacity: .9;
            transform: translate3d(0, 0, 0);
            transition: background .3s;
        }

        body.dark .orb {
            background: rgba(0, 184, 255, 0.14);
        }

        body.dark .orb2 {
            background: rgba(132, 0, 255, 0.12);
        }

        body.light .orb {
            background: rgba(0, 119, 255, 0.12);
        }

        body.light .orb2 {
            background: rgba(255, 77, 166, 0.10);
        }

        /* Card */
        .auth-card {
            position: relative;
            z-index: 2;
            border-radius: 18px;
            padding: 26px;
            width: 100%;
            max-width: 760px;
            display: flex;
            gap: 10px;
            align-items: stretch;
            box-sizing: border-box;
        }

        body.light .auth-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(250, 250, 255, 0.85));
            box-shadow: 0 10px 30px rgba(5, 30, 80, 0.06);
        }

        body.dark .auth-card {
            background: linear-gradient(180deg, var(--glass-dark), rgba(255, 255, 255, 0.03));
            box-shadow: 0 12px 30px rgba(0, 110, 180, 0.12);
            backdrop-filter: blur(10px);
        }

        /* Left column - visual */
        .auth-left {
            flex: 1.25;
            padding: 22px;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
        }

        .auth-right {
            flex: 1;
            padding: 22px;
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 14px;
        }

        /* Logo placeholder */
        .logo-circle {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 28px;
            margin: 0 auto;
            box-shadow: 0 8px 30px rgba(0, 150, 255, 0.12);
        }

        body.light .logo-circle {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
        }

        body.dark .logo-circle {
            background: linear-gradient(135deg, var(--accent-2), var(--accent-1));
            color: white;
        }

        h1.title {
            text-align: center;
            margin: 0;
            font-weight: 700;
            font-size: 22px;
        }

        p.subtitle {
            text-align: center;
            margin: 0;
            color: inherit;
            opacity: 0.85;
            font-weight: 500;
            font-size: 14px;
        }

        /* form inputs */
        .form-control {
            background: transparent;
            border-radius: 8px;
            height: 46px;
            padding: 10px 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: inherit;
            transition: border .18s, box-shadow .18s;
            font-size: 14px;
        }

        body.light .form-control {
            border: 1px solid rgba(10, 25, 50, 0.12);
            color: #0b1b32;
            background: rgba(255, 255, 255, 0.95);
        }

        body.dark .form-control {
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #e6f7ff;
            background: rgba(255, 255, 255, 0.05);
        }

        .form-control:focus {
            outline: none;
            box-shadow: 0 6px 16px rgba(3, 40, 95, 0.06);
            border-color: var(--accent-1);
        }

        /* Date input styling */
        body.light input[type="date"] {
            color: #0b1b32;
            background: rgba(255, 255, 255, 0.95);
        }

        body.dark input[type="date"] {
            color: #e6f7ff;
            background: rgba(255, 255, 255, 0.05);
        }

        /* Date input calendar icon */
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            border-radius: 4px;
            padding: 4px;
            transition: background 0.2s;
        }

        body.light input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.3);
        }

        body.dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.9);
        }

        body.light input[type="date"]::-webkit-calendar-picker-indicator:hover {
            background: rgba(0, 108, 255, 0.1);
        }

        body.dark input[type="date"]::-webkit-calendar-picker-indicator:hover {
            background: rgba(0, 212, 255, 0.1);
        }

        /* Dropdown styling - FIXED: No duplicate arrows */
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }

        /* Light theme dropdown */
        body.light select.form-control {
            color: #0b1b32;
            background-color: rgba(255, 255, 255, 0.95);
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23006cff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        }

        /* Dark theme dropdown */
        body.dark select.form-control {
            color: #e6f7ff;
            background-color: rgba(255, 255, 255, 0.05);
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2300d4ff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        }

        /* Dropdown options */
        select.form-control option {
            padding: 10px;
        }

        body.light select.form-control option {
            background: white;
            color: #0b1b32;
        }

        body.dark select.form-control option {
            background: #08193a;
            color: #e6f7ff;
        }

        label.form-label {
            font-weight: 600;
            font-size: 13px;
            display: block;
            margin-bottom: 6px;
            color: inherit;
            opacity: 0.95;
        }

        .btn-primary.custom {
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
        }

        body.light .btn-primary.custom {
            background: linear-gradient(90deg, var(--accent-2), var(--accent-1));
            color: #fff;
        }

        body.dark .btn-primary.custom {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #002a43;
        }

        .linkish {
            background: none;
            border: none;
            color: inherit;
            text-decoration: underline;
            padding: 0;
        }

        .small-muted {
            font-size: 12px;
            opacity: 0.85;
        }

        @media(min-width:1100px) {
            .auth-card {
                max-width: 980px;
            }

            .auth-left {
                flex: 1;
            }

            .auth-right {
                flex: 1.1;
            }
        }

        @media(min-width:1400px) {
            .auth-card {
                max-width: 1100px;
            }

            .auth-left {
                flex: 1;
            }

            .auth-right {
                flex: 1.2;
            }
        }

        @media(min-width:768px) {
            .auth-card {
                padding: 30px;
            }
        }

        @media(max-width:767px) {
            .auth-card {
                flex-direction: column;
                padding: 18px;
            }

            .logo-circle {
                width: 84px;
                height: 84px;
            }
        }

        /* Terms modal */
        .terms-body {
            height: 260px;
            overflow: auto;
            padding: 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.6;
        }

        body.light .terms-body {
            background: #f8f9fa;
            color: #333;
        }

        body.dark .terms-body {
            background: rgba(30, 30, 40, 0.8);
            color: #e6f7ff;
        }

        .terms-body h5 {
            color: var(--accent-2);
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .terms-body h6 {
            color: var(--accent-1);
            margin-top: 15px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .terms-body p {
            margin-bottom: 12px;
        }

        .terms-body ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .terms-body li {
            margin-bottom: 8px;
        }

        /* Modal */
        .light-modal {
            background: white;
            color: #0b1b32;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .dark-modal {
            background: #08193a;
            color: #e6f7ff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        /* error message */
        .form-error {
            color: #ff6b6b;
            font-weight: 600;
            font-size: 13px;
            margin-top: 8px;
            display: none;
        }

        .dev-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(0, 150, 255, 0.18);
        }

        .dev-rights {
            font-size: 12px;
            text-align: center;
            color: inherit;
        }

        .dev-info-container {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .dev-details {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dev-name {
            font-weight: 600;
        }

        .dev-icons a {
            margin: 0 4px;
            color: inherit;
            transition: transform 0.2s, color 0.2s;
        }

        .dev-icons a:hover {
            transform: scale(1.2);
            color: #00d4ff;
        }

        .dev-hr {
            margin: 8px 0;
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media(max-width: 600px) {

            .dev-info-container,
            .dev-details {
                justify-content: center;
            }
        }

        /* Buttons */
        #toggle-to-login,
        #toggle-to-signup {
            transition: background 0.3s, color 0.3s, border 0.3s;
        }

        body.dark #toggle-to-login,
        body.dark #toggle-to-signup {
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: transparent;
        }

        body.light #toggle-to-login,
        body.light #toggle-to-signup {
            color: #0b1b32;
            border: 1px solid rgba(10, 25, 50, 0.2);
            background: transparent;
        }

        #toggle-to-login:hover,
        #toggle-to-signup:hover {
            transform: scale(1.05);
        }

        /* Link buttons */
        .link-btn {
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            padding: 0;
            margin: 0;
            transition: all 0.2s;
        }

        body.dark .link-btn {
            color: #ffffff;
        }

        body.light .link-btn {
            color: #000000;
        }

        .link-btn:hover {
            transform: scale(1.05);
        }

        .forgot-btn {
            font-size: 0.85em;
            font-style: italic;
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>

<body class="dark">
    <div class="page-wrap">
        <div class="orb"></div>
        <div class="orb2"></div>

        <div class="auth-card">
            <!-- LEFT: Visual / Branding -->
            <div class="auth-left text-center">
                <div class="logo-circle" id="logo-placeholder">
                    <img src="images/isokologo.jpg" alt="IsokoAI Logo" style="width:80%; height:80%; border-radius:50%;">
                </div>

                <h1 class="title">Welcome to IsŌkoAI</h1>
                <p class="subtitle"><em>IsŌko means "Source"</em></p>

                <div style="margin-top:8px; text-align:center;">
                    <button class="btn btn-sm btn-outline-light" id="toggle-to-login" style="margin-right:8px;">Login</button>
                    <button class="btn btn-sm btn-outline-light" id="toggle-to-signup">Sign up</button>
                </div>
                <div class="dev-info-container">
                    <img src="images/cyiza.avif" alt="Landry" class="dev-img">
                    <div class="dev-details">
                        <span class="dev-name">Mpayimana Cyiza Landry</span> |
                        <div class="dev-icons">
                            <a href="https://cyizalandry.com" target="_blank"><i class="fa-solid fa-globe"></i></a>
                            <a href="https://github.com/Cyizalandry5" target="_blank"><i class="fa-brands fa-github"></i></a>
                            <a href="https://instagram.com/cyiza5" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <hr class="dev-hr">
                <div class="dev-rights">
                    &copy; <?php echo date('d-F-Y'); ?> | All rights reserved.
                </div>
            </div>

            <!-- RIGHT: forms area -->
            <div class="auth-right">
                <!-- Theme toggle -->
                <div style="display:flex; justify-content:flex-end;">
                    <button id="theme-toggle" class="linkish small-muted" title="Toggle theme">
                        <i class="fa-solid fa-sun" id="sun-icon" style="display:none;"></i>
                        <i class="fa-solid fa-moon" id="moon-icon"></i>
                    </button>
                </div>

                <!-- LOGIN FORM -->
                <div id="login-form" style="display:block;">
                    <label class="form-label">Email</label>
                    <input id="login-email" class="form-control" type="email" placeholder="you@example.com">
                    <label class="form-label mt-2">Password</label>
                    <input id="login-password" class="form-control" type="password" placeholder="Your password">
                    <div class="form-error" id="login-error"></div>
                    <div style="margin-top:10px;">
                        <button id="login-submit" class="btn btn-primary custom"><i class="fa fa-sign-in-alt"></i> Login</button>
                    </div>
                    <div style="margin-top:10px; text-align:center;">
                        <button id="show-signup" class="link-btn">Sign up</button>
                        <span style="margin:0 8px;">|</span>
                        <a href="forgot_password.php" class="link-btn forgot-btn">Forgot Password?</a>
                    </div>
                </div>

                <!-- SIGNUP FORM -->
                <div id="signup-form" style="display:none;">
                    <div style="display:flex; gap:8px;">
                        <div style="flex:1;">
                            <label class="form-label">First name *</label>
                            <input id="first" class="form-control" type="text" placeholder="First name">
                        </div>
                        <div style="flex:1;">
                            <label class="form-label">Last name *</label>
                            <input id="last" class="form-control" type="text" placeholder="Last name">
                        </div>
                    </div>

                    <label class="form-label mt-2">Email *</label>
                    <input id="email" class="form-control" type="email" placeholder="you@example.com">

                    <label class="form-label mt-2">Password *</label>
                    <input id="password" class="form-control" type="password" placeholder="Min 6 characters">

                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <div style="flex:1;">
                            <label class="form-label">Country</label>
                            <select id="country" class="form-control">
                                <option value="">Select a country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo htmlspecialchars($country); ?>"><?php echo htmlspecialchars($country); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label class="form-label">Date of birth</label>
                            <input id="dob" class="form-control" type="date" max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>" min="1919-01-01">
                        </div>
                    </div>

                    <label class="form-label mt-2">Occupation</label>
                    <input id="occupation" class="form-control" type="text" placeholder="Your occupation">

                    <div style="margin-top:10px;">
                        <button id="open-terms" class="btn btn-outline-secondary btn-sm">Read Terms & Conditions</button>
                        <span style="font-size:12px; margin-left:10px;" class="small-muted"><- Must read and Must Accept.</span>
                    </div>

                    <div style="margin-top:10px;">
                        <input type="checkbox" id="terms-checkbox"> <label for="terms-checkbox" style="font-weight:600;"> I accept the Terms & Conditions</label>
                    </div>

                    <div class="form-error" id="signup-error"></div>

                    <div style="margin-top:10px;">
                        <button id="signup-submit" class="btn btn-primary custom">Create account</button>
                    </div>

                    <div style="margin-top:10px; text-align:center;">
                        <button id="show-login" class="linkish small-muted">Already have an account? Login</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="terms-modal" style="display:none; position:fixed; inset:0; background:rgba(3,6,20,0.6); align-items:center; justify-content:center; z-index:9999;">
        <div style="width:92%; max-width:760px; border-radius:12px; padding:16px; max-height:90vh; overflow:hidden;"
            class="dark-modal"
            id="terms-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h4 style="margin:0; color: var(--accent-2);">Terms & Conditions</h4>
                <button id="close-terms" class="linkish" style="font-size:18px; padding:5px;">✕</button>
            </div>

            <div style="margin-top:12px;">
                <div id="terms-scroll" class="terms-body">
                    <h5>Welcome to IsŌkoAI — Terms of Service</h5>
                    <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>

                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing and using IsŌkoAI ("the Platform"), you agree to be bound by these Terms and Conditions. If you do not agree with any part of these terms, you must not use our services.</p>

                    <h6>2. Description of Service</h6>
                    <p>IsŌkoAI provides an intelligent AI platform with multilingual capabilities, including native-level understanding of Kinyarwanda and other languages. The service includes AI-powered conversations, content generation, and language processing features.</p>

                    <h6>3. User Registration</h6>
                    <p>To access certain features, you must register an account. You agree to provide accurate, current, and complete information during registration and to update such information to keep it accurate.</p>

                    <h6>4. Data Collection and Usage</h6>
                    <p>By using IsŌkoAI, you acknowledge and consent to our data practices as described below:</p>
                    <ul>
                        <li><strong>Personal Information:</strong> We collect information you provide during registration including name, email, country, date of birth, and occupation.</li>
                        <li><strong>Usage Data:</strong> We collect information about how you interact with our AI models, including prompts, conversations, and queries.</li>
                        <li><strong>Location Data:</strong> We may collect approximate location information based on your IP address or provided country information.</li>
                        <li><strong>Model Training:</strong> <strong>You expressly consent that we may use your anonymized prompts, conversations, and interactions to train and improve our AI models.</strong> This helps us enhance the intelligence and cultural relevance of our AI, particularly for African languages and contexts.</li>
                        <li><strong>Age Data:</strong> Date of birth information helps us ensure age-appropriate content and comply with regulations.</li>
                    </ul>

                    <h6>5. Data Anonymization</h6>
                    <p>When using your data for model training, we:</p>
                    <ul>
                        <li>Remove personally identifiable information</li>
                        <li>Aggregate data to prevent identification of individuals</li>
                        <li>Use encryption and security measures to protect data</li>
                        <li>Retain data only as long as necessary for model improvement</li>
                    </ul>

                    <h6>6. Purpose of Data Collection</h6>
                    <p>We collect and use your data to:</p>
                    <ul>
                        <li>Provide and improve our AI services</li>
                        <li>Train our models to better understand cultural contexts, especially African perspectives</li>
                        <li>Reduce biases in AI responses</li>
                        <li>Personalize your experience</li>
                        <li>Ensure compliance with legal requirements</li>
                        <li>Enhance multilingual capabilities</li>
                    </ul>

                    <h6>7. User Rights</h6>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal data</li>
                        <li>Request correction of inaccurate data</li>
                        <li>Request deletion of your account and data</li>
                        <li>Opt-out of certain data collection (where applicable)</li>
                        <li>Export your data</li>
                    </ul>
                    <p>To exercise these rights, contact us at support@isokoai.com</p>

                    <h6>8. Age Restrictions</h6>
                    <p>You must be at least 5 years old to use IsŌkoAI. Users under 13 should have parental consent. We do not knowingly collect data from children under 5.</p>

                    <h6>9. Intellectual Property</h6>
                    <p>All content, features, and functionality of IsŌkoAI are owned by us and are protected by international copyright, trademark, and other intellectual property laws.</p>

                    <h6>10. User-Generated Content</h6>
                    <p>You retain ownership of your prompts and inputs, but grant us a license to use anonymized versions for model training as described above.</p>

                    <h6>11. Prohibited Activities</h6>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Use the service for illegal purposes</li>
                        <li>Attempt to bypass security measures</li>
                        <li>Generate harmful, abusive, or discriminatory content</li>
                        <li>Impersonate others</li>
                        <li>Attempt to reverse engineer our models</li>
                    </ul>

                    <h6>12. Limitation of Liability</h6>
                    <p>IsŌkoAI is provided "as is" without warranties of any kind. We are not liable for any indirect, incidental, or consequential damages arising from your use of the service.</p>

                    <h6>13. Service Modifications</h6>
                    <p>We reserve the right to modify or discontinue the service at any time without notice. We are not liable for any modification, suspension, or discontinuance.</p>

                    <h6>14. Termination</h6>
                    <p>We may terminate or suspend your account for violations of these terms. Upon termination, your right to use the service will immediately cease.</p>

                    <h6>15. Governing Law</h6>
                    <p>These terms are governed by the laws of Rwanda. Any disputes shall be resolved in the courts of Kigali.</p>

                    <h6>16. Changes to Terms</h6>
                    <p>We may update these terms periodically. Continued use after changes constitutes acceptance of the new terms.</p>

                    <h6>17. Contact Information</h6>
                    <p>For questions about these terms, contact us at: info@cyizalandry.com</p>

                    <h6>18. Consent Declaration</h6>
                    <p>By accepting these terms, you specifically consent to:</p>
                    <ul>
                        <li>Collection of your registration data</li>
                        <li>Use of your anonymized prompts and interactions for AI model training</li>
                        <li>Storage of your data in secure servers</li>
                        <li>Processing of your location and demographic information for service improvement</li>
                    </ul>

                    <p style="font-weight:700; color: var(--accent-1); margin-top: 20px; padding: 10px; background: rgba(0, 212, 255, 0.1); border-radius: 5px;">
                        <strong>END OF TERMS</strong><br>
                        <small>Your acceptance indicates you understand we may use your anonymized data to train our AI models, helping to create more culturally relevant AI for African contexts.</small>
                    </p>
                </div>

                <div style="margin-top:15px; display:flex; align-items:center; gap:12px; padding-top:15px; border-top:1px solid rgba(0,0,0,0.1);">
                    <input type="checkbox" id="terms-accept-in-modal" disabled>
                    <label for="terms-accept-in-modal" style="font-weight:600;">I have read, understood, and accept these Terms & Conditions</label>
                    <div style="margin-left:auto;">
                        <button id="accept-terms-button" class="btn btn-primary btn-sm" disabled>Accept Terms</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery + Bootstrap JS (CDN) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // --- UI initialization ---
        $(function() {
            // Theme: persisted in localStorage
            if (localStorage.getItem('theme') === 'light') {
                $('body').removeClass('dark').addClass('light');
                $('#sun-icon').show();
                $('#moon-icon').hide();
                $('#terms-modal-content').removeClass('dark-modal').addClass('light-modal');
            } else {
                $('body').removeClass('light').addClass('dark');
                $('#sun-icon').hide();
                $('#moon-icon').show();
                $('#terms-modal-content').removeClass('light-modal').addClass('dark-modal');
            }

            // Set date limits for DOB
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 5, today.getMonth(), today.getDate());
            const minDate = new Date(1919, 0, 1);

            const maxDateStr = maxDate.toISOString().split('T')[0];
            const minDateStr = minDate.toISOString().split('T')[0];

            $('#dob').attr('max', maxDateStr);
            $('#dob').attr('min', minDateStr);

            // Update form controls based on theme
            function updateFormControlsForTheme() {
                if ($('body').hasClass('light')) {
                    $('.form-control').css({
                        'color': '#0b1b32',
                        'background': 'rgba(255, 255, 255, 0.95)',
                        'border-color': 'rgba(10, 25, 50, 0.12)'
                    });
                    $('input[type="date"]').css({
                        'color': '#0b1b32',
                        'background': 'rgba(255, 255, 255, 0.95)'
                    });
                } else {
                    $('.form-control').css({
                        'color': '#e6f7ff',
                        'background': 'rgba(255, 255, 255, 0.05)',
                        'border-color': 'rgba(255, 255, 255, 0.12)'
                    });
                    $('input[type="date"]').css({
                        'color': '#e6f7ff',
                        'background': 'rgba(255, 255, 255, 0.05)'
                    });
                }
            }

            updateFormControlsForTheme();

            // Footer current date D-M-YYYY
            const d = new Date();
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            $('#footer-date').text(day + '-' + month + '-' + year);

            // Toggle login/signup
            $('#show-signup, #toggle-to-signup').on('click', function() {
                $('#login-form').hide();
                $('#signup-form').show();
            });
            $('#show-login, #toggle-to-login').on('click', function() {
                $('#signup-form').hide();
                $('#login-form').show();
            });

            // Theme toggle
            $('#theme-toggle').on('click', function() {
                if ($('body').hasClass('dark')) {
                    $('body').removeClass('dark').addClass('light');
                    localStorage.setItem('theme', 'light');
                    $('#sun-icon').show();
                    $('#moon-icon').hide();
                    $('#terms-modal-content').removeClass('dark-modal').addClass('light-modal');

                    $('#toggle-to-login, #toggle-to-signup').css({
                        'color': '#0b1b32',
                        'border': '1px solid rgba(10,25,50,0.2)'
                    });
                } else {
                    $('body').removeClass('light').addClass('dark');
                    localStorage.setItem('theme', 'dark');
                    $('#sun-icon').hide();
                    $('#moon-icon').show();
                    $('#terms-modal-content').removeClass('light-modal').addClass('dark-modal');

                    $('#toggle-to-login, #toggle-to-signup').css({
                        'color': '#fff',
                        'border': '1px solid rgba(255,255,255,0.3)'
                    });
                }

                updateFormControlsForTheme();
            });

            // Show terms modal
            $('#open-terms').on('click', function() {
                $('#terms-modal').fadeIn(120);
            });

            // Close modal
            $('#close-terms').on('click', function() {
                closeTermsModal();
            });

            // Close modal on background click
            $('#terms-modal').on('click', function(e) {
                if (e.target === this) {
                    closeTermsModal();
                }
            });

            // Scroll detection in terms modal
            $('#terms-scroll').on('scroll', function() {
                const el = this;
                const atBottom = el.scrollHeight - el.scrollTop <= el.clientHeight + 10;
                $('#terms-accept-in-modal').prop('disabled', !atBottom);
                $('#accept-terms-button').prop('disabled', !atBottom);
            });

            // Accept in modal
            $('#accept-terms-button').on('click', function() {
                $('#terms-checkbox').prop('checked', true).prop('disabled', false);
                $('#terms-accept-in-modal').prop('checked', true);
                closeTermsModal();
            });

            function closeTermsModal() {
                $('#terms-modal').fadeOut(120);
            }

            // --- SIGNUP ---
            $('#signup-submit').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.text('Processing...').removeClass('btn-primary').css({
                    'background': '#ccc',
                    'color': '#fff'
                });

                $('#signup-error').hide().text('');

                const first = $('#first').val().trim();
                const last = $('#last').val().trim();
                const email = $('#email').val().trim();
                const password = $('#password').val();
                const country = $('#country').val();
                const dob = $('#dob').val();
                const occupation = $('#occupation').val().trim();
                const termsAccepted = $('#terms-checkbox').is(':checked');

                // --- Validations ---
                if (!first || !last || !email || !password) {
                    showSignupError('Please fill all required fields.');
                    resetButton();
                    return;
                }
                if (/\d/.test(first) || /\d/.test(last)) {
                    showSignupError('Names cannot contain numbers.');
                    resetButton();
                    return;
                }
                if (!validateEmail(email)) {
                    showSignupError('Please enter a valid email.');
                    resetButton();
                    return;
                }
                if (password.length < 6) {
                    showSignupError('Password must be at least 6 characters.');
                    resetButton();
                    return;
                }

                // Date validation - CHECKS YEAR CONSTRAINTS PROPERLY:
                // 1. Prevents future dates (year 4000+ etc.)
                // 2. Minimum age 5 years
                // 3. No dates before 1919
                if (dob) {
                    const dobDate = new Date(dob);
                    const today = new Date();

                    // Check if date is in the future (prevents year 4000+ etc.)
                    if (dobDate > today) {
                        showSignupError('Date of birth cannot be in the future.');
                        resetButton();
                        return;
                    }

                    const age = today.getFullYear() - dobDate.getFullYear();
                    const monthDiff = today.getMonth() - dobDate.getMonth();

                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dobDate.getDate())) {
                        age--;
                    }

                    if (age < 5) {
                        showSignupError('You must be at least 5 years old to register.');
                        resetButton();
                        return;
                    }

                    if (dobDate.getFullYear() < 1919) {
                        showSignupError('Date of birth cannot be before 1919.');
                        resetButton();
                        return;
                    }
                }

                if (!termsAccepted) {
                    showSignupError('You must accept the Terms & Conditions.');
                    resetButton();
                    return;
                }

                // --- AJAX POST ---
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        action: 'signup',
                        first: first,
                        last: last,
                        email: email,
                        password: password,
                        country: country,
                        dob: dob,
                        occupation: occupation,
                        terms: termsAccepted ? '1' : '0'
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (!resp) {
                            showSignupError('Server error. Please try again.');
                            resetButton();
                            return;
                        }

                        if (resp.success) {
                            // --- Success: Keep button disabled and show static green message ---
                            $btn.text('✅ Registration Successful').css({
                                'background': '#28a745',
                                'color': '#fff',
                                'cursor': 'default'
                            }).prop('disabled', true); // Keep disabled

                            // Show static success message above the button
                            $('#signup-error').css({
                                'color': '#28a745',
                                'font-weight': '600',
                                'font-size': '14px',
                                'padding': '10px',
                                'background': 'rgba(40, 167, 69, 0.1)',
                                'border-radius': '6px',
                                'border-left': '4px solid #28a745'
                            }).show().html(
                                '✅ <strong>Success! Check your Email.</strong><br>'
                            );

                            // Clear all form fields
                            $('#first').val('');
                            $('#last').val('');
                            $('#email').val('');
                            $('#password').val('');
                            $('#country').val('');
                            $('#dob').val('');
                            $('#occupation').val('');
                            $('#terms-checkbox').prop('checked', false).prop('disabled', true);

                            // Disable all form inputs to prevent further edits
                            $('#signup-form .form-control').prop('disabled', true);
                            $('#open-terms').prop('disabled', true);

                        } else {
                            showSignupError(resp.message || 'Signup failed. Please try again.');
                            resetButton();
                        }
                    },
                    error: function(xhr, status, error) {
                        showSignupError('Server error, please try again.');
                        resetButton();
                    }
                });

                function resetButton() {
                    $btn.prop('disabled', false).text('Create account').removeAttr('style').addClass('btn-primary');
                }
            });

            // --- LOGIN ---
            $('#login-submit').on('click', function() {
                $('#login-error').hide().text('');

                const email = $('#login-email').val().trim();
                const password = $('#login-password').val();
                if (!email || !password) {
                    showLoginError('Email and password are required.');
                    return;
                }
                if (!validateEmail(email)) {
                    showLoginError('Please enter a valid email.');
                    return;
                }

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        action: 'login',
                        email: email,
                        password: password
                    },
                    dataType: 'json',
                    success: function(resp) {
                        if (!resp) {
                            showLoginError('Server error.');
                            return;
                        }
                        if (resp.success) {
                            $('#login-error').css('color', 'green').show().text('Login successful. Redirecting...');
                            setTimeout(function() {
                                window.location.href = resp.redirect || 'dashboard.php';
                            }, 1500);
                        } else {
                            showLoginError(resp.message || 'Login failed.');
                        }
                    },
                    error: function() {
                        showLoginError('Server error, try again.');
                    }
                });
            });

            // --- Helpers ---
            function showSignupError(m) {
                $('#signup-error').css('color', '#ff6b6b').show().text(m);
            }

            function showLoginError(m) {
                $('#login-error').css('color', '#ff6b6b').show().text(m);
            }

            function validateEmail(e) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
            }

            // Accessibility: pressing Enter in fields triggers the intended action
            $('#login-password').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    $('#login-submit').click();
                }
            });
            $('#password').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    $('#signup-submit').click();
                }
            });

        }); // end ready
    </script>
</body>

</html>