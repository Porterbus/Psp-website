<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Planner') {
    echo "<h1>Access Denied. You must be a TORS Planner to view this page.</h1>";
    echo "<a href='index.php'>Return to Login</a>";
    exit();
}

require 'db_connect.php';

$conflict_sql = "
    SELECT ri.equipment_id, r.required_date, e.item_name, e.stock_quantity, SUM(ri.quantity) as total_req
    FROM Request_Items ri
    JOIN Requests r ON ri.request_id = r.request_id
    JOIN Equipment e ON ri.equipment_id = e.equipment_id
    GROUP BY ri.equipment_id, r.required_date
    HAVING total_req > e.stock_quantity
";
$conflict_result = $conn->query($conflict_sql);

$conflicts = [];
if ($conflict_result->num_rows > 0) {
    while($c = $conflict_result->fetch_assoc()) {
        $conflicts[$c['required_date']][] = $c['item_name']; 
    }
}

$notif_sql = "
    SELECT 
        COUNT(DISTINCT r.request_id) as pending_count,
        SUM(e.needs_health_safety) as hs_items_count
    FROM Requests r
    LEFT JOIN Request_Items ri ON r.request_id = ri.request_id
    LEFT JOIN Equipment e ON ri.equipment_id = e.equipment_id
    WHERE r.status = 'Pending'
";
$notif_result = $conn->query($notif_sql);
$notif_data = $notif_result->fetch_assoc();

$pendingCount = $notif_data['pending_count'] ?? 0;
$hsCount = $notif_data['hs_items_count'] ?? 0;

$sql = "
    SELECT 
        r.request_id, u.full_name AS academic_name, r.lesson_name, r.room, r.required_date, 
        r.status, r.other_items, r.attached_file, r.created_at,
        GROUP_CONCAT(CONCAT(ri.quantity, 'x ', e.item_name) SEPARATOR '<br>') as equipment_list
    FROM Requests r
    JOIN Users u ON r.user_id = u.user_id
    LEFT JOIN Request_Items ri ON r.request_id = ri.request_id
    LEFT JOIN Equipment e ON ri.equipment_id = e.equipment_id
    GROUP BY r.request_id
    ORDER BY r.required_date ASC
";
$result = $conn->query($sql);

$total_requests = 0;
$approved_requests = 0;
$pending_requests = 0;
$rejected_requests = 0;
$double_booked_requests = 0;

