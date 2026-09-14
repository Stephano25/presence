<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatDate($date) {
    if (empty($date)) return '-';
    return date('d/m/Y', strtotime($date));
}

function calculateWorkHours($entree, $sortie) {
    if (!$entree || !$sortie) return 0;
    $diff = strtotime($sortie) - strtotime($entree);
    return round($diff / 3600, 2);
}

function getStatusBadge($status) {
    $classes = [
        'actif' => 'badge-success',
        'inactif' => 'badge-danger',
        'en_attente' => 'badge-warning'
    ];
    $labels = [
        'actif' => 'Actif',
        'inactif' => 'Inactif',
        'en_attente' => 'En attente'
    ];
    return '<span class="badge ' . ($classes[$status] ?? '') . '">' . ($labels[$status] ?? $status) . '</span>';
}

function getTypeBadge($type) {
    if ($type == 'entree') {
        return '<span class="badge badge-success">Entrée</span>';
    } else {
        return '<span class="badge badge-danger">Sortie</span>';
    }
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($data[0] ?? []));
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

function exportToExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Pointages</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    echo '<body><table>';
    
    // En-têtes
    echo '<tr>';
    foreach (array_keys($data[0] ?? []) as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    
    // Données
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table></body></html>';
    exit();
}
?>