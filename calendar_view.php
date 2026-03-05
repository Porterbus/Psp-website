<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Planner') {
    echo "<h1>Access Denied. You must be a TORS Planner to view this page.</h1>";
    echo "<a href='index.php'>Return to Login</a>";
    exit();
}

require 'db_connect.php';

$sql = "
    SELECT 
        r.required_date, 
        r.room, 
        r.lesson_name, 
        u.full_name AS academic_name,
        GROUP_CONCAT(CONCAT(ri.quantity, 'x ', e.item_name) SEPARATOR '<br>') as equipment_list
    FROM Requests r
    JOIN Users u ON r.user_id = u.user_id
    LEFT JOIN Request_Items ri ON r.request_id = ri.request_id
    LEFT JOIN Equipment e ON ri.equipment_id = e.equipment_id
    WHERE r.status = 'Approved'
    GROUP BY r.request_id
    ORDER BY r.required_date ASC, r.room ASC
";
$result = $conn->query($sql);

$schedule = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $date = $row['required_date'];
        $schedule[$date][] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TORS Printable Schedule</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header class="top-header no-print">
        <div class="header-container">
            <h1>TORS Master Schedule</h1>
            <nav aria-label="Main Navigation">
                <a href="planner_dashboard.php">Back to Dashboard</a>
            </nav>
        </div>
    </header>

    <main class="calendar-container">
        <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 20px; flex-wrap: wrap;">
            <h2 style="margin: 0;">Approved Practical Lessons</h2>
            <button onclick="window.print()" class="btn-submit" style="width: auto; margin: 0; padding: 10px 20px;">🖨️ Print Schedule</button>
        </div>

        <?php
        if (empty($schedule)) {
            echo "<p>No approved requests to display.</p>";
        } else {
            foreach ($schedule as $date => $lessons) {
                $formattedDate = date("l, d M Y", strtotime($date));
                
                echo "<div class='date-section'>";
                echo "<h2 class='date-header'>" . $formattedDate . "</h2>";
                
                foreach ($lessons as $lesson) {
                    echo "<div class='lesson-card'>";
                    echo "<h3><span class='room-badge'>Room: " . htmlspecialchars($lesson['room']) . "</span> " . htmlspecialchars($lesson['lesson_name']) . "</h3>";
                    echo "<p style='margin-bottom: 15px;'><strong>Requested by:</strong> " . htmlspecialchars($lesson['academic_name']) . "</p>";
                    
                    echo "<p><strong>Equipment Required:</strong></p>";
                    echo "<p style='margin-left: 20px; line-height: 1.5;'>" . ($lesson['equipment_list'] ? $lesson['equipment_list'] : '<em>No stock items</em>') . "</p>";
                    echo "</div>";
                }
                echo "</div>";
            }
        }
        ?>
    </main>

</body>
</html>