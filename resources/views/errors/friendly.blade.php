<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4f6f8; color: #1a2e4e; font-family: system-ui, sans-serif; }
        main { max-width: 28rem; padding: 2rem; text-align: center; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { line-height: 1.5; }
        a { color: #1a2e4e; }
    </style>
</head>
<body>
    <main>
        <h1>Something went wrong</h1>
        <p>Please try again. If this keeps happening, go back and check the form, or contact the school office.</p>
        <p><a href="{{ url()->previous() }}">Go back</a></p>
    </main>
</body>
</html>
