-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 03, 2026 at 11:23 PM
-- Server version: 8.0.44
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_todo`
--

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `color` varchar(20) DEFAULT '#fffacd',
  `position_x` int DEFAULT '100',
  `position_y` int DEFAULT '100',
  `is_archived` tinyint(1) DEFAULT '0' COMMENT '0=Active, 1=Archived',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `user_id`, `title`, `content`, `color`, `position_x`, `position_y`, `is_archived`, `created_at`, `updated_at`) VALUES
(1, 1, 'Frontend Development Stack', '<p><strong>Tech Stack untuk Frontend:</strong></p><ul><li>HTML5 & CSS3 - Semantic markup dan modern styling</li><li>JavaScript ES6+ - Modern JavaScript features</li><li>React.js - Component-based UI library</li><li>Tailwind CSS - Utility-first CSS framework</li><li>Bootstrap 5 - Responsive framework</li></ul><p>Catatan: Fokus pada responsive design dan performance optimization</p>', '#ffe4e1', 684, 20, 0, '2026-01-10 08:30:00', '2026-01-10 08:30:00'),
(2, 1, 'Backend Technologies', '<p><strong>Backend Development Tools:</strong></p><ol><li>PHP 8.2 - Server-side scripting</li><li>Node.js & Express - JavaScript runtime</li><li>MySQL/PostgreSQL - Relational databases</li><li>MongoDB - NoSQL database</li><li>RESTful API Design</li><li>Authentication & Authorization (JWT)</li></ol><p>Penting: Implement security best practices dan proper error handling</p>', '#e0f7fa', 820, 20, 0, '2026-01-10 09:15:00', '2026-01-10 09:15:00'),
(3, 2, 'Web Developer Roadmap 2026', '<p><strong>Learning Path:</strong></p><ul><li>✅ HTML, CSS, JavaScript Fundamentals</li><li>✅ Version Control (Git & GitHub)</li><li>🔄 Frontend Framework (React/Vue)</li><li>📝 Backend Development (Node.js/PHP)</li><li>📝 Database Management</li><li>📝 DevOps Basics (Docker, CI/CD)</li></ul><p>Target: Menjadi Full Stack Developer dalam 6 bulan</p>', '#fff9c4', 100, 320, 0, '2026-01-11 10:00:00', '2026-01-11 10:00:00'),
(4, 2, 'API Development Notes', '<p><strong>RESTful API Best Practices:</strong></p><ul><li>Use proper HTTP methods (GET, POST, PUT, DELETE)</li><li>Implement pagination for large datasets</li><li>Version your APIs (v1, v2)</li><li>Return proper status codes</li><li>Document with Swagger/OpenAPI</li><li>Implement rate limiting</li></ul><p>Resources: Postman for testing, Thunder Client extension</p>', '#e1f5fe', 236, 320, 0, '2026-01-11 14:30:00', '2026-01-11 14:30:00'),
(5, 1, 'Database Design Principles', '<p><strong>Konsep Database untuk Full Stack:</strong></p><ol><li>Normalization vs Denormalization</li><li>Indexing untuk performance</li><li>Foreign Keys & Relationships</li><li>Transactions & ACID properties</li><li>Query optimization</li></ol><p>Tools: phpMyAdmin, MySQL Workbench, TablePlus</p>', '#f3e5f5', 372, 320, 0, '2026-01-12 11:20:00', '2026-01-12 11:20:00'),
(6, 2, 'DevOps & Deployment', '<p><strong>Deployment Checklist:</strong></p><ul><li>Environment variables (.env files)</li><li>Docker containerization</li><li>CI/CD Pipeline (GitHub Actions)</li><li>Cloud hosting (AWS, DigitalOcean, Vercel)</li><li>SSL/HTTPS configuration</li><li>Performance monitoring</li></ul><p>Praktek: Deploy portfolio project ke production</p>', '#c8e6c9', 508, 320, 0, '2026-01-13 16:45:00', '2026-01-13 16:45:00'),
(7, 1, 'JavaScript Advanced Concepts', '<p><strong>Advanced JS Topics:</strong></p><ul><li>Promises & Async/Await</li><li>Closures & Scope</li><li>Event Loop & Call Stack</li><li>Prototypal Inheritance</li><li>ES6+ Features (Destructuring, Spread/Rest)</li><li>Module Systems (CommonJS, ES Modules)</li></ul><p>Practice: Build mini projects untuk setiap konsep</p>', '#fce4ec', 644, 320, 0, '2026-01-14 09:00:00', '2026-01-14 09:00:00'),
(8, 2, 'Web Security Essentials', '<p><strong>Security Best Practices:</strong></p><ol><li>SQL Injection Prevention (Prepared Statements)</li><li>XSS Protection</li><li>CSRF Tokens</li><li>Password Hashing (bcrypt, Argon2)</li><li>Input Validation & Sanitization</li><li>HTTPS & Secure Headers</li></ol><p>Important: Never store sensitive data in plain text!</p>', '#ffebee', 780, 320, 0, '2026-01-15 10:30:00', '2026-01-15 10:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `todos`
--

CREATE TABLE `todos` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `task` varchar(255) NOT NULL,
  `description` text,
  `status` enum('pending','in_progress','completed','archived') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `todos`
--

INSERT INTO `todos` (`id`, `user_id`, `task`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at`) VALUES
(2, 1, 'Setup Development Environment', 'Install Node.js, VS Code, Git, dan semua ekstensi yang diperlukan untuk web development. Konfigurasi PATH environment variables dan test semua tools.', 'completed', 'high', '2026-01-10', '2026-01-08 08:00:00', '2026-01-10 10:30:00'),
(3, 1, 'Belajar React Hooks', 'Pelajari useState, useEffect, useContext, useReducer, useMemo, dan useCallback. Buat mini project untuk setiap hook.', 'in_progress', 'high', '2026-01-18', '2026-01-10 09:00:00', '2026-01-15 14:20:00'),
(4, 2, 'Build REST API dengan Express', 'Develop RESTful API menggunakan Node.js dan Express. Implement CRUD operations, authentication middleware, dan error handling.', 'in_progress', 'high', '2026-01-20', '2026-01-11 10:00:00', '2026-01-15 11:00:00'),
(5, 1, 'Database Schema Design', 'Design database schema untuk aplikasi e-commerce. Include users, products, orders, categories, dan payment tables dengan proper relationships.', 'pending', 'medium', '2026-01-19', '2026-01-12 11:00:00', '2026-01-12 11:00:00'),
(6, 2, 'Implement JWT Authentication', 'Setup JSON Web Token authentication untuk API. Include login, register, refresh token, dan protected routes.', 'pending', 'high', '2026-01-22', '2026-01-12 14:00:00', '2026-01-12 14:00:00'),
(7, 1, 'Learn TypeScript Basics', 'Pelajari TypeScript fundamentals: types, interfaces, generics, decorators. Convert existing JavaScript project ke TypeScript.', 'completed', 'medium', '2026-01-15', '2026-01-09 08:30:00', '2026-01-15 16:00:00'),
(8, 2, 'Deploy Application to AWS', 'Deploy full stack application ke AWS menggunakan EC2, RDS, dan S3. Setup CI/CD pipeline dengan GitHub Actions.', 'pending', 'medium', '2026-01-25', '2026-01-13 09:00:00', '2026-01-13 09:00:00'),
(9, 1, 'Optimize Website Performance', 'Implement lazy loading, code splitting, image optimization, caching strategies. Target: Lighthouse score > 90.', 'pending', 'high', '2026-01-21', '2026-01-14 10:00:00', '2026-01-14 10:00:00'),
(10, 2, 'Study Docker Containerization', 'Learn Docker basics, create Dockerfile, docker-compose untuk development environment. Containerize existing applications.', 'in_progress', 'medium', '2026-01-24', '2026-01-13 15:00:00', '2026-01-15 09:30:00'),
(11, 1, 'Build Portfolio Website', 'Create personal portfolio website menggunakan React dan Tailwind CSS. Showcase projects, skills, dan contact information.', 'completed', 'high', '2026-01-16', '2026-01-08 09:00:00', '2026-01-16 18:00:00'),
(12, 2, 'Learn GraphQL API', 'Pelajari GraphQL fundamentals, setup Apollo Server, create schema, resolvers, dan implement queries/mutations.', 'pending', 'low', '2026-01-28', '2026-01-14 11:00:00', '2026-01-14 11:00:00'),
(13, 1, 'Implement WebSocket Real-time Chat', 'Build real-time chat application menggunakan Socket.io. Include private messaging, typing indicators, dan online status.', 'pending', 'medium', '2026-01-26', '2026-01-14 13:00:00', '2026-01-14 13:00:00'),
(14, 2, 'Code Review & Refactoring', 'Review existing codebase, identify code smells, implement clean code principles, dan refactor untuk better maintainability.', 'pending', 'medium', '2026-01-23', '2026-01-13 14:00:00', '2026-01-13 14:00:00'),
(15, 1, 'Learn Redux State Management', 'Master Redux toolkit, setup store, create slices, implement async thunks, dan integrate dengan React application.', 'in_progress', 'high', '2026-01-20', '2026-01-12 10:00:00', '2026-01-15 13:15:00'),
(16, 2, 'Setup Testing Environment', 'Configure Jest dan React Testing Library. Write unit tests, integration tests untuk components dan API endpoints.', 'pending', 'high', '2026-01-27', '2026-01-14 15:00:00', '2026-01-14 15:00:00'),
(17, 1, 'Study Web Accessibility (a11y)', 'Learn WCAG guidelines, implement semantic HTML, ARIA labels, keyboard navigation, dan screen reader compatibility.', 'pending', 'medium', '2026-01-29', '2026-01-15 10:00:00', '2026-01-15 10:00:00'),
(18, 2, 'Build Progressive Web App (PWA)', 'Convert web app ke PWA. Implement service workers, offline functionality, push notifications, dan installability.', 'pending', 'low', '2026-01-30', '2026-01-15 11:00:00', '2026-01-15 11:00:00'),
(19, 1, 'Learn Next.js Framework', 'Study Next.js features: SSR, SSG, ISR, API routes, file-based routing, dan image optimization. Build blog application.', 'pending', 'high', '2026-01-31', '2026-01-15 14:00:00', '2026-01-15 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_approved` tinyint(1) DEFAULT '1',
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_approved`, `is_aktif`, `approved_by`, `approved_at`, `created_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$8Nq/3Kiz7jDWpz4HDhdvJ.wb2YZHL3xypcAnJEIA0qn/zzJRvyBLm', 'admin', 1, 1, 1, '2026-01-03 11:49:22', '2026-01-03 07:31:28'),
(2, 'user', 'user@example.com', '$2y$10$SkbKzeFswaKUyBA78IhKk.7ztAhUJX/X4rti7PJNmsqCIIP1DkIJS', 'user', 1, 1, 1, '2026-01-03 12:10:00', '2026-01-03 07:31:28'),
(4, 'Naufal', 'nufal@mail.com', '$2y$10$gXhiARVZQi8neuUYdh32suINjpN3pzVNBw7H1t//YyVM2rQa5r/YG', 'user', 1, 1, 1, '2026-01-03 12:14:11', '2026-01-03 12:10:51'),
(5, 'Fakhriza', 'fakhriza@mail.com', '$2y$10$ni5ColGEc.REoDXGnHFlnu7lYzD0O7P/dQZJLx.ZKcZL529DhE50.', 'admin', 1, 1, 1, '2026-01-03 12:23:26', '2026-01-03 12:20:29'),
(6, 'Andre', 'andre@mail.com', '$2y$10$mU9/odvSCXUPDHg1/h3n9.F0O9Gfx/A1/KlSStEHtPsMyfST8GlJe', 'user', 1, 1, NULL, NULL, '2026-01-03 13:38:08'),
(11, 'Susan', 'susan@mail.com', '$2y$10$o.kEm4u7xoyVlf7gIt3acOO94kuyeA2aMrFu7Evsef/ouevTl3gkq', 'user', 1, 1, 1, '2026-01-03 13:56:15', '2026-01-03 13:54:52'),
(12, 'Indra', 'indra@mail.com', '$2y$10$HWrATbzgnVWZesR74gBPbOOMiZx0bA32D4cbJ6PjEky8VPt0rXPEm', 'user', 1, 1, 1, '2026-01-03 13:56:19', '2026-01-03 13:55:09'),
(13, 'Santi', 'santi@mail.com', '$2y$10$k9UcWuCs5RGAp1lmEEs7OuWhc/NDjwX2S.lfjH//oQVJcub4jdStu', 'user', 1, 1, 1, '2026-01-03 13:56:23', '2026-01-03 13:55:43'),
(14, 'Rina', 'rina@mail.com', '$2y$10$XEj7X84.94uHLvMYz6vdHe6tfvikbqhhMl.OxWEsL2s/s0cq1YETG', 'user', 1, 1, NULL, NULL, '2026-01-03 13:57:06'),
(18, 'Randi', 'randi@mail.com', '$2y$10$pLSQ2MQwUpP8OGvC3Yx.muvCxP41UiJ3VOBsoQOZA.I1l6GiDNtb.', 'user', 1, 0, NULL, NULL, '2026-01-03 14:01:18'),
(20, 'jane_admin', 'jane@example.com', '$2y$10$rBINOeyb1A8eQXO6LpbHueuwWXEw6Tqd0Y.pxX9b6LpEU8hC.FFPW', 'admin', 1, 1, NULL, NULL, '2026-01-03 15:49:49'),
(22, 'Agus', 'agus@mail.com', '$2y$10$coGyxNrci8oGl/0YhpkKGOhj3pW7.PPeGO2V6TTz1Uk9CYJcdXQm.', 'user', 1, 1, NULL, NULL, '2026-01-03 17:02:50'),
(25, 'inactive_user', 'inactive@example.com', '$2y$10$AgMmQEdPLaHlMX/Emd0Tgu/VqfxbcfEtO22vBeMvrGpGucqOFexSi', 'user', 1, 0, NULL, NULL, '2026-01-03 17:03:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `todos`
--
ALTER TABLE `todos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_todos_user_status` (`user_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `todos`
--
ALTER TABLE `todos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `todos`
--
ALTER TABLE `todos`
  ADD CONSTRAINT `todos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
