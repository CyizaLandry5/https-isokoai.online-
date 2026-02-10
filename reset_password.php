<?php
session_start();
include 'db_connection.php';

$email = $_GET['email'] ?? '';
$email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (!$email || !$code || !$new_password || strlen($new_password) < 6) {
        $response['message'] = "All fields are required and password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND reset_code=? LIMIT 1");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = "Invalid code or email.";
        } else {
            $user = $result->fetch_assoc();
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password=?, reset_code=NULL WHERE id=?");
            $stmt->bind_param("si", $hashed, $user['id']);
            $stmt->execute();

            $response['success'] = true;
            $response['message'] = "Password changed successfully! Redirecting...";
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>IsŌkoAI — Reset Password</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
            padding: 30px;
            width: 100%;
            max-width: 500px;
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

        /* Logo */
        .logo-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 24px;
            margin: 0 auto 20px;
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
            margin: 0 0 10px;
            font-weight: 700;
            font-size: 24px;
        }

        p.subtitle {
            text-align: center;
            margin: 0 0 30px;
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
            width: 100%;
            margin-bottom: 15px;
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
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        body.light .btn-primary.custom {
            background: linear-gradient(90deg, var(--accent-2), var(--accent-1));
            color: #fff;
        }

        body.dark .btn-primary.custom {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #002a43;
        }

        .btn-primary.custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 108, 255, 0.3);
        }

        .btn-primary.custom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Message boxes */
        .form-error {
            color: #ff6b6b;
            font-weight: 600;
            font-size: 13px;
            margin: 10px 0;
            padding: 12px;
            border-radius: 8px;
            display: none;
        }

        .form-error.alert {
            background: rgba(255, 107, 107, 0.1);
            border-left: 4px solid #ff6b6b;
        }

        .form-error.success {
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
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
            margin: 15px 0 0;
            display: block;
            text-align: center;
            width: 100%;
            color: inherit;
            transition: all 0.2s;
        }

        .link-btn:hover {
            transform: scale(1.05);
        }

        /* Back button */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: none;
            border: none;
            color: inherit;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .back-btn:hover {
            opacity: 1;
        }

        /* Theme toggle */
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .theme-toggle:hover {
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .auth-card {
                padding: 20px;
                margin: 20px;
            }

            .logo-circle {
                width: 70px;
                height: 70px;
            }

            h1.title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body class="dark">
    <button class="back-btn" onclick="window.location.href='index.php'">
        <i class="fa fa-arrow-left"></i>
    </button>

    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
        <i class="fa-solid fa-sun" id="sun-icon" style="display:none;"></i>
        <i class="fa-solid fa-moon" id="moon-icon"></i>
    </button>

    <div class="page-wrap">
        <div class="orb"></div>
        <div class="orb2"></div>

        <div class="auth-card">
            <div class="logo-circle">
                <img src="images/isokologo.jpg" alt="IsokoAI Logo" style="width:70%; height:70%; border-radius:50%;">
            </div>

            <h1 class="title">Reset Password</h1>
            <p class="subtitle">Enter your email and reset code to set a new password</p>

            <div class="form-error" id="rp-message"></div>

            <form id="resetForm">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required placeholder="you@example.com">

                <label class="form-label">Reset Code</label>
                <input type="text" name="code" id="code" class="form-control"
                    required placeholder="Enter the 6-digit code">

                <label class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password"
                    class="form-control" required minlength="6"
                    placeholder="Minimum 6 characters">

                <button type="submit" id="rp-submit" class="btn btn-primary custom">
                    <i class="fa fa-key"></i> Reset Password
                </button>
            </form>

            <button class="link-btn" onclick="window.location.href='index.php'">
                ← Back to Login
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Theme functionality
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

            // Reset form submission
            $("#resetForm").on("submit", function(e) {
                e.preventDefault();
                const $btn = $("#rp-submit");
                const $msg = $("#rp-message");

                $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                $msg.hide().removeClass("alert success");

                $.ajax({
                    url: "",
                    type: "POST",
                    dataType: "json",
                    data: {
                        email: $("#email").val().trim(),
                        code: $("#code").val().trim(),
                        new_password: $("#new_password").val().trim()
                    },
                    success: function(res) {
                        if (res.success) {
                            $msg.show().text(res.message).addClass("success");
                            $btn.html('<i class="fa fa-check"></i> Password Reset ✓').css({
                                'background': '#28a745',
                                'color': '#fff'
                            });

                            // Clear form fields
                            $("#email").val('');
                            $("#code").val('');
                            $("#new_password").val('');

                            setTimeout(() => window.location = "index.php", 1500);
                        } else {
                            $msg.show().text(res.message).addClass("alert");
                            $btn.prop("disabled", false).html('<i class="fa fa-key"></i> Reset Password');
                        }
                    },
                    error: function() {
                        $msg.show().text("Server error. Please try again.").addClass("alert");
                        $btn.prop("disabled", false).html('<i class="fa fa-key"></i> Reset Password');
                    }
                });
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
        });
    </script>
</body>

</html>