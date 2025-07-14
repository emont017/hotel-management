<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Night Audit";
require_once __DIR__ . '/../includes/header.php';

$current_business_date = '2025-07-14'; // Placeholder
$last_audit_date = '2025-07-13 23:45'; // Placeholder
$last_audit_by = 'Manager Name'; // Placeholder
$pending_departures = 3; // Placeholder
$potential_no_shows = 1; // Placeholder
$message = ''; // Placeholder for status messages
$message_type = 'info'; // Placeholder
?>

<style>
    /* Styles for the Night Audit page layout */
    .night-audit-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    .audit-header {
        text-align: center;
        margin-bottom: 25px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 15px;
    }
    .audit-header h2 {
        color: #081C3A; /* Dark Blue from theme */
        font-family: 'Orbitron', sans-serif;
    }
    .audit-header p {
        font-size: 1.1rem;
        color: #555;
    }
    .audit-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
        text-align: center;
    }
    .status-card {
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .status-card h4 {
        margin-top: 0;
        color: #122C55; /* Medium Blue from theme */
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-card p {
        font-size: 1.5rem;
        font-weight: bold;
        color: #081C3A;
        margin-bottom: 0;
        font-family: 'Roboto', sans-serif;
    }
    .audit-checklist {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
    }
    .audit-checklist h3 {
        margin-top: 0;
        color: #122C55;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .audit-checklist ul {
        list-style-type: none;
        padding-left: 0;
    }
    .audit-checklist li {
        padding: 10px;
        border-bottom: 1px solid #f2f2f2;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .audit-checklist li:last-child {
        border-bottom: none;
    }
    .audit-checklist strong {
        font-size: 1.1rem;
        color: #081C3A;
    }
    .audit-actions {
        text-align: center;
        padding: 25px;
        background: #fff3cd; /* Light yellow warning background */
        border: 1px solid #ffeeba;
        border-radius: 8px;
    }
    .audit-actions p {
        font-weight: bold;
        margin-top: 0;
        color: #856404; /* Dark yellow text */
        font-size: 1.1rem;
    }
    .audit-actions .btn-run-audit {
        background-color: #dc3545; /* Red for critical action */
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-size: 1.2rem;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
        text-transform: uppercase;
    }
    .audit-actions .btn-run-audit:hover, .audit-actions .btn-run-audit:focus {
        background-color: #c82333; /* Darker red on hover */
    }
    .flash-message {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        color: #fff;
        text-align: center;
        font-weight: bold;
    }
    .flash-message.success { background-color: #28a745; }
    .flash-message.error { background-color: #dc3545; }
</style>

<div class="night-audit-container">
    <div class="audit-header">
        <h2>Night Audit</h2>
        <p>Finalize the day's business and advance the hotel to the next calendar day.</p>
    </div>

    <?php if ($message): ?>
        <div class="flash-message <?= htmlspecialchars($message_type) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="audit-status-grid">
        <div class="status-card">
            <h4>Current Business Date</h4>
            <p><?= date("F j, Y", strtotime($current_business_date)) ?></p>
        </div>
        <div class="status-card">
            <h4>Last Audit Run</h4>
            <p><?= date("M j, Y H:i", strtotime($last_audit_date)) ?></p>
        </div>
        <div class="status-card">
            <h4>Last Audit By</h4>
            <p><?= htmlspecialchars($last_audit_by) ?></p>
        </div>
    </div>

    <div class="audit-checklist">
        <h3>Pre-Audit Checklist for <?= date("F j, Y", strtotime($current_business_date)) ?></h3>
        <ul>
            <li>
                <span>Pending Departures to Process</span>
                <strong><?= $pending_departures ?></strong>
            </li>
            <li>
                <span>Potential No-Shows to Process</span>
                <strong><?= $potential_no_shows ?></strong>
            </li>
             <li>
                <span>Reconcile Daily Payments</span>
                <strong>Pending</strong>
            </li>
        </ul>
    </div>

    <div class="audit-actions">
        <p>This process is IRREVERSIBLE. Confirm all daily tasks are complete.</p>
        <form method="POST" onsubmit="return confirm('This action cannot be undone. Are you absolutely sure you want to run the Night Audit?');">
            <button type="submit" name="run_audit" class="btn-run-audit">Run Night Audit</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>