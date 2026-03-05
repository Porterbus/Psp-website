<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'db_connect.php';

$userId = $_SESSION['user_id'];

$sql = "
    SELECT 
        r.request_id, r.lesson_name, r.room, r.required_date, 
        r.status, r.other_items, r.attached_file, r.created_at,
        GROUP_CONCAT(CONCAT(ri.quantity, 'x ', e.item_name) SEPARATOR '<br>') as equipment_list
    FROM Requests r
    LEFT JOIN Request_Items ri ON r.request_id = ri.request_id
    LEFT JOIN Equipment e ON ri.equipment_id = e.equipment_id
    WHERE r.user_id = ?
    GROUP BY r.request_id
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - TORS</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header class="top-header">
        <div class="header-container">
            <h1>TORS Healthcare</h1>
            <nav aria-label="Main Navigation">
                <span style="margin-right: 15px;">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                <a href="catalogue.php" style="margin-right: 15px;">New Request</a>
                <a href="logout.php">Log Out</a> 
            </nav>
        </div>
    </header>

    <main class="dashboard-container">
        <h2 style="color: var(--primary-blue); margin-bottom: 20px;">My Equipment Requests</h2>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Req #</th>
                        <th>Submitted On</th>
                        <th>Lesson Details</th>
                        <th>Required Date</th>
                        <th>Equipment Requested</th>
                        <th>Other / Files</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row['request_id'] . "</td>";
                            echo "<td>" . date("d M Y, H:i", strtotime($row['created_at'])) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['lesson_name']) . "</strong><br><small>Room: " . htmlspecialchars($row['room']) . "</small></td>";
                            echo "<td>" . date("d M Y", strtotime($row['required_date'])) . "</td>";
                            
                            echo "<td>" . ($row['equipment_list'] ? $row['equipment_list'] : '<em>No stock items</em>') . "</td>";
                            
                            echo "<td>";
                            if (!empty($row['other_items'])) echo "<p><strong>Other:</strong> " . nl2br(htmlspecialchars($row['other_items'])) . "</p>";
                            if (!empty($row['attached_file'])) echo "<a href='uploads/" . htmlspecialchars($row['attached_file']) . "' target='_blank' style='color: var(--primary-blue); font-weight: bold;'>View File</a>";
                            echo "</td>";

                            $badgeClass = "status-" . $row['status'];
                            $badgeStyle = "";
                            if ($row['status'] === 'Rejected') {
                                $badgeStyle = "background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;";
                            }
                            echo "<td><span class='status-badge {$badgeClass}' style='{$badgeStyle}'>" . $row['status'] . "</span></td>";
                            
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center;'>You have not submitted any requests yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>