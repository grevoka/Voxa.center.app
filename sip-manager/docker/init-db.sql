-- Create Asterisk Realtime database
CREATE DATABASE IF NOT EXISTS asterisk_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant access to the app user on both databases
GRANT ALL PRIVILEGES ON asterisk_rt.* TO 'sip'@'%';
FLUSH PRIVILEGES;
