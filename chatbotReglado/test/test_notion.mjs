// Test aislado del notionService: valida que crea una página en cada DB.
// Ejecutar: node test/test_notion.mjs
// NOTA: este script crea registros reales en Notion — borralos luego si no los quieres.

import { crearUsuarioNotion, crearCitaNotion } from '../backend/services/notionService.js';

const stamp = new Date().toISOString().slice(0, 19).replace('T', ' ');

console.log('→ Creando usuario de prueba en Notion...');
const usuarioId = await crearUsuarioNotion({
  nombre: `TEST Usuario ${stamp}`,
  email: 'test@reglado.test',
  telefono: '+34600000000'
});
console.log('  Resultado:', usuarioId ? `OK (${usuarioId})` : 'FALLÓ');

console.log('→ Creando cita de prueba en Notion...');
const citaId = await crearCitaNotion({
  nombre: `TEST Cliente ${stamp}`,
  fecha: '2026-05-15',
  hora: '16:30:00',
  motivo: 'Prueba de integración Notion',
  estado: 'pendiente'
});
console.log('  Resultado:', citaId ? `OK (${citaId})` : 'FALLÓ');

process.exit(usuarioId && citaId ? 0 : 1);
