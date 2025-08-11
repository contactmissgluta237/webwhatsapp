<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Authentification')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .auth-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
        }

        .auth-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            max-width: 450px;
            width: 100%;
        }

        .auth-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0;
        }

        .auth-tabs {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .auth-tabs .btn-check:checked+.btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-color: transparent;
            color: white;
        }

        .auth-tabs .btn {
            border-radius: 0;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .auth-tabs .btn:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-auth {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .auth-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-link:hover {
            color: #764ba2;
        }

        .password-toggle {
            border-left: none;
            background: transparent;
            color: #6c757d;
        }

        .password-toggle:hover {
            background-color: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        @media (max-width: 576px) {
            .auth-wrapper {
                padding: 10px;
            }

            .auth-card {
                margin: 0 auto;
            }

            .auth-tabs .btn {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
        }
    </style>

    @livewireStyles
</head>

<body class="auth-wrapper d-flex align-items-center justify-content-center">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 d-flex justify-content-center">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    @livewireScripts

    <script>
        $(document).ready(function() {
            $('.alert').each(function() {
                const alert = $(this);
                setTimeout(function() {
                    alert.fadeOut(300, function() {
                        alert.remove();
                    });
                }, 5000);
            });

            $('.auth-card').hide().fadeIn(800);
        });
    </script>
</body>

</html>
