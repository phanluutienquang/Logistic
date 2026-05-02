<?php

namespace app\api\controller;

use think\Controller;
use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="Zhaunyun API Documentation",
 *     version="1.0.0",
 *     description="API documentation for Zhaunyun logistics system"
 *   )
 * )
 * @OA\Server(
 *     url="/index.php?s=/api",
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="apiKey",
 *     in="query",
 *     name="token",
 *     description="Enter your token here"
 * )
 */
class Swagger extends Controller
{
    /**
     * Swagger UI page
     */
    public function index()
    {
        // Get the absolute URL for the JSON endpoint
        $jsonUrl = url('api/swagger/json');
        
        // If the URL generation doesn't work as expected in this environment, 
        // we can fallback to a relative path or a hardcoded one if needed.
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zhaunyun API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3/swagger-ui.css" >
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@3/favicon-32x32.png" sizes="32x32" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
        #swagger-ui { min-height: 100vh; }
    </style>
</head>
<body>
    <div id="swagger-ui">
        <div style="padding: 20px; font-family: sans-serif;">
            <h2>Loading API Documentation...</h2>
            <p>If this page remains blank, please check:</p>
            <ul>
                <li>Your internet connection (to load Swagger UI assets from unpkg.com)</li>
                <li>Browser console for any errors (F12 -> Console)</li>
                <li>The JSON endpoint: <a href="{$jsonUrl}">{$jsonUrl}</a></li>
            </ul>
        </div>
    </div>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-bundle.js"> </script>
    <script src="https://unpkg.com/swagger-ui-dist@3/swagger-ui-standalone-preset.js"> </script>
    <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        url: "{$jsonUrl}",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout"
      })
      window.ui = ui
    }
  </script>
</body>
</html>
HTML;
        return response($html, 200, [], 'html');
    }

    /**
     * Generate Swagger JSON
     */
    public function json()
    {
        // Scan the API controllers directory
        $path = APP_PATH . 'api'; 
        
        // Generate OpenAPI object
        $openapi = \OpenApi\scan($path);
        
        // Return as JSON with appropriate header
        return json($openapi)->header('Content-Type', 'application/json');
    }
}
