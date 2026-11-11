<?php
declare(strict_types=1);
// Root-level compatibility endpoint for ByetHost deployments.
// A direct browser visit should return to the public registration form;
// POST requests are delegated to the actual PMB processor.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /pmb.php', true, 302);
    exit;
}
require __DIR__ . '/website/pmb-form-handler.php';
