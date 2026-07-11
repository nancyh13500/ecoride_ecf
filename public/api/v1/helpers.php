<?php

declare(strict_types=1);

function apiSendResponse(bool $success, mixed $data = null, ?array $error = null, int $code = 200): never
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function apiSendError(string $message, int $code = 400): never
{
    apiSendResponse(false, null, [
        'code' => $code,
        'message' => $message,
    ], $code);
}

function apiSetupCors(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function apiParseJsonBody(): array
{
    $input = json_decode(file_get_contents('php://input') ?: '', true);

    return is_array($input) ? $input : [];
}
