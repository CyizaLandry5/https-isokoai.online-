<?php
session_start();
include 'db_connection.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
        exit;
    }

    $user = $result->fetch_assoc();
    $code = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

    $stmt = $conn->prepare("UPDATE users SET reset_code=? WHERE email=?");
    $stmt->bind_param("ss", $code, $email);
    $stmt->execute();

    $resetLink = "https://isokoai.online/reset_password.php?email=" . urlencode($email) . "&code=" . urlencode($code);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "cyizalandry5@gmail.com";
        $mail->Password = "jmga unfr osfp fbtn";
        $mail->SMTPSecure = "tls";
        $mail->Port = 587;

        $mail->setFrom("cyizalandry5@gmail.com", "IsokoAI Support");
        $mail->addAddress($email);

        $mail->Subject = "Reset Your IsOkoAI Password";
        $mail->isHTML(true);

        // Professional HTML email template
        $mail->Body = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
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
        
        /* Reset Code Section */
        .code-section {
            text-align: center;
            margin: 25px 0;
            padding: 25px;
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            border-radius: 10px;
            border-left: 4px solid #006cff;
        }
        
        .reset-code {
            display: inline-block;
            background: linear-gradient(135deg, #006cff 0%, #00d4ff 100%);
            color: white !important;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 22px;
            letter-spacing: 5px;
            box-shadow: 0 4px 12px rgba(0, 108, 255, 0.2);
            margin: 15px 0;
            font-family: monospace;
        }
        
        .reset-button {
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
            margin-top: 15px;
        }
        
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 108, 255, 0.3);
        }
        
        .reset-link {
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
        
        /* Security Note */
        .security-note {
            margin: 20px 0;
            padding: 15px;
            background: #fff5f5;
            border-radius: 8px;
            border-left: 3px solid #f56565;
        }
        
        .security-text {
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
        
        /* Company info comes first */
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
        
        /* Founder info comes second */
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
            
            .code-section {
                padding: 20px;
            }
            
            .reset-code {
                font-size: 18px;
                letter-spacing: 3px;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1 class="welcome-text">Password Reset</h1>
            <p class="tagline">IsŌko means "Source"</p>
        </div>
        
        <!-- Content -->
        <div class="email-content">
            <p class="greeting">Hello ' . htmlspecialchars($user['first_name']) . ',</p>
            
            <p class="message">
                We received a request to reset your password for your <span class="highlight">IsŌkoAI</span> account.
                Please use the verification code below or click the reset button to proceed.
            </p>
            
            <!-- Reset Code Section -->
            <div class="code-section">
                <p style="margin-bottom: 15px; color: #4a5568;">
                    Your password reset verification code:
                </p>
                
                <div class="reset-code">' . $code . '</div>
                
                <p style="margin: 15px 0; font-size: 13px; color: #718096;">
                    Or click the button below to reset your password:
                </p>
                
                <a href="' . $resetLink . '" class="reset-button">
                    Reset Password Now
                </a>
                
                <p style="margin-top: 15px; font-size: 13px; color: #718096;">
                    Or copy and paste this link in your browser:
                </p>
                
                <div class="reset-link">
                    ' . $resetLink . '
                </div>
                
                <p style="margin-top: 10px; font-size: 12px; color: #718096;">
                    <strong>Note:</strong> This code and link will expire in 1 hour.
                </p>
            </div>
            
            <!-- Security Note -->
            <div class="security-note">
                <p class="security-text">
                    <strong>🔒 Security Tip:</strong> If you didn\'t request this password reset, please ignore this email.
                    Your account security is important to us. Never share your verification code with anyone.
                </p>
            </div>
            
            <p style="margin-top: 25px; color: #4a5568;">
                Need help? Contact me at: info@cyizalandry.com
            </p>
            
            <p style="margin-top: 20px; color: #4a5568;">
                Best regards,<br>
                <strong>Cyiza Landry</strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <!-- Company info comes FIRST -->
            <div class="company-info">
                <div class="company-name">IsŌkoAI</div>
                <div class="company-tagline">Together we can end the colonial legacies and biases in AI models for Africa!</div>
            </div>
            
            <!-- Founder info comes SECOND -->
            <div class="founder-section">
                <div class="founder-name">Mpayimana Cyiza Landry</div>
                <div class="founder-title">IsŌkoAI- Founder & Developer</div>
            </div>
            
            <div style="margin-top: 10px; font-size: 11px; color: rgba(255, 255, 255, 0.7);">
                © ' . date('Y') . ' All rights reserved.
            </div>
            
            <p class="privacy-note">
                This email was sent to ' . htmlspecialchars($email) . ' in response to a password reset request.
                If you didn\'t make this request, please secure your account.
            </p>
        </div>
    </div>
</body>
</html>';

        // Plain text fallback
        $mail->AltBody = "Password Reset Request\n\nHello " . $user['first_name'] . ",\n\nYour password reset code is: " . $code . "\n\nOr click this link to reset: " . $resetLink . "\n\nThis code expires in 1 hour.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nThe IsŌkoAI Team";

        $mail->send();

        echo json_encode(['success' => true, 'message' => 'Reset code sent to your email. Check your inbox.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Email sending failed. Please try again.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Forgot Password - IsŌkoAI</title>
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
            gap: 20px;
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
            cursor: pointer;
        }

        .small-muted {
            font-size: 12px;
            opacity: 0.85;
            text-align: center;
            margin-top: 10px;
        }

        /* error message */
        .form-error {
            color: #ff6b6b;
            font-weight: 600;
            font-size: 13px;
            margin-top: 8px;
            display: none;
            padding: 10px;
            border-radius: 6px;
            background: rgba(255, 107, 107, 0.1);
            border-left: 3px solid #ff6b6b;
        }

        .form-success {
            color: #28a745;
            font-weight: 600;
            font-size: 13px;
            margin-top: 8px;
            padding: 10px;
            border-radius: 6px;
            background: rgba(40, 167, 69, 0.1);
            border-left: 3px solid #28a745;
        }

        .dev-rights {
            font-size: 12px;
            text-align: center;
            color: inherit;
        }

        .dev-hr {
            margin: 8px 0;
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Instructions */
        .instructions {
            font-size: 13px;
            color: inherit;
            opacity: 0.8;
            text-align: center;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        /* Theme toggle */
        .theme-toggle-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        /* Responsive */
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
    </style>
</head>

<body class="dark">
    <div class="page-wrap">
        <div class="orb"></div>
        <div class="orb2"></div>

        <div class="auth-card">
            <!-- LEFT: Visual / Branding -->
            <div class="auth-left text-center">
                <div class="logo-circle">
                    <img src="images/isokologo.avif" alt="IsokoAI Logo" style="width:80%; height:80%; border-radius:50%;">
                </div>

                <h1 class="title">Forgot Password?</h1>
                <p class="subtitle"><em>Don't worry, we'll help you reset it</em></p>

                <div class="instructions">
                    Enter your email address below and we'll send you a verification code to reset your password.
                </div>

                <hr class="dev-hr">
                <div class="dev-rights">
                    &copy; <?php echo date('d-F-Y'); ?> | All rights reserved.
                </div>
            </div>

            <!-- RIGHT: forms area -->
            <div class="auth-right">
                <!-- Theme toggle -->
                <div class="theme-toggle-container">
                    <button id="theme-toggle" class="linkish small-muted" title="Toggle theme">
                        <i class="fa-solid fa-sun" id="sun-icon" style="display:none;"></i>
                        <i class="fa-solid fa-moon" id="moon-icon"></i>
                    </button>
                </div>

                <div id="fp-error" class="form-error"></div>
                <div id="fp-success" class="form-success" style="display:none;"></div>

                <div style="margin-bottom: 10px;">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="fp-email" class="form-control" placeholder="you@example.com">
                </div>

                <button id="fp-submit" class="btn-primary custom">
                    <i class="fa fa-paper-plane"></i> Send Reset Code
                </button>

                <div class="small-muted">
                    Remember your password? <a href="index.php" class="linkish">Back to Login</a>
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
            } else {
                $('body').removeClass('light').addClass('dark');
                $('#sun-icon').hide();
                $('#moon-icon').show();
            }

            // Theme toggle
            $('#theme-toggle').on('click', function() {
                if ($('body').hasClass('dark')) {
                    $('body').removeClass('dark').addClass('light');
                    localStorage.setItem('theme', 'light');
                    $('#sun-icon').show();
                    $('#moon-icon').hide();
                } else {
                    $('body').removeClass('light').addClass('dark');
                    localStorage.setItem('theme', 'dark');
                    $('#sun-icon').hide();
                    $('#moon-icon').show();
                }
            });

            // Update form controls based on theme
            function updateFormControlsForTheme() {
                if ($('body').hasClass('light')) {
                    $('.form-control').css({
                        'color': '#0b1b32',
                        'background': 'rgba(255, 255, 255, 0.95)',
                        'border-color': 'rgba(10, 25, 50, 0.12)'
                    });
                } else {
                    $('.form-control').css({
                        'color': '#e6f7ff',
                        'background': 'rgba(255, 255, 255, 0.05)',
                        'border-color': 'rgba(255, 255, 255, 0.12)'
                    });
                }
            }

            updateFormControlsForTheme();
            $('#theme-toggle').on('click', updateFormControlsForTheme);

            // Forgot password form submission
            $("#fp-submit").on("click", function() {
                const $btn = $(this);
                const email = $("#fp-email").val().trim();
                $("#fp-error, #fp-success").hide();

                if (!email) {
                    $("#fp-error").text("Please enter your email address.").show();
                    return;
                }

                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    $("#fp-error").text("Please enter a valid email address.").show();
                    return;
                }

                $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

                $.post("", {
                    email: email
                }, function(res) {
                    if (res.success) {
                        $("#fp-success").html('<i class="fa fa-check-circle"></i> ' + res.message).show();
                        $btn.html('<i class="fa fa-check"></i> Code Sent').css("background", "#28a745");

                        // Clear email field after success
                        $("#fp-email").val('');

                        // Redirect hint
                        setTimeout(function() {
                            $("#fp-success").append('<br><small>Check your email and follow the instructions.</small>');
                        }, 1000);
                    } else {
                        $("#fp-error").html('<i class="fa fa-exclamation-circle"></i> ' + res.message).show();
                        $btn.prop("disabled", false).html('<i class="fa fa-paper-plane"></i> Send Reset Code');
                    }
                }, "json").fail(function() {
                    $("#fp-error").html('<i class="fa fa-exclamation-circle"></i> Server error. Please try again.').show();
                    $btn.prop("disabled", false).html('<i class="fa fa-paper-plane"></i> Send Reset Code');
                });
            });

            // Allow pressing Enter to submit
            $("#fp-email").on("keydown", function(e) {
                if (e.key === "Enter") {
                    $("#fp-submit").click();
                }
            });
        });
    </script>
</body>

</html>