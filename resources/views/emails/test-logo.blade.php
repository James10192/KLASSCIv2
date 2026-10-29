<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Logo</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">
    <h1 style="color: #007bff;">Test Logo Debug</h1>

    <div style="background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <p><strong>Debug Info:</strong></p>
        <p>Logo Path: {{ $schoolLogoPath ?? 'NOT SET' }}</p>
        <p>Message variable: {{ isset($message) ? '✓ Available' : '✗ Not Available' }}</p>
        <p>File exists: {{ file_exists($schoolLogoPath) ? '✓ YES' : '✗ NO' }}</p>
    </div>

    @if(isset($schoolLogoPath) && $schoolLogoPath && isset($message))
        <div style="background: #e7f3ff; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <p><strong>Attempting to embed logo:</strong></p>
            <img src="{{ $message->embed($schoolLogoPath) }}" alt="Test Logo" style="max-width: 200px; border: 2px solid #007bff;">
        </div>
    @else
        <div style="background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24;">
            <p><strong>ERROR:</strong> Cannot embed logo</p>
            @if(!isset($message))
                <p>- $message variable is not available</p>
            @endif
            @if(!isset($schoolLogoPath))
                <p>- $schoolLogoPath is not set</p>
            @endif
            @if(isset($schoolLogoPath) && !file_exists($schoolLogoPath))
                <p>- File does not exist at path: {{ $schoolLogoPath }}</p>
            @endif
        </div>
    @endif

    <p style="margin-top: 30px; color: #6c757d; font-size: 12px;">
        This is a debug email to test logo embedding.
    </p>
</body>
</html>
