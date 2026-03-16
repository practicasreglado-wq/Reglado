import fs from 'fs';

const file = 'c:/xampp/htdocs/Reglado/RegladoEnergy/src/components/SiteHeader.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace('<div class="container header-inner">', '<div class="header-inner" style="padding: 14px 20px;">');

fs.writeFileSync(file, content);
console.log('Class container removed, full-width headers applied.');
