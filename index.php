<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Remove in production
?>

<!DOCTYPE html>
<html>
<head>
<title>Library System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    min-height: 100vh; 
    padding: 20px; 
}
.card { box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
</style>
</head>
<body class="d-flex align-items-center min-vh-100">
<div class="container">
<div class="row justify-content-center">
<div class="col-md-8 col-lg-6">

<?php
// DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "library_system");
if ($conn->connect_error) {
    die("<div class='alert alert-danger text-center p-5'><h3>❌ Database Error</h3><p>" . $conn->connect_error . "</p><p>1. Create 'library_system' database<br>2. Run the SQL above</p></div>");
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// REGISTER
if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    if ($conn->query("INSERT INTO users (username, password) VALUES ('$username', '$password')")) {
        $success = "✅ Registered! Login now.";
    } else {
        $error = "❌ Username exists!";
    }
}

// LOGIN
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    
    $result = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$password'");
    if ($result->num_rows > 0) {
        $_SESSION['user'] = $result->fetch_assoc();
        header("Location: index.php");
        exit;
    } else {
        $error = "❌ Invalid credentials!";
    }
}

// ADD BOOK (Admin only)
if (isset($_POST['add_book']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role']=='admin') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $qty = (int)$_POST['quantity'];
    
    $conn->query("INSERT INTO books (title, author, quantity) VALUES ('$title', '$author', $qty)");
    $success = "✅ Book added!";
}

// BORROW
if (isset($_GET['borrow']) && isset($_SESSION['user']) && $_SESSION['user']['role']=='user') {
    $book_id = (int)$_GET['borrow'];
    
    // Check if available
    $check = $conn->query("SELECT quantity FROM books WHERE id=$book_id");
    if ($check->fetch_assoc()['quantity'] > 0) {
        $conn->query("UPDATE books SET quantity = quantity - 1 WHERE id=$book_id");
        $success = "✅ Book borrowed!";
    } else {
        $error = "❌ No books available!";
    }
}
?>

<?php if (!isset($_SESSION['user'])): ?>
<!-- LOGIN FORM -->
<div class="card p-5 mt-5">
    <h2 class="text-center mb-4">📚 Library System</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="mb-4">
            <input type="text" name="username" class="form-control form-control-lg" placeholder="Username" required>
        </div>
        <div class="mb-4">
            <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required>
        </div>
        <button name="login" class="btn btn-success btn-lg w-100 mb-3">Login</button>
        <button name="register" class="btn btn-outline-light btn-lg w-100">Register New User</button>
    </form>
    
    <div class="text-center mt-4">
        <small class="text-muted">Demo: <code>admin/admin123</code> | <code>user/user123</code></small>
    </div>
</div>

<?php else: $user = $_SESSION['user']; ?>
<!-- DASHBOARD -->
<div class="card p-5 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>👋 Welcome, <?= htmlspecialchars($user['username']) ?>!</h3>
            <span class="badge bg-<?= $user['role']=='admin' ? 'success' : 'primary' ?>">
                <?= ucfirst($user['role']) ?>
            </span>
        </div>
        <a href="?logout" class="btn btn-danger btn-lg">Logout</a>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if ($user['role'] == 'admin'): ?>
    <!-- ADD BOOK -->
    <div class="card border-0 bg-light p-4 mb-4">
        <h5>➕ Add New Book</h5>
        <form method="POST" class="row g-3">
            <div class="col-md-5">
                <input name="title" class="form-control" placeholder="Book Title" required>
            </div>
            <div class="col-md-4">
                <input name="author" class="form-control" placeholder="Author" required>
            </div>
            <div class="col-md-2">
                <input name="quantity" type="number" class="form-control" value="1" min="1" required>
            </div>
            <div class="col-md-1">
                <button name="add_book" class="btn btn-success w-100">Add</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- BOOKS LIST -->
    <h4>📚 Available Books</h4>
    
    <?php
    $books = $conn->query("SELECT * FROM books ORDER BY title");
    if ($books->num_rows == 0): ?>
        <div class="text-center py-5">
            <h5>No books yet 😢</h5>
            <?php if($user['role']=='admin'): ?>
                <p>Add some books above!</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php while($book = $books->fetch_assoc()): ?>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($book['title']) ?></h6>
                        <p class="card-text"><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></p>
                        <p class="card-text"><strong>Available:</strong> <span class="badge bg-<?= $book['quantity']>0 ? 'success' : 'danger' ?>"><?= $book['quantity'] ?></span></p>
                        
                        <?php if($user['role']=='user' && $book['quantity'] > 0): ?>
                            <a href="?borrow=<?= $book['id'] ?>" class="btn btn-primary w-100" onclick="return confirm('Borrow this book?')">
                                📖 Borrow
                            </a>
                        <?php elseif($book['quantity'] == 0): ?>
                            <button class="btn btn-danger w-100" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
</div>
</body>
</html>