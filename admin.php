<?php
session_start();

if (isset($_SESSION["user_id"]) && isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "customer") {
        header("Location: index.php");
        exit();
    } else if ($_SESSION["role"] === "manager") {
        header("Location: manager.php");
    }
}
else {
    header("Location: index.php");
    exit();
}

include("includes/connect-db.php");

$stats = array();

$result = $conn->query("
    SELECT COUNT(DISTINCT m.movie_id) AS total
    FROM movies m
    JOIN screenings s ON m.movie_id = s.movie_id
    WHERE s.date >= CURDATE()
");
$stats['movies'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM screenings WHERE date >= CURDATE()");
$stats['screenings'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM customers");
$stats['customers'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM bookings");
$stats['bookings'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM theaters");
$stats['theaters'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM managers");
$stats['managers'] = $result->fetch_assoc()['total'];

$managers = array();
$result = $conn->query("SELECT * FROM managers ORDER BY manager_id DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $managers[] = $row;
    }
}

$theaters = array();
$result = $conn->query("SELECT * FROM theaters ORDER BY theater_id DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $theaters[] = $row;
    }
}

$message = '';
$error = '';

if (isset($_POST['add_manager'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $check = $conn->query("SELECT * FROM managers WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $error = "Manager with this email already exists.";
    } else {
        $sql = "INSERT INTO managers (email, password) VALUES ('$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            $message = "Manager added successfully.";
            $result = $conn->query("SELECT * FROM managers ORDER BY manager_id DESC");
            $managers = array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $managers[] = $row;
                }
            }
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

if (isset($_POST['delete_manager'])) {
    $manager_id = $conn->real_escape_string($_POST['manager_id']);
    
    $sql = "DELETE FROM managers WHERE manager_id = $manager_id";
    if ($conn->query($sql) === TRUE) {
        $message = "Manager deleted successfully.";
        $result = $conn->query("SELECT * FROM managers ORDER BY manager_id DESC");
        $managers = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $managers[] = $row;
            }
        }
    } else {
        $error = "Error: " . $conn->error;
    }
}

if (isset($_POST['add_theater'])) {
    $theater_name = $conn->real_escape_string($_POST['theater_name']);
    $capacity = $conn->real_escape_string($_POST['capacity']);
    
    $sql = "INSERT INTO theaters (theater_name, capacity) VALUES ('$theater_name', $capacity)";
    if ($conn->query($sql) === TRUE) {
        $message = "Theater added successfully.";
        $result = $conn->query("SELECT * FROM theaters ORDER BY theater_id DESC");
        $theaters = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $theaters[] = $row;
            }
        }
    } else {
        $error = "Error: " . $conn->error;
    }
}

$recent_bookings = array();
$sql = "SELECT b.booking_id, c.customer_name, m.title, s.date, s.time, t.theater_name 
        FROM bookings b 
        JOIN customers c ON b.customer_id = c.customer_id 
        JOIN screenings s ON b.screening_id = s.screening_id 
        JOIN movies m ON s.movie_id = m.movie_id 
        JOIN theaters t ON s.theater_id = t.theater_id 
        ORDER BY b.booking_id DESC LIMIT 5";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_bookings[] = $row;
    }
}

$conn->close();
?>

