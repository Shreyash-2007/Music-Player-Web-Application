<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$message = '';

// Handle song upload with actual files
if (isset($_POST['add_song']) && isset($_FILES['song_file'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $artist = mysqli_real_escape_string($conn, $_POST['artist']);
    $album = mysqli_real_escape_string($conn, $_POST['album']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    
    // Handle song file upload
    $song_file = $_FILES['song_file'];
    $song_name = time() . '_' . basename($song_file['name']);
    $song_path = 'uploads/songs/' . $song_name;
    
    // Handle cover image upload
    $cover_path = 'uploads/covers/default.jpg';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $cover_file = $_FILES['cover_image'];
        $cover_name = time() . '_' . basename($cover_file['name']);
        $cover_path = 'uploads/covers/' . $cover_name;
        move_uploaded_file($cover_file['tmp_name'], $cover_path);
    }
    
    if (move_uploaded_file($song_file['tmp_name'], $song_path)) {
        $query = "INSERT INTO songs (title, artist, album, file_path, cover_image, duration, uploaded_by) 
                  VALUES ('$title', '$artist', '$album', '$song_path', '$cover_path', '$duration', {$_SESSION['user_id']})";
        if (mysqli_query($conn, $query)) {
            $message = 'Song uploaded successfully!';
        } else {
            $message = 'Database error: ' . mysqli_error($conn);
        }
    } else {
        $message = 'Error uploading song file';
    }
}

// Handle song deletion
if (isset($_GET['delete_song'])) {
    $id = intval($_GET['delete_song']);
    
    // Get file paths before deleting
    $get_query = "SELECT file_path, cover_image FROM songs WHERE id = $id";
    $result = mysqli_query($conn, $get_query);
    $song = mysqli_fetch_assoc($result);
    
    // Delete from database
    $query = "DELETE FROM songs WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        // Delete actual files
        if (file_exists($song['file_path'])) unlink($song['file_path']);
        if (file_exists($song['cover_image'])) unlink($song['cover_image']);
        $message = 'Song deleted successfully!';
    }
}

// Handle song update
if (isset($_POST['update_song'])) {
    $id = intval($_POST['song_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $artist = mysqli_real_escape_string($conn, $_POST['artist']);
    $album = mysqli_real_escape_string($conn, $_POST['album']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    
    $query = "UPDATE songs SET title='$title', artist='$artist', album='$album', duration='$duration' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        $message = 'Song updated successfully!';
    }
}

// Handle user management
if (isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')";
    if (mysqli_query($conn, $query)) {
        $message = 'User added successfully!';
    } else {
        $message = 'Error: ' . mysqli_error($conn);
    }
}

if (isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $query = "UPDATE users SET username='$username', email='$email', role='$role' WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        $message = 'User updated successfully!';
    }
}

if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    if ($id != $_SESSION['user_id']) { // Can't delete yourself
        $query = "DELETE FROM users WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $message = 'User deleted successfully!';
        }
    } else {
        $message = 'Cannot delete your own account!';
    }
}

// Get all songs
$songs_query = "SELECT s.*, u.username FROM songs s LEFT JOIN users u ON s.uploaded_by = u.id ORDER BY s.created_at DESC";
$songs_result = mysqli_query($conn, $songs_query);

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Music Player</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .message {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: #667eea;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f8f8f8;
            font-weight: 600;
            color: #667eea;
        }
        
        .action-btn {
            padding: 5px 10px;
            margin: 0 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        
        .edit-btn {
            background: #4CAF50;
            color: white;
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background: white;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
        }
        
        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🎵 Admin Panel</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Add Song Section -->
        <div class="section">
            <h2>📤 Upload New Song</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Song Title *</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Artist *</label>
                        <input type="text" name="artist" required>
                    </div>
                    <div class="form-group">
                        <label>Album</label>
                        <input type="text" name="album">
                    </div>
                    <div class="form-group">
                        <label>Duration (mm:ss)</label>
                        <input type="text" name="duration" placeholder="3:45">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Song File (MP3) *</label>
                        <input type="file" name="song_file" accept=".mp3" required>
                    </div>
                    <div class="form-group">
                        <label>Cover Image (Optional)</label>
                        <input type="file" name="cover_image" accept="image/*">
                    </div>
                </div>
                <button type="submit" name="add_song" class="btn">Upload Song</button>
            </form>
        </div>
        
        <!-- Songs List -->
        <div class="section">
            <h2>🎵 All Songs</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Artist</th>
                        <th>Album</th>
                        <th>Duration</th>
                        <th>Uploaded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($songs_result, 0);
                    while ($song = mysqli_fetch_assoc($songs_result)): 
                    ?>
                    <tr>
                        <td><?php echo $song['id']; ?></td>
                        <td><?php echo $song['title']; ?></td>
                        <td><?php echo $song['artist']; ?></td>
                        <td><?php echo $song['album']; ?></td>
                        <td><?php echo $song['duration']; ?></td>
                        <td><?php echo $song['username']; ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="editSong(<?php echo $song['id']; ?>, '<?php echo addslashes($song['title']); ?>', '<?php echo addslashes($song['artist']); ?>', '<?php echo addslashes($song['album']); ?>', '<?php echo $song['duration']; ?>')">Edit</button>
                            <a href="?delete_song=<?php echo $song['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure? This will delete the song file.')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- User Management Section -->
        <div class="section">
            <h2>👥 Add New User</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn">Add User</button>
            </form>
        </div>
        
        <!-- Users List -->
        <div class="section">
            <h2>👥 All Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($users_result, 0);
                    while ($user = mysqli_fetch_assoc($users_result)): 
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')">Edit</button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="?delete_user=<?php echo $user['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit Song Modal -->
    <div id="editSongModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSongModal()">&times;</span>
            <h2>Edit Song</h2>
            <form method="POST" action="">
                <input type="hidden" name="song_id" id="edit_song_id">
                <div class="form-group">
                    <label>Song Title</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                <div class="form-group">
                    <label>Artist</label>
                    <input type="text" name="artist" id="edit_artist" required>
                </div>
                <div class="form-group">
                    <label>Album</label>
                    <input type="text" name="album" id="edit_album">
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <input type="text" name="duration" id="edit_duration">
                </div>
                <button type="submit" name="update_song" class="btn">Update Song</button>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUserModal()">&times;</span>
            <h2>Edit User</h2>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="update_user" class="btn">Update User</button>
            </form>
        </div>
    </div>
    
    <script>
        function editSong(id, title, artist, album, duration) {
            document.getElementById('edit_song_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_artist').value = artist;
            document.getElementById('edit_album').value = album;
            document.getElementById('edit_duration').value = duration;
            document.getElementById('editSongModal').style.display = 'block';
        }
        
        function closeSongModal() {
            document.getElementById('editSongModal').style.display = 'none';
        }
        
        function editUser(id, username, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editUserModal').style.display = 'block';
        }
        
        function closeUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const songModal = document.getElementById('editSongModal');
            const userModal = document.getElementById('editUserModal');
            if (event.target == songModal) {
                songModal.style.display = 'none';
            }
            if (event.target == userModal) {
                userModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>