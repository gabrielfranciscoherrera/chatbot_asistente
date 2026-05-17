-- =============================================================================
-- Chatbot — Estructura de tablas
-- Motor: MySQL / MariaDB, charset utf8mb4
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS chatbot_entradas (
    id             INT UNSIGNED                                                          NOT NULL AUTO_INCREMENT,
    categoria      ENUM('servicios','contacto','proyectos','precios','general')          NOT NULL DEFAULT 'general',
    pregunta_tipo  VARCHAR(200)                                                          NOT NULL,
    palabras_clave TEXT                                                                  NOT NULL,
    respuesta      TEXT                                                                  NOT NULL,
    tiene_botones  TINYINT(1)                                                            NOT NULL DEFAULT 0,
    botones_json   JSON                                                                  NULL,
    prioridad      TINYINT UNSIGNED                                                      NOT NULL DEFAULT 5,
    activo         TINYINT(1)                                                            NOT NULL DEFAULT 1,
    creado_en      DATETIME                                                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME                                                              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chatbot_entradas_activo_prioridad (activo, prioridad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_conversaciones (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sesion_id      VARCHAR(64)  NOT NULL,
    ip             VARCHAR(45)  NOT NULL,
    user_agent     VARCHAR(255) NULL,
    pagina_origen  VARCHAR(255) NULL,
    inicio         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_mensaje DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_chatbot_sesion (sesion_id),
    KEY idx_chatbot_conv_inicio (inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_mensajes (
    id               INT UNSIGNED          NOT NULL AUTO_INCREMENT,
    conversacion_id  INT UNSIGNED          NOT NULL,
    tipo             ENUM('usuario','bot') NOT NULL,
    mensaje          TEXT                  NOT NULL,
    entrada_id       INT UNSIGNED          NULL,
    fecha            DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chatbot_msg_conv (conversacion_id),
    CONSTRAINT fk_chatbot_msg_conv    FOREIGN KEY (conversacion_id) REFERENCES chatbot_conversaciones (id),
    CONSTRAINT fk_chatbot_msg_entrada FOREIGN KEY (entrada_id)      REFERENCES chatbot_entradas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de rate limiting (se crea también en chatbot.php al vuelo, pero conviene tenerla aquí)
CREATE TABLE IF NOT EXISTS chatbot_rate (
    ip        VARCHAR(45)    NOT NULL,
    ventana   INT UNSIGNED   NOT NULL,
    contador  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (ip, ventana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
