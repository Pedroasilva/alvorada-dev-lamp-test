<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Research System</title>
    <link rel="stylesheet" href="/public/css/styles.css">
</head>
<body>
    <div class="main-wrapper">
        <div class="container">
            <h1>Property Research System</h1>
            <p class="subtitle">Add a new property with automatic geolocation enrichment</p>

            <div id="message" class="message"></div>
            <div id="loading" class="loading">Processing property data</div>

            <form id="propertyForm">
                <div class="form-group">
                    <label for="name">Property Name *</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Downtown Office Building">
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" required placeholder="e.g., 123 Main Street, New York, NY 10001"></textarea>
                </div>

                <button type="submit">Add Property</button>
            </form>

            <div id="result" class="result"></div>
        </div>

        <div class="sidebar">
            <h2>Recent Properties</h2>
            <ul id="recentProperties" class="property-list">
                <li class="no-properties">Loading...</li>
            </ul>
        </div>
    </div>

    <script src="/public/js/main.js"></script>
</body>
</html>
