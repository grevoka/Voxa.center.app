-- ══════════════════════════════════════
--  Script d'initialisation automatique
--  Execute au premier demarrage du container
-- ══════════════════════════════════════

-- Creer la base Asterisk Realtime
CREATE DATABASE IF NOT EXISTS asterisk_rt
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Utilisateur Asterisk
CREATE USER IF NOT EXISTS 'asterisk_user'@'%'
    IDENTIFIED BY 'ast_secret';
GRANT ALL PRIVILEGES ON asterisk_rt.* TO 'asterisk_user'@'%';
FLUSH PRIVILEGES;

USE asterisk_rt;

-- Tables PJSIP Realtime
CREATE TABLE IF NOT EXISTS ps_endpoints (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    transport VARCHAR(40), aors VARCHAR(200), auth VARCHAR(40),
    context VARCHAR(40), disallow VARCHAR(200), allow VARCHAR(200),
    direct_media ENUM('yes','no') DEFAULT 'no',
    force_rport ENUM('yes','no') DEFAULT 'yes',
    rewrite_contact ENUM('yes','no') DEFAULT 'yes',
    rtp_symmetric ENUM('yes','no') DEFAULT 'yes',
    callerid VARCHAR(100), from_user VARCHAR(40), from_domain VARCHAR(100),
    dtmf_mode ENUM('rfc4733','inband','info','auto') DEFAULT 'rfc4733',
    media_encryption ENUM('no','sdes','dtls') DEFAULT 'no',
    outbound_proxy VARCHAR(255),
    ice_support ENUM('yes','no') DEFAULT 'no',
    media_use_received_transport ENUM('yes','no') DEFAULT 'no',
    trust_id_inbound ENUM('yes','no') DEFAULT 'no',
    accountcode VARCHAR(80)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ps_auths (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    auth_type ENUM('userpass','md5') DEFAULT 'userpass',
    username VARCHAR(40), password VARCHAR(80),
    md5_cred VARCHAR(40), realm VARCHAR(40), nonce_lifetime INT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ps_aors (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    max_contacts INT DEFAULT 1, minimum_expiration INT DEFAULT 60,
    default_expiration INT DEFAULT 3600, maximum_expiration INT DEFAULT 7200,
    remove_existing ENUM('yes','no') DEFAULT 'yes',
    contact VARCHAR(255), qualify_frequency INT DEFAULT 60,
    outbound_proxy VARCHAR(255), support_path ENUM('yes','no') DEFAULT 'no'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ps_registrations (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    transport VARCHAR(40), outbound_auth VARCHAR(40),
    server_uri VARCHAR(255), client_uri VARCHAR(255),
    retry_interval INT DEFAULT 60, expiration INT DEFAULT 3600,
    contact_user VARCHAR(40), outbound_proxy VARCHAR(255),
    support_path ENUM('yes','no') DEFAULT 'no',
    line VARCHAR(40), endpoint VARCHAR(40)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ps_endpoint_id_ips (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    endpoint VARCHAR(40), `match` VARCHAR(80)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ps_domain_aliases (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    domain VARCHAR(80)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cdr (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    clid VARCHAR(80) DEFAULT '', src VARCHAR(80) DEFAULT '',
    dst VARCHAR(80) DEFAULT '', dcontext VARCHAR(80) DEFAULT '',
    channel VARCHAR(80) DEFAULT '', dstchannel VARCHAR(80) DEFAULT '',
    lastapp VARCHAR(80) DEFAULT '', lastdata VARCHAR(80) DEFAULT '',
    duration INT DEFAULT 0, billsec INT DEFAULT 0,
    disposition VARCHAR(45) DEFAULT '', amaflags INT DEFAULT 0,
    accountcode VARCHAR(20) DEFAULT '', uniqueid VARCHAR(150) DEFAULT '',
    userfield VARCHAR(255) DEFAULT '',
    INDEX idx_calldate (calldate),
    INDEX idx_src (src), INDEX idx_dst (dst)
) ENGINE=InnoDB;
