<?php
// Start output buffering
ob_start();

require('fpdf/fpdf.php');
include 'admin/db_connect.php';

// Suppress any errors to avoid output before PDF generation
error_reporting(E_ERROR);
ini_set('display_errors', 0);

if (!file_exists('fpdf')) {
    ob_end_clean(); // Clean the buffer before dying
    die("FPDF library not found. Please download and install FPDF from <a href='http://www.fpdf.org/'>http://www.fpdf.org/</a> into the fpdf directory.");
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$connected_filter = isset($_GET['connected']) ? $_GET['connected'] : '';
$batch_filter = isset($_GET['batch']) ? $_GET['batch'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';

$filter_conditions = [];
$params = [];

if (!empty($search)) {
    $filter_conditions[] = "(a.firstname LIKE ? OR a.lastname LIKE ? OR a.alumni_id LIKE ? OR c.course LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($course_filter > 0) {
    $filter_conditions[] = "a.course_id = ?";
    $params[] = $course_filter;
}

if ($status_filter !== '') {
    $filter_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($connected_filter !== '') {
    $filter_conditions[] = "a.connected_to = ?";
    $params[] = $connected_filter;
}

if ($batch_filter !== '') {
    $filter_conditions[] = "a.batch = ?";
    $params[] = $batch_filter;
}

if ($gender_filter !== '') {
    $filter_conditions[] = "a.gender = ?";
    $params[] = $gender_filter;
}

$where_clause = count($filter_conditions) > 0 ? " WHERE " . implode(" AND ", $filter_conditions) : "";

$courses_query = "SELECT id, course FROM courses ORDER BY course ASC";
$courses_result = $conn->query($courses_query);
$courses = [];
while ($course_row = $courses_result->fetch_assoc()) {
    $courses[$course_row['id']] = $course_row['course'];
}

$alumni_query = "SELECT a.*, c.course as course_name FROM alumnus_bio a 
                LEFT JOIN courses c ON a.course_id = c.id" . 
                $where_clause . 
                " ORDER BY a.lastname ASC";

$stmt = $conn->prepare($alumni_query);

if (count($params) > 0) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$alumni_result = $stmt->get_result();

class PDF extends FPDF {
    public $lightBlue = false;
    
    function Header() {
        $mainColor = [31, 70, 144];
        $accentColor = [82, 171, 229]; 
        
        $this->SetFillColor($mainColor[0], $mainColor[1], $mainColor[2]);
        $this->Rect(0, 0, 220, 30, 'F');
        
        if (file_exists('images/logo.png')) {
            $this->Image('images/logo.png', 15, 5, 20);
        }
        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(40); 
        $this->Cell(130, 10, 'PAMANTASAN NG LUNGSOD NG PASIG', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(40); 
        $this->Cell(130, 10, 'ALUMNI DIRECTORY', 0, 0, 'C');
        $this->Ln(15);
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 10, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'R');
        
        if (!empty($_GET['search']) || !empty($_GET['course']) || !empty($_GET['batch']) || 
            isset($_GET['status']) || isset($_GET['connected']) || !empty($_GET['gender'])) {
            
            $this->SetFillColor(245, 245, 245);
            $this->Rect(10, $this->GetY(), 190, 20, 'F');
            
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor($mainColor[0], $mainColor[1], $mainColor[2]);
            $this->Cell(190, 6, 'APPLIED FILTERS', 0, 1, 'L');
            
            $filters = array();
            if (!empty($_GET['search'])) $filters[] = "Search: " . $_GET['search'];
            if (!empty($_GET['course']) && $_GET['course'] > 0) {
                global $courses;
                $course_id = $_GET['course'];
                $filters[] = "Course: " . (isset($courses[$course_id]) ? $courses[$course_id] : "Course ID $course_id");
            }
            if (!empty($_GET['batch'])) $filters[] = "Batch: " . $_GET['batch'];
            if (isset($_GET['status']) && $_GET['status'] === '1') $filters[] = "Status: Verified";
            if (isset($_GET['status']) && $_GET['status'] === '0') $filters[] = "Status: Unverified";
            if (isset($_GET['connected']) && $_GET['connected'] === '1') $filters[] = "Connected: Yes";
            if (isset($_GET['connected']) && $_GET['connected'] === '0') $filters[] = "Connected: No";
            if (!empty($_GET['gender'])) $filters[] = "Gender: " . $_GET['gender'];
            
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(0, 0, 0);
            $this->MultiCell(190, 4, implode(' | ', $filters), 0, 'L');
            $this->Ln(5);
        } else {
            $this->Ln(10);
        }
        
        $this->SetFillColor($accentColor[0], $accentColor[1], $accentColor[2]);
        $this->SetTextColor(255);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
        
        $w = array(25, 45, 55, 15, 25, 25);
        
        $this->Cell($w[0], 7, 'ALUMNI ID', 1, 0, 'C', true);
        $this->Cell($w[1], 7, 'NAME', 1, 0, 'C', true);
        $this->Cell($w[2], 7, 'COURSE', 1, 0, 'C', true);
        $this->Cell($w[3], 7, 'BATCH', 1, 0, 'C', true);
        $this->Cell($w[4], 7, 'GENDER', 1, 0, 'C', true);
        $this->Cell($w[5], 7, 'STATUS', 1, 0, 'C', true);
        $this->Ln();
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Page '.$this->PageNo().' of {nb}', 0, 0, 'C');
        $this->Cell(0, 10, 'PLP Alumni Portal - Confidential', 0, 0, 'R');
    }
    
    function Row($data, $isAlternate = false) {
        $w = array(25, 45, 55, 15, 25, 25);
        
        if ($isAlternate) {
            $this->SetFillColor(240, 240, 240);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $this->Cell($w[0], 6, $this->truncateText($data[0], $w[0]), 'LR', 0, 'L', true);
        $this->Cell($w[1], 6, $this->truncateText($data[1], $w[1]), 'LR', 0, 'L', true);
        $this->Cell($w[2], 6, $this->truncateText($data[2], $w[2]), 'LR', 0, 'L', true);
        $this->Cell($w[3], 6, $data[3], 'LR', 0, 'C', true);
        $this->Cell($w[4], 6, $data[4], 'LR', 0, 'C', true);
        $this->Cell($w[5], 6, $this->truncateText($data[5], $w[5]), 'LR', 0, 'C', true);
        $this->Ln();
    }
    
    function truncateText($text, $width) {
        $fontSize = $this->FontSize;
        $fontWidth = $this->GetStringWidth($text);
        
        if ($fontWidth <= $width - 4) {
            return $text;
        }
        
        // More efficient truncation algorithm
        $ratio = ($width - 4) / $fontWidth;
        $charLimit = floor(strlen($text) * $ratio) - 3;
        if ($charLimit < 5) $charLimit = 5;
        
        return substr($text, 0, $charLimit) . '...';
    }
}

ob_clean();

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('P', 'Letter');
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetFont('Arial', '', 9);

if ($alumni_result->num_rows > 0) {
    $rowCount = 0;
    while ($row = $alumni_result->fetch_assoc()) {
        if ($row['status'] == 1) {
            $status = 'Ver';
            $status .= ($row['connected_to'] == 1) ? '/Con' : '/NC';
        } else {
            $status = 'Unv';
            $status .= ($row['connected_to'] == 1) ? '/Con' : '/NC';
        }
        
        $rowData = array(
            $row['alumni_id'],
            $row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename'],
            $row['course_name'],
            $row['batch'],
            $row['gender'],
            $status
        );
        
        $pdf->Row($rowData, $rowCount % 2 == 1);
        $rowCount++;
        
        $pdf->Cell(190, 0, '', 'T');
        $pdf->Ln();
        
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            $rowCount = 0; 
        }
    }
} else {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->SetTextColor(150, 0, 0);
    
    if (count($filter_conditions) > 0) {
        $pdf->Cell(0, 20, 'No alumni match your filter criteria.', 0, 1, 'C');
    } else {
        $pdf->Cell(0, 20, 'No alumni records found.', 0, 1, 'C');
    }
}

while (ob_get_level()) {
    ob_end_clean();
}

$pdf->Output('D', 'PLP_Alumni_List_'.date('Y-m-d').'.pdf');
