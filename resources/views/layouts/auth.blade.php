<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Avinash-EYE') }} - Authentication</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --border-color: #dadce0;
            --hover-bg: #f1f3f4;
            --error-color: #d93025;
            --success-color: #137333;
            --shadow-sm: 0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15);
            --shadow-md: 0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15);
            --transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        body {
            font-family: 'Roboto', 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: #202124;
            line-height: 1.5;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            padding: 2.5rem;
            margin-bottom: 1rem;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.75rem;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-family: 'Google Sans', sans-serif;
        }

        .auth-logo-icon {
            font-size: 2.5rem;
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 500;
            color: #202124;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: var(--secondary-color);
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #202124;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }

        .form-input.error {
            border-color: var(--error-color);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            cursor: pointer;
            user-select: none;
        }

        .form-error {
            color: var(--error-color);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .form-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-checkbox label {
            font-size: 0.875rem;
            color: var(--secondary-color);
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #1765cc;
            box-shadow: var(--shadow-sm);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        .auth-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-links a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: #e6f4ea;
            color: var(--success-color);
        }

        .alert-error {
            background: #fce8e6;
            color: var(--error-color);
        }

        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
        }

        .password-requirements {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.75rem;
            color: var(--secondary-color);
        }

        .password-requirements ul {
            list-style: none;
            margin: 0;
            padding-left: 1.25rem;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
            position: relative;
        }

        .password-requirements li::before {
            content: "•";
            position: absolute;
            left: -1rem;
        }

        .password-requirements li.valid {
            color: var(--success-color);
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 1.5rem;
            }

            .auth-title {
                font-size: 1.25rem;
            }
        }
    </style>

    @livewireStyles
</head>
<body>
    <div class="auth-container">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>

