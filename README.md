<div align="center">
  <h1>🎵 Music Player Web Application</h1>
  <p>A full-stack web application designed for streaming music with user authentication.</p>
  <br>
  <img src="https://img.shields.io/badge/PHP-7.0%2B-blue" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Database-orange" alt="MySQL">
  <img src="https://img.shields.io/badge/Frontend-HTML5%2FCSS3-brightgreen" alt="Frontend">
</div>

<hr>

<h2>🚀 Features</h2>
<ul>
  <li><b>User Authentication:</b> Secure registration and login.</li>
  <li><b>Playlist Management:</b> Add and organize your favorite tracks.</li>
  <li><b>Admin Dashboard:</b> Full control over song uploads and user management.</li>
</ul>

<hr>

<h2>🛠 How to Add Content</h2>
<p>This application manages media files directly through the server's filesystem. To add new music to your library:</p>

<table border="1" cellpadding="10" cellspacing="0">
  <tr>
    <th>Step</th>
    <th>Description</th>
  </tr>
  <tr>
    <td><b>1. Prepare Files</b></td>
    <td>Ensure audio files are <b>.mp3</b> and covers are <b>.jpg/.png</b>.</td>
  </tr>
  <tr>
    <td><b>2. Upload Paths</b></td>
    <td>Verify your server has write permissions for <code>/uploads/songs/</code> and <code>/uploads/covers/</code>.</td>
  </tr>
  <tr>
    <td><b>3. Admin Entry</b></td>
    <td>Log in as Admin, go to the <b>Dashboard</b>, fill in song details, and upload via the form.</td>
  </tr>
</table>

<p><i>The system will automatically link the files and update the database for you.</i></p>

<hr>

<div align="center">
  <h3>🛠 Installation</h3>
  <pre>git clone https://github.com/Shreyash-2007/Music-Player-Web-Application.git</pre>
  <p>Run via XAMPP/WAMP and import the <code>database.sql</code> file.</p>
</div>
