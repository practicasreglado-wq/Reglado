// Script puntual para mover el padding inline del header a las clases CSS del componente.
const fs = require('fs');

const file = 'c:/xampp/htdocs/Reglado/RegladoEnergy/src/components/SiteHeader.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace('<div class="header-inner" style="padding: 14px 20px;">', '<div class="header-inner">');
content = content.replace(
  '.header-inner{ display:flex; align-items:center; justify-content:space-between; padding: 14px 0; gap: 14px; position: relative; }',
  '.header-inner{ display:flex; align-items:center; justify-content:space-between; padding: 14px 20px; gap: 14px; position: relative; width: 100%; box-sizing: border-box; }'
);

fs.writeFileSync(file, content);
console.log('Padding inline style moved to CSS classes successfully.');
