-- Database Setup for Mini Blog Admin Panel
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE blog_admin;
USE blog_admin;

-- Posts table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user
-- Username: admin
-- Password: admin123
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert some sample posts (optional)
INSERT INTO posts (title, content) VALUES 
('Welcome to the Blog Admin Panel', 'This is your first blog post! You can edit or delete this post, and create new ones using the admin interface.'),
('Getting Started', 'To create a new post, click on "Create Post" in the navigation. You can add titles, content, and even upload images to make your posts more engaging.'),
('Managing Your Content', 'Use the admin panel to manage all your blog posts. You can edit existing posts, delete unwanted content, and keep your blog fresh with new updates.');