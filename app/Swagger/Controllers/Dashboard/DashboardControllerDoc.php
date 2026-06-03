<?php

namespace App\Swagger\Controllers\Dashboard;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/v1/dashboard',
    summary: 'Get role-based dashboard statistics',
    description: 'Returns statistics based on the authenticated user role (admin, doctor, patient, receptionist)',
    tags: ['Dashboard'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Dashboard data retrieved successfully', content: new OA\JsonContent(properties: [
            new OA\Property(property: 'status', type: 'integer', example: 200),
            new OA\Property(property: 'message', type: 'string', example: 'Dashboard retrieved successfully'),
            new OA\Property(property: 'data', type: 'object', oneOf: [
                new OA\Schema(ref: '#/components/schemas/AdminDashboard'),
                new OA\Schema(ref: '#/components/schemas/DoctorDashboard'),
                new OA\Schema(ref: '#/components/schemas/PatientDashboard'),
                new OA\Schema(ref: '#/components/schemas/ReceptionistDashboard'),
            ]),
        ])),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
class DashboardControllerDoc {}

#[OA\Schema(
    schema: 'AdminDashboard',
    description: 'Admin dashboard statistics',
    properties: [
        new OA\Property(property: 'users', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 150),
            new OA\Property(property: 'doctors', type: 'integer', example: 25),
            new OA\Property(property: 'patients', type: 'integer', example: 100),
            new OA\Property(property: 'receptionists', type: 'integer', example: 10),
            new OA\Property(property: 'admins', type: 'integer', example: 5),
            new OA\Property(property: 'active', type: 'integer', example: 140),
            new OA\Property(property: 'inactive', type: 'integer', example: 10),
            new OA\Property(property: 'new_today', type: 'integer', example: 3),
            new OA\Property(property: 'new_this_week', type: 'integer', example: 15),
            new OA\Property(property: 'new_this_month', type: 'integer', example: 50),
        ]),
        new OA\Property(property: 'appointments', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 500),
            new OA\Property(property: 'today', type: 'integer', example: 12),
            new OA\Property(property: 'this_week', type: 'integer', example: 60),
            new OA\Property(property: 'this_month', type: 'integer', example: 200),
            new OA\Property(property: 'by_status', type: 'object', properties: [
                new OA\Property(property: 'pending', type: 'integer', example: 30),
                new OA\Property(property: 'confirmed', type: 'integer', example: 100),
                new OA\Property(property: 'completed', type: 'integer', example: 350),
                new OA\Property(property: 'cancelled', type: 'integer', example: 20),
            ]),
        ]),
        new OA\Property(property: 'medical_records', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 300),
        ]),
        new OA\Property(property: 'prescriptions', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 450),
        ]),
        new OA\Property(property: 'specializations', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 15),
            new OA\Property(property: 'top', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Cardiology'),
                new OA\Property(property: 'doctors_count', type: 'integer', example: 5),
            ])),
        ]),
        new OA\Property(property: 'ratings', type: 'object', properties: [
            new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.2),
            new OA\Property(property: 'total', type: 'integer', example: 80),
            new OA\Property(property: 'negative_count', type: 'integer', example: 5),
            new OA\Property(property: 'top_positive', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'doctor_id', type: 'integer', example: 1),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.9),
                new OA\Property(property: 'total', type: 'integer', example: 20),
            ])),
            new OA\Property(property: 'lowest_positive', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'doctor_id', type: 'integer', example: 5),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'Jane Smith'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 3.1),
                new OA\Property(property: 'total', type: 'integer', example: 10),
            ])),
            new OA\Property(property: 'most_rated', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'doctor_id', type: 'integer', example: 2),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'Alice Brown'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.5),
                new OA\Property(property: 'total', type: 'integer', example: 50),
            ])),
            new OA\Property(property: 'top_per_specialization', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'specialization_id', type: 'integer', example: 1),
                new OA\Property(property: 'specialization_name', type: 'string', example: 'Cardiology'),
                new OA\Property(property: 'doctor_id', type: 'integer', example: 1),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.8),
                new OA\Property(property: 'total', type: 'integer', example: 15),
            ])),
        ]),
    ]
)]
class AdminDashboardSchema {}

