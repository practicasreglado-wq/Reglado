--
-- Base de datos: `inmobiliaria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activos_recibidos`
--

CREATE TABLE `activos_recibidos` (
  `id` int(11) NOT NULL,
  `origen` varchar(100) NOT NULL,
  `email_remitente` varchar(255) DEFAULT NULL,
  `texto_recibido` longtext NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `procesado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `captador_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `content_hash` varchar(64) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `buyer_intents`
--

CREATE TABLE `buyer_intents` (
  `id` int(10) UNSIGNED NOT NULL,
  `buyer_user_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `city` varchar(150) DEFAULT NULL,
  `max_price` decimal(14,2) DEFAULT NULL,
  `min_m2` int(11) DEFAULT NULL,
  `criteria_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_json`)),
  `criteria_summary` varchar(500) DEFAULT NULL,
  `status` enum('active','matched','cancelled') NOT NULL DEFAULT 'active',
  `matched_property_id` int(10) UNSIGNED DEFAULT NULL,
  `matched_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `buyer_property_access`
--

CREATE TABLE `buyer_property_access` (
  `id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `buyer_user_id` int(10) UNSIGNED NOT NULL,
  `nda_uploaded` tinyint(1) NOT NULL DEFAULT 0,
  `loi_uploaded` tinyint(1) NOT NULL DEFAULT 0,
  `nda_approved` tinyint(1) NOT NULL DEFAULT 0,
  `loi_approved` tinyint(1) NOT NULL DEFAULT 0,
  `dossier_unlocked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `buyer_property_document_download_progress`
--

CREATE TABLE `buyer_property_document_download_progress` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `buyer_user_id` int(11) NOT NULL,
  `nda_downloaded` tinyint(1) NOT NULL DEFAULT 0,
  `loi_downloaded` tinyint(1) NOT NULL DEFAULT 0,
  `nda_downloaded_at` datetime DEFAULT NULL,
  `loi_downloaded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `captadores`
--

CREATE TABLE `captadores` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos_firmados`
--

CREATE TABLE `documentos_firmados` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `propiedad_id` int(10) UNSIGNED NOT NULL,
  `nda_file_path` varchar(500) DEFAULT NULL,
  `loi_file_path` varchar(500) DEFAULT NULL,
  `nda_subido_at` datetime DEFAULT NULL,
  `loi_subido_at` datetime DEFAULT NULL,
  `nda_valido` tinyint(1) NOT NULL DEFAULT 0,
  `loi_valido` tinyint(1) NOT NULL DEFAULT 0,
  `validado_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(64) NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_request_id` int(11) DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `property_deletion_requests`
--

CREATE TABLE `property_deletion_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `property_id` int(10) UNSIGNED NOT NULL,
  `requester_user_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(1000) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` varchar(1000) DEFAULT NULL,
  `resolved_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedades`
--

CREATE TABLE `propiedades` (
  `id` int(11) NOT NULL,
  `tipo_propiedad` varchar(150) NOT NULL,
  `ciudad` varchar(150) NOT NULL,
  `zona` varchar(150) NOT NULL,
  `metros_cuadrados` int(11) NOT NULL,
  `precio` decimal(15,2) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'Captada',
  `estado` varchar(30) NOT NULL DEFAULT 'disponible',
  `caracteristicas_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`caracteristicas_json`)),
  `dossier_file` varchar(255) DEFAULT NULL,
  `confidentiality_file` varchar(255) DEFAULT NULL,
  `intention_file` varchar(255) DEFAULT NULL,
  `captador_id` int(11) DEFAULT NULL,
  `activo_recibido_id` int(11) DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `address_hash` varchar(64) DEFAULT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `owner_email_pending` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedades_favoritas`
--

CREATE TABLE `propiedades_favoritas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `propiedad_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_appointments`
--

CREATE TABLE `purchase_appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `notary_name` varchar(255) DEFAULT NULL,
  `notary_address` varchar(500) DEFAULT NULL,
  `notary_city` varchar(150) DEFAULT NULL,
  `notary_phone` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `admin_notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` int(11) NOT NULL,
  `buyer_user_id` int(11) NOT NULL,
  `buyer_email` varchar(255) NOT NULL,
  `buyer_name` varchar(255) DEFAULT NULL,
  `buyer_phone` varchar(50) DEFAULT NULL,
  `property_id` int(11) NOT NULL,
  `property_title` varchar(255) DEFAULT NULL,
  `status` enum('pending','contacted','closed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_promotion_requests`
--

CREATE TABLE `role_promotion_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) NOT NULL,
  `first_name` varchar(150) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `username` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `token_hash` char(64) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `search_history`
--

CREATE TABLE `search_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`preferences`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `signed_document_review_tokens`
--

CREATE TABLE `signed_document_review_tokens` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `buyer_user_id` int(11) NOT NULL,
  `reviewer_email` varchar(255) DEFAULT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `expiration_notified_at` datetime DEFAULT NULL,
  `expiration_email_sent_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_inmo_status`
--

CREATE TABLE `user_inmo_status` (
  `user_id` int(11) NOT NULL,
  `last_token_invalidated_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_match_preferences`
--

CREATE TABLE `user_match_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `answers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activos_recibidos`
--
ALTER TABLE `activos_recibidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_activos_message_id` (`message_id`),
  ADD UNIQUE KEY `uq_activos_content_hash` (`content_hash`),
  ADD UNIQUE KEY `uniq_activos_content_hash` (`content_hash`),
  ADD UNIQUE KEY `uniq_activos_message_id` (`message_id`),
  ADD KEY `idx_activos_status` (`procesado`),
  ADD KEY `idx_activos_captador` (`captador_id`),
  ADD KEY `idx_content_hash` (`content_hash`);

--
-- Indices de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_resource` (`resource_type`,`resource_id`);

--
-- Indices de la tabla `buyer_intents`
--
ALTER TABLE `buyer_intents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buyer_intents_buyer` (`buyer_user_id`),
  ADD KEY `idx_buyer_intents_status` (`status`),
  ADD KEY `idx_buyer_intents_category_city` (`category`,`city`);

--
-- Indices de la tabla `buyer_property_access`
--
ALTER TABLE `buyer_property_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_buyer_property` (`property_id`,`buyer_user_id`);

--
-- Indices de la tabla `buyer_property_document_download_progress`
--
ALTER TABLE `buyer_property_document_download_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_buyer_property_download_progress` (`property_id`,`buyer_user_id`),
  ADD KEY `idx_download_progress_property` (`property_id`),
  ADD KEY `idx_download_progress_buyer` (`buyer_user_id`);

--
-- Indices de la tabla `captadores`
--
ALTER TABLE `captadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_captadores_email` (`email`),
  ADD KEY `idx_captadores_email` (`email`);

--
-- Indices de la tabla `documentos_firmados`
--
ALTER TABLE `documentos_firmados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_propiedad` (`user_id`,`propiedad_id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_notifications_user_related` (`user_id`,`type`,`related_request_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_user_created_at` (`user_id`,`created_at`);

--
-- Indices de la tabla `property_deletion_requests`
--
ALTER TABLE `property_deletion_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prop_delete_status` (`status`),
  ADD KEY `idx_prop_delete_property` (`property_id`),
  ADD KEY `idx_prop_delete_requester` (`requester_user_id`);

--
-- Indices de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_propiedades_address_hash` (`address_hash`),
  ADD KEY `idx_propiedades_owner` (`owner_user_id`),
  ADD KEY `idx_propiedades_captador` (`captador_id`),
  ADD KEY `idx_propiedades_ciudad` (`ciudad`),
  ADD KEY `idx_propiedades_tipo` (`tipo_propiedad`),
  ADD KEY `idx_prop_owner_email_pending` (`owner_email_pending`);

--
-- Indices de la tabla `propiedades_favoritas`
--
ALTER TABLE `propiedades_favoritas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_propiedad` (`user_id`,`propiedad_id`),
  ADD KEY `idx_favoritas_user` (`user_id`),
  ADD KEY `idx_favoritas_propiedad` (`propiedad_id`);

--
-- Indices de la tabla `purchase_appointments`
--
ALTER TABLE `purchase_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_property` (`property_id`),
  ADD KEY `idx_status_date` (`status`,`appointment_date`);

--
-- Indices de la tabla `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_buyer` (`buyer_user_id`),
  ADD KEY `idx_property` (`property_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `role_promotion_requests`
--
ALTER TABLE `role_promotion_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`token_hash`),
  ADD KEY `idx_user_email` (`user_email`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_search_history_user_created` (`user_id`,`created_at`);

--
-- Indices de la tabla `signed_document_review_tokens`
--
ALTER TABLE `signed_document_review_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_review_token` (`token_hash`),
  ADD KEY `idx_review_property` (`property_id`),
  ADD KEY `idx_review_buyer` (`buyer_user_id`);

--
-- Indices de la tabla `user_inmo_status`
--
ALTER TABLE `user_inmo_status`
  ADD PRIMARY KEY (`user_id`);

--
-- Indices de la tabla `user_match_preferences`
--
ALTER TABLE `user_match_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_match` (`user_id`),
  ADD KEY `idx_user_match_category` (`category`),
  ADD KEY `idx_user_match_user` (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activos_recibidos`
--
ALTER TABLE `activos_recibidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT de la tabla `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `buyer_intents`
--
ALTER TABLE `buyer_intents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `buyer_property_access`
--
ALTER TABLE `buyer_property_access`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `buyer_property_document_download_progress`
--
ALTER TABLE `buyer_property_document_download_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT de la tabla `captadores`
--
ALTER TABLE `captadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `documentos_firmados`
--
ALTER TABLE `documentos_firmados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `property_deletion_requests`
--
ALTER TABLE `property_deletion_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT de la tabla `propiedades_favoritas`
--
ALTER TABLE `propiedades_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT de la tabla `purchase_appointments`
--
ALTER TABLE `purchase_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `role_promotion_requests`
--
ALTER TABLE `role_promotion_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `signed_document_review_tokens`
--
ALTER TABLE `signed_document_review_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT de la tabla `user_match_preferences`
--
ALTER TABLE `user_match_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `propiedades_favoritas`
--
ALTER TABLE `propiedades_favoritas`
  ADD CONSTRAINT `fk_propiedades_favoritas_propiedad` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON DELETE CASCADE;
COMMIT;

