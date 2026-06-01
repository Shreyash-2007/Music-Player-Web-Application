<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle add to playlist
if (isset($_POST['add_to_playlist'])) {
    $song_id = intval($_POST['song_id']);
    $user_id = $_SESSION['user_id'];
    
    $check = "SELECT * FROM playlists WHERE user_id=$user_id AND song_id=$song_id";
    if (mysqli_num_rows(mysqli_query($conn, $check)) == 0) {
        $query = "INSERT INTO playlists (user_id, song_id) VALUES ($user_id, $song_id)";
        mysqli_query($conn, $query);
    }
}

// Handle remove from playlist
if (isset($_GET['remove'])) {
    $song_id = intval($_GET['remove']);
    $user_id = $_SESSION['user_id'];
    $query = "DELETE FROM playlists WHERE user_id=$user_id AND song_id=$song_id";
    mysqli_query($conn, $query);
    header('Location: user.php');
    exit();
}

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_condition = '';
if ($search) {
    $search_condition = " WHERE title LIKE '%$search%' OR artist LIKE '%$search%' OR album LIKE '%$search%'";
}

// Get all songs with search filter
$songs_query = "SELECT * FROM songs $search_condition ORDER BY created_at DESC";
$songs_result = mysqli_query($conn, $songs_query);

// Get user's playlist
$playlist_query = "SELECT s.* FROM songs s 
                   INNER JOIN playlists p ON s.id = p.song_id 
                   WHERE p.user_id = {$_SESSION['user_id']}";
