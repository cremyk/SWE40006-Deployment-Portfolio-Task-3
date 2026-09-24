const express = require('express');
const path = require('path');
const app = express();

// Serve static frontend files from the "public" folder
app.use(express.static(path.join(__dirname, 'public')));

// Fallback route
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Azure provides the PORT environment variable
const PORT = process.env.PORT || 8080;
app.listen(PORT, () => {
    console.log(`MiniGrocery running on port ${PORT}`);
});