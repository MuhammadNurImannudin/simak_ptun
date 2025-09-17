<?php
// api/generate-nomor-surat.php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? 'masuk';

try {
    $nomor_surat = generateNomorSurat($type);
    
    echo json_encode([
        'success' => true,
        'nomor_surat' => $nomor_surat,
        'type' => $type
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating nomor surat: ' . $e->getMessage()
    ]);
}
?>
