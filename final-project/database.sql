-- Blog Platform Database Schema
CREATE DATABASE IF NOT EXISTS blog_platform;
USE blog_platform;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Posts table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(300),
    featured_image VARCHAR(255) DEFAULT NULL,
    author_id INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Comments table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_status (post_id, status),
    INDEX idx_created_at (created_at)
);

-- Insert sample admin user (username: admin, password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@blogplatform.com', '$2y$10$ezQTRxPkuZ8h7z7QrQ8WjOo3/6E3M5GH.7iFZ4zKJ3HCZq5N4xdJu', 'admin');

-- Insert sample user
INSERT INTO users (username, email, password, role) VALUES 
('john_writer', 'john@example.com', '$2y$10$ezQTRxPkuZ8h7z7QrQ8WjOo3/6E3M5GH.7iFZ4zKJ3HCZq5N4xdJu', 'user');

-- Insert sample posts
INSERT INTO posts (title, slug, content, excerpt, author_id, status) VALUES
('Welcome to Our Blog Platform', 'welcome-to-our-blog-platform', 'Welcome to our new blog platform! This is a modern content management system built with PHP and MySQL. Here you can create, manage, and share your thoughts with the world.\n\nOur platform features:\n- User registration and authentication\n- Post creation and management\n- Comment system\n- File upload capabilities\n- Responsive design\n\nStart exploring and sharing your ideas today!', 'Welcome to our new blog platform! A modern CMS built with PHP and MySQL featuring user management, posts, and comments.', 1, 'published'),

('Getting Started with Content Creation', 'getting-started-with-content-creation', 'Creating engaging content is both an art and a science. In this post, we''ll explore the fundamentals of content creation that will help you connect with your audience.\n\n## Planning Your Content\n\nBefore you start writing, take time to plan:\n- Who is your target audience?\n- What value will you provide?\n- What''s your unique perspective?\n\n## Writing Tips\n\n1. Start with a compelling headline\n2. Write a strong opening paragraph\n3. Use subheadings to break up text\n4. Include examples and stories\n5. End with a clear call-to-action\n\nRemember, great content takes practice. Keep writing, keep improving!', 'Learn the fundamentals of content creation, from planning to writing tips that will help you connect with your audience.', 1, 'published'),

('The Future of Web Development', 'future-of-web-development', 'Web development is constantly evolving. Let''s look at some trends shaping the future:\n\n## Progressive Web Apps (PWAs)\nPWAs combine the best of web and mobile apps, offering offline functionality and app-like experiences.\n\n## Artificial Intelligence Integration\nAI is being integrated into web development through:\n- Automated testing\n- Code generation\n- User experience optimization\n\n## Serverless Architecture\nServerless computing is changing how we build and deploy applications, offering:\n- Reduced operational overhead\n- Better scalability\n- Cost efficiency\n\nThe future is exciting for web developers. Stay curious and keep learning!', 'Explore the latest trends in web development including PWAs, AI integration, and serverless architecture.', 2, 'published');

-- Insert sample comments
INSERT INTO comments (post_id, author_id, content, status) VALUES
(1, 2, 'Great introduction! I''m excited to start using this platform for my blog.', 'approved'),
(1, 1, 'Thank you! We''re glad you''re enjoying the platform. Let us know if you need any help getting started.', 'approved'),
(2, 2, 'These are excellent tips for content creation. The planning section especially resonates with me.', 'approved'),
(3, 1, 'Serverless architecture is definitely game-changing. Have you experimented with any specific platforms?', 'approved');