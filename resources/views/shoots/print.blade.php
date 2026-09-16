<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url={{ route('shoots.show', $shoot) }}">
    <title>Redirecting to Shoot Call Sheet...</title>
    <script>window.location.href = "{{ route('shoots.show', $shoot) }}";</script>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 50px;">
    <p>Redirecting to <a href="{{ route('shoots.show', $shoot) }}">Shoot Call Sheet</a>...</p>
</body>
</html>