$playlist_result = mysqli_query($conn, $playlist_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Player - User Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a1a;
            color: white;
            padding-bottom: 120px;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .search-box {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            width: 300px;
            font-size: 14px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .songs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .song-card {
            background: #2a2a2a;
            border-radius: 10px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .song-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .song-cover {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            overflow: hidden;
        }
        
        .song-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .song-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .song-artist {
            color: #999;
            margin-bottom: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .song-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.3s;
        }
        
        .btn-play {
            background: #667eea;
            color: white;
            flex: 1;
        }
        
        .btn-add {
            background: #4CAF50;
            color: white;
        }
        
        .btn-remove {
            background: #f44336;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .player {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #2a2a2a;
            padding: 20px;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .player-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .player-top {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .player-info {
            flex: 1;
        }
        
        .player-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .player-artist {
            color: #999;
            font-size: 14px;
        }
        
        .player-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .control-btn:hover {
            color: #667eea;
        }
        
        .control-btn.play-pause {
            font-size: 32px;
            background: #667eea;
            border-radius: 50%;
        }
        
        .progress-container {
            width: 100%;
        }
        
        .progress-bar {
            width: 100%;
            height: 5px;
            background: #444;
            border-radius: 5px;
            overflow: hidden;
            cursor: pointer;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.1s;
        }
        
        .time {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .volume-slider {
            width: 100px;
        }
        
        .no-results {
            text-align: center;
            color: #999;
            padding: 40px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🎵 Music Player</h1>
        <div class="navbar-right">
            <form method="GET" style="margin: 0;">
                <input type="text" name="search" class="search-box" placeholder="Search songs, artists, albums..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <span>Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- My Playlist Section -->
        <div class="section">
            <h2>My Playlist</h2>
            <div class="songs-grid">
                <?php 
                $playlist_result = mysqli_query($conn, $playlist_query);
                if (mysqli_num_rows($playlist_result) > 0):
                    while ($song = mysqli_fetch_assoc($playlist_result)): 
                ?>
                <div class="song-card">
                    <div class="song-cover">
                        <?php if (file_exists($song['cover_image'])): ?>
                            <img src="<?php echo $song['cover_image']; ?>" alt="Cover">
                        <?php else: ?>
                            🎵
                        <?php endif; ?>
                    </div>
                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="song-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                    <div class="song-actions">
                        <button class="btn btn-play" onclick='playSong(<?php echo json_encode($song); ?>)'>▶ Play</button>
                        <a href="?remove=<?php echo $song['id']; ?>" class="btn btn-remove">Remove</a>
                    </div>
                </div>
                <?php 
                    endwhile;
                else:
                    echo '<p style="color: #999;">Your playlist is empty. Add some songs!</p>';
                endif;
                ?>
            </div>
        </div>
        
        <!-- All Songs Section -->
        <div class="section">
            <h2><?php echo $search ? 'Search Results' : 'All Songs'; ?></h2>
            <?php if (mysqli_num_rows($songs_result) > 0): ?>
            <div class="songs-grid">
                <?php while ($song = mysqli_fetch_assoc($songs_result)): ?>
                <div class="song-card">
                    <div class="song-cover">
                        <?php if (file_exists($song['cover_image'])): ?>
                            <img src="<?php echo $song['cover_image']; ?>" alt="Cover">
                        <?php else: ?>
                            🎵
                        <?php endif; ?>
                    </div>
                    <div class="song-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="song-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                    <div class="song-actions">
                        <button class="btn btn-play" onclick='playSong(<?php echo json_encode($song); ?>)'>▶ Play</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="song_id" value="<?php echo $song['id']; ?>">
                            <button type="submit" name="add_to_playlist" class="btn btn-add">+ Add</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
                <div class="no-results">
                    No songs found. <?php echo $search ? 'Try a different search term.' : ''; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Music Player -->
    <div class="player" id="player" style="display: none;">
        <div class="player-content">
            <div class="player-top">
                <div class="player-info">
                    <div class="player-title" id="currentTitle">No song playing</div>
                    <div class="player-artist" id="currentArtist">-</div>
                </div>
                
                <div class="player-controls">
                    <button class="control-btn" onclick="previousSong()">⏮</button>
                    <button class="control-btn play-pause" id="playPauseBtn" onclick="togglePlay()">▶</button>
                    <button class="control-btn" onclick="nextSong()">⏭</button>
                </div>
                
                <div class="volume-control">
                    <span>🔊</span>
                    <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="70" onchange="changeVolume(this.value)">
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-bar" onclick="seek(event)">
                    <div class="progress" id="progress"></div>
                </div>
                <div class="time">
                    <span id="currentTime">0:00</span>
                    <span id="duration">0:00</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden Audio Element -->
    <audio id="audioPlayer"></audio>
    
    <script>
        const audioPlayer = document.getElementById('audioPlayer');
        let currentSongData = null;
        
        function playSong(songData) {
            currentSongData = songData;
            
            // Show player
            document.getElementById('player').style.display = 'block';
            
            // Update UI
            document.getElementById('currentTitle').textContent = songData.title;
            document.getElementById('currentArtist').textContent = songData.artist;
            document.getElementById('duration').textContent = songData.duration || '0:00';
            
            // Load and play audio
            audioPlayer.src = songData.file_path;
            audioPlayer.volume = document.getElementById('volumeSlider').value / 100;
            audioPlayer.play();
            
            document.getElementById('playPauseBtn').textContent = '⏸';
            
            // Update progress as song plays
            audioPlayer.addEventListener('timeupdate', updateProgress);
            audioPlayer.addEventListener('loadedmetadata', function() {
                document.getElementById('duration').textContent = formatTime(audioPlayer.duration);
            });
            audioPlayer.addEventListener('ended', nextSong);
        }
        
        function togglePlay() {
            if (audioPlayer.paused) {
                audioPlayer.play();
                document.getElementById('playPauseBtn').textContent = '⏸';
            } else {
                audioPlayer.pause();
                document.getElementById('playPauseBtn').textContent = '▶';
            }
        }
        
        function updateProgress() {
            const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
            document.getElementById('progress').style.width = progress + '%';
            document.getElementById('currentTime').textContent = formatTime(audioPlayer.currentTime);
        }
        
        function seek(event) {
            const bar = event.currentTarget;
            const rect = bar.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            audioPlayer.currentTime = percent * audioPlayer.duration;
        }
        
        function changeVolume(value) {
            audioPlayer.volume = value / 100;
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return mins + ':' + (secs < 10 ? '0' : '') + secs;
        }
        
        function previousSong() {
            audioPlayer.currentTime = 0;
        }
        
        function nextSong() {
            // In a full implementation, this would play the next song in queue
            alert('Next song feature - would play the next song in your playlist');
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space' && currentSongData) {
                e.preventDefault();
                togglePlay();
            }
        });
    </script>
</body>
</html>
