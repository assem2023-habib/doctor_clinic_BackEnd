<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(version: '1.0.0', title: 'Doctor Clinic API', description: 'API for managing doctor clinic appointments, medical records, and prescriptions')]
#[OA\Server(url: 'http://localhost', description: 'Local server')]
#[OA\Server(url: 'http://localhost:8081', description: 'Docker server')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', scheme: 'bearer', bearerFormat: 'Passport')]
class OpenApiSpec
{
}
