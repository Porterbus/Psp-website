<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require 'db_connect.php';

$sql = "SELECT * FROM Equipment WHERE is_active = TRUE";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Catalogue - TORS</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header class="top-header">
        <div class="header-container">
            <h1>TORS Healthcare</h1>
            <nav aria-label="Main Navigation">
                <span style="margin-right: 15px;">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
                <a href="my_requests.php" style="margin-right: 15px;">My Requests</a>
                <a href="logout.php">Log Out</a> 
            </nav>
        </div>
    </header>

    <div class="catalogue-header">
        <div class="header-container" style="display: block;">
            <h2>Interactive Equipment Catalogue</h2>
            <p>Select items below to build your practical lesson shopping list.</p>
        </div>
    </div>

    <div class="main-layout">
        
        <main class="catalogue-grid">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<div class='item-card'>";
                    echo "<h3>" . htmlspecialchars($row['item_name']) . "</h3>";
                    echo "<p><strong>Category:</strong> " . htmlspecialchars($row['category']) . "</p>";
                    echo "<p><strong>In Stock:</strong> " . htmlspecialchars($row['stock_quantity']) . "</p>";
                    
                    if ($row['needs_health_safety']) {
                        echo "<span class='tag-warning'>⚠ Risk Assessment Required</span>";
                    }
                    echo "<button class='btn-submit add-to-cart-btn' data-id='" . $row['equipment_id'] . "' data-name='" . htmlspecialchars($row['item_name']) . "' style='margin-top: 15px;'>Add to List</button>";
                    echo "</div>";
                }
            } else {
                echo "<p>No equipment found in the database.</p>";
            }
            ?>
        </main>

        <aside class="shopping-cart">
            <h2>Your Practical Lesson List</h2>
            <ul id="cart-items" aria-live="polite">
                <li style="color: #777;">Your list is currently empty.</li>
            </ul>
            <button id="checkout-btn" class="btn-submit" style="background-color: #28a745; display: none;">Finalize Request</button>
        </aside>

    </div>

    <div id="checkout-modal" class="modal-hidden" aria-hidden="true">
        <div class="modal-content">
            <h2 style="color: var(--primary-blue); margin-bottom: 10px;">Finalize Equipment Request</h2>
            <p style="margin-bottom: 20px;">Please provide the details for your practical lesson.</p>
            
            <form id="checkout-form">
                <div class="form-group">
                    <label for="lesson-name">Lesson Name / Topic</label>
                    <input type="text" id="lesson-name" required>
                </div>
                
                <div class="form-group">
                    <label for="room-name">Room / Lab Allocation</label>
                    <input type="text" id="room-name" required>
                </div>
                
                <div class="form-group">
                    <label for="required-date">Date Required</label>
                    <input type="date" id="required-date" required>
                </div>
                
                <div class="form-group">
                    <label for="other-items">Other Items (Not in stock)</label>
                    <textarea id="other-items" rows="2" style="width: 100%; padding: 0.75rem; border: 1px solid #CCC; border-radius: 4px;" placeholder="Describe items and quantities..."></textarea>
                </div>

                <div class="form-group">
                    <label for="upload-file">Attach Files (Risk Assessments / Moulage)</label>
                    <input type="file" id="upload-file" accept=".pdf,.jpg,.png,.doc,.docx" style="width: 100%; padding: 0.5rem; border: 1px solid #CCC; background: #f9f9f9;">
                    <small style="color: var(--error-red); font-weight: bold; display: block; margin-top: 5px;">*Compulsory if requesting hazardous items.</small>
                </div>

                <div class="modal-actions">
                    <button type="button" id="close-modal" class="btn-remove" style="padding: 10px 15px; font-size: 1rem;">Cancel</button>
                    <button type="submit" class="btn-submit" style="margin-top: 0; width: auto; padding: 10px 15px;">Submit Request to TORS</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="JS/cart.js"></script>
</body>
</html>