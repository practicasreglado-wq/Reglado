# Referencia de Arquitectura Reglado

Este documento sirve como base de código (boilerplate) para asegurar que Claude genere estructuras compatibles con el ecosistema.

## 🏗️ Patrón Frontend (Vue 3 + Scoped CSS)

```vue
<template>
  <div class="reglado-container">
    <div class="glass-card">
      <h1 class="title">{{ title }}</h1>
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  title: String
});

onMounted(() => {
  // Inicialización estándar
});
</script>

<style scoped>
.reglado-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f172a, #1e293b);
  display: flex;
  justify-content: center;
}

.glass-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 2rem;
}

.title {
  color: #00e5ff; /* Color cian corporativo de Maps */
  font-family: 'Outfit', sans-serif;
}
</style>
```

## ⚙️ Patrón Backend (PHP MVC)

### 1. Controlador (Router -> Controller)
```php
<?php
namespace Controllers;

use Services\MyService;

class MyController {
    public function handleRequest($data) {
        $service = new MyService();
        $result = $service->executeAction($data);
        echo json_encode($result);
    }
}
```

### 2. Servicio (Lógica de Negocio)
```php
<?php
namespace Services;

use Models\MyModel;

class MyService {
    public function executeAction($data) {
        // Validaciones
        if (empty($data['id'])) return ['error' => 'ID requerido'];
        
        $model = new MyModel();
        return $model->findById($data['id']);
    }
}
```

### 3. Modelo (Acceso a Datos / PDO)
```php
<?php
namespace Models;

use Config\Database;
use PDO;

class MyModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM table WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
```
