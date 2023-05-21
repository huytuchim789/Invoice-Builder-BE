<!DOCTYPE html>
<html>

<head>
    <title>Swagger UI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui.css" integrity="sha512-hki9EANjyxPBN/cHBLCllLIRqS/XFUYzEv32spn5AgpZVk5TteqGuTtQThuaTXoZ74i1WNox6ywGY0oTGerbow==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div id="swagger-ui"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-bundle.js" integrity="sha512-jh2caNMlyLKhcis1ISQoVqJeT/SLXdeVz46/g46RsUuVPOVAMcXTeB9DGQymsQKIwieP6zXX1Lna+ESnXLbouQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-standalone-preset.js" integrity="sha512-V5Zdt9ZPs6AWG/eQJp9a27pWGO8iBITXxdCD6/VNbO+XdHD8QnhsifVaUKeAkoYumdQZOFy45hPPLZ5aRolGxQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: window.location.origin + '/documentation/swagger.json', // Replace with the path to your Swagger JSON file
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: 'BaseLayout',
            });
        };
    </script>
</body>

</html>