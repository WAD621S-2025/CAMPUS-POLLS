const express = require('express');
const mysql = require('mysql2');
const multer = require('multer');
const path = require('path');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());
app.use('/uploads', express.static('uploads'));

// Database connection with connection pooling and error handling
const db = mysql.createPool({
  host: '127.0.0.1',
  user: 'root',
  password: '',
  database: 'buzz',
  port: 3307,
  waitForConnections: true,
  connectionLimit: 10,
  maxIdle: 10,
  idleTimeout: 60000,
  queueLimit: 0,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0
});

// Test the connection
db.getConnection((err, connection) => {
  if (err) {
    console.error('❌ Database connection failed:', err);
    return;
  }
  console.log('✅ Connected to database');
  connection.release();
});

// Handle connection errors
db.on('error', (err) => {
  console.error('Database error:', err);
  if (err.code === 'PROTOCOL_CONNECTION_LOST') {
    console.log('Reconnecting to database...');
  }
});
// File upload configuration
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, 'uploads/');
  },
  filename: (req, file, cb) => {
    cb(null, Date.now() + path.extname(file.originalname));
  }
});

const upload = multer({ 
  storage: storage,
  limits: { fileSize: 5 * 1024 * 1024 },
  fileFilter: (req, file, cb) => {
    const allowedTypes = /jpeg|jpg|png|gif/;
    const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
    const mimetype = allowedTypes.test(file.mimetype);
    
    if (mimetype && extname) {
      return cb(null, true);
    } else {
      cb('Error: Images only!');
    }
  }
});

// GET all memes
app.get('/api/memes', (req, res) => {
  const query = `
    SELECT 
      meme_id as id,
      user_id,
      title,
      image_url as image,
      caption,
      category,
      upvotes as likes,
      downvotes,
      total_score,
      view_count,
      is_flagged,
      is_approved,
      created_at,
      updated_at
    FROM memes 
    WHERE is_approved = 1 
    ORDER BY created_at DESC
  `;
  
  db.query(query, (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    const memesWithComments = results.map(meme => ({
      ...meme,
      comments: [],
      liked: false
    }));
    
    res.json(memesWithComments);
  });
});

// POST new meme
app.post('/api/memes', upload.single('memeImage'), (req, res) => {
  const { caption, user_id, title, category } = req.body;
  
  if (!req.file) {
    return res.status(400).json({ error: 'No image uploaded' });
  }
  
  const imageUrl = `/uploads/${req.file.filename}`;
  
  const query = `
    INSERT INTO memes 
    (user_id, title, image_url, caption, category, upvotes, downvotes, total_score, view_count, is_flagged, is_approved, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 1, NOW(), NOW())
  `;
  
  db.query(query, [user_id || 1, title || 'Untitled', imageUrl, caption, category || 'general'], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    
    res.json({
      success: true,
      meme_id: result.insertId,
      message: 'Meme uploaded successfully'
    });
  });
});

// POST like/unlike meme
app.post('/api/memes/:id/like', (req, res) => {
  const memeId = req.params.id;
  const { liked } = req.body;
  
  const query = liked 
    ? `UPDATE memes SET upvotes = upvotes + 1, total_score = total_score + 1 WHERE meme_id = ?`
    : `UPDATE memes SET upvotes = upvotes - 1, total_score = total_score - 1 WHERE meme_id = ?`;
  
  db.query(query, [memeId], (err) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json({ success: true });
  });
});

// GET comments for a meme
app.get('/api/memes/:id/comments', (req, res) => {
  const query = `
    SELECT comment_id, user_id, comment_text as text, created_at
    FROM comments 
    WHERE meme_id = ? 
    ORDER BY created_at DESC
  `;
  
  db.query(query, [req.params.id], (err, results) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
});

// POST new comment
app.post('/api/memes/:id/comments', (req, res) => {
  const { user_id, text } = req.body;
  const memeId = req.params.id;
  
  const query = `
    INSERT INTO comments (meme_id, user_id, comment_text, created_at)
    VALUES (?, ?, ?, NOW())
  `;
  
  db.query(query, [memeId, user_id || 1, text], (err, result) => {
    if (err) {
      return res.status(500).json({ error: err.message });
    }
    res.json({ 
      success: true, 
      comment_id: result.insertId 
    });
  });
});

const PORT = 3000;
app.listen(PORT, () => {
  console.log(`🚀 Server running on http://localhost:${PORT}`);
  console.log(`📊 API: http://localhost:${PORT}/api/memes`);
});