$all_requests = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total_requests++;
        
        if ($row['status'] === 'Approved') $approved_requests++;
        if ($row['status'] === 'Pending') $pending_requests++;
        if ($row['status'] === 'Rejected') $rejected_requests++;

        $reqDate = $row['required_date'];
        $has_conflict = false;
        if (isset($conflicts[$reqDate])) {
            foreach ($conflicts[$reqDate] as $conflictItem) {
                if (strpos($row['equipment_list'], $conflictItem) !== false) {
                    $has_conflict = true;
                    break;
                }
            }
        }
        
        if ($has_conflict) {
            $double_booked_requests++;
            $row['has_conflict'] = true; 
            $row['conflict_items'] = $conflicts[$reqDate];
        } else {
            $row['has_conflict'] = false;
        }
        
        $all_requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TORS Planning Dashboard</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header class="top-header">
        <div class="header-container">
            <h1>TORS Planning Interface</h1>
            <nav aria-label="Main Navigation">
                <span style="margin-right: 15px;">Planner: <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="calendar_view.php" style="margin-right: 15px;">View Calendar</a>
                <a href="logout.php">Log Out</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-container">
        <h2 style="color: var(--primary-blue); margin-bottom: 20px;">TORS Dashboard Overview</h2>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Requests</h3>
                <p class="stat-number"><?php echo $total_requests; ?></p>
            </div>
            <div class="stat-card" style="border-top-color: #28a745;">
                <h3>Approved</h3>
                <p class="stat-number" style="color: #28a745;"><?php echo $approved_requests; ?></p>
            </div>
            <div class="stat-card" style="border-top-color: #f39c12;">
                <h3>Pending</h3>
                <p class="stat-number" style="color: #f39c12;"><?php echo $pending_requests; ?></p>
            </div>
            <div class="stat-card" style="border-top-color: #6c757d;">
                <h3>Rejected</h3>
                <p class="stat-number" style="color: #6c757d;"><?php echo $rejected_requests; ?></p>
            </div>
            <div class="stat-card stat-warning">
                <h3>Double Booked</h3>
                <p class="stat-number"><?php echo $double_booked_requests; ?></p>
            </div>
        </div>

        <?php if ($pendingCount > 0): ?>
            <div class="notification-banner">
                <span><strong>🔔 System Alert:</strong> You have <strong><?php echo $pendingCount; ?></strong> new equipment request(s) awaiting your approval.</span>
            </div>
            <?php if ($hsCount > 0): ?>
                <div class="notification-banner notification-hs-alert">
                    <span><strong>⚠ HEALTH & SAFETY ALERT:</strong> Pending requests contain hazardous equipment. Risk assessment files must be reviewed before approval!</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; margin-bottom: 20px;">
            <h2 style="color: var(--primary-blue); margin: 0;">Incoming Equipment Requests</h2>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="status-filter" style="margin: 0; font-weight: bold; color: var(--primary-blue);">Filter Status:</label>
                <select id="status-filter" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #CCC; font-size: 1rem;">
                    <option value="All">All Requests</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Req #</th>
                        <th>Academic</th>
                        <th>Lesson Details</th>
                        <th>Required Date</th>
                        <th>Equipment Requested</th>
                        <th>Other / Files</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="requests-tbody">
                    <?php
                    if (!empty($all_requests)) {
                        foreach($all_requests as $row) {
                            echo "<tr class='request-row' data-status='" . $row['status'] . "'>";
                            
                            echo "<td>" . $row['request_id'] . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['academic_name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['lesson_name']) . "<br><small>Room: " . htmlspecialchars($row['room']) . "</small></td>";
                            echo "<td>" . date("d M Y", strtotime($row['required_date'])) . "</td>";
                            
                            echo "<td>";
                            if ($row['has_conflict']) {
                                foreach ($row['conflict_items'] as $conflictItem) {
                                    if (strpos($row['equipment_list'], $conflictItem) !== false) {
                                        echo "<span class='conflict-warning'>⚠ DOUBLE BOOKED: " . htmlspecialchars($conflictItem) . "</span>";
                                    }
                                }
                            }
                            echo ($row['equipment_list'] ? $row['equipment_list'] : '<em>No stock items</em>');
                            echo "</td>";
                            
                            echo "<td>";
                            if (!empty($row['other_items'])) echo "<p><strong>Other:</strong> " . nl2br(htmlspecialchars($row['other_items'])) . "</p>";
                            if (!empty($row['attached_file'])) echo "<a href='uploads/" . htmlspecialchars($row['attached_file']) . "' target='_blank' style='color: var(--primary-blue); font-weight: bold;'>View Attached File</a>";
                            echo "</td>";

                            $badgeClass = "status-" . $row['status'];
                            $badgeStyle = "";
                            if ($row['status'] === 'Rejected') {
                                $badgeStyle = "background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;";
                            }
                            echo "<td><span class='status-badge {$badgeClass}' style='{$badgeStyle}'>" . $row['status'] . "</span></td>";
                            
                            echo "<td>";
                            if ($row['status'] === 'Pending') {
                                echo "<button onclick='updateStatus(" . $row['request_id'] . ", \"Approved\")' class='btn-submit' style='padding: 5px 10px; font-size: 0.9rem; margin-bottom: 5px; background-color: #28a745;'>Approve</button><br>";
                                echo "<button onclick='updateStatus(" . $row['request_id'] . ", \"Rejected\")' class='btn-submit' style='padding: 5px 10px; font-size: 0.9rem; margin-top: 5px; background-color: var(--error-red);'>Reject</button>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align: center;'>No requests found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        document.getElementById('status-filter').addEventListener('change', function() {
            const selectedStatus = this.value;
            const rows = document.querySelectorAll('.request-row'); 

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                
                if (selectedStatus === 'All' || rowStatus === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function updateStatus(requestId, newStatus) {
            if (confirm('Are you sure you want to mark Request #' + requestId + ' as ' + newStatus + '?')) {
                fetch('update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, new_status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); 
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error occurred.');
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>