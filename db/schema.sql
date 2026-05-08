-- Most — Task Tracker Schema
-- ВАЖНО: перед вставкой пользователей сгенерируй хэши командой:
-- php -r "echo password_hash('твой_пароль', PASSWORD_BCRYPT, ['cost'=>12]);"

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    login         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    theme         VARCHAR(50)  NOT NULL DEFAULT 'dark-default',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ВНИМАНИЕ: замени хэши на реальные, сгенерированные через password_hash()
-- Пример: php -r "echo password_hash('admask', PASSWORD_BCRYPT, ['cost'=>12]);"
-- INSERT INTO users (name, login, password_hash) VALUES
-- ('Имя Фамилия', 'login', '$2y$12$СЮДА_ВАШ_ХЭШ');

CREATE TABLE IF NOT EXISTS projects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    is_archived TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assignees (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name          VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tags (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(100) NOT NULL,
    color VARCHAR(7)   NOT NULL DEFAULT '#888888'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    title                 VARCHAR(500) NOT NULL,
    description           TEXT,
    project_id            INT          NOT NULL,
    assignee_id           INT          DEFAULT NULL,
    department_id         INT          DEFAULT NULL,
    customer_id           INT          DEFAULT NULL,
    is_presidency         TINYINT(1)   NOT NULL DEFAULT 0,
    status                ENUM('new','in_progress','testing','done','pending_archive') NOT NULL DEFAULT 'new',
    priority              ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
    complexity            TINYINT      DEFAULT NULL,
    work_type             ENUM('new_project','improvement','bugfix') DEFAULT NULL,
    estimated_hours       DECIMAL(6,1) DEFAULT NULL,
    date_start            DATE         DEFAULT NULL,
    date_end              DATE         DEFAULT NULL,
    is_archived           TINYINT(1)   NOT NULL DEFAULT 0,
    archive_requested_by  INT          DEFAULT NULL,
    archive_reason        VARCHAR(50)  DEFAULT NULL,
    archive_reason_custom VARCHAR(500) DEFAULT NULL,
    created_by            INT          DEFAULT NULL,
    created_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id)           REFERENCES projects(id)    ON DELETE CASCADE,
    FOREIGN KEY (assignee_id)          REFERENCES assignees(id)   ON DELETE SET NULL,
    FOREIGN KEY (department_id)        REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id)          REFERENCES customers(id)   ON DELETE SET NULL,
    FOREIGN KEY (archive_requested_by) REFERENCES users(id)       ON DELETE SET NULL,
    FOREIGN KEY (created_by)           REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS task_tags (
    task_id INT NOT NULL,
    tag_id  INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    task_id    INT  NOT NULL,
    user_id    INT  NOT NULL,
    content    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS code_snippets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    task_id     INT          NOT NULL,
    user_id     INT          NOT NULL,
    description VARCHAR(500) DEFAULT NULL,
    code_before TEXT         DEFAULT NULL,
    code_after  TEXT         NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS history (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    task_id    INT          NOT NULL,
    user_id    INT          NOT NULL,
    action     VARCHAR(255) NOT NULL,
    old_value  TEXT         DEFAULT NULL,
    new_value  TEXT         DEFAULT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