<?php include("includes/header.php"); ?>
    <main class="admin-dashboard">
        <div class="container">
            <h2 class="section-title">Admin Dashboard</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">🎬</div>
                    <div class="stat-info">
                        <h3>Movies</h3>
                        <p class="stat-number"><?php echo $stats['movies']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎭</div>
                    <div class="stat-info">
                        <h3>Screenings</h3>
                        <p class="stat-number"><?php echo $stats['screenings']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3>Customers</h3>
                        <p class="stat-number"><?php echo $stats['customers']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎟️</div>
                    <div class="stat-info">
                        <h3>Bookings</h3>
                        <p class="stat-number"><?php echo $stats['bookings']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-tabs">
                <button class="dashboard-tab active" data-tab="managers">Managers</button>
                <button class="dashboard-tab" data-tab="theaters">Theaters</button>
                <button class="dashboard-tab" data-tab="statistics">Statistics</button>
            </div>
            
            <div class="dashboard-content active" id="managers-content">
                <div class="content-header">
                    <h3>Manage Managers</h3>
                    <button class="btn-primary" id="show-add-manager">Add New Manager</button>
                </div>
                
                <div class="add-form" id="add-manager-form" style="display: none;">
                    <h3>Add New Manager</h3>
                    <form action="" method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password*</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="cancel-add-manager">Cancel</button>
                            <button type="submit" name="add_manager" class="btn-primary">Add Manager</button>
                        </div>
                    </form>
                </div>
                
                <div class="table-container">
                    <table class="manager-table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($managers) > 0): ?>
                                <?php foreach ($managers as $manager): ?>
                                    <tr>
                                        <td><?php echo $manager['email']; ?></td>
                                        <td>
                                            <form action="" method="post" class="delete-form">
                                                <input type="hidden" name="manager_id" value="<?php echo $manager['manager_id']; ?>">
                                                <button type="submit" name="delete_manager" class="btn-delete" onclick="return confirm('Are you sure you want to delete this manager?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="no-data">No managers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="dashboard-content" id="theaters-content">
                <div class="content-header">
                    <h3>Manage Theaters</h3>
                    <button class="btn-primary" id="show-add-theater">Add New Theater</button>
                </div>
                
                <div class="add-form" id="add-theater-form" style="display: none;">
                    <h3>Add New Theater</h3>
                    <form action="" method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="theater_name">Theater Name*</label>
                                <input type="text" id="theater_name" name="theater_name" required>
                            </div>
                            <div class="form-group">
                                <label for="capacity">Capacity*</label>
                                <input type="number" id="capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="cancel-add-theater">Cancel</button>
                            <button type="submit" name="add_theater" class="btn-primary">Add Theater</button>
                        </div>
                    </form>
                </div>
                
                <div class="table-container">
                    <table class="manager-table">
                        <thead>
                            <tr>
                                <th>Theater Name</th>
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($theaters) > 0): ?>
                                <?php foreach ($theaters as $theater): ?>
                                    <tr>
                                        <td><?php echo $theater['theater_name']; ?></td>
                                        <td><?php echo $theater['capacity']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="no-data">No theaters found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="dashboard-content" id="statistics-content">
                <div class="stats-grid">
                    <div class="stats-card">
                        <h3>Recent Bookings</h3>
                        <div class="table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Movie</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Theater</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_bookings) > 0): ?>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td><?php echo $booking['customer_name']; ?></td>
                                                <td><?php echo $booking['title']; ?></td>
                                                <td><?php echo $booking['date']; ?></td>
                                                <td><?php echo $booking['time']; ?></td>
                                                <td><?php echo $booking['theater_name']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="no-data">No recent bookings found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                    <div class="stats-card">
                        <h3>System Overview</h3>
                        <div class="overview-stats">
                            <div class="overview-stat">
                                <h4>Movies</h4>
                                <p><?php echo $stats['movies']; ?></p>
                            </div>
                            <div class="overview-stat">
                                <h4>Screenings</h4>
                                <p><?php echo $stats['screenings']; ?></p>
                            </div>
                            <div class="overview-stat">
                                <h4>Total Customers</h4>
                                <p><?php echo $stats['customers']; ?></p>
                            </div>
                            <div class="overview-stat">
                                <h4>Total Bookings</h4>
                                <p><?php echo $stats['bookings']; ?></p>
                            </div>
                            <div class="overview-stat">
                                <h4>Total Theaters</h4>
                                <p><?php echo $stats['theaters']; ?></p>
                            </div>
                            <div class="overview-stat">
                                <h4>Total Managers</h4>
                                <p><?php echo $stats['managers']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include("includes/footer.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.dashboard-tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const contents = document.querySelectorAll('.dashboard-content');
                    contents.forEach(content => content.classList.remove('active'));
                    
                    const tabName = this.getAttribute('data-tab');
                    document.getElementById(tabName + '-content').classList.add('active');
                });
            });
            
            document.getElementById('show-add-manager').addEventListener('click', function() {
                document.getElementById('add-manager-form').style.display = 'block';
            });
            
            document.getElementById('cancel-add-manager').addEventListener('click', function() {
                document.getElementById('add-manager-form').style.display = 'none';
            });
            
            document.getElementById('show-add-theater').addEventListener('click', function() {
                document.getElementById('add-theater-form').style.display = 'block';
            });
            
            document.getElementById('cancel-add-theater').addEventListener('click', function() {
                document.getElementById('add-theater-form').style.display = 'none';
            });
        });
    </script>
</body>
</html>