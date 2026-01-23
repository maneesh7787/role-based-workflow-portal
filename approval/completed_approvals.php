<?php
/**
 * Approval - Completed Approvals
 */
require_once '../config/config.php';

// Check if user is logged in and is in Approval role
if (!isLoggedIn() || !hasRole('Approval')) {
    redirect('../login.php');
}

$page_title = 'Completed Approvals';
$db = getDB();

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query - show requests that have been through approval
$where_conditions = ["r.status IN ('Sent to Sales', 'Approved', 'Closed', 'Returned to Design')"];
$params = [];
$types = '';

if ($search !== '') {
    $where_conditions[] = "(r.event_name LIKE ? OR r.location LIKE ? OR u.full_name LIKE ?)";
    $search_param1 = "%$search%";
    $search_param2 = "%$search%";
    $search_param3 = "%$search%";
    $params[] = $search_param1;
    $params[] = $search_param2;
    $params[] = $search_param3;
    $types .= 'sss';
}

if ($status_filter !== '') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT r.id) as total 
                FROM requests r 
                JOIN users u ON r.created_by = u.id 
                LEFT JOIN approvals a ON r.id = a.request_id
                WHERE $where_clause";

if (!empty($params)) {
    $count_stmt = $db->prepare($count_query);
    if ($types) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $db->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Fetch requests with approval data
$query = "SELECT r.*, u.full_name as created_by_name,
          a.pricing, a.remarks, a.approved_at, au.full_name as approved_by_name
          FROM requests r 
          JOIN users u ON r.created_by = u.id 
          LEFT JOIN approvals a ON r.id = a.request_id
          LEFT JOIN users au ON a.approved_by = au.id
          WHERE $where_clause
          ORDER BY r.updated_at DESC 
          LIMIT ? OFFSET ?";

$requests = [];
if (!empty($params)) {
    $stmt = $db->prepare($query);
    $params[] = $records_per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $db->prepare($query);
    $stmt->bind_param('ii', $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-check-double"></i> Completed Approvals</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="pending_requests.php" class="btn btn-primary">
            <i class="fas fa-hourglass-half"></i> Pending Approvals
        </a>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Event name, location, or created by..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Sent to Sales" <?php echo $status_filter === 'Sent to Sales' ? 'selected' : ''; ?>>Sent to Sales</option>
                    <option value="Approved" <?php echo $status_filter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                    <option value="Returned to Design" <?php echo $status_filter === 'Returned to Design' ? 'selected' : ''; ?>>Returned to Design</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="completed_approvals.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="card">
    <div class="card-body">
        <?php if (count($requests) > 0): ?>
        <div class="mb-3">
            <p class="text-muted">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> 
                of <?php echo $total_records; ?> results
            </p>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Name</th>
                        <th>Location</th>
                        <th>Event Date</th>
                        <th>Status</th>
                        <th>Pricing</th>
                        <th>Approved By</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo $request['id']; ?></td>
                        <td><?php echo htmlspecialchars($request['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['location']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($request['event_date'])); ?></td>
                        <td>
                            <span class="badge status-badge status-<?php echo strtolower(str_replace(' ', '-', $request['status'])); ?>">
                                <?php echo $request['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($request['pricing']): ?>
                                <strong>$<?php echo number_format($request['pricing'], 2); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($request['approved_by_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?></td>
                        <td>
                            <a href="view_approval.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                        Previous
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                        Next
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-check-double fa-4x text-muted mb-3"></i>
            <h4>No Completed Approvals Found</h4>
            <p class="text-muted">
                <?php if ($search || $status_filter): ?>
                    Try adjusting your search filters.
                <?php else: ?>
                    You haven't completed any approvals yet.
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
