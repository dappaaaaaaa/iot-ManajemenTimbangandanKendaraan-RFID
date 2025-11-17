<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }

        img {
            display: block;
            width: 100%;
            height: auto;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <img src="{{ $imageBase64 }}" alt="Lampiran">
</body>

</html>
