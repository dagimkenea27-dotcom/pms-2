<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Inventory Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            padding: 20px;
        }
        .icon {
            font-size: 64px;
            color: #4e73df;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #3a3b45;
        }
        p {
            font-size: 16px;
            margin-bottom: 30px;
            color: #858796;
        }
        .btn {
            display: inline-block;
            background-color: #4e73df;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #2e59d9;
        }
    </style>
</head>
<body>
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" viewBox="0 0 16 16">
          <path d="M10.706 3.294A.5.5 0 1 0 10 4a3.5 3.5 0 0 1 3.5 3.5c0 .776-.248 1.493-.666 2.062a.5.5 0 0 0 .796.608 4.5 4.5 0 0 0 .87-2.67A4.5 4.5 0 0 0 10.706 3.294zM7.5 1a5.5 5.5 0 0 0-3.666 9.619.5.5 0 1 0 .664-.747A4.5 4.5 0 1 1 12 7.5a.5.5 0 1 0 1 0 5.5 5.5 0 0 0-5.5-5.5z"/>
          <path d="m2.146 2.854-.708-.708L.707 2.854l12.5 12.5.707-.707-1.414-1.414.708.708-.708-.708L2.146 2.854z"/>
        </svg>
    </div>
    <h1>You are offline</h1>
    <p>It seems there is a problem with your connection. Please check your network and try again.</p>
    <a href="index.php" class="btn">Try Again</a>
</body>
</html>