#[OA\Schema(
    schema: 'DoctorDashboard',
    description: 'Doctor dashboard statistics',
    properties: [
        new OA\Property(property: 'patients', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 50),
            new OA\Property(property: 'new_this_month', type: 'integer', example: 5),
        ]),
        new OA\Property(property: 'appointments', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 200),
            new OA\Property(property: 'today', type: 'integer', example: 8),
            new OA\Property(property: 'upcoming', type: 'integer', example: 15),
            new OA\Property(property: 'by_status', type: 'object', properties: [
                new OA\Property(property: 'pending', type: 'integer', example: 5),
                new OA\Property(property: 'confirmed', type: 'integer', example: 15),
                new OA\Property(property: 'completed', type: 'integer', example: 175),
                new OA\Property(property: 'cancelled', type: 'integer', example: 5),
            ]),
        ]),
        new OA\Property(property: 'medical_records', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 180),
        ]),
        new OA\Property(property: 'prescriptions', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 250),
        ]),
        new OA\Property(property: 'ratings', type: 'object', properties: [
            new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.5),
            new OA\Property(property: 'total', type: 'integer', example: 30),
            new OA\Property(property: 'negative_count', type: 'integer', example: 2),
        ]),
    ]
)]
class DoctorDashboardSchema {}

#[OA\Schema(
    schema: 'PatientDashboard',
    description: 'Patient dashboard statistics',
    properties: [
        new OA\Property(property: 'doctors', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 3),
        ]),
        new OA\Property(property: 'appointments', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 15),
            new OA\Property(property: 'upcoming', type: 'integer', example: 2),
            new OA\Property(property: 'by_status', type: 'object', properties: [
                new OA\Property(property: 'pending', type: 'integer', example: 1),
                new OA\Property(property: 'confirmed', type: 'integer', example: 2),
                new OA\Property(property: 'completed', type: 'integer', example: 10),
                new OA\Property(property: 'cancelled', type: 'integer', example: 2),
            ]),
        ]),
        new OA\Property(property: 'medical_records', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 5),
        ]),
        new OA\Property(property: 'prescriptions', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 8),
        ]),
    ]
)]
class PatientDashboardSchema {}

#[OA\Schema(
    schema: 'ReceptionistDashboard',
    description: 'Receptionist dashboard statistics',
    properties: [
        new OA\Property(property: 'appointments', type: 'object', properties: [
            new OA\Property(property: 'today_total', type: 'integer', example: 20),
            new OA\Property(property: 'by_status', type: 'object', properties: [
                new OA\Property(property: 'pending', type: 'integer', example: 5),
                new OA\Property(property: 'confirmed', type: 'integer', example: 8),
                new OA\Property(property: 'completed', type: 'integer', example: 5),
                new OA\Property(property: 'cancelled', type: 'integer', example: 2),
            ]),
        ]),
        new OA\Property(property: 'patients', type: 'object', properties: [
            new OA\Property(property: 'registered_today', type: 'integer', example: 3),
            new OA\Property(property: 'total', type: 'integer', example: 100),
        ]),
        new OA\Property(property: 'doctors', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 25),
        ]),
        new OA\Property(property: 'medical_records', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 300),
        ]),
        new OA\Property(property: 'prescriptions', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer', example: 450),
        ]),
        new OA\Property(property: 'ratings', type: 'object', properties: [
            new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.2),
            new OA\Property(property: 'total', type: 'integer', example: 80),
            new OA\Property(property: 'negative_count', type: 'integer', example: 5),
            new OA\Property(property: 'top_positive', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'doctor_id', type: 'integer', example: 1),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.9),
                new OA\Property(property: 'total', type: 'integer', example: 20),
            ])),
            new OA\Property(property: 'lowest_positive', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'doctor_id', type: 'integer', example: 5),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'Jane Smith'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 3.1),
                new OA\Property(property: 'total', type: 'integer', example: 10),
            ])),
            new OA\Property(property: 'most_rated', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'doctor_id', type: 'integer', example: 2),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'Alice Brown'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.5),
                new OA\Property(property: 'total', type: 'integer', example: 50),
            ])),
            new OA\Property(property: 'top_per_specialization', type: 'array', items: new OA\Items(type: 'object', properties: [
                new OA\Property(property: 'specialization_id', type: 'integer', example: 1),
                new OA\Property(property: 'specialization_name', type: 'string', example: 'Cardiology'),
                new OA\Property(property: 'doctor_id', type: 'integer', example: 1),
                new OA\Property(property: 'doctor_name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'average', type: 'number', format: 'float', example: 4.8),
                new OA\Property(property: 'total', type: 'integer', example: 15),
            ])),
        ]),
    ]
)]
class ReceptionistDashboardSchema {}